<?php

namespace App\Http\Controllers;

use App\Models\JobVacancy;
use App\Models\Candidate;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecruitmentController extends Controller
{
    public function index(Request $request)
    {
        $vacancies = JobVacancy::withCount('candidates')->orderBy('created_at', 'desc')->get();

        $stageFilter = $request->get('stage');
        $vacancyFilter = $request->get('vacancy_id');

        $query = Candidate::with('vacancy');
        if ($stageFilter) {
            $query->where('stage', $stageFilter);
        }
        if ($vacancyFilter) {
            $query->where('job_vacancy_id', $vacancyFilter);
        }

        $candidates = $query->orderBy('created_at', 'desc')->paginate(15);

        // Metrik Rekrutmen & Onboarding
        $totalCandidates = Candidate::count();
        $screeningCount = Candidate::whereIn('stage', ['screening', 'interview'])->count();
        $offeringCount = Candidate::where('stage', 'offering')->count();
        $onboardingCount = Candidate::where('stage', 'hired')->count();

        // Candidates yang sedang dalam tahap Onboarding (hired)
        $onboardingList = Candidate::where('stage', 'hired')
            ->with('vacancy')
            ->orderBy('expected_join_date', 'asc')
            ->get();

        return view('departments.hr.recruitment_index', compact(
            'vacancies',
            'candidates',
            'stageFilter',
            'vacancyFilter',
            'totalCandidates',
            'screeningCount',
            'offeringCount',
            'onboardingCount',
            'onboardingList'
        ));
    }

    // Tambah Lowongan Baru
    public function storeVacancy(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'department' => 'required|string',
            'employment_type' => 'required|string',
            'quota' => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        JobVacancy::create([
            'title' => $request->title,
            'department' => $request->department,
            'employment_type' => $request->employment_type,
            'quota' => $request->quota,
            'location' => $request->location ?? 'Headquarters (Jakarta)',
            'description' => $request->description,
            'status' => 'open',
        ]);

        return back()->with('success', 'Lowongan pekerjaan baru berhasil dipublikasikan.');
    }

    // Tambah Kandidat / Pelamar Baru
    public function storeCandidate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'job_vacancy_id' => 'required|exists:job_vacancies,id',
            'applied_role' => 'nullable|string',
            'notes' => 'nullable|string',
            'interview_date' => 'nullable|date',
            'expected_join_date' => 'nullable|date',
        ]);

        $vacancy = JobVacancy::findOrFail($request->job_vacancy_id);

        Candidate::create([
            'job_vacancy_id' => $vacancy->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'applied_role' => $request->applied_role ?: $vacancy->title,
            'department' => $vacancy->department,
            'stage' => 'applied',
            'notes' => $request->notes,
            'interview_date' => $request->interview_date,
            'expected_join_date' => $request->expected_join_date,
        ]);

        return back()->with('success', 'Kandidat pelamar berhasil didaftarkan ke pipeline rekrutmen.');
    }

    // Update Tahapan Seleksi Kandidat (Pipeline)
    public function updateCandidateStage(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);
        $request->validate([
            'stage' => 'required|in:applied,screening,interview,offering,hired,rejected',
            'notes' => 'nullable|string',
            'interview_date' => 'nullable|date',
            'expected_join_date' => 'nullable|date',
        ]);

        $candidate->update([
            'stage' => $request->stage,
            'notes' => $request->notes ?? $candidate->notes,
            'interview_date' => $request->interview_date ?? $candidate->interview_date,
            'expected_join_date' => $request->expected_join_date ?? $candidate->expected_join_date,
        ]);

        return back()->with('success', "Tahapan pelamar {$candidate->name} diperbarui ke {$request->stage}.");
    }

    // Update Checklist Onboarding
    public function updateOnboardingChecklist(Request $request, $id)
    {
        $candidate = Candidate::findOrFail($id);

        $candidate->update([
            'onboarding_doc_submitted' => $request->has('onboarding_doc_submitted'),
            'onboarding_device_allocated' => $request->has('onboarding_device_allocated'),
            'onboarding_orientation_done' => $request->has('onboarding_orientation_done'),
        ]);

        // Jika semua checklist terpenuhi dan belum dikonversi ke pegawai tetap, bisa buat pegawai otomatis jika diminta
        if ($request->has('convert_to_employee') && !$candidate->converted_employee_id) {
            $lastNip = Employee::max('id') + 1;
            $nip = 'NX-' . date('Y') . '-' . str_pad($lastNip, 4, '0', STR_PAD_LEFT);

            $emp = Employee::create([
                'nip' => $nip,
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'role_title' => $candidate->applied_role,
                'department' => $candidate->department,
                'employment_type' => 'Probation',
                'status' => 'active',
                'join_date' => $candidate->expected_join_date ?? now()->toDateString(),
                'annual_leave_quota' => 12,
                'annual_leave_used' => 0,
            ]);

            $candidate->update(['converted_employee_id' => $emp->id]);

            return back()->with('success', "Checklist Onboarding diperbarui & {$candidate->name} resmi terdaftar di Direktori Pegawai (NIP: {$nip}).");
        }

        return back()->with('success', "Checklist Onboarding untuk {$candidate->name} berhasil diperbarui.");
    }
}
