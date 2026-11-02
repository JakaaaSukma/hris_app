<?php

namespace App\Http\Controllers;

use App\Models\Offboarding;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OffboardingController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->get('status');
        $query = Offboarding::with('employee');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $offboardings = $query->orderBy('created_at', 'desc')->paginate(15);
        $employees = Employee::where('status', 'active')->orderBy('name')->get();

        // Metrik Offboarding
        $totalOffboardings = Offboarding::count();
        $processingCount = Offboarding::where('status', 'processing')->count();
        $pendingCount = Offboarding::where('status', 'pending')->count();
        $completedCount = Offboarding::where('status', 'completed')->count();

        return view('departments.hr.offboarding_index', compact(
            'offboardings',
            'employees',
            'statusFilter',
            'totalOffboardings',
            'processingCount',
            'pendingCount',
            'completedCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:Resign,PHK,Habis Kontrak,Pensiun',
            'reason' => 'required|string',
            'request_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:request_date',
            'hr_notes' => 'nullable|string',
        ]);

        Offboarding::create([
            'employee_id' => $request->employee_id,
            'type' => $request->type,
            'reason' => $request->reason,
            'request_date' => $request->request_date,
            'effective_date' => $request->effective_date,
            'status' => 'processing',
            'hr_notes' => $request->hr_notes,
        ]);

        return back()->with('success', 'Pengajuan offboarding karyawan berhasil didaftarkan dan diproses.');
    }

    public function updateChecklist(Request $request, $id)
    {
        $offboarding = Offboarding::findOrFail($id);

        $offboarding->update([
            'asset_returned' => $request->has('asset_returned'),
            'finance_cleared' => $request->has('finance_cleared'),
            'handover_completed' => $request->has('handover_completed'),
            'exit_interview_done' => $request->has('exit_interview_done'),
            'status' => $request->status ?? $offboarding->status,
            'hr_notes' => $request->hr_notes ?? $offboarding->hr_notes,
        ]);

        // Jika status diubah menjadi completed, set status karyawan di tabel employee menjadi inactive
        if ($request->status === 'completed') {
            $offboarding->employee()->update(['status' => 'inactive']);
        }

        return back()->with('success', "Proses offboarding untuk {$offboarding->employee->name} berhasil diperbarui.");
    }
}
