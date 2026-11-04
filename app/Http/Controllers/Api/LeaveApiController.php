<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeaveApiController extends Controller
{
    /**
     * Get Leave Requests & Quotas (Personal or Pending Approvals)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan.',
            ], 422);
        }

        $tab = $request->input('tab', 'my_leaves'); // 'my_leaves' | 'approval'

        // 1. Hitung Kuota Cuti Karyawan
        $myQuota = (int) $employee->annual_leave_quota;
        $myUsed = (int) $employee->annual_leave_used;
        $myPending = (int) LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'Cuti Tahunan')
            ->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
            ->sum('duration_days');
        $myRemaining = max(0, $myQuota - $myUsed - $myPending);

        // 2. Query Data
        if ($tab === 'approval' && ($user->isManager() || $user->isDirektur() || ($user->isHR() && $user->isManager()))) {
            $query = LeaveRequest::with(['employee', 'approver', 'managerApprover'])
                ->orderBy('created_at', 'desc');

            if ($user->isDirektur() || ($user->isHR() && $user->isManager())) {
                // Direktur & Manager HR: Bisa approve level 2 (pending_hr) atau semua
                $query->whereIn('status', ['pending_manager', 'pending_hr', 'pending']);
            } elseif ($user->isManager()) {
                // Manager Departemen: Hanya pengajuan anggotanya di tahap pending_manager
                $userDept = $user->department;
                $query->where('status', 'pending_manager')
                    ->whereHas('employee', function ($q) use ($userDept) {
                        $q->where('department', $userDept);
                    });
            }

            // Aturan Anti Self-Approval: Sembunyikan pengajuan milik sendiri dari tab approval
            $query->where('employee_id', '!=', $employee->id);

            $leaves = $query->paginate(15);
        } else {
            // Tab pengajuan milik sendiri
            $leaves = LeaveRequest::with(['employee', 'approver', 'managerApprover'])
                ->where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return response()->json([
            'success' => true,
            'tab' => $tab,
            'quota' => [
                'total_quota' => $myQuota,
                'used' => $myUsed,
                'pending' => $myPending,
                'remaining' => $myRemaining,
            ],
            'leaves' => $leaves,
        ]);
    }

    /**
     * Store New Leave Request
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan.',
            ], 422);
        }

        $request->validate([
            'type' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $duration = $start->diffInDays($end) + 1;

        // Validasi Kuota Cuti Tahunan
        if ($request->type === 'Cuti Tahunan') {
            $sisaKuota = $employee->annual_leave_quota - $employee->annual_leave_used;
            $pendingDays = LeaveRequest::where('employee_id', $employee->id)
                ->where('type', 'Cuti Tahunan')
                ->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
                ->sum('duration_days');
            $sisaEfektif = $sisaKuota - $pendingDays;

            if ($duration > $sisaEfektif) {
                return response()->json([
                    'success' => false,
                    'message' => "Sisa kuota cuti tahunan Anda tidak mencukupi. Sisa efektif: {$sisaEfektif} hari (kuota: {$employee->annual_leave_quota}, terpakai: {$employee->annual_leave_used}, pending: {$pendingDays} hari).",
                ], 422);
            }
        }

        // Validasi Overlap Tanggal
        $overlapExists = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'pending_manager', 'pending_hr', 'approved'])
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $end->toDateString())
                        ->where('end_date', '>=', $start->toDateString());
                });
            })
            ->exists();

        if ($overlapExists) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat pengajuan cuti/izin lain yang masih aktif pada rentang tanggal yang sama atau tumpang tindih.',
            ], 422);
        }

        // Two-Tier Approval logic:
        $applicantDept = strtolower($employee->department ?? '');
        $isNonHrKaryawan = $user->isKaryawan() && $applicantDept !== 'hr';
        $initialStatus = $isNonHrKaryawan ? 'pending_manager' : 'pending_hr';

        $leave = LeaveRequest::create([
            'employee_id' => $employee->id,
            'type' => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'duration_days' => $duration,
            'reason' => $request->reason,
            'status' => $initialStatus,
        ]);

        // Send notifications
        try {
            $formattedDates = "{$leave->start_date} s/d {$leave->end_date}";
            if ($initialStatus === 'pending_manager') {
                $deptManagers = User::where('department', $employee->department)
                    ->where('role', 'manager')
                    ->where('id', '!=', $user->id)
                    ->get();

                foreach ($deptManagers as $mgr) {
                    $mgr->notify(new GeneralNotification(
                        'Persetujuan Cuti Departemen Diperlukan',
                        "{$employee->name} ({$employee->department}) mengajukan {$leave->type} ({$duration} hari, {$formattedDates}). Mohon tinjau & setujui.",
                        route('leave.index'),
                        'assignment_ind',
                        'amber'
                    ));
                }
            } else {
                $hrApprovers = User::where(function ($q) {
                    $q->where('department', 'HR')->where('role', 'manager');
                })->orWhere('role', 'direktur')->where('id', '!=', $user->id)->get();

                foreach ($hrApprovers as $approver) {
                    $approver->notify(new GeneralNotification(
                        'Pengajuan Cuti Baru',
                        "{$employee->name} ({$employee->department}) mengajukan {$leave->type} ({$duration} hari, {$formattedDates}).",
                        route('leave.index'),
                        'event_busy',
                        'amber'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Notifikasi pengajuan cuti mobile gagal: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => ($initialStatus === 'pending_manager')
                ? "Permohonan {$leave->type} berhasil diajukan dan sedang menunggu persetujuan Manager Departemen."
                : "Permohonan {$leave->type} berhasil diajukan dan menunggu persetujuan Tim HR.",
            'data' => $leave,
        ], 201);
    }

    /**
     * Detail of a Leave Request
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $employee = $user->employee;

        $leave = LeaveRequest::with(['employee', 'approver', 'managerApprover'])->findOrFail($id);

        // Otorisasi: Pemohon sendiri, Manager Departemen, atau HR/Direktur
        if ($employee && $leave->employee_id === $employee->id) {
            // Pemohon sah
        } elseif ($user->isDirektur() || ($user->isHR() && $user->isManager())) {
            // HR / Direktur sah
        } elseif ($user->isManager() && strtolower($user->department ?? '') === strtolower($leave->employee?->department ?? '')) {
            // Manager dept sah
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki hak untuk melihat detail pengajuan ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $leave,
        ]);
    }

    /**
     * Approve Leave Request (Mobile)
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $employee = $user->employee;
        $currentEmpId = $employee ? $employee->id : null;

        $leave = LeaveRequest::with('employee')->findOrFail($id);

        // Anti Self-Approval
        if ($currentEmpId && (int)$leave->employee_id === (int)$currentEmpId) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Anda tidak dapat menyetujui permohonan yang diajukan oleh diri Anda sendiri.',
            ], 403);
        }

        $isStageManager = $leave->status === 'pending_manager';
        $isStageHR = in_array($leave->status, ['pending_hr', 'pending']);

        if ($isStageManager) {
            if (!$user->canApproveDepartmentLeave($leave)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Hanya Manager Departemen terkait atau Direktur Utama yang berwenang pada tahap ini.',
                ], 403);
            }

            $leave->update([
                'status' => 'pending_hr',
                'manager_approved_by' => $currentEmpId,
                'manager_approved_at' => Carbon::now(),
            ]);

            // Notifikasi ke Tim HR & Pemohon
            try {
                $applicantUser = User::where('employee_id', $leave->employee_id)->first();
                if ($applicantUser) {
                    $applicantUser->notify(new GeneralNotification(
                        'Persetujuan Tahap 1 Disetujui',
                        "Permohonan {$leave->type} Anda disetujui oleh Manager Departemen ({$user->name}) dan diteruskan ke Manager HR.",
                        route('leave.index'),
                        'check_circle',
                        'emerald'
                    ));
                }
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'message' => 'Permohonan cuti berhasil disetujui tingkat departemen dan diteruskan ke Manager HR.',
                'data' => $leave,
            ]);
        } elseif ($isStageHR) {
            if (!$user->canApproveLeaves()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Hanya Manager HR atau Direktur Utama yang berwenang menyetujui tingkat final.',
                ], 403);
            }

            $leave->update([
                'status' => 'approved',
                'approved_by' => $currentEmpId,
                'action_at' => Carbon::now(),
            ]);

            if ($leave->type === 'Cuti Tahunan') {
                $leave->employee->increment('annual_leave_used', $leave->duration_days);
            }

            // Notifikasi ke Pemohon
            try {
                $applicantUser = User::where('employee_id', $leave->employee_id)->first();
                if ($applicantUser) {
                    $applicantUser->notify(new GeneralNotification(
                        'Pengajuan Cuti Disetujui Sepenuhnya',
                        "Permohonan {$leave->type} Anda telah disetujui sepenuhnya oleh Tim HR ({$user->name}).",
                        route('leave.index'),
                        'check_circle',
                        'emerald'
                    ));
                }
            } catch (\Throwable $e) {}

            return response()->json([
                'success' => true,
                'message' => 'Permohonan cuti berhasil disetujui sepenuhnya.',
                'data' => $leave,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Permohonan cuti ini sudah tidak dalam status pending.',
        ], 422);
    }

    /**
     * Reject Leave Request (Mobile)
     */
    public function reject(Request $request, $id)
    {
        $user = $request->user();
        $employee = $user->employee;
        $currentEmpId = $employee ? $employee->id : null;

        $request->validate([
            'rejection_note' => 'required|string|min:3|max:1000',
        ]);

        $leave = LeaveRequest::with('employee')->findOrFail($id);

        // Anti Self-Approval
        if ($currentEmpId && (int)$leave->employee_id === (int)$currentEmpId) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Anda tidak dapat menolak permohonan yang diajukan oleh diri Anda sendiri.',
            ], 403);
        }

        $isStageManager = $leave->status === 'pending_manager';
        $isStageHR = in_array($leave->status, ['pending_hr', 'pending']);

        if ($isStageManager && !$user->canApproveDepartmentLeave($leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak permohonan ini.',
            ], 403);
        }

        if ($isStageHR && !$user->canApproveLeaves()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses Ditolak: Hanya Manager HR atau Direktur Utama yang berwenang menolak tingkat final.',
            ], 403);
        }

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $currentEmpId,
            'action_at' => Carbon::now(),
            'rejection_note' => $request->rejection_note,
        ]);

        // Notifikasi ke Pemohon
        try {
            $applicantUser = User::where('employee_id', $leave->employee_id)->first();
            if ($applicantUser) {
                $applicantUser->notify(new GeneralNotification(
                    'Permohonan Cuti Ditolak',
                    "Permohonan {$leave->type} Anda ditolak oleh {$user->name}. Alasan: {$request->rejection_note}",
                    route('leave.index'),
                    'cancel',
                    'rose'
                ));
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Permohonan cuti berhasil ditolak.',
            'data' => $leave,
        ]);
    }
}

