<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarSchedule;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * Display the Calendar and Scheduled Notes/Agenda.
     */
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        // Month & Year from query or current
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $selectedDate = $request->query('date', now()->toDateString());

        // Create Carbon instance for current viewed month
        $currentMonth = Carbon::createFromDate($year, $month, 1)->locale('id');
        $monthName = $currentMonth->translatedFormat('F Y');
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // Start and end dates of calendar grid (Monday to Sunday)
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Start from Monday of the first week
        $startOfGrid = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        // End on Sunday of the last week
        $endOfGrid = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Fetch schedules visible to this user:
        // 1. Individu: user_id = $authUser->id (or Direktur)
        // 2. Departemen: department = $authUser->department (or Direktur)
        // 3. Antar Departemen: department = $authUser->department OR target_department = $authUser->department (or Direktur)
        // 4. Company-wide schedules
        $filterDept = $request->query('filter_dept', 'all');
        $filterScope = $request->query('filter_scope', 'all');
        $filterUserId = $request->query('filter_user', 'all');

        $schedulesQuery = CalendarSchedule::with(['user', 'employee', 'creator', 'approver', 'approvals.user'])
            ->where(function ($q) use ($authUser) {
                // Own schedules (whether approved or pending)
                $q->where('user_id', $authUser->id);

                // Company-wide schedules that are approved
                $q->orWhere(function ($sub) {
                    $sub->where('is_company_wide', true)
                        ->where('approval_status', 'approved');
                });

                // Department schedules for user's department (jika user bukan Direktur yang terisolasi dari dept operasional)
                if (!$authUser->isDirektur() && $authUser->department) {
                    $q->orWhere(function ($sub) use ($authUser) {
                        $sub->where('scope', 'departemen')
                            ->where('department', $authUser->department);
                    });

                    // Cross-department schedules involving user's department (as creator dept or one of target depts)
                    $q->orWhere(function ($sub) use ($authUser) {
                        $sub->where('scope', 'antar_departemen')
                            ->where(function ($cross) use ($authUser) {
                                $cross->where('department', $authUser->department)
                                      ->orWhere('target_department', $authUser->department)
                                      ->orWhere('target_departments', 'like', '%"' . $authUser->department . '"%');
                            });
                    });
                }
            });

        // Apply interactive filters (especially useful for Direktur & Managers)
        if ($filterDept !== 'all' && !empty($filterDept)) {
            $schedulesQuery->where(function ($dq) use ($filterDept) {
                $dq->where('department', $filterDept)
                   ->orWhere('target_department', $filterDept)
                   ->orWhere('target_departments', 'like', '%"' . $filterDept . '"%');
            });
        }

        if ($filterScope !== 'all' && !empty($filterScope)) {
            if ($filterScope === 'karyawan') {
                $schedulesQuery->whereHas('creator', function ($uq) {
                    $uq->where('role', 'karyawan');
                });
            } elseif ($filterScope === 'manager') {
                $schedulesQuery->whereHas('creator', function ($uq) {
                    $uq->where('role', 'manager');
                });
            } elseif (in_array($filterScope, ['individu', 'departemen', 'antar_departemen'])) {
                $schedulesQuery->where('scope', $filterScope);
            }
        }

        if ($filterUserId !== 'all' && !empty($filterUserId)) {
            $schedulesQuery->where('user_id', $filterUserId);
        }

        $schedulesQuery->whereBetween('date', [$startOfGrid->toDateString(), $endOfGrid->toDateString()])
            ->orderBy('time_start', 'asc')
            ->orderBy('id', 'asc');

        $allGridSchedules = $schedulesQuery->get();

        // Group schedules by date 'YYYY-MM-DD'
        $schedulesByDate = $allGridSchedules->groupBy(function ($item) {
            return Carbon::parse($item->date)->toDateString();
        });

        // Schedules for the right panel (selected date)
        $selectedDateCarbon = Carbon::parse($selectedDate)->locale('id');
        $selectedDateFormatted = $selectedDateCarbon->translatedFormat('l, d F Y');
        $selectedDateSchedules = $allGridSchedules->filter(function ($item) use ($selectedDate) {
            return Carbon::parse($item->date)->toDateString() === $selectedDate;
        });

        // Summary counts for the month
        $monthKunjunganCount = $allGridSchedules->where('category', 'kunjungan')->count();
        $monthMeetingCount = $allGridSchedules->where('category', 'meeting')->count();
        $monthTaskCount = $allGridSchedules->where('category', 'tugas')->count();
        $monthCatatanCount = $allGridSchedules->where('category', 'catatan')->count();
        $totalMonthEvents = $allGridSchedules->count();

        // Total days in current month and first day of week (Monday = 1, Sunday = 7)
        $daysInMonth = $startOfMonth->daysInMonth;
        $firstDayOfWeek = $startOfMonth->dayOfWeekIso; // 1 (Mon) to 7 (Sun)
        $leadingBlanks = $firstDayOfWeek - 1; // Number of empty cells before day 1

        // Build exact month days grid
        $monthDays = [];
        for ($dayNum = 1; $dayNum <= $daysInMonth; $dayNum++) {
            $dayCarbon = Carbon::createFromDate($year, $month, $dayNum)->locale('id');
            $dateStr = $dayCarbon->toDateString();
            $daySchedules = $schedulesByDate->get($dateStr, collect());

            $monthDays[] = [
                'day' => $dayNum,
                'date' => $dateStr,
                'isToday' => $dayCarbon->isToday(),
                'isSelected' => ($dateStr === $selectedDate),
                'schedules' => $daySchedules,
                'scheduleCount' => $daySchedules->count(),
            ];
        }

        // Selected day number (e.g. "14")
        $selectedDayNum = Carbon::parse($selectedDate)->day;

        // List of all departments for target department select
        $departmentsList = ['HR', 'Finance', 'Bisnis Development', 'GA', 'Resepsionis', 'Trainer'];
        $availableTargetDepartments = array_values(array_filter($departmentsList, function ($d) use ($authUser) {
            return strcasecmp($d, $authUser->department ?? '') !== 0;
        }));

        // Pending schedules that need this user's review:
        // 1. Departemen: scope = 'departemen', user_id != $authUser->id, department = $authUser->department, not yet approved by this user
        // 2. Antar Departemen: scope = 'antar_departemen', user_id != $authUser->id, user is manager or direktur, and user's department is in target departments
        $pendingReviewsList = CalendarSchedule::with(['creator', 'approvals.user'])
            ->where('approval_status', 'pending')
            ->where('user_id', '!=', $authUser->id)
            ->where(function ($q) use ($authUser) {
                if ($authUser->isDirektur()) {
                    // Direktur tidak melihat/mereview jadwal departemen operasional
                    $q->whereRaw('1 = 0');
                    return;
                }

                if ($authUser->department) {
                    // Departemen
                    $q->where(function ($sub) use ($authUser) {
                        $sub->where('scope', 'departemen')
                            ->where('department', $authUser->department);
                    });

                    // Antar Departemen (only manager can review target dept)
                    if ($authUser->isManager()) {
                        $q->orWhere(function ($sub) use ($authUser) {
                            $sub->where('scope', 'antar_departemen')
                                ->where(function ($tq) use ($authUser) {
                                    $tq->where('target_department', $authUser->department)
                                       ->orWhere('target_departments', 'like', '%"' . $authUser->department . '"%');
                                });
                        });
                    }
                }
            })
            ->get()
            ->filter(function ($item) use ($authUser) {
                // filter out if already approved by this user
                return !$item->approvals->where('user_id', $authUser->id)->where('status', 'approved')->count();
            });

        $allDepartments = ['HR', 'Finance', 'Bisnis Development', 'GA'];
        $allUsers = \App\Models\User::orderBy('name', 'asc')->get();

        return view('calendar.index', compact(
            'authUser',
            'currentUser',
            'month',
            'year',
            'monthName',
            'prevMonth',
            'nextMonth',
            'selectedDate',
            'selectedDayNum',
            'selectedDateFormatted',
            'selectedDateSchedules',
            'leadingBlanks',
            'monthDays',
            'totalMonthEvents',
            'monthKunjunganCount',
            'monthMeetingCount',
            'monthTaskCount',
            'monthCatatanCount',
            'availableTargetDepartments',
            'pendingReviewsList',
            'allDepartments',
            'allUsers',
            'filterDept',
            'filterScope',
            'filterUserId'
        ));
    }

    /**
     * Store a new Calendar Schedule / Note.
     */
    public function store(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        // Determine allowed scope based on user role
        // Karyawan: only individu
        // Manager / Direktur: individu, departemen, antar_departemen
        $rules = [
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:jadwal,kunjungan,meeting,tugas,catatan,pengumuman',
            'color' => 'nullable|string|in:rose,emerald,amber,blue,purple,cyan',
            'time_start' => 'nullable',
            'time_end' => 'nullable',
            'description' => 'nullable|string|max:1000',
            'scope' => 'required|string|in:individu,departemen,antar_departemen',
            'target_department' => 'nullable|string',
            'target_departments' => 'nullable|array',
            'target_departments.*' => 'string|in:HR,Finance,Bisnis Development,GA,Resepsionis,Trainer,Executive',
            'is_company_wide' => 'nullable|boolean',
        ];

        $request->validate($rules);

        $scope = $request->scope;

        // If karyawan, force scope to individu
        if ($authUser->isKaryawan()) {
            $scope = 'individu';
        }

        $targetDepartment = null;
        $targetDepartments = [];
        $approvalStatus = 'approved'; // Default for individu
        $approvedBy = null;
        $approvedAt = null;

        if ($scope === 'individu') {
            $approvalStatus = 'approved';
            $approvedBy = $authUser->id;
            $approvedAt = now();
        } elseif ($scope === 'departemen') {
            // Manager proposing department schedule: requires approval from members
            $approvalStatus = 'pending';
        } elseif ($scope === 'antar_departemen') {
            // Manager proposing cross-department schedule: can choose multiple target departments
            $chosenDepartments = $request->input('target_departments', []);

            // If passed as comma-separated or single target_department field
            if (empty($chosenDepartments) && $request->filled('target_department')) {
                $chosenDepartments = array_filter(array_map('trim', explode(',', $request->target_department)));
            }

            // Remove duplicates and exclude current user's department
            $chosenDepartments = array_values(array_unique(array_filter($chosenDepartments, function ($d) use ($authUser) {
                return !empty($d) && strcasecmp($d, $authUser->department ?? '') !== 0;
            })));

            if (empty($chosenDepartments)) {
                return back()->withInput()->with('error', 'Pilih minimal satu departemen tujuan (selain departemen Anda sendiri) untuk jadwal antar departemen.');
            }

            $targetDepartments = $chosenDepartments;
            $targetDepartment = implode(', ', $targetDepartments);
            $approvalStatus = 'pending';
        }

        // Auto color based on category if not selected
        $color = $request->color;
        if (!$color) {
            $color = match ($request->category) {
                'jadwal' => 'rose',
                'kunjungan' => 'rose',
                'meeting' => 'blue',
                'tugas' => 'purple',
                'catatan' => 'amber',
                'pengumuman' => 'emerald',
                default => 'rose'
            };
        }

        $isCompanyWide = $request->has('is_company_wide') && ($authUser->isDirektur() || $authUser->isHR());

        CalendarSchedule::create([
            'user_id' => $authUser->id,
            'employee_id' => $currentUser?->id,
            'date' => $request->date,
            'title' => $request->title,
            'description' => $request->description,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'category' => $request->category,
            'color' => $color,
            'is_company_wide' => $isCompanyWide,
            'department' => $authUser->department,
            'created_by' => $authUser->id,
            'scope' => $scope,
            'target_department' => $targetDepartment,
            'target_departments' => $targetDepartments,
            'approval_status' => $approvalStatus,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
        ]);

        $dateCarbon = Carbon::parse($request->date);

        $successMsg = 'Jadwal baru berhasil ditambahkan!';
        if ($approvalStatus === 'pending') {
            if ($scope === 'departemen') {
                $successMsg = 'Jadwal departemen berhasil diajukan dan menunggu persetujuan karyawan di departemen Anda.';
            } else {
                $successMsg = 'Jadwal antar departemen (' . implode(', ', $targetDepartments) . ') berhasil diajukan dan menunggu persetujuan Manager terkait.';
            }
        }

        return redirect()->route('calendar.index', [
            'month' => $dateCarbon->month,
            'year' => $dateCarbon->year,
            'date' => $request->date
        ])->with('success', $successMsg);
    }

    /**
     * Approve a department or cross-department schedule.
     */
    public function approve(Request $request, $id)
    {
        $schedule = CalendarSchedule::with(['approvals.user'])->findOrFail($id);
        $authUser = Auth::user();

        // 1. Anti-self-approval: Pembuat jadwal tidak bisa meng-approve jadwalnya sendiri
        if ($schedule->user_id === $authUser->id) {
            return back()->with('error', 'Anda tidak dapat menyetujui jadwal yang Anda buat sendiri.');
        }

        // 2. Validate permission based on scope
        if ($schedule->scope === 'departemen') {
            // Harus satu departemen dengan jadwal
            if (!$authUser->isDirektur() && strcasecmp($authUser->department ?? '', $schedule->department ?? '') !== 0) {
                return back()->with('error', 'Anda hanya dapat menyetujui jadwal dari departemen Anda sendiri.');
            }

            // Karyawan di departemen tersebut memberikan persetujuan
            // Record this user's approval
            $schedule->approvals()->updateOrCreate(
                ['user_id' => $authUser->id],
                ['status' => 'approved', 'notes' => $request->input('notes', 'Disetujui')]
            );

            // Periksa apakah semua karyawan di departemen telah menyetujui
            $deptMembersCount = \App\Models\User::where('department', $schedule->department)
                ->where('id', '!=', $schedule->user_id)
                ->count();

            $approvedMembersCount = $schedule->approvals()->where('status', 'approved')->count();

            if ($approvedMembersCount >= $deptMembersCount || $authUser->isDirektur()) {
                $schedule->update([
                    'approval_status' => 'approved',
                    'approved_by' => $authUser->id,
                    'approved_at' => now(),
                    'rejection_notes' => null,
                ]);
                $message = 'Jadwal departemen telah disetujui oleh seluruh anggota dan resmi dijadwalkan!';
            } else {
                $message = "Persetujuan Anda telah dicatat ({$approvedMembersCount}/{$deptMembersCount} anggota menyetujui).";
            }

            return back()->with('success', $message);
        }

        if ($schedule->scope === 'antar_departemen') {
            $targetDepts = $schedule->getTargetDepartmentsList();
            
            // Check if user is Direktur or Manager of one of the target departments
            $canApprove = false;
            $approvingDept = null;

            if ($authUser->isDirektur()) {
                $canApprove = true;
            } elseif ($authUser->isManager() && $schedule->hasTargetDepartment($authUser->department)) {
                $canApprove = true;
                $approvingDept = $authUser->department;
            }

            if (!$canApprove) {
                return back()->with('error', 'Hanya Manager dari departemen tujuan (' . implode(', ', $targetDepts) . ') yang berhak menyetujui jadwal ini.');
            }

            // Record this manager's approval
            $schedule->approvals()->updateOrCreate(
                ['user_id' => $authUser->id],
                ['status' => 'approved', 'notes' => $request->input('notes', 'Disetujui oleh Manager ' . ($approvingDept ?? $authUser->department))]
            );

            // Check if all targeted departments have had at least one manager approval
            // Or if Direktur approves, it directly approves all
            if ($authUser->isDirektur()) {
                $schedule->update([
                    'approval_status' => 'approved',
                    'approved_by' => $authUser->id,
                    'approved_at' => now(),
                    'rejection_notes' => null,
                ]);
                return back()->with('success', 'Jadwal antar departemen telah disetujui secara langsung oleh Direktur!');
            }

            // Count how many target departments have approved
            // Re-fetch approvals
            $approvedUserIds = $schedule->approvals()->where('status', 'approved')->pluck('user_id');
            $approvedDepts = \App\Models\User::whereIn('id', $approvedUserIds)->pluck('department')->unique()->toArray();

            $missingDepts = array_filter($targetDepts, function ($t) use ($approvedDepts) {
                return !in_array($t, $approvedDepts);
            });

            if (empty($missingDepts)) {
                $schedule->update([
                    'approval_status' => 'approved',
                    'approved_by' => $authUser->id,
                    'approved_at' => now(),
                    'rejection_notes' => null,
                ]);
                return back()->with('success', 'Semua departemen tujuan (' . implode(', ', $targetDepts) . ') telah menyetujui. Jadwal resmi dijadwalkan!');
            } else {
                return back()->with('success', 'Persetujuan dari Manager ' . $authUser->department . ' telah dicatat. Menunggu persetujuan: ' . implode(', ', $missingDepts) . '.');
            }
        }

        return back()->with('error', 'Jadwal ini tidak membutuhkan persetujuan.');
    }

    /**
     * Reject a department or cross-department schedule.
     */
    public function reject(Request $request, $id)
    {
        $schedule = CalendarSchedule::findOrFail($id);
        $authUser = Auth::user();

        // 1. Anti-self-approval / rejection by creator
        if ($schedule->user_id === $authUser->id) {
            return back()->with('error', 'Anda tidak dapat memproses penolakan untuk jadwal yang Anda buat sendiri.');
        }

        $request->validate([
            'notes' => 'required|string|min:3|max:500',
        ], [
            'notes.required' => 'Alasan penolakan jadwal wajib disertakan agar pihak pengaju memahami penyebabnya.',
            'notes.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        // 2. Validate permission based on scope
        if ($schedule->scope === 'departemen') {
            if (!$authUser->isDirektur() && strcasecmp($authUser->department ?? '', $schedule->department ?? '') !== 0) {
                return back()->with('error', 'Anda tidak memiliki hak untuk menolak jadwal dari departemen ini.');
            }

            $schedule->approvals()->updateOrCreate(
                ['user_id' => $authUser->id],
                ['status' => 'rejected', 'notes' => $request->input('notes', 'Ditolak')]
            );

            $schedule->update([
                'approval_status' => 'rejected',
                'approved_by' => $authUser->id,
                'approved_at' => now(),
                'rejection_notes' => $request->input('notes', 'Ditolak oleh anggota departemen (' . $authUser->name . ')'),
            ]);

            return back()->with('success', 'Jadwal departemen telah ditolak.');
        }

        if ($schedule->scope === 'antar_departemen') {
            $targetDepts = $schedule->getTargetDepartmentsList();
            $canReject = false;

            if ($authUser->isDirektur()) {
                $canReject = true;
            } elseif ($authUser->isManager() && $schedule->hasTargetDepartment($authUser->department)) {
                $canReject = true;
            }

            if (!$canReject) {
                return back()->with('error', 'Hanya Manager dari departemen tujuan (' . implode(', ', $targetDepts) . ') yang berhak menolak jadwal antar departemen ini.');
            }

            $schedule->approvals()->updateOrCreate(
                ['user_id' => $authUser->id],
                ['status' => 'rejected', 'notes' => $request->input('notes', 'Ditolak oleh Manager')]
            );

            $schedule->update([
                'approval_status' => 'rejected',
                'approved_by' => $authUser->id,
                'approved_at' => now(),
                'rejection_notes' => $request->input('notes', 'Ditolak oleh Manager Departemen ' . $authUser->department . ' (' . $authUser->name . ')'),
            ]);

            return back()->with('success', 'Jadwal antar departemen telah ditolak.');
        }

        return back()->with('error', 'Jadwal ini tidak membutuhkan tindakan.');
    }

    /**
     * Update an existing Calendar Schedule / Note.
     */
    public function update(Request $request, $id)
    {
        $schedule = CalendarSchedule::findOrFail($id);
        $authUser = Auth::user();

        // Check permission (owner, HR, or Director)
        if ($schedule->user_id !== $authUser->id && !$authUser->isDirektur() && !$authUser->isHR()) {
            return back()->with('error', 'Anda tidak memiliki hak untuk mengubah jadwal ini.');
        }

        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'category' => 'required|string|in:jadwal,kunjungan,meeting,tugas,catatan,pengumuman',
            'color' => 'nullable|string|in:rose,emerald,amber,blue,purple,cyan',
            'time_start' => 'nullable',
            'time_end' => 'nullable',
            'description' => 'nullable|string|max:1000',
        ]);

        $schedule->update([
            'date' => $request->date,
            'title' => $request->title,
            'description' => $request->description,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'category' => $request->category,
            'color' => $request->color ?? $schedule->color,
            'is_company_wide' => $request->has('is_company_wide') ? $request->boolean('is_company_wide') : $schedule->is_company_wide,
        ]);

        $dateCarbon = Carbon::parse($request->date);

        return redirect()->route('calendar.index', [
            'month' => $dateCarbon->month,
            'year' => $dateCarbon->year,
            'date' => $request->date
        ])->with('success', 'Jadwal berhasil diperbarui!');
    }

    /**
     * Delete a Calendar Schedule / Note.
     */
    public function destroy($id)
    {
        $schedule = CalendarSchedule::findOrFail($id);
        $authUser = Auth::user();

        // Check permission (owner, HR, or Director)
        if ($schedule->user_id !== $authUser->id && !$authUser->isDirektur() && !$authUser->isHR()) {
            return back()->with('error', 'Anda tidak memiliki hak untuk menghapus jadwal ini.');
        }

        $date = $schedule->date->toDateString();
        $dateCarbon = Carbon::parse($date);
        $schedule->delete();

        return redirect()->route('calendar.index', [
            'month' => $dateCarbon->month,
            'year' => $dateCarbon->year,
            'date' => $date
        ])->with('success', 'Catatan/jadwal telah dihapus.');
    }
}
