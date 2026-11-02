<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeadProject;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class BisnisDevelopmentController extends Controller
{
    public function index(Request $request)
    {
        $stage = $request->query('stage');
        $authUser = Auth::user();

        $query = LeadProject::with('creator')->orderBy('created_at', 'desc');

        if ($stage) {
            $query->where('stage', $stage);
        }

        $projects = $query->paginate(10);
        $employees = Employee::where('department', 'Bisnis Development')->orWhere('department', 'like', '%Business%')->get();
        if ($employees->isEmpty()) {
            $employees = Employee::orderBy('name')->get();
        }

        $totalPipeline = LeadProject::whereIn('stage', ['prospect', 'proposal', 'negotiation'])->sum('deal_value');
        $totalWon = LeadProject::where('stage', 'won')->sum('deal_value');
        $totalLost = LeadProject::where('stage', 'lost')->sum('deal_value');
        $activeDealsCount = LeadProject::whereIn('stage', ['prospect', 'proposal', 'negotiation'])->count();
        $lostDealsCount = LeadProject::where('stage', 'lost')->count();

        return view('departments.bizdev.index', compact(
            'projects',
            'employees',
            'authUser',
            'stage',
            'totalPipeline',
            'totalWon',
            'totalLost',
            'activeDealsCount',
            'lostDealsCount'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'project_title' => 'required|string|max:255',
            'deal_value' => 'required|numeric|min:0',
            'stage' => 'required|in:prospect,proposal,negotiation,won,lost',
            'lost_reason' => 'required_if:stage,lost|nullable|string|max:1000',
            'target_close_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ], [
            'lost_reason.required_if' => 'Wajib menyertakan keterangan/alasan kenapa deal bisnis ini menjadi Lost.',
        ]);

        $authUser = Auth::user();

        LeadProject::create([
            'created_by' => $authUser->employee_id ?? abort(403, 'Akun Anda belum terhubung dengan data karyawan.'),
            'client_name' => $request->client_name,
            'project_title' => $request->project_title,
            'deal_value' => $request->deal_value,
            'stage' => $request->stage,
            'lost_reason' => $request->stage === 'lost' ? $request->lost_reason : null,
            'target_close_date' => $request->target_close_date,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Peluang proyek baru (Lead Deal) berhasil ditambahkan ke pipeline.');
    }

    public function updateStage(Request $request, $id)
    {
        $request->validate([
            'stage' => 'required|in:prospect,proposal,negotiation,won,lost',
            'lost_reason' => 'required_if:stage,lost|nullable|string|max:1000',
        ], [
            'lost_reason.required_if' => 'Wajib menyertakan keterangan/alasan kenapa deal bisnis ini menjadi Lost.',
        ]);

        $project = LeadProject::findOrFail($id);

        $updateData = ['stage' => $request->stage];
        if ($request->stage === 'lost') {
            $updateData['lost_reason'] = $request->lost_reason;
        } else {
            // Jika deal diaktifkan kembali dari lost, bersihkan keterangan lost sebelumnya
            if ($project->stage === 'lost') {
                $updateData['lost_reason'] = null;
            }
        }

        $project->update($updateData);

        return back()->with('success', 'Stage deal bisnis berhasil diperbarui ke: ' . strtoupper($request->stage) . '.');
    }
}
