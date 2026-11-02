<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Reimbursement;
use App\Models\SalarySlip;
use App\Models\FacilityTicket;
use App\Models\InventoryItem;
use App\Models\LeadProject;
use App\Models\Candidate;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportExportController extends Controller
{
    /**
     * Export KPI Report (Individual & Department) to printable PDF template
     */
    public function exportKpi(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;
        $period = $request->query('period', 'monthly'); // daily, weekly, monthly
        $targetDept = $request->query('department');
        $today = Carbon::today()->toDateString();
        $startOfWeek = Carbon::now()->startOfWeek()->toDateString();
        $endOfWeek = Carbon::now()->endOfWeek()->toDateString();

        // 1. Hitung Metrik Individual jika ada
        $individualKpi = null;
        if ($currentUser) {
            $myTasks = Task::where('assigned_to', $currentUser->id)->get();
            $myTotalTasks = $myTasks->count();
            $myCompletedTasks = $myTasks->where('status', 'completed')->count();
            $myTaskRate = $myTotalTasks > 0 ? round(($myCompletedTasks / $myTotalTasks) * 100, 1) : 100;
            $myAvgProgress = $myTotalTasks > 0 ? round($myTasks->avg('progress_percentage') ?? 0, 1) : 100;

            // Daily
            $myDailyAtt = Attendance::where('employee_id', $currentUser->id)->whereDate('date', $today)->first();
            $dailyPunctualScore = $myDailyAtt ? ($myDailyAtt->status === 'on_time' ? 100 : 60) : 0;
            $dailyTasks = $myTasks->filter(fn($t) => Carbon::parse($t->updated_at)->isToday() || Carbon::parse($t->created_at)->isToday());
            $dailyTaskProgress = $dailyTasks->count() > 0 ? round($dailyTasks->avg('progress_percentage') ?? 0, 1) : ($myDailyAtt ? 85.0 : 0.0);
            $dailyScore = round(($dailyPunctualScore * 0.4) + ($dailyTaskProgress * 0.6), 1);

            // Weekly
            $myWeeklyAtt = Attendance::where('employee_id', $currentUser->id)->whereBetween('date', [$startOfWeek, $endOfWeek])->get();
            $weeklyAttCount = $myWeeklyAtt->count();
            $weeklyOnTime = $myWeeklyAtt->where('status', 'on_time')->count();
            $weeklyPunctualRate = $weeklyAttCount > 0 ? round(($weeklyOnTime / $weeklyAttCount) * 100, 1) : 100.0;
            $weeklyTasks = $myTasks->filter(function($t) use ($startOfWeek, $endOfWeek) {
                $up = Carbon::parse($t->updated_at)->toDateString();
                return $up >= $startOfWeek && $up <= $endOfWeek;
            });
            $weeklyTaskRate = $weeklyTasks->count() > 0 ? round(($weeklyTasks->where('status', 'completed')->count() / $weeklyTasks->count()) * 100, 1) : $myTaskRate;
            $weeklyScore = round(($weeklyTaskRate * 0.5) + ($weeklyPunctualRate * 0.5), 1);

            // Monthly
            $thisMonthAttendances = Attendance::where('employee_id', $currentUser->id)
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->get();
            $myAttendanceCount = $thisMonthAttendances->count();
            $myOnTimeCount = $thisMonthAttendances->where('status', 'on_time')->count();
            $myPunctualityRate = $myAttendanceCount > 0 ? round(($myOnTimeCount / $myAttendanceCount) * 100, 1) : 100;
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
                ],
                'weekly' => [
                    'score' => $weeklyScore,
                    'attendedDays' => $weeklyAttCount,
                    'punctualRate' => $weeklyPunctualRate,
                    'taskRate' => $weeklyTaskRate,
                ],
            ];
        }

        // 2. Hitung Metrik Departemen
        // HR
        $hrTotalCandidates = Candidate::count();
        $hrHired = Candidate::whereIn('stage', ['offering', 'onboarding'])->count();
        $hrHiringRate = $hrTotalCandidates > 0 ? round(($hrHired / $hrTotalCandidates) * 100, 1) : 85.0;
        $hrEmp = Employee::where('department', 'HR')->pluck('id');
        $hrAttDaily = Attendance::whereIn('employee_id', $hrEmp)->whereDate('date', $today)->get();
        $hrDailyOnTime = $hrAttDaily->count() > 0 ? round(($hrAttDaily->where('status', 'on_time')->count() / $hrAttDaily->count()) * 100, 1) : 100.0;
        $hrAttWeekly = Attendance::whereIn('employee_id', $hrEmp)->whereBetween('date', [$startOfWeek, $endOfWeek])->get();
        $hrWeeklyOnTime = $hrAttWeekly->count() > 0 ? round(($hrAttWeekly->where('status', 'on_time')->count() / $hrAttWeekly->count()) * 100, 1) : 95.0;
        $hrScore = round(($hrHiringRate * 0.5) + ($hrDailyOnTime * 0.5), 1);

        // Finance
        $finReimbCount = Reimbursement::count();
        $finPaidReimb = Reimbursement::where('status', 'paid')->count();
        $finReimbRate = $finReimbCount > 0 ? round(($finPaidReimb / $finReimbCount) * 100, 1) : 100.0;
        $finSlipsCount = SalarySlip::count();
        $finPaidSlips = SalarySlip::where('status', 'paid')->count();
        $finPayrollRate = $finSlipsCount > 0 ? round(($finPaidSlips / $finSlipsCount) * 100, 1) : 100.0;
        $financeScore = round(($finReimbRate * 0.5) + ($finPayrollRate * 0.5), 1);

        // BizDev
        $bizWon = LeadProject::where('stage', 'won')->sum('deal_value');
        $bizPipeline = LeadProject::where('stage', '!=', 'lost')->sum('deal_value');
        $bizWinRate = $bizPipeline > 0 ? round(($bizWon / $bizPipeline) * 100, 1) : 75.0;
        $bizTasks = Task::whereHas('assignedEmployee', fn($q) => $q->where('department', 'Bisnis Development'))->get();
        $bizTaskRate = $bizTasks->count() > 0 ? round(($bizTasks->where('status', 'completed')->count() / $bizTasks->count()) * 100, 1) : 80.0;
        $bizdevScore = round(($bizWinRate * 0.6) + ($bizTaskRate * 0.4), 1);

        // GA
        $gaTickets = FacilityTicket::count();
        $gaResolved = FacilityTicket::whereIn('status', ['resolved', 'closed'])->count();
        $gaResolutionRate = $gaTickets > 0 ? round(($gaResolved / $gaTickets) * 100, 1) : 90.0;
        $gaTotalItems = InventoryItem::count();
        $gaGoodItems = InventoryItem::where('condition', 'good')->count();
        $gaAssetHealthRate = $gaTotalItems > 0 ? round(($gaGoodItems / $gaTotalItems) * 100, 1) : 95.0;
        $gaScore = round(($gaResolutionRate * 0.5) + ($gaAssetHealthRate * 0.5), 1);

        $allDepartmentKpis = [
            'HR' => [
                'name' => 'Human Resources (HR)',
                'score' => $hrScore,
                'dailyScore' => round(($hrDailyOnTime * 0.6) + (100 * 0.4), 1),
                'weeklyScore' => round(($hrWeeklyOnTime * 0.5) + ($hrHiringRate * 0.5), 1),
                'metrics' => [
                    'Hiring & Onboarding' => $hrHiringRate . '%',
                    'Disiplin Presensi Tim' => $hrDailyOnTime . '%',
                ],
                'description' => 'Efektivitas pemenuhan talent rekrutmen dan kepatuhan jam kerja karyawan.'
            ],
            'Finance' => [
                'name' => 'Finance & Payroll',
                'score' => $financeScore,
                'dailyScore' => 96.0,
                'weeklyScore' => round(($finReimbRate * 0.6) + ($finPayrollRate * 0.4), 1),
                'metrics' => [
                    'Realisasi Reimbursement' => $finReimbRate . '%',
                    'Distribusi Payroll Slip Gaji' => $finPayrollRate . '%',
                ],
                'description' => 'Ketepatan pencairan anggaran operasional dan kepatuhan pembayaran payroll.'
            ],
            'Bisnis Development' => [
                'name' => 'Bisnis Development',
                'score' => $bizdevScore,
                'dailyScore' => 88.0,
                'weeklyScore' => round(($bizWinRate * 0.5) + ($bizTaskRate * 0.5), 1),
                'metrics' => [
                    'Deals Conversion Win Rate' => $bizWinRate . '%',
                    'Penyelesaian Tugas Pipeline' => $bizTaskRate . '%',
                ],
                'description' => 'Konversi prospek bisnis baru, ekspansi kemitraan, dan perolehan deal.'
            ],
            'General Affairs' => [
                'name' => 'General Affairs (GA)',
                'score' => $gaScore,
                'dailyScore' => 94.0,
                'weeklyScore' => 92.0,
                'metrics' => [
                    'Penyelesaian Tiket Fasilitas' => $gaResolutionRate . '%',
                    'Aset Kantor Kondisi Baik' => $gaAssetHealthRate . '%',
                ],
                'description' => 'Kecepatan penanganan kendala inventaris kantor dan kelaikan fasilitas gedung.'
            ],
        ];

        // Scope departemen
        if ($authUser->isDirektur() || $authUser->isSekretaris()) {
            $departmentKpis = $allDepartmentKpis;
        } elseif ($authUser->isHR() && $authUser->isManager()) {
            $departmentKpis = array_filter($allDepartmentKpis, fn($k) => in_array($k, ['HR', 'General Affairs']), ARRAY_FILTER_USE_KEY);
        } else {
            $userDept = $authUser->department ?? ($currentUser->department ?? 'HR');
            if ($userDept === 'GA') $userDept = 'General Affairs';
            $departmentKpis = array_filter($allDepartmentKpis, fn($k) => str_contains(strtolower($k), strtolower($userDept)), ARRAY_FILTER_USE_KEY);
        }

        $reportTitle = 'Laporan Eksekutif Kinerja & Key Performance Indicator (KPI)';
        $reportCode = 'RPT-KPI-' . strtoupper(date('Ymd-His'));

        return view('reports.kpi_pdf', compact(
            'individualKpi',
            'departmentKpis',
            'currentUser',
            'authUser',
            'period',
            'reportTitle',
            'reportCode',
            'today',
            'startOfWeek',
            'endOfWeek'
        ));
    }

    /**
     * Export Attendance Report to PDF
     */
    public function exportAttendance(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;
        $status = $request->query('status');

        $todayStr = Carbon::today()->toDateString();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if (!$startDate && !$endDate) {
            $singleDate = $request->query('date', $todayStr);
            $startDate = $singleDate;
            $endDate = $singleDate;
        } elseif ($startDate && !$endDate) {
            $endDate = $startDate;
        } elseif (!$startDate && $endDate) {
            $startDate = $endDate;
        }

        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        if ($startCarbon->greaterThan($endCarbon)) {
            $temp = $startCarbon;
            $startCarbon = $endCarbon;
            $endCarbon = $temp;
        }

        if ($startCarbon->diffInDays($endCarbon) > 31) {
            $endCarbon = $startCarbon->copy()->addDays(31);
        }

        $startDate = $startCarbon->toDateString();
        $endDate = $endCarbon->toDateString();
        $isRange = ($startDate !== $endDate);
        $date = $startDate;

        $query = Attendance::with('employee')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'asc');

        $canViewAll = $authUser->isDirektur() || $authUser->isHR();
        if (!$canViewAll) {
            if ($authUser->isKaryawan()) {
                if ($currentUser) $query->where('employee_id', $currentUser->id);
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $query->whereHas('employee', fn($q) => $q->where('department', $userDept)->orWhere('id', $currentUser?->id));
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->get();
        $totalPresent = $attendances->count();
        $totalOnTime = $attendances->where('status', 'on_time')->count();
        $totalLate = $attendances->where('status', 'late')->count();

        $reportTitle = 'Laporan Presensi & Log Kehadiran Karyawan';
        $reportCode = 'RPT-ATT-' . Carbon::parse($startDate)->format('Ymd') . ($isRange ? '-' . Carbon::parse($endDate)->format('Ymd') : '');

        return view('reports.attendance_pdf', compact(
            'attendances',
            'authUser',
            'currentUser',
            'date',
            'startDate',
            'endDate',
            'isRange',
            'totalPresent',
            'totalOnTime',
            'totalLate',
            'reportTitle',
            'reportCode'
        ));
    }
}
