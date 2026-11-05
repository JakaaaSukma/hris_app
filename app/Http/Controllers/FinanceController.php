<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reimbursement;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\SalarySlip;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'slips'); // Default ke tab slip gaji / reimbursements
        $status = $request->query('status');
        $period = $request->query('period');
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        // 1. DATA REIMBURSEMENTS
        $query = Reimbursement::with(['employee', 'reviewer'])->orderBy('created_at', 'desc');

        // Jika BUKAN tim Finance dan BUKAN Direktur, hanya tampilkan klaim milik sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $query->where('employee_id', $currentUser->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $reimbursements = $query->paginate(10);

        $pendingQuery = Reimbursement::where('status', 'pending');
        $approvedQuery = Reimbursement::where('status', 'approved');
        $paidQuery = Reimbursement::where('status', 'paid');

        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $pendingQuery->where('employee_id', $currentUser->id);
            $approvedQuery->where('employee_id', $currentUser->id);
            $paidQuery->where('employee_id', $currentUser->id);
        }

        $totalPending = $pendingQuery->sum('amount');
        $totalApproved = $approvedQuery->sum('amount');
        $totalPaid = $paidQuery->sum('amount');

        // 2. DATA SLIP GAJI (SALARY SLIPS)
        $slipsQuery = SalarySlip::with('employee')->orderBy('payment_date', 'desc');

        // Hak akses: Jika bukan Finance dan bukan Direktur, hanya bisa lihat slip gaji miliknya sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $slipsQuery->where('employee_id', $currentUser->id);
        }

        if ($period) {
            $slipsQuery->where('period_month', $period);
        }

        $salarySlips = $slipsQuery->paginate(10, ['*'], 'slips_page');

        // Metrik Ringkasan Pengeluaran Gaji / Payroll
        $payrollQuery = SalarySlip::query();
        if (!$authUser->canApproveReimbursements() && $currentUser) {
            $payrollQuery->where('employee_id', $currentUser->id);
        }

        $totalPayrollDisbursed = (clone $payrollQuery)->where('status', 'paid')->sum('net_salary');
        $totalSlipsCount = (clone $payrollQuery)->count();
        $availablePeriods = SalarySlip::select('period_month')->distinct()->pluck('period_month');

        $employees = $authUser->canApproveReimbursements()
            ? Employee::orderBy('name')->get()
            : ($currentUser ? Employee::where('id', $currentUser->id)->get() : Employee::all());

        return view('departments.finance.index', compact(
            'tab',
            'reimbursements',
            'salarySlips',
            'employees',
            'authUser',
            'currentUser',
            'status',
            'period',
            'availablePeriods',
            'totalPending',
            'totalApproved',
            'totalPaid',
            'totalPayrollDisbursed',
            'totalSlipsCount'
        ));
    }

    public function showSalarySlip($id)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        $slip = SalarySlip::with('employee')->findOrFail($id);

        // Keamanan: Karyawan hanya bisa melihat slip miliknya sendiri
        if (!$authUser->canApproveReimbursements() && $currentUser && $slip->employee_id !== $currentUser->id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki akses untuk melihat slip gaji karyawan lain.');
        }

        return view('departments.finance.salary_slip_detail', compact('slip', 'authUser'));
    }

    public function storeSalarySlip(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canApproveReimbursements()) {
            abort(403, 'Akses Ditolak: Hanya Departemen Finance atau Direktur yang dapat menerbitkan slip gaji.');
        }

        // Mendukung penerbitan untuk 1 orang (employee_id) atau banyak orang sekaligus (employee_ids[])
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'employee_ids' => 'nullable|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
            'period_month' => 'required|string',
            'payment_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowance_position' => 'nullable|numeric|min:0',
            'allowance_transport' => 'nullable|numeric|min:0',
            'allowance_meal' => 'nullable|numeric|min:0',
            'overtime_pay' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'deduction_bpjs_tk' => 'nullable|numeric|min:0',
            'deduction_bpjs_kes' => 'nullable|numeric|min:0',
            'deduction_tax_pph21' => 'nullable|numeric|min:0',
            'deduction_absence' => 'nullable|numeric|min:0',
            'deduction_other' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'bank_name' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $employeeIds = $request->input('employee_ids');
        if (empty($employeeIds) && $request->filled('employee_id')) {
            $employeeIds = [$request->input('employee_id')];
        }

        if (empty($employeeIds)) {
            return back()->with('error', 'Silakan pilih minimal satu karyawan atau manager untuk menerbitkan slip gaji.');
        }

        $basic = (float) $request->basic_salary;
        $allowPos = (float) ($request->allowance_position ?? 0);
        $allowTrans = (float) ($request->allowance_transport ?? 0);
        $allowMeal = (float) ($request->allowance_meal ?? 0);
        $overtime = (float) ($request->overtime_pay ?? 0);
        $bonus = (float) ($request->bonus ?? 0);
        $totalEarnings = $basic + $allowPos + $allowTrans + $allowMeal + $overtime + $bonus;

        $bpjsTk = (float) ($request->deduction_bpjs_tk ?? 0);
        $bpjsKes = (float) ($request->deduction_bpjs_kes ?? 0);
        $pph21 = (float) ($request->deduction_tax_pph21 ?? 0);
        $absence = (float) ($request->deduction_absence ?? 0);
        $otherDeduct = (float) ($request->deduction_other ?? 0);
        $totalDeductions = $bpjsTk + $bpjsKes + $pph21 + $absence + $otherDeduct;

        $netSalary = $totalEarnings - $totalDeductions;
        $count = 0;

        DB::transaction(function () use ($employeeIds, $request, $basic, $allowPos, $allowTrans, $allowMeal, $overtime, $bonus, $bpjsTk, $bpjsKes, $pph21, $absence, $otherDeduct, $totalEarnings, $totalDeductions, $netSalary, $authUser, &$count) {
            foreach ($employeeIds as $empId) {
                $slipNumber = 'SLIP-' . date('Ym', strtotime($request->payment_date)) . '-' . str_pad($empId, 4, '0', STR_PAD_LEFT) . '-' . rand(10, 99);

                SalarySlip::create([
                    'slip_number' => $slipNumber,
                    'employee_id' => $empId,
                    'period_month' => $request->period_month,
                    'payment_date' => $request->payment_date,
                    'basic_salary' => $basic,
                    'allowance_position' => $allowPos,
                    'allowance_transport' => $allowTrans,
                    'allowance_meal' => $allowMeal,
                    'overtime_pay' => $overtime,
                    'bonus' => $bonus,
                    'deduction_bpjs_tk' => $bpjsTk,
                    'deduction_bpjs_kes' => $bpjsKes,
                    'deduction_tax_pph21' => $pph21,
                    'deduction_absence' => $absence,
                    'deduction_other' => $otherDeduct,
                    'total_earnings' => $totalEarnings,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'status' => 'paid',
                    'payment_method' => $request->payment_method,
                    'bank_name' => $request->bank_name,
                    'bank_account_number' => $request->bank_account_number,
                    'notes' => $request->notes,
                    'created_by' => $authUser->id,
                ]);

                $count++;
            }
        });

        $message = ($count > 1) 
            ? "Berhasil menerbitkan {$count} slip gaji karyawan & manager secara massal untuk periode {$request->period_month}."
            : "Slip gaji baru berhasil diterbitkan oleh Departemen Finance.";

        return redirect()->route('finance.index', ['tab' => 'slips'])->with('success', $message);
    }

    /**
     * Hapus Slip Gaji (Khusus Akun Manager Finance & Direktur Utama)
     */
    public function destroySalarySlip($id)
    {
        $authUser = Auth::user();
        if (!$authUser->canDeleteSalarySlips()) {
            abort(403, 'Akses Ditolak: Hanya Manager Finance atau Direktur yang memiliki wewenang untuk menghapus slip gaji.');
        }

        $slip = SalarySlip::with('employee')->findOrFail($id);
        $slipNumber = $slip->slip_number;
        $employeeName = $slip->employee?->name ?? 'Karyawan';
        $period = $slip->period_month;

        $slip->delete();

        return redirect()->route('finance.index', ['tab' => 'slips'])
            ->with('success', "Slip gaji {$slipNumber} ({$employeeName} - Periode {$period}) berhasil dihapus dari sistem.");
    }

    /**
     * Hapus Banyak Slip Gaji Sekaligus / Mass Delete (Khusus Akun Manager Finance & Direktur Utama)
     */
    public function bulkDestroySalarySlips(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canDeleteSalarySlips()) {
            abort(403, 'Akses Ditolak: Hanya Manager Finance atau Direktur yang memiliki wewenang untuk menghapus slip gaji.');
        }

        $request->validate([
            'slip_ids' => 'required|array|min:1',
            'slip_ids.*' => 'exists:salary_slips,id',
        ]);

        $count = SalarySlip::whereIn('id', $request->slip_ids)->delete();

        return redirect()->route('finance.index', ['tab' => 'slips'])
            ->with('success', "Berhasil menghapus {$count} slip gaji yang dipilih dari sistem.");
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        // Karyawan/manager biasa hanya bisa mengajukan untuk dirinya sendiri
        $employeeId = $authUser->canApproveReimbursements()
            ? ($request->employee_id ?? $authUser->employee_id)
            : ($authUser->employee_id ?? $request->employee_id);

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:1000',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $reimbursement = Reimbursement::create([
            'employee_id' => $employeeId,
            'title' => $request->title,
            'category' => $request->category,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        // Notifikasi ke Tim Finance & Direktur
        try {
            $applicantName = $authUser->name;
            $formattedAmount = 'Rp ' . number_format($reimbursement->amount, 0, ',', '.');
            $financeApprovers = \App\Models\User::where(function ($q) {
                $q->where('department', 'Finance')->where('role', 'manager');
            })->orWhere('role', 'direktur')->get();

            foreach ($financeApprovers as $approver) {
                if ($approver->id !== $authUser->id) {
                    $approver->notify(new \App\Notifications\GeneralNotification(
                        'Klaim Reimbursement Baru',
                        "{$applicantName} mengajukan klaim '{$reimbursement->title}' sebesar {$formattedAmount}.",
                        route('finance.index'),
                        'payments',
                        'emerald'
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi pengajuan reimbursement: ' . $e->getMessage());
        }

        return back()->with('success', 'Pengajuan reimbursement berhasil diajukan dan menunggu verifikasi Departemen Finance.');
    }

    public function updateStatus(Request $request, $id)
    {
        $authUser = Auth::user();

        // Hanya Departemen Finance atau Direktur yang dapat approve/reject/paid klaim
        if (!$authUser->canApproveReimbursements()) {
            abort(403, 'Akses Ditolak: Hanya tim Departemen Finance atau Direktur yang berhak menyetujui klaim reimbursement.');
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,paid',
            'notes' => 'required_if:status,rejected|nullable|string|max:1000',
        ], [
            'notes.required_if' => 'Alasan penolakan wajib diisi ketika menolak klaim reimbursement.',
        ]);

        $item = Reimbursement::findOrFail($id);

        // Aturan Anti-Self-Approval: Pengaju tidak boleh menyetujui klaim diri sendiri
        $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);
        if ($currentEmpId && (int)$item->employee_id === (int)$currentEmpId) {
            abort(403, 'Akses Ditolak: Anda tidak dapat memproses persetujuan atas klaim reimbursement diri Anda sendiri. Otorisasi harus dilakukan oleh pihak verifikator lain.');
        }

        $item->update([
            'status' => $request->status,
            'reviewed_by' => $currentEmpId,
            'reviewed_at' => Carbon::now(),
            'notes' => $request->notes,
        ]);

        // Kirim notifikasi ke pemohon klaim
        try {
            $claimantUser = \App\Models\User::where('employee_id', $item->employee_id)->first();
            if ($claimantUser) {
                $statusMap = [
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'paid' => 'Sudah Dibayarkan (Cair)',
                ];
                $statusLabel = $statusMap[$request->status] ?? $request->status;
                $color = $request->status === 'rejected' ? 'rose' : 'emerald';
                $icon = $request->status === 'rejected' ? 'cancel' : 'check_circle';
                $reasonMsg = $request->status === 'rejected' && $request->notes ? " Alasan: {$request->notes}" : "";

                $claimantUser->notify(new \App\Notifications\GeneralNotification(
                    "Reimbursement {$statusLabel}",
                    "Klaim '{$item->title}' Anda telah {$statusLabel} oleh Finance.{$reasonMsg}",
                    route('finance.index'),
                    $icon,
                    $color
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi update status reimbursement: ' . $e->getMessage());
        }

        return back()->with('success', 'Status klaim reimbursement berhasil diubah oleh Finance menjadi ' . strtoupper($request->status) . '.');
    }
}
