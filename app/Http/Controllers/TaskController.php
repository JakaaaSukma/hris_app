<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;
        $scope = $request->query('scope'); // 'department', 'individual', atau null
        $status = $request->query('status');
        $departmentFilter = $request->query('department');
        $employeeFilter = $request->query('employee_id');

        // ==========================================
        // 1. TAMPILAN DIREKTUR: Pelaporan Komprehensif Seluruh Departemen & Karyawan
        // ==========================================
        if ($authUser->isDirektur()) {
            $query = Task::with(['creator', 'assignedEmployee'])->orderBy('created_at', 'desc');

            if ($departmentFilter) {
                $query->where('department', $departmentFilter);
            }

            if ($employeeFilter) {
                $query->where(function ($q) use ($employeeFilter) {
                    $q->where('assigned_to', $employeeFilter)
                      ->orWhere('created_by', $employeeFilter);
                });
            }

            if ($scope) {
                $query->where('scope', $scope);
            }

            if ($status) {
                $query->where('status', $status);
            }

            $tasks = $query->paginate(12);

            // Statistik Progres Per Departemen untuk Direktur
            $departmentProgress = [];
            $departments = ['HR', 'Finance', 'Bisnis Development', 'GA'];
            foreach ($departments as $dept) {
                $deptTasks = Task::where('department', $dept);
                $total = $deptTasks->count();
                $completed = (clone $deptTasks)->where('status', 'completed')->count();
                $inProgress = (clone $deptTasks)->where('status', 'in_progress')->count();
                $avgProgress = $total > 0 ? round((clone $deptTasks)->avg('progress_percentage')) : 0;

                $departmentProgress[$dept] = [
                    'total' => $total,
                    'completed' => $completed,
                    'in_progress' => $inProgress,
                    'avg_progress' => $avgProgress,
                ];
            }

            $allEmployees = Employee::orderBy('name')->get();

            return view('tasks.director_index', compact(
                'tasks',
                'authUser',
                'currentUser',
                'departmentProgress',
                'departments',
                'allEmployees',
                'scope',
                'status',
                'departmentFilter',
                'employeeFilter'
            ));
        }

        // ==========================================
        // 2. TAMPILAN MANAGER & KARYAWAN: Tim Departemen vs Individu
        // ==========================================
        $userDept = $authUser->department;
        $query = Task::with(['creator', 'assignedEmployee'])->where('department', $userDept);

        // Jika filter scope dipilih:
        $isDaily = $request->query('is_daily');
        $taskDate = $request->query('task_date');

        if ($isDaily) {
            // Filter Daily Task (Tugas Harian)
            $query->where('is_daily', true);
            if ($taskDate) {
                $query->whereDate('task_date', $taskDate);
            }
            if ($authUser->isKaryawan()) {
                $query->where('assigned_to', $currentUser?->id);
            }
        } elseif ($scope === 'individual') {
            // Task Individu: Khusus tugas yang ditugaskan ke dirinya atau dibuat oleh dirinya sendiri
            $query->where(function ($q) use ($currentUser) {
                $q->where('scope', 'individual')
                  ->where(function ($sub) use ($currentUser) {
                      $sub->where('assigned_to', $currentUser?->id)
                          ->orWhere('created_by', $currentUser?->id);
                  });
            });
        } elseif ($scope === 'department') {
            // Task Skala Tim Departemen
            $query->where('scope', 'department');
        } else {
            // Default: Jika Karyawan biasa, tampilkan task departemen dan task individu miliknya
            if ($authUser->isKaryawan()) {
                $query->where(function ($q) use ($currentUser) {
                    $q->where('scope', 'department')
                      ->orWhere(function ($sub) use ($currentUser) {
                          $sub->where('scope', 'individual')
                              ->where('assigned_to', $currentUser?->id);
                      });
                });
            }
            // Manager: bisa melihat semua task di departemennya (baik tim maupun individu stafnya)
        }

        if ($status) {
            $query->where('status', $status);
        }

        $tasks = $query->with(['creator', 'assignedEmployee'])->orderBy('created_at', 'desc')->paginate(10);

        // Anggota tim pada departemen ini untuk opsi penugasan
        $deptEmployees = Employee::where('department', $userDept)->orderBy('name')->get();

        // Metrik departemen
        $deptTotalTasks = Task::where('department', $userDept)->count();
        $deptTeamTasks = Task::where('department', $userDept)->where('scope', 'department')->count();
        $myIndividualTasks = Task::where('department', $userDept)
            ->where('scope', 'individual')
            ->where('assigned_to', $currentUser?->id)
            ->count();
        $myDailyTasksToday = Task::where('assigned_to', $currentUser?->id)
            ->where('is_daily', true)
            ->whereDate('task_date', now()->toDateString())
            ->count();
        $completedTasksCount = Task::where('department', $userDept)->where('status', 'completed')->count();

        return view('tasks.index', compact(
            'tasks',
            'authUser',
            'currentUser',
            'deptEmployees',
            'scope',
            'status',
            'isDaily',
            'taskDate',
            'deptTotalTasks',
            'deptTeamTasks',
            'myIndividualTasks',
            'myDailyTasksToday',
            'completedTasksCount'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scope' => 'required|in:department,individual',
            'assigned_to' => 'nullable|exists:employees,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'department' => 'nullable|string',
            'is_daily' => 'nullable|boolean',
            'task_date' => 'nullable|date',
        ]);

        // Tugas skala Tim Departemen hanya boleh dibuat oleh Manager Departemen atau Direktur
        if ($request->scope === 'department' && !$authUser->isManager() && !$authUser->isDirektur()) {
            return back()->withErrors(['scope' => 'Hanya Manager Departemen atau Direktur yang berwenang membuat tugas skala Tim Departemen.'])->withInput();
        }

        // Karyawan biasa hanya bisa membuat task individu untuk diri sendiri
        $assignedToId = null;
        if ($request->scope === 'individual') {
            if ($authUser->isKaryawan()) {
                // Karyawan dipaksa menugaskan ke diri sendiri
                $assignedToId = $currentUser?->id;
            } else {
                // Manager / Direktur bebas memilih PIC karyawan di departemennya atau diri sendiri
                $assignedToId = $request->assigned_to ?? $currentUser?->id;
            }
        }

        $dept = $authUser->isDirektur() ? ($request->department ?? 'HR') : $authUser->department;
        $isDaily = $request->boolean('is_daily');
        $taskDate = $isDaily ? ($request->task_date ?? now()->toDateString()) : null;
        $dueDate = $isDaily ? $taskDate : $request->due_date;

        $newTask = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'department' => $dept,
            'scope' => $request->scope,
            'is_daily' => $isDaily,
            'task_date' => $taskDate,
            'created_by' => $currentUser?->id ?? 1,
            'assigned_to' => $assignedToId,
            'priority' => $request->priority,
            'status' => 'todo',
            'progress_percentage' => 0,
            'due_date' => $dueDate,
        ]);

        // Kirim notifikasi jika task ditugaskan ke karyawan tertentu
        try {
            if ($assignedToId) {
                $assignedUser = \App\Models\User::where('employee_id', $assignedToId)->first();
                if ($assignedUser && $assignedUser->id !== $authUser->id) {
                    $assignedUser->notify(new \App\Notifications\GeneralNotification(
                        'Tugas Baru Ditugaskan',
                        "Anda ditugaskan pada tugas: '{$newTask->title}' (Prioritas: " . ucfirst($newTask->priority) . ") oleh {$authUser->name}.",
                        route('tasks.index'),
                        'task',
                        'blue'
                    ));
                }
            } elseif ($request->scope === 'department') {
                // Notifikasi ke seluruh anggota departemen
                $deptMembers = \App\Models\User::where('department', $dept)->where('id', '!=', $authUser->id)->get();
                foreach ($deptMembers as $member) {
                    $member->notify(new \App\Notifications\GeneralNotification(
                        'Tugas Tim Departemen Baru',
                        "Tugas baru tim {$dept}: '{$newTask->title}' telah ditambahkan oleh {$authUser->name}.",
                        route('tasks.index'),
                        'groups',
                        'blue'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi penugasan task baru: ' . $e->getMessage());
        }

        $message = $isDaily 
            ? 'Daily Task harian baru berhasil dibuat untuk tanggal ' . \Carbon\Carbon::parse($taskDate)->format('d M Y') . '.'
            : 'Tugas baru berhasil dibuat dan ditambahkan ke daftar task.';

        return back()->with('success', $message);
    }

    public function updateProgress(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $authUser = Auth::user();

        // Validasi otoritas: Direktur, Manager di departemennya, atau PIC individu
        if (!$authUser->isDirektur() && $authUser->department !== $task->department) {
            abort(403, 'Anda tidak berwenang mengupdate tugas departemen lain.');
        }

        $request->validate([
            'status' => 'required|in:todo,in_progress,review,completed',
            'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

        $task->update([
            'status' => $request->status,
            'progress_percentage' => $request->progress_percentage,
            'completed_at' => $request->status === 'completed' ? Carbon::now()->toDateString() : null,
        ]);

        // Notifikasi ke pembuat tugas jika yang mengupdate adalah orang lain
        try {
            $creatorUser = \App\Models\User::where('employee_id', $task->created_by)->first();
            if ($creatorUser && $creatorUser->id !== $authUser->id) {
                $statusMap = [
                    'todo' => 'To Do',
                    'in_progress' => 'Sedang Dikerjakan',
                    'review' => 'Menunggu Review',
                    'completed' => 'Selesai',
                ];
                $creatorUser->notify(new \App\Notifications\GeneralNotification(
                    'Progres Tugas Diperbarui',
                    "Tugas '{$task->title}' diperbarui oleh {$authUser->name}: Status {$statusMap[$task->status]}, progres {$task->progress_percentage}%.",
                    route('tasks.index'),
                    'check_circle',
                    $task->status === 'completed' ? 'emerald' : 'blue'
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi update progres task: ' . $e->getMessage());
        }

        return back()->with('success', "Progres tugas '{$task->title}' berhasil diperbarui ke {$task->progress_percentage}%.");
    }

    /**
     * Mempersiapkan Data Ekspor Task & KPI Departemen
     */
    private function prepareTaskExportData(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->isManager() && !$authUser->isDirektur()) {
            abort(403, 'Akses Ditolak: Hanya Manager Departemen dan Direktur Utama yang memiliki wewenang mengekspor laporan tugas & KPI.');
        }

        // Tentukan departemen yang diekspor
        if ($authUser->isDirektur()) {
            $department = $request->query('department');
            if (empty($department) || $department === 'all') {
                $department = 'Semua Departemen';
            }
        } else {
            $department = $authUser->department ?? ($authUser->employee->department ?? 'HR');
        }

        // Pilihan periode: 1_week atau 1_month
        $period = $request->query('period', '1_week');
        if ($period === '1_month') {
            $startDate = Carbon::now()->subDays(30)->startOfDay();
            $periodText = '1 Bulan Terakhir (' . $startDate->translatedFormat('d M Y') . ' s/d ' . Carbon::now()->translatedFormat('d M Y') . ')';
        } else {
            $period = '1_week';
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $periodText = '1 Minggu Terakhir (' . $startDate->translatedFormat('d M Y') . ' s/d ' . Carbon::now()->translatedFormat('d M Y') . ')';
        }
        $endDate = Carbon::now()->endOfDay();

        // Query Tasks
        $query = Task::with(['creator', 'assignedEmployee']);

        if ($department !== 'Semua Departemen') {
            $query->where('department', $department);
        }

        // Filter periode tugas
        $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate])
              ->orWhereBetween('task_date', [$startDate->toDateString(), $endDate->toDateString()])
              ->orWhereBetween('due_date', [$startDate->toDateString(), $endDate->toDateString()])
              ->orWhereBetween('completed_at', [$startDate->toDateString(), $endDate->toDateString()]);
        });

        $tasks = $query->orderBy('due_date', 'asc')->orderBy('created_at', 'desc')->get();

        // Ringkasan KPI Departemen
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $reviewTasks = $tasks->where('status', 'review')->count();
        $todoTasks = $tasks->where('status', 'todo')->count();

        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
        $avgProgress = $totalTasks > 0 ? round($tasks->avg('progress_percentage'), 1) : 0;

        // On-time completion: completed tasks where completed_at <= due_date or no due_date
        $onTimeCount = $tasks->where('status', 'completed')->filter(function ($t) {
            if (!$t->due_date || !$t->completed_at) return true;
            return $t->completed_at <= $t->due_date;
        })->count();
        $onTimeRate = $completedTasks > 0 ? round(($onTimeCount / $completedTasks) * 100, 1) : 100.0;

        // Skor Indeks KPI Departemen (0 - 100)
        $kpiScore = round(($completionRate * 0.5) + ($avgProgress * 0.3) + ($onTimeRate * 0.2), 1);
        if ($kpiScore >= 90) {
            $kpiGrade = 'A (Sangat Baik / Unggul)';
        } elseif ($kpiScore >= 75) {
            $kpiGrade = 'B (Baik / Memuaskan)';
        } elseif ($kpiScore >= 60) {
            $kpiGrade = 'C (Cukup / Standar)';
        } else {
            $kpiGrade = 'D (Perlu Peningkatan)';
        }

        // Breakdown Per PIC (Anggota Tim Departemen)
        $empQuery = Employee::orderBy('name');
        if ($department !== 'Semua Departemen') {
            $empQuery->where('department', $department);
        }
        $deptEmployees = $empQuery->get();

        $picStats = [];
        foreach ($deptEmployees as $emp) {
            $empTasks = $tasks->where('assigned_to', $emp->id);
            $empTotal = $empTasks->count();
            $empCompleted = $empTasks->where('status', 'completed')->count();
            $empInProgress = $empTasks->where('status', 'in_progress')->count();
            $empAvgProg = $empTotal > 0 ? round($empTasks->avg('progress_percentage'), 1) : 0;
            $empCompRate = $empTotal > 0 ? round(($empCompleted / $empTotal) * 100, 1) : 0;
            $empScore = round(($empCompRate * 0.6) + ($empAvgProg * 0.4), 1);

            $picStats[] = [
                'id' => $emp->id,
                'name' => $emp->name,
                'nip' => $emp->nip ?? '-',
                'role_title' => $emp->role_title ?? 'Staff',
                'department' => $emp->department,
                'total_tasks' => $empTotal,
                'completed' => $empCompleted,
                'in_progress' => $empInProgress,
                'avg_progress' => $empAvgProg,
                'completion_rate' => $empCompRate,
                'kpi_score' => $empScore,
            ];
        }

        usort($picStats, fn($a, $b) => $b['kpi_score'] <=> $a['kpi_score']);

        return [
            'department' => $department,
            'period' => $period,
            'periodText' => $periodText,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'inProgressTasks' => $inProgressTasks,
            'reviewTasks' => $reviewTasks,
            'todoTasks' => $todoTasks,
            'completionRate' => $completionRate,
            'avgProgress' => $avgProgress,
            'onTimeCount' => $onTimeCount,
            'onTimeRate' => $onTimeRate,
            'kpiScore' => $kpiScore,
            'kpiGrade' => $kpiGrade,
            'picStats' => $picStats,
            'authUser' => $authUser,
            'reportTitle' => 'Laporan Tugas, Progres & KPI Departemen',
        ];
    }

    /**
     * Ekspor Laporan Task & KPI ke Format Native Microsoft Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $data = $this->prepareTaskExportData($request);
        $cleanDept = str_replace(' ', '_', $data['department']);
        $filename = "Laporan_Task_KPI_{$cleanDept}_{$data['period']}_" . date('Ymd_His') . ".xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Task & KPI ' . substr($data['department'], 0, 15));
        $sheet->setShowGridLines(true);

        // 1. Header Resmi Perusahaan
        $sheet->mergeCells('A2:M2');
        $sheet->setCellValue('A2', 'PT GYOKAI GLOBAL INDONESIA');
        $sheet->getStyle('A2')->getFont()->setName('Calibri')->setSize(16)->setBold(true)->getColor()->setRGB('881337');

        $sheet->mergeCells('A3:M3');
        $sheet->setCellValue('A3', 'LAPORAN TUGAS, PROGRES KERJA & KEY PERFORMANCE INDICATOR (KPI)');
        $sheet->getStyle('A3')->getFont()->setName('Calibri')->setSize(12)->setBold(true)->getColor()->setRGB('1E293B');

        $sheet->mergeCells('A4:M4');
        $sheet->setCellValue('A4', 'Departemen: ' . $data['department'] . ' • Periode: ' . $data['periodText'] . ' • Dokumen Resmi Internal');
        $sheet->getStyle('A4')->getFont()->setName('Calibri')->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');

        // 2. Metadata Box (Baris 6)
        $sheet->setCellValue('A6', 'Tanggal Cetak:');
        $sheet->setCellValue('B6', Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB');
        $sheet->setCellValue('D6', 'Diekspor Oleh:');
        $sheet->setCellValue('E6', $data['authUser']->name . ' (' . strtoupper($data['authUser']->role) . ')');
        $sheet->setCellValue('G6', 'Skor KPI Divisi:');
        $sheet->setCellValue('H6', $data['kpiScore'] . ' / 100 (' . $data['kpiGrade'] . ')');
        $sheet->setCellValue('K6', 'Total Beban Tugas:');
        $sheet->setCellValue('L6', $data['totalTasks'] . ' Task');

        $metaHeaders = ['A6', 'D6', 'G6', 'K6'];
        foreach ($metaHeaders as $c) {
            $sheet->getStyle($c)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('475569');
        }
        $sheet->getStyle('H6')->getFont()->setBold(true)->getColor()->setRGB($data['kpiScore'] >= 75 ? '059669' : 'D97706');
        $sheet->getStyle('L6')->getFont()->setBold(true)->getColor()->setRGB('0284C7');
        $sheet->getStyle('A6:M6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle('A6:M6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('CBD5E1');
        $sheet->getRowDimension(6)->setRowHeight(22);

        // 3. Mini KPI Summary Cards (Baris 8 - 9)
        $kpiCards = [
            ['col_start' => 'A', 'col_end' => 'B', 'title' => 'SKOR KPI DIVISI', 'val' => $data['kpiScore'] . ' Pts', 'color' => '059669'],
            ['col_start' => 'C', 'col_end' => 'D', 'title' => 'TINGKAT SELESAI', 'val' => $data['completionRate'] . '%', 'color' => '0284C7'],
            ['col_start' => 'E', 'col_end' => 'G', 'title' => 'TUGAS SELESAI (100%)', 'val' => $data['completedTasks'] . ' / ' . $data['totalTasks'] . ' Task', 'color' => '0F172A'],
            ['col_start' => 'H', 'col_end' => 'J', 'title' => 'RATA-RATA PROGRES TIM', 'val' => $data['avgProgress'] . '%', 'color' => '7C3AED'],
            ['col_start' => 'K', 'col_end' => 'M', 'title' => 'KETEPATAN WAKTU (ON-TIME)', 'val' => $data['onTimeRate'] . '%', 'color' => '0D9488'],
        ];

        foreach ($kpiCards as $card) {
            $rangeTitle = $card['col_start'] . '8:' . $card['col_end'] . '8';
            $rangeVal = $card['col_start'] . '9:' . $card['col_end'] . '9';
            $sheet->mergeCells($rangeTitle);
            $sheet->mergeCells($rangeVal);
            $sheet->setCellValue($card['col_start'] . '8', $card['title']);
            $sheet->setCellValue($card['col_start'] . '9', $card['val']);
            $sheet->getStyle($rangeTitle)->getFont()->setSize(8)->setBold(true)->getColor()->setRGB('64748B');
            $sheet->getStyle($rangeVal)->getFont()->setSize(12)->setBold(true)->getColor()->setRGB($card['color']);
            $sheet->getStyle($rangeTitle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($rangeVal)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $boxRange = $card['col_start'] . '8:' . $card['col_end'] . '9';
            $sheet->getStyle($boxRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            $sheet->getStyle($boxRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');
        }
        $sheet->getRowDimension(8)->setRowHeight(16);
        $sheet->getRowDimension(9)->setRowHeight(22);

        // 4. Section 1: Ringkasan Performa & KPI Per PIC (Baris 11)
        $sheet->mergeCells('A11:I11');
        $sheet->setCellValue('A11', '1. RINGKASAN PERFORMA & KPI PER PIC (ANGGOTA TIM)');
        $sheet->getStyle('A11')->getFont()->setName('Calibri')->setSize(11)->setBold(true)->getColor()->setRGB('1E293B');

        $picHeaders = [
            'A12' => 'No',
            'B12' => 'Nama PIC (Anggota Tim)',
            'C12' => 'NIP',
            'D12' => 'Jabatan',
            'E12' => 'Total Task',
            'F12' => 'Selesai',
            'G12' => 'Berjalan',
            'H12' => 'Rata Progres (%)',
            'I12' => 'Skor KPI Individu',
        ];
        foreach ($picHeaders as $cell => $title) {
            $sheet->setCellValue($cell, $title);
        }
        $picHeaderRange = 'A12:I12';
        $sheet->getStyle($picHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('334155');
        $sheet->getStyle($picHeaderRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($picHeaderRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($picHeaderRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension(12)->setRowHeight(24);

        $curRow = 13;
        $noPic = 1;
        foreach ($data['picStats'] as $pic) {
            $sheet->setCellValue('A' . $curRow, $noPic++);
            $sheet->setCellValue('B' . $curRow, $pic['name']);
            $sheet->setCellValueExplicit('C' . $curRow, $pic['nip'], DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $curRow, $pic['role_title']);
            $sheet->setCellValue('E' . $curRow, $pic['total_tasks']);
            $sheet->setCellValue('F' . $curRow, $pic['completed']);
            $sheet->setCellValue('G' . $curRow, $pic['in_progress']);
            $sheet->setCellValue('H' . $curRow, $pic['avg_progress'] . '%');
            $sheet->setCellValue('I' . $curRow, $pic['kpi_score'] . ' Pts');

            $rowBg = ($curRow % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$curRow}:I{$curRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rowBg);
            $sheet->getStyle("A{$curRow}:I{$curRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle("A{$curRow}:I{$curRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("A{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle("C{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$curRow}:I{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('059669');
            $sheet->getStyle("I{$curRow}")->getFont()->setBold(true)->getColor()->setRGB($pic['kpi_score'] >= 75 ? '059669' : 'D97706');
            $sheet->getRowDimension($curRow)->setRowHeight(20);
            $curRow++;
        }

        // 5. Section 2: Detail Daftar Tugas & Progres (Mulai setelah Tabel PIC + 2 baris)
        $curRow += 2;
        $sheet->mergeCells("A{$curRow}:M{$curRow}");
        $sheet->setCellValue("A{$curRow}", "2. DETAIL DAFTAR TUGAS & PROGRES KERJA (" . $data['tasks']->count() . " TUGAS)");
        $sheet->getStyle("A{$curRow}")->getFont()->setName('Calibri')->setSize(11)->setBold(true)->getColor()->setRGB('1E293B');

        $curRow++;
        $taskHeaders = [
            'A' . $curRow => 'No',
            'B' . $curRow => 'Judul Tugas / Pekerjaan',
            'C' . $curRow => 'Deskripsi / Detail',
            'D' . $curRow => 'Skala / Tipe',
            'E' . $curRow => 'PIC Penanggung Jawab',
            'F' . $curRow => 'Prioritas',
            'G' . $curRow => 'Tgl Task / Mulai',
            'H' . $curRow => 'Tenggat (Due)',
            'I' . $curRow => 'Tgl Selesai',
            'J' . $curRow => 'Status',
            'K' . $curRow => 'Progres (%)',
            'L' . $curRow => 'Ketepatan Waktu',
            'M' . $curRow => 'Dibuat Oleh',
        ];

        foreach ($taskHeaders as $cell => $tTitle) {
            $sheet->setCellValue($cell, $tTitle);
        }

        $taskHeaderRange = "A{$curRow}:M{$curRow}";
        $sheet->getStyle($taskHeaderRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B');
        $sheet->getStyle($taskHeaderRange)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($taskHeaderRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle($taskHeaderRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($curRow)->setRowHeight(26);

        $curRow++;
        $taskDataStartRow = $curRow;
        $noTask = 1;

        foreach ($data['tasks'] as $task) {
            $isDone = $task->status === 'completed';
            $picName = $task->assignedEmployee->name ?? ($task->scope === 'department' ? 'Seluruh Tim' : '-');
            $scaleLabel = $task->is_daily ? 'Daily Task' : ($task->scope === 'department' ? 'Tim Dept' : 'Individu');
            $createdByName = $task->creator->name ?? 'System';

            $startDateFormatted = $task->task_date ? Carbon::parse($task->task_date)->format('d/m/Y') : ($task->created_at ? Carbon::parse($task->created_at)->format('d/m/Y') : '-');
            $dueDateFormatted = $task->due_date ? Carbon::parse($task->due_date)->format('d/m/Y') : '-';
            $completedFormatted = $task->completed_at ? Carbon::parse($task->completed_at)->format('d/m/Y') : ($isDone ? 'Selesai' : '-');

            $statusText = strtoupper($task->status);
            if ($task->status === 'completed') $statusText = 'SELESAI';
            elseif ($task->status === 'in_progress') $statusText = 'BERJALAN';
            elseif ($task->status === 'review') $statusText = 'REVIEW';
            elseif ($task->status === 'todo') $statusText = 'TODO';

            // Status waktu
            if ($isDone) {
                if ($task->due_date && $task->completed_at && $task->completed_at > $task->due_date) {
                    $timeliness = 'Terlambat Selesai';
                } else {
                    $timeliness = 'Tepat Waktu';
                }
            } else {
                if ($task->due_date && Carbon::parse($task->due_date)->isPast()) {
                    $timeliness = 'Lewat Tenggat';
                } else {
                    $timeliness = 'Sesuai Jadwal';
                }
            }

            $sheet->setCellValue('A' . $curRow, $noTask++);
            $sheet->setCellValue('B' . $curRow, $task->title);
            $sheet->setCellValue('C' . $curRow, $task->description ?? '-');
            $sheet->setCellValue('D' . $curRow, $scaleLabel);
            $sheet->setCellValue('E' . $curRow, $picName);
            $sheet->setCellValue('F' . $curRow, strtoupper($task->priority));
            $sheet->setCellValue('G' . $curRow, $startDateFormatted);
            $sheet->setCellValue('H' . $curRow, $dueDateFormatted);
            $sheet->setCellValue('I' . $curRow, $completedFormatted);
            $sheet->setCellValue('J' . $curRow, $statusText);
            $sheet->setCellValue('K' . $curRow, $task->progress_percentage . '%');
            $sheet->setCellValue('L' . $curRow, $timeliness);
            $sheet->setCellValue('M' . $curRow, $createdByName);

            $tRowBg = ($curRow % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$curRow}:M{$curRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($tRowBg);
            $sheet->getStyle("A{$curRow}:M{$curRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle("A{$curRow}:M{$curRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle("A{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');
            $sheet->getStyle("D{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('0284C7');
            $sheet->getStyle("F{$curRow}:L{$curRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Styling status & progress
            if ($isDone) {
                $sheet->getStyle("J{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('059669');
                $sheet->getStyle("K{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('059669');
            } elseif ($task->status === 'in_progress') {
                $sheet->getStyle("J{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('2563EB');
            }

            if (str_contains($timeliness, 'Terlambat') || str_contains($timeliness, 'Lewat')) {
                $sheet->getStyle("L{$curRow}")->getFont()->setBold(true)->getColor()->setRGB('DC2626');
            } else {
                $sheet->getStyle("L{$curRow}")->getFont()->getColor()->setRGB('059669');
            }

            $sheet->getRowDimension($curRow)->setRowHeight(21);
            $curRow++;
        }

        // Auto-fit Column Widths (A s/d M)
        $columns = range('A', 'M');
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze Panes di bawah Header Tugas
        $freezeRow = $taskDataStartRow;
        $sheet->freezePane("A{$freezeRow}");

        // Simpan ke temporary file dan unduh sebagai binary attachment
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'hris_task_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Ekspor Laporan Task & KPI ke Format Cetak / PDF
     */
    public function exportPdf(Request $request)
    {
        $data = $this->prepareTaskExportData($request);
        return view('reports.tasks_pdf', $data);
    }
}
