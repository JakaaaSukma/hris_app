<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        $query = LeaveRequest::with(['employee', 'approver', 'managerApprover'])->orderBy('created_at', 'desc');

        // Hak Akses Visibilitas 2-Tier:
        // 1. Direktur Utama & Manager HR: Melihat seluruh pengajuan dari semua departemen.
        // 2. Manager Departemen Non-HR (Finance, GA, BizDev, Resepsionis, Trainer):
        //    Melihat pengajuan dari anggota departemennya sendiri + pengajuan milik dirinya sendiri.
        // 3. Karyawan Biasa: HANYA melihat pengajuan miliknya sendiri.
        if ($authUser->isDirektur() || ($authUser->isHR() && $authUser->isManager())) {
            // Full access across all departments
        } elseif ($authUser->isManager()) {
            $userDept = $authUser->department;
            $query->where(function ($q) use ($userDept, $currentUser) {
                $q->whereHas('employee', function ($sub) use ($userDept) {
                    $sub->where('department', $userDept);
                });
                if ($currentUser) {
                    $q->orWhere('employee_id', $currentUser->id);
                }
            });
        } else {
            if ($currentUser) {
                $query->where('employee_id', $currentUser->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($status === 'pending') {
            $query->whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
        } elseif ($status) {
            $query->where('status', $status);
        }

        $leaveRequests = $query->paginate(10);
        $employees = $authUser->canApproveLeaves()
            ? Employee::orderBy('name')->get()
            : ($currentUser ? Employee::where('id', $currentUser->id)->get() : Employee::all());

        // Counter Badges dengan scope visibilitas yang proporsional
        $pendingQuery = LeaveRequest::whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
        $approvedQuery = LeaveRequest::where('status', 'approved');
        $rejectedQuery = LeaveRequest::where('status', 'rejected');

        if ($authUser->isDirektur() || ($authUser->isHR() && $authUser->isManager())) {
            // Full count
        } elseif ($authUser->isManager()) {
            $userDept = $authUser->department;
            $applyDeptScope = function ($q) use ($userDept, $currentUser) {
                $q->where(function ($sub) use ($userDept, $currentUser) {
                    $sub->whereHas('employee', function ($e) use ($userDept) {
                        $e->where('department', $userDept);
                    });
                    if ($currentUser) {
                        $sub->orWhere('employee_id', $currentUser->id);
                    }
                });
            };
            $applyDeptScope($pendingQuery);
            $applyDeptScope($approvedQuery);
            $applyDeptScope($rejectedQuery);
        } else {
            if ($currentUser) {
                $pendingQuery->where('employee_id', $currentUser->id);
                $approvedQuery->where('employee_id', $currentUser->id);
                $rejectedQuery->where('employee_id', $currentUser->id);
            }
        }

        $pendingCount = $pendingQuery->count();
        $approvedCount = $approvedQuery->count();
        $rejectedCount = $rejectedQuery->count();

        // 1. Data Personal Kuota Cuti Tahunan (Untuk User yang sedang login: Karyawan / Manager / HR / Direktur)
        $myQuota = $currentUser ? (int) $currentUser->annual_leave_quota : 0;
        $myUsed = $currentUser ? (int) $currentUser->annual_leave_used : 0;
        $myPending = $currentUser ? (int) LeaveRequest::where('employee_id', $currentUser->id)
            ->where('type', 'Cuti Tahunan')
            ->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
            ->sum('duration_days') : 0;
        $myRemaining = max(0, $myQuota - $myUsed - $myPending);
        $myPctUsed = $myQuota > 0 ? min(100, round(($myUsed / $myQuota) * 100, 1)) : 0;
        $myPctPending = $myQuota > 0 ? min(100, round(($myPending / $myQuota) * 100, 1)) : 0;
        $myPctRemaining = max(0, 100 - $myPctUsed - $myPctPending);

        // Riwayat Cuti Tahunan Pribadi yang telah disetujui / diajukan
        $myAnnualLeaveHistory = $currentUser ? LeaveRequest::with(['approver', 'managerApprover'])
            ->where('employee_id', $currentUser->id)
            ->where('type', 'Cuti Tahunan')
            ->orderBy('start_date', 'desc')
            ->get() : collect();

        // 2. Data Monitoring Seluruh Karyawan & Manager (HANYA UNTUK Manager HR & Direktur)
        $allEmployeesLeave = null;
        $companyLeaveMetrics = null;
        $departmentsList = collect();

        if ($authUser->canApproveLeaves()) {
            $allEmployees = Employee::with(['user', 'leaveRequests' => function ($q) {
                $q->where('type', 'Cuti Tahunan');
            }])->orderBy('name')->get();

            $allEmployeesLeave = $allEmployees->map(function ($emp) {
                $pendingDays = (int) $emp->leaveRequests->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])->sum('duration_days');
                $approvedDays = (int) $emp->leaveRequests->where('status', 'approved')->sum('duration_days');
                $quota = (int) $emp->annual_leave_quota;
                $used = (int) $emp->annual_leave_used;
                $remaining = max(0, $quota - $used - $pendingDays);
                $pctUsed = $quota > 0 ? min(100, round(($used / $quota) * 100, 1)) : 0;
                $pctPending = $quota > 0 ? min(100, round(($pendingDays / $quota) * 100, 1)) : 0;

                $statusKey = 'aman';
                $statusLabel = 'Aman';
                $statusBadgeClass = 'bg-emerald-950/60 text-emerald-400 border-emerald-500/30';

                if ($remaining <= 0) {
                    $statusKey = 'habis';
                    $statusLabel = 'Habis';
                    $statusBadgeClass = 'bg-red-950/60 text-red-400 border-red-500/30';
                } elseif ($remaining <= 3) {
                    $statusKey = 'kritis';
                    $statusLabel = 'Kritis (≤ 3)';
                    $statusBadgeClass = 'bg-rose-950/60 text-rose-300 border-rose-500/30';
                } elseif ($remaining <= 5) {
                    $statusKey = 'menipis';
                    $statusLabel = 'Menipis';
                    $statusBadgeClass = 'bg-amber-950/60 text-amber-400 border-amber-500/30';
                }

                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'nip' => $emp->nip,
                    'avatar_url' => $emp->avatar_url,
                    'department' => $emp->department,
                    'role_title' => $emp->role_title,
                    'user_role' => $emp->user?->role ?? 'karyawan',
                    'quota' => $quota,
                    'used' => $used,
                    'pending' => $pendingDays,
                    'remaining' => $remaining,
                    'pct_used' => $pctUsed,
                    'pct_pending' => $pctPending,
                    'status_key' => $statusKey,
                    'status_label' => $statusLabel,
                    'status_class' => $statusBadgeClass,
                ];
            });

            $departmentsList = $allEmployees->pluck('department')->filter()->unique()->values();

            $totalQuota = $allEmployeesLeave->sum('quota');
            $totalUsed = $allEmployeesLeave->sum('used');
            $totalPending = $allEmployeesLeave->sum('pending');
            $totalRemaining = $allEmployeesLeave->sum('remaining');
            $criticalCount = $allEmployeesLeave->whereIn('status_key', ['habis', 'kritis'])->count();

            $companyLeaveMetrics = [
                'total_employees' => $allEmployeesLeave->count(),
                'total_quota' => $totalQuota,
                'total_used' => $totalUsed,
                'total_pending' => $totalPending,
                'total_remaining' => $totalRemaining,
                'critical_count' => $criticalCount,
                'avg_remaining' => $allEmployeesLeave->count() > 0 ? round($totalRemaining / $allEmployeesLeave->count(), 1) : 0,
            ];
        }

        return view('leave.index', compact(
            'leaveRequests',
            'employees',
            'currentUser',
            'authUser',
            'status',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'myQuota',
            'myUsed',
            'myPending',
            'myRemaining',
            'myPctUsed',
            'myPctPending',
            'myPctRemaining',
            'myAnnualLeaveHistory',
            'allEmployeesLeave',
            'companyLeaveMetrics',
            'departmentsList'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        // Jika bukan HR/Direktur, permohonan selalu atas nama dirinya sendiri
        $employeeId = $authUser->canApproveLeaves() 
            ? ($request->employee_id ?? $authUser->employee_id)
            : ($authUser->employee_id ?? $request->employee_id);

        $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) + 1;

        $applicant = Employee::with('user')->find($employeeId);
        if (!$applicant) {
            return back()->with('error', 'Profil karyawan pemohon tidak ditemukan.')->withInput();
        }

        // Validasi Kuota Cuti Tahunan: Pastikan sisa kuota mencukupi sebelum pengajuan
        if ($request->type === 'Cuti Tahunan') {
            $sisaKuota = $applicant->annual_leave_quota - $applicant->annual_leave_used;
            // Hitung juga cuti pending yang belum diproses
            $pendingDays = LeaveRequest::where('employee_id', $employeeId)
                ->where('type', 'Cuti Tahunan')
                ->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
                ->sum('duration_days');
            $sisaEfektif = $sisaKuota - $pendingDays;

            if ($duration > $sisaEfektif) {
                return back()->with('error', "Sisa kuota cuti tahunan Anda tidak mencukupi. Sisa efektif: {$sisaEfektif} hari (kuota: {$applicant->annual_leave_quota}, terpakai: {$applicant->annual_leave_used}, pending: {$pendingDays} hari).")->withInput();
            }
        }

        // Validasi Overlap: Cegah pengajuan cuti pada tanggal yang sudah ada pengajuan aktif
        $overlapExists = LeaveRequest::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'pending_manager', 'pending_hr', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $end->toDateString())
                        ->where('end_date', '>=', $start->toDateString());
                });
            })
            ->exists();

        if ($overlapExists) {
            return back()->with('error', 'Terdapat pengajuan cuti/izin lain yang masih aktif pada rentang tanggal yang sama atau tumpang tindih. Silakan pilih tanggal yang berbeda.')->withInput();
        }

        // ALUR BERJENJANG (TWO-TIER APPROVAL):
        // 1. Jika pemohon adalah Karyawan di departemen non-HR (Finance, GA, BizDev, Resepsionis, Trainer):
        //    -> Wajib mengajukan ke Manager Departemen terlebih dahulu (status: 'pending_manager').
        // 2. Jika pemohon adalah staf di departemen HR, atau pemohon adalah Manager Departemen sendiri, atau Direktur:
        //    -> Langsung ke antrean persetujuan Manager HR / Direktur Utama (status: 'pending_hr').
        $applicantUser = $applicant->user;
        $applicantDept = strtolower($applicant->department ?? '');
        $isNonHrKaryawan = ($applicantUser?->isKaryawan() ?? true) && $applicantDept !== 'hr';

        $initialStatus = $isNonHrKaryawan ? 'pending_manager' : 'pending_hr';

        $newLeave = LeaveRequest::create([
            'employee_id' => $employeeId,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'duration_days' => $duration,
            'reason' => $request->reason,
            'status' => $initialStatus,
        ]);

        // Pengiriman Notifikasi Otomatis
        try {
            $applicantName = $applicant->name;
            $formattedDates = "{$newLeave->start_date} s/d {$newLeave->end_date}";

            if ($initialStatus === 'pending_manager') {
                // Notifikasi khusus ke Manager Departemen pemohon
                $deptManagers = \App\Models\User::where('department', $applicant->department)
                    ->where('role', 'manager')
                    ->where('id', '!=', $authUser->id)
                    ->get();

                foreach ($deptManagers as $mgr) {
                    $mgr->notify(new \App\Notifications\GeneralNotification(
                        'Persetujuan Cuti Departemen Diperlukan',
                        "{$applicantName} ({$applicant->department}) mengajukan {$newLeave->type} ({$duration} hari, {$formattedDates}). Mohon tinjau & setujui sebelum diteruskan ke Manager HR.",
                        route('leave.index'),
                        'assignment_ind',
                        'amber'
                    ));
                }
            } else {
                // Notifikasi ke Manager HR & Direktur
                $hrApprovers = \App\Models\User::where(function ($q) {
                    $q->where('department', 'HR')->where('role', 'manager');
                })->orWhere('role', 'direktur')->where('id', '!=', $authUser->id)->get();

                foreach ($hrApprovers as $approver) {
                    $approver->notify(new \App\Notifications\GeneralNotification(
                        'Pengajuan Cuti Baru',
                        "{$applicantName} ({$applicant->department}) mengajukan {$newLeave->type} selama {$duration} hari ({$formattedDates}).",
                        route('leave.index'),
                        'event_busy',
                        'amber'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi pengajuan cuti: ' . $e->getMessage());
        }

        $successMsg = ($initialStatus === 'pending_manager')
            ? "Permohonan cuti/izin berhasil diajukan dan sedang menunggu persetujuan dari Manager Departemen ({$applicant->department}). Setelah disetujui, permohonan akan otomatis diteruskan ke Manager HR."
            : "Permohonan cuti/izin berhasil diajukan dan saat ini menunggu persetujuan Tim HR.";

        return back()->with('success', $successMsg);
    }

    public function updateStatus(Request $request, $id)
    {
        $authUser = Auth::user();
        $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_note' => 'required_if:status,rejected|nullable|string|max:1000',
        ], [
            'rejection_note.required_if' => 'Alasan penolakan wajib diisi ketika menolak permohonan cuti / izin.',
        ]);

        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);

        // Aturan Anti Self-Approval: Pemohon dilarang menyetujui pengajuannya sendiri
        if ($currentEmpId && (int)$leaveRequest->employee_id === (int)$currentEmpId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat menyetujui atau menolak permohonan cuti yang diajukan oleh diri Anda sendiri.');
        }

        $isStageManager = $leaveRequest->status === 'pending_manager';
        $isStageHR = in_array($leaveRequest->status, ['pending_hr', 'pending']);

        if ($isStageManager) {
            // TAHAP 1: Verifikasi oleh Manager Departemen Pemohon (atau Direktur Utama)
            if (!$authUser->canApproveDepartmentLeave($leaveRequest)) {
                abort(403, 'Akses Ditolak: Permohonan ini berada pada tahap verifikasi Manager Departemen (' . $leaveRequest->employee->department . '). Hanya Manager Departemen terkait atau Direktur Utama yang berwenang memproses.');
            }

            if ($request->status === 'approved') {
                // Manager Departemen menyetujui -> otomatis diteruskan ke Manager HR
                $leaveRequest->update([
                    'status' => 'pending_hr',
                    'manager_approved_by' => $currentEmpId,
                    'manager_approved_at' => Carbon::now(),
                ]);

                // Notifikasi ke Tim HR & Direktur
                try {
                    $hrApprovers = \App\Models\User::where(function ($q) {
                        $q->where('department', 'HR')->where('role', 'manager');
                    })->orWhere('role', 'direktur')->where('id', '!=', $authUser->id)->get();

                    $applicantName = $leaveRequest->employee->name;
                    $deptName = $leaveRequest->employee->department;

                    foreach ($hrApprovers as $approver) {
                        $approver->notify(new \App\Notifications\GeneralNotification(
                            'Rekomendasi Cuti dari Manager ' . $deptName,
                            "Manager {$deptName} ({$authUser->name}) telah menyetujui cuti {$applicantName}. Permohonan kini menunggu persetujuan akhir dari Manager HR.",
                            route('leave.index'),
                            'verified',
                            'indigo'
                        ));
                    }

                    // Notifikasi ke Pemohon
                    $applicantUser = \App\Models\User::where('employee_id', $leaveRequest->employee_id)->first();
                    if ($applicantUser) {
                        $applicantUser->notify(new \App\Notifications\GeneralNotification(
                            'Persetujuan Tahap 1 Disetujui',
                            "Permohonan {$leaveRequest->type} Anda telah disetujui oleh Manager Departemen ({$authUser->name}) dan secara otomatis diteruskan ke Manager HR.",
                            route('leave.index'),
                            'check_circle',
                            'emerald'
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim notifikasi persetujuan manager dept: ' . $e->getMessage());
                }

                return back()->with('success', 'Permohonan cuti berhasil disetujui oleh Manager Departemen dan secara otomatis telah diteruskan ke Manager HR untuk persetujuan final.');
            } else {
                // Manager Departemen Menolak
                $leaveRequest->update([
                    'status' => 'rejected',
                    'approved_by' => $currentEmpId,
                    'action_at' => Carbon::now(),
                    'rejection_note' => $request->rejection_note,
                ]);

                // Notifikasi ke Pemohon
                try {
                    $applicantUser = \App\Models\User::where('employee_id', $leaveRequest->employee_id)->first();
                    if ($applicantUser) {
                        $applicantUser->notify(new \App\Notifications\GeneralNotification(
                            'Permohonan Cuti Ditolak Manager Departemen',
                            "Permohonan {$leaveRequest->type} Anda ditolak oleh Manager Departemen ({$authUser->name}). Alasan: {$request->rejection_note}",
                            route('leave.index'),
                            'cancel',
                            'rose'
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim notifikasi penolakan manager dept: ' . $e->getMessage());
                }

                return back()->with('success', 'Permohonan cuti/izin ditolak pada tingkat Manager Departemen.');
            }
        } elseif ($isStageHR) {
            // TAHAP 2: Verifikasi Final oleh Manager HR atau Direktur Utama
            if (!$authUser->canApproveLeaves()) {
                abort(403, 'Akses Ditolak: Hanya Manager HR atau Direktur Utama yang memiliki wewenang untuk menyetujui persetujuan final permohonan cuti.');
            }

            if ($request->status === 'approved') {
                $leaveRequest->update([
                    'status' => 'approved',
                    'approved_by' => $currentEmpId,
                    'action_at' => Carbon::now(),
                ]);

                if ($leaveRequest->type === 'Cuti Tahunan') {
                    $leaveRequest->employee->increment('annual_leave_used', $leaveRequest->duration_days);
                }

                // Notifikasi ke Pemohon
                try {
                    $applicantUser = \App\Models\User::where('employee_id', $leaveRequest->employee_id)->first();
                    if ($applicantUser) {
                        $applicantUser->notify(new \App\Notifications\GeneralNotification(
                            'Pengajuan Cuti Disetujui Sepenuhnya',
                            "Permohonan {$leaveRequest->type} Anda telah disetujui sepenuhnya oleh Tim HR ({$authUser->name}).",
                            route('leave.index'),
                            'check_circle',
                            'emerald'
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim notifikasi persetujuan final HR: ' . $e->getMessage());
                }

                return back()->with('success', 'Permohonan cuti/izin berhasil disetujui sepenuhnya oleh Tim HR.');
            } else {
                $leaveRequest->update([
                    'status' => 'rejected',
                    'approved_by' => $currentEmpId,
                    'action_at' => Carbon::now(),
                    'rejection_note' => $request->rejection_note,
                ]);

                // Notifikasi ke Pemohon
                try {
                    $applicantUser = \App\Models\User::where('employee_id', $leaveRequest->employee_id)->first();
                    if ($applicantUser) {
                        $applicantUser->notify(new \App\Notifications\GeneralNotification(
                            'Pengajuan Cuti Ditolak oleh HR',
                            "Permohonan {$leaveRequest->type} Anda ditolak oleh Tim HR ({$authUser->name}). Alasan: {$request->rejection_note}",
                            route('leave.index'),
                            'cancel',
                            'rose'
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal mengirim notifikasi penolakan HR: ' . $e->getMessage());
                }

                return back()->with('success', 'Permohonan cuti/izin ditolak oleh Tim HR.');
            }
        } else {
            return back()->with('error', 'Permohonan ini telah selesai ditinjau sebelumnya (status: ' . $leaveRequest->status . ').');
        }
    }

    public function updateQuota(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->canApproveLeaves()) {
            abort(403, 'Akses Ditolak: Hanya Manager HR dan Direktur Utama yang memiliki wewenang untuk menyesuaikan kuota cuti tahunan.');
        }

        $validated = $request->validate([
            'annual_leave_quota' => 'required|integer|min:0|max:100',
            'annual_leave_used' => 'required|integer|min:0',
        ]);

        $employee = Employee::findOrFail($id);
        $oldQuota = $employee->annual_leave_quota;
        $oldUsed = $employee->annual_leave_used;

        $employee->update([
            'annual_leave_quota' => $validated['annual_leave_quota'],
            'annual_leave_used' => $validated['annual_leave_used'],
        ]);

        return back()->with('success', "Kuota cuti tahunan untuk {$employee->name} berhasil diperbarui (Kuota: {$oldQuota} -> {$validated['annual_leave_quota']} hari, Terpakai: {$oldUsed} -> {$validated['annual_leave_used']} hari).");
    }
}
