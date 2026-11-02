<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobVacancy;
use App\Models\Candidate;
use App\Models\Offboarding;
use App\Models\Employee;

class RecruitmentAndOffboardingSeeder extends Seeder
{
    public function run(): void
    {
        if (JobVacancy::count() === 0) {
            $v1 = JobVacancy::create([
                'title' => 'Senior Talent Acquisition Specialist',
                'department' => 'HR',
                'employment_type' => 'Tetap (FTE)',
                'location' => 'Headquarters (Jakarta)',
                'quota' => 1,
                'description' => 'Bertanggung jawab atas end-to-end recruitment process, sourcing talenta strategis, dan employer branding.',
                'status' => 'open'
            ]);

            $v2 = JobVacancy::create([
                'title' => 'Financial Analyst & Treasury',
                'department' => 'Finance',
                'employment_type' => 'Tetap (FTE)',
                'location' => 'Headquarters (Jakarta)',
                'quota' => 2,
                'description' => 'Menganalisis cash flow, budgeting, reconciliations, dan compliance keuangan perusahaan.',
                'status' => 'open'
            ]);

            $v3 = JobVacancy::create([
                'title' => 'Partnership & BizDev Lead',
                'department' => 'Bisnis Development',
                'employment_type' => 'Tetap (FTE)',
                'location' => 'Headquarters (Jakarta)',
                'quota' => 1,
                'description' => 'Mengembangkan kemitraan korporat, B2B deal acquisition, dan ekspansi pasar.',
                'status' => 'open'
            ]);

            Candidate::create([
                'job_vacancy_id' => $v1->id,
                'name' => 'Aditya Pratama, S.Psi',
                'email' => 'aditya.pratama@gmail.com',
                'phone' => '081298765431',
                'applied_role' => 'Senior Talent Acquisition Specialist',
                'department' => 'HR',
                'stage' => 'offering',
                'notes' => 'Hasil psikotes sangat baik, nego offering salary tahap final.',
                'interview_date' => now()->subDays(3)->toDateString(),
                'expected_join_date' => now()->addDays(14)->toDateString(),
                'onboarding_doc_submitted' => true,
                'onboarding_device_allocated' => false,
                'onboarding_orientation_done' => false
            ]);

            Candidate::create([
                'job_vacancy_id' => $v2->id,
                'name' => 'Bella Safitri, S.E',
                'email' => 'bella.safitri@yahoo.com',
                'phone' => '085712349988',
                'applied_role' => 'Financial Analyst & Treasury',
                'department' => 'Finance',
                'stage' => 'hired',
                'notes' => 'Sudah tanda tangan offering letter. Sedang persiapan onboarding hari pertama.',
                'interview_date' => now()->subDays(7)->toDateString(),
                'expected_join_date' => now()->addDays(2)->toDateString(),
                'onboarding_doc_submitted' => true,
                'onboarding_device_allocated' => true,
                'onboarding_orientation_done' => false
            ]);

            Candidate::create([
                'job_vacancy_id' => $v3->id,
                'name' => 'Dimas Wicaksono',
                'email' => 'dimas.wicaksono@outlook.com',
                'phone' => '082188776655',
                'applied_role' => 'Partnership & BizDev Lead',
                'department' => 'Bisnis Development',
                'stage' => 'interview',
                'notes' => 'Jadwal user interview dengan Manager BizDev.',
                'interview_date' => now()->addDays(2)->toDateString(),
                'expected_join_date' => null,
                'onboarding_doc_submitted' => false,
                'onboarding_device_allocated' => false,
                'onboarding_orientation_done' => false
            ]);
        }

        if (Offboarding::count() === 0) {
            $emp = Employee::where('department', 'HR')->where('role_title', 'like', '%Staff%')->first() 
                ?? Employee::skip(2)->first() 
                ?? Employee::first();

            if ($emp) {
                Offboarding::create([
                    'employee_id' => $emp->id,
                    'type' => 'Resign',
                    'reason' => 'Menerima tawaran studi beasiswa S2 di luar negeri.',
                    'request_date' => now()->subDays(7)->toDateString(),
                    'effective_date' => now()->addDays(21)->toDateString(),
                    'status' => 'processing',
                    'asset_returned' => false,
                    'finance_cleared' => true,
                    'handover_completed' => false,
                    'exit_interview_done' => true,
                    'hr_notes' => 'Exit interview telah dilaksanakan. Sedang dalam tahap handover dokumen pekerjaan dan pengembalian laptop GA.'
                ]);
            }
        }
    }
}
