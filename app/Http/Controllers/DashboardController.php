<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        // 1. Metric Counts
        $totalEmployees = Employee::count();
        $newEmployeesThisMonth = Employee::whereMonth('join_date', Carbon::now()->month)
            ->whereYear('join_date', Carbon::now()->year)
            ->count();

        $presentTodayCount = Attendance::whereDate('date', $today)->count();
        $onTimeCount = Attendance::whereDate('date', $today)->where('status', 'on_time')->count();
        $lateCount = Attendance::whereDate('date', $today)->where('status', 'late')->count();

        $attendanceRate = $totalEmployees > 0 ? round(($presentTodayCount / $totalEmployees) * 100, 1) : 0;

        $leaveCount = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        // Analytical Metrics Khusus Direktur & Sekretaris Direktur
        $directorMetrics = null;
        if ($authUser->isDirektur() || $authUser->isSekretaris()) {
            $onTimeRate = $presentTodayCount > 0 ? round(($onTimeCount / $presentTodayCount) * 100, 1) : 0;
            $lateRate = $presentTodayCount > 0 ? round(($lateCount / $presentTodayCount) * 100, 1) : 0;
            $leaveRate = $totalEmployees > 0 ? round(($leaveCount / $totalEmployees) * 100, 1) : 0;

            // Tasks Metrics Perusahaan
            $totalTasks = \App\Models\Task::count();
            $completedTasks = \App\Models\Task::where('status', 'completed')->count();
            $inProgressTasks = \App\Models\Task::where('status', 'in_progress')->count();
            $tasksCompletionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
            $avgTaskProgress = $totalTasks > 0 ? round(\App\Models\Task::avg('progress_percentage') ?? 0, 1) : 0;

            // Finance Reimbursement Metrics
            $totalReimburseCount = \App\Models\Reimbursement::count();
            $paidReimburseCount = \App\Models\Reimbursement::where('status', 'paid')->count();
            $pendingReimburseCount = \App\Models\Reimbursement::where('status', 'pending')->count();
            $paidReimburseRate = $totalReimburseCount > 0 ? round(($paidReimburseCount / $totalReimburseCount) * 100, 1) : 0;

            // Facilities Tickets GA
            $totalTickets = \App\Models\FacilityTicket::count();
            $resolvedTickets = \App\Models\FacilityTicket::whereIn('status', ['resolved', 'closed'])->count();
            $ticketResolvedRate = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;

            // Recruitment Pipeline
            $totalCandidates = \App\Models\Candidate::count();
            $onboardingCandidates = \App\Models\Candidate::where('stage', 'onboarding')->count();
            $hireConversionRate = $totalCandidates > 0 ? round(($onboardingCandidates / $totalCandidates) * 100, 1) : 0;

            $directorMetrics = [
                'onTimeRate' => $onTimeRate,
                'lateRate' => $lateRate,
                'leaveRate' => $leaveRate,
                'totalTasks' => $totalTasks,
                'completedTasks' => $completedTasks,
                'inProgressTasks' => $inProgressTasks,
                'tasksCompletionRate' => $tasksCompletionRate,
                'avgTaskProgress' => $avgTaskProgress,
                'totalReimburseCount' => $totalReimburseCount,
                'paidReimburseRate' => $paidReimburseRate,
                'pendingReimburseCount' => $pendingReimburseCount,
                'totalTickets' => $totalTickets,
                'ticketResolvedRate' => $ticketResolvedRate,
                'totalCandidates' => $totalCandidates,
                'hireConversionRate' => $hireConversionRate,
            ];
        }

        // ==========================================
        // 1. KPI INDIVIDU (PERSONAL EMPLOYEE KPI: DAILY, WEEKLY, MONTHLY)
        // ==========================================
        $individualKpi = null;
        if ($currentUser) {
            $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
            $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

            // Task completion individu
            $myTasks = \App\Models\Task::where('assigned_to', $currentUser->id)->get();
            $myTotalTasks = $myTasks->count();
            $myCompletedTasks = $myTasks->where('status', 'completed')->count();
            $myTaskRate = $myTotalTasks > 0 ? round(($myCompletedTasks / $myTotalTasks) * 100, 1) : 100;
            $myAvgProgress = $myTotalTasks > 0 ? round($myTasks->avg('progress_percentage') ?? 0, 1) : 100;

            // --- A. KPI HARIAN (DAILY INDIVIDUAL) ---
            $myDailyAtt = Attendance::where('employee_id', $currentUser->id)
                ->whereDate('date', $today)
                ->first();
            $dailyPunctualScore = $myDailyAtt ? ($myDailyAtt->status === 'on_time' ? 100 : 60) : 0;
            // Tasks updated today or completed today
            $dailyTasks = $myTasks->filter(function($t) use ($today) {
                return Carbon::parse($t->updated_at)->isToday() || Carbon::parse($t->created_at)->isToday();
            });
            $dailyTaskProgress = $dailyTasks->count() > 0 ? round($dailyTasks->avg('progress_percentage') ?? 0, 1) : ($myDailyAtt ? 85.0 : 0.0);
            $dailyScore = round(($dailyPunctualScore * 0.4) + ($dailyTaskProgress * 0.6), 1);

            // --- B. KPI MINGGUAN (WEEKLY INDIVIDUAL) ---
            $myWeeklyAtt = Attendance::where('employee_id', $currentUser->id)
                ->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->get();
            $weeklyAttCount = $myWeeklyAtt->count();
            $weeklyOnTime = $myWeeklyAtt->where('status', 'on_time')->count();
            $weeklyPunctualRate = $weeklyAttCount > 0 ? round(($weeklyOnTime / $weeklyAttCount) * 100, 1) : 100.0;
            $weeklyTasks = $myTasks->filter(function($t) use ($startOfWeek, $endOfWeek) {
                $up = Carbon::parse($t->updated_at)->toDateString();
                return $up >= $startOfWeek && $up <= $endOfWeek;
            });
            $weeklyTaskRate = $weeklyTasks->count() > 0 ? round(($weeklyTasks->where('status', 'completed')->count() / $weeklyTasks->count()) * 100, 1) : $myTaskRate;
            $weeklyScore = round(($weeklyTaskRate * 0.5) + ($weeklyPunctualRate * 0.5), 1);

            // --- C. KPI BULANAN / OVERALL ---
            $thisMonthAttendances = Attendance::where('employee_id', $currentUser->id)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->get();
            $myAttendanceCount = $thisMonthAttendances->count();
            $myOnTimeCount = $thisMonthAttendances->where('status', 'on_time')->count();
            $myPunctualityRate = $myAttendanceCount > 0 ? round(($myOnTimeCount / $myAttendanceCount) * 100, 1) : 100;

            // Indeks Performa Gabungan Individu (Composite KPI Score)
            $overallIndividualScore = round(($myTaskRate * 0.5) + ($myPunctualityRate * 0.3) + ($myAvgProgress * 0.2), 1);

            $individualKpi = [
                'totalTasks' => $myTotalTasks,
                'completedTasks' => $myCompletedTasks,
                'taskRate' => $myTaskRate,
                'avgProgress' => $myAvgProgress,
                'attendanceCount' => $myAttendanceCount,
                'punctualityRate' => $myPunctualityRate,
                'overallScore' => $overallIndividualScore,
                'daily' => [
                    'score' => $dailyScore,
                    'status' => $myDailyAtt ? ($myDailyAtt->status === 'on_time' ? 'Tepat Waktu' : 'Terlambat') : 'Belum Hadir',
                    'taskProgress' => $dailyTaskProgress,
                    'isClockedIn' => (bool)$myDailyAtt,
                ],
                'weekly' => [
                    'score' => $weeklyScore,
                    'attendedDays' => $weeklyAttCount,
                    'punctualRate' => $weeklyPunctualRate,
                    'taskRate' => $weeklyTaskRate,
                ],
            ];
        }

        // ==========================================
        // 2. GRAFIK KPI MASING-MASING DEPARTEMEN (DAILY, WEEKLY & OVERALL)
        // ==========================================
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        // A. HR Dept: Recruitment Pipeline & Attendance Discipline
        $hrTotalCandidates = \App\Models\Candidate::count();
        $hrHired = \App\Models\Candidate::whereIn('stage', ['offering', 'onboarding'])->count();
        $hrHiringRate = $hrTotalCandidates > 0 ? round(($hrHired / $hrTotalCandidates) * 100, 1) : 85.0;

        $hrEmp = Employee::where('department', 'HR')->pluck('id');
        $hrAttDaily = Attendance::whereIn('employee_id', $hrEmp)->whereDate('date', $today)->get();
        $hrDailyOnTime = $hrAttDaily->count() > 0 ? round(($hrAttDaily->where('status', 'on_time')->count() / $hrAttDaily->count()) * 100, 1) : 100.0;
        $hrDailyAttRate = $hrEmp->count() > 0 ? round(($hrAttDaily->count() / $hrEmp->count()) * 100, 1) : 100.0;
        $hrDailyScore = round(($hrDailyOnTime * 0.6) + ($hrDailyAttRate * 0.4), 1);

        $hrAttWeekly = Attendance::whereIn('employee_id', $hrEmp)->whereBetween('date', [$startOfWeek, $endOfWeek])->get();
        $hrWeeklyOnTime = $hrAttWeekly->count() > 0 ? round(($hrAttWeekly->where('status', 'on_time')->count() / $hrAttWeekly->count()) * 100, 1) : 95.0;
        $hrWeeklyScore = round(($hrWeeklyOnTime * 0.5) + ($hrHiringRate * 0.5), 1);

        $hrScore = round(($hrHiringRate * 0.5) + ($hrDailyOnTime * 0.5), 1);

        // B. Finance Dept: Reimbursement Realization & Payroll Distribution
        $finReimbCount = \App\Models\Reimbursement::count();
        $finPaidReimb = \App\Models\Reimbursement::where('status', 'paid')->count();
        $finReimbRate = $finReimbCount > 0 ? round(($finPaidReimb / $finReimbCount) * 100, 1) : 100.0;

        $finSlipsCount = \App\Models\SalarySlip::count();
        $finPaidSlips = \App\Models\SalarySlip::where('status', 'paid')->count();
        $finPayrollRate = $finSlipsCount > 0 ? round(($finPaidSlips / $finSlipsCount) * 100, 1) : 100.0;
        $financeScore = round(($finReimbRate * 0.5) + ($finPayrollRate * 0.5), 1);

        // Finance Daily & Weekly
        $finReimbDaily = \App\Models\Reimbursement::whereDate('created_at', $today)->count();
        $finDailyScore = $finReimbDaily > 0 ? 92.0 : 96.0;
        $finWeeklyScore = round(($finReimbRate * 0.6) + ($finPayrollRate * 0.4), 1);

        // C. Bisnis Development: Deals Win Rate & Pipeline Conversion
        $bizWon = \App\Models\LeadProject::where('stage', 'won')->sum('deal_value');
        $bizPipeline = \App\Models\LeadProject::where('stage', '!=', 'lost')->sum('deal_value');
        $bizWinRate = $bizPipeline > 0 ? round(($bizWon / $bizPipeline) * 100, 1) : 75.0;

        $bizTasks = \App\Models\Task::whereHas('assignedEmployee', fn($q) => $q->where('department', 'Bisnis Development'))->get();
        $bizTaskRate = $bizTasks->count() > 0 ? round(($bizTasks->where('status', 'completed')->count() / $bizTasks->count()) * 100, 1) : 80.0;
        $bizdevScore = round(($bizWinRate * 0.6) + ($bizTaskRate * 0.4), 1);

        // BizDev Daily & Weekly
        $bizDailyTasks = $bizTasks->filter(fn($t) => Carbon::parse($t->updated_at)->isToday());
        $bizDailyScore = $bizDailyTasks->count() > 0 ? round(($bizDailyTasks->where('status', 'completed')->count() / $bizDailyTasks->count()) * 100, 1) : 85.0;
        $bizWeeklyScore = round(($bizWinRate * 0.5) + ($bizTaskRate * 0.5), 1);

        // D. General Affairs (GA): Facility Ticket Resolution & Asset Health
        $gaTickets = \App\Models\FacilityTicket::count();
        $gaResolved = \App\Models\FacilityTicket::whereIn('status', ['resolved', 'closed'])->count();
        $gaResolutionRate = $gaTickets > 0 ? round(($gaResolved / $gaTickets) * 100, 1) : 90.0;

        $gaTotalItems = \App\Models\InventoryItem::count();
        $gaGoodItems = \App\Models\InventoryItem::where('condition', 'good')->count();
        $gaAssetHealthRate = $gaTotalItems > 0 ? round(($gaGoodItems / $gaTotalItems) * 100, 1) : 95.0;
        $gaScore = round(($gaResolutionRate * 0.5) + ($gaAssetHealthRate * 0.5), 1);

        // GA Daily & Weekly
        $gaDailyTickets = \App\Models\FacilityTicket::whereDate('created_at', $today)->count();
        $gaDailyResolved = \App\Models\FacilityTicket::whereDate('resolved_at', $today)->count();
        $gaDailyScore = $gaDailyTickets > 0 ? round(($gaDailyResolved / $gaDailyTickets) * 100, 1) : 95.0;
        $gaWeeklyTickets = \App\Models\FacilityTicket::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        $gaWeeklyResolved = \App\Models\FacilityTicket::whereBetween('resolved_at', [$startOfWeek, $endOfWeek])->count();
        $gaWeeklyScore = $gaWeeklyTickets > 0 ? round(($gaWeeklyResolved / $gaWeeklyTickets) * 100, 1) : 92.0;

        $allDepartmentKpis = [
            'HR' => [
                'name' => 'Human Resources (HR)',
                'icon' => 'groups',
                'color' => 'rose',
                'score' => $hrScore,
                'dailyScore' => $hrDailyScore,
                'weeklyScore' => $hrWeeklyScore,
                'metrics' => [
                    ['label' => 'Hiring & Onboarding Rate', 'value' => $hrHiringRate . '%'],
                    ['label' => 'Kedisiplinan Presensi Tim', 'value' => $hrDailyOnTime . '%'],
                ]
            ],
            'Finance' => [
                'name' => 'Finance & Payroll',
                'icon' => 'payments',
                'color' => 'emerald',
                'score' => $financeScore,
                'dailyScore' => $finDailyScore,
                'weeklyScore' => $finWeeklyScore,
                'metrics' => [
                    ['label' => 'Realisasi Reimbursement', 'value' => $finReimbRate . '%'],
                    ['label' => 'Distribusi Slip Gaji / Payroll', 'value' => $finPayrollRate . '%'],
                ]
            ],
            'Bisnis Development' => [
                'name' => 'Bisnis Development',
                'icon' => 'trending_up',
                'color' => 'blue',
                'score' => $bizdevScore,
                'dailyScore' => $bizDailyScore,
                'weeklyScore' => $bizWeeklyScore,
                'metrics' => [
                    ['label' => 'Deals Win Rate & Value', 'value' => $bizWinRate . '%'],
                    ['label' => 'Eksekusi Task Prospek', 'value' => $bizTaskRate . '%'],
                ]
            ],
            'General Affairs' => [
                'name' => 'General Affairs (GA)',
                'icon' => 'inventory_2',
                'color' => 'amber',
                'score' => $gaScore,
                'dailyScore' => $gaDailyScore,
                'weeklyScore' => $gaWeeklyScore,
                'metrics' => [
                    ['label' => 'Resolusi Tiket Kerusakan', 'value' => $gaResolutionRate . '%'],
                    ['label' => 'Rasio Kondisi Aset Baik', 'value' => $gaAssetHealthRate . '%'],
                ]
            ],
        ];


        // Filter KPI Departemen:
        // 1. Direktur & Sekretaris Direktur: Melihat seluruh 4 departemen (KPI & Kinerja Global)
        // 2. Manager HR: Melihat Departemen HR dan Departemen GA (General Affairs yang berada dalam kontrol pengawasannya)
        // 3. Staf / Manager divisi lain: HANYA menampilkan KPI departemen tempat akun tersebut bertugas
        if ($authUser->isDirektur() || $authUser->isSekretaris()) {
            $departmentKpis = $allDepartmentKpis;
        } elseif ($authUser->isHR() && $authUser->isManager()) {
            // Manager HR menampilkan KPI Departemen HR dan Divisi GA yang berada di bawah kepemimpinannya
            $departmentKpis = array_filter($allDepartmentKpis, function ($key) {
                return in_array($key, ['HR', 'General Affairs']);
            }, ARRAY_FILTER_USE_KEY);
        } elseif ($authUser->isAsistenManager() || $authUser->isGA()) {
            // Asisten Manager GA & Staf GA HANYA melihat KPI General Affairs (GA)
            $departmentKpis = array_filter($allDepartmentKpis, function ($key) {
                return $key === 'General Affairs';
            }, ARRAY_FILTER_USE_KEY);
        } elseif ($authUser->isPureHR()) {
            // Karyawan HR murni HANYA melihat KPI HR
            $departmentKpis = array_filter($allDepartmentKpis, function ($key) {
                return $key === 'HR';
            }, ARRAY_FILTER_USE_KEY);
        } else {
            $userDept = $authUser->department ?? ($currentUser->department ?? null);
            if ($userDept === 'GA') {
                $userDept = 'General Affairs';
            }
            if ($userDept && isset($allDepartmentKpis[$userDept])) {
                $departmentKpis = [$userDept => $allDepartmentKpis[$userDept]];
            } else {
                $departmentKpis = array_filter($allDepartmentKpis, function ($key) use ($userDept) {
                    return str_contains(strtolower($key), strtolower($userDept ?? ''));
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        // Scope Pending Approvals Berjenjang (Two-Tier):
        // - Direktur Utama: Melihat seluruh permohonan pending_manager & pending_hr dari semua departemen
        // - Manager HR: Melihat seluruh permohonan yang menunggu persetujuan HR (pending_hr / pending), serta pending_manager divisi HR
        // - Manager Departemen Lain: Melihat permohonan tahap 1 (pending_manager) dari anggotanya + permohonan miliknya sendiri
        // - Karyawan: HANYA melihat pengajuan permohonan miliknya sendiri
        $pendingApprovalsQuery = LeaveRequest::with(['employee', 'managerApprover']);

        if ($authUser->isDirektur()) {
            $pendingApprovalsQuery->whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
        } elseif ($authUser->isHR() && $authUser->isManager()) {
            $pendingApprovalsQuery->where(function ($q) use ($currentUser) {
                $q->whereIn('status', ['pending', 'pending_hr'])
                  ->orWhere(function ($sub) {
                      $sub->where('status', 'pending_manager')
                          ->whereHas('employee', function ($e) {
                              $e->where('department', 'HR');
                          });
                  });
                if ($currentUser) {
                    $q->orWhere('employee_id', $currentUser->id);
                }
            });
        } elseif ($authUser->isManager()) {
            $userDept = $authUser->department;
            $pendingApprovalsQuery->where(function ($q) use ($userDept, $currentUser) {
                $q->where('status', 'pending_manager')
                  ->whereHas('employee', function ($e) use ($userDept) {
                      $e->where('department', $userDept);
                  });
                if ($currentUser) {
                    $q->orWhere('employee_id', $currentUser->id);
                }
            });
        } elseif ($authUser->isSekretaris()) {
            // Sekretaris HANYA untuk mengecek KPI dan kinerja, tidak ada pengambil keputusan operasional cuti/izin
            $pendingApprovalsQuery->whereRaw('1 = 0');
        } else {
            if ($currentUser) {
                $pendingApprovalsQuery->where('employee_id', $currentUser->id)
                    ->whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
            } else {
                $pendingApprovalsQuery->whereRaw('1 = 0');
            }
        }
        $pendingApprovals = $pendingApprovalsQuery->orderBy('created_at', 'desc')->get();

        // Scope attendance feed based on role & department:
        // 1. Direktur, Sekretaris Direktur & Departemen HR (Manager & Karyawan HR): Melihat presensi seluruh karyawan
        // 2. Manager divisi lain: Melihat dirinya sendiri dan karyawan pada departemennya
        // 3. Karyawan divisi lain: Hanya melihat dirinya sendiri
        $attendanceQuery = Attendance::with('employee')
            ->whereDate('date', $today)
            ->orderBy('clock_in', 'desc');

        if (!$authUser->isDirektur() && !$authUser->isHR() && !$authUser->isSekretaris()) {
            if ($authUser->isKaryawan()) {
                if ($currentUser) {
                    $attendanceQuery->where('employee_id', $currentUser->id);
                }
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $attendanceQuery->whereHas('employee', function ($q) use ($userDept, $currentUser) {
                    $q->where('department', $userDept);
                    if ($currentUser) {
                        $q->orWhere('id', $currentUser->id);
                    }
                });
            }
        }


        $recentAttendances = $attendanceQuery->limit(10)->get();

        // 3. Current User's own attendance today
        $userTodayAttendance = $currentUser ? Attendance::where('employee_id', $currentUser->id)
            ->whereDate('date', $today)
            ->first() : null;

        return view('dashboard.index', compact(
            'authUser',
            'currentUser',
            'totalEmployees',
            'newEmployeesThisMonth',
            'presentTodayCount',
            'onTimeCount',
            'lateCount',
            'attendanceRate',
            'leaveCount',
            'pendingApprovals',
            'recentAttendances',
            'userTodayAttendance',
            'directorMetrics',
            'individualKpi',
            'departmentKpis'
        ));
    }
}
