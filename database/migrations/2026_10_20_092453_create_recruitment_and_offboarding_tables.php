<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Lowongan Pekerjaan (Job Vacancies / Rekrutmen)
        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g. Senior Frontend Developer
            $table->string('department'); // HR, Finance, Bisnis Development, GA
            $table->string('employment_type')->default('Full-Time'); // Full-Time, Contract, Internship
            $table->string('location')->default('Jakarta Headquarter');
            $table->integer('quota')->default(1);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'open', 'closed'])->default('open');
            $table->timestamps();
        });

        // 2. Tabel Pelamar Kerja & Status Rekrutmen / Onboarding
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_vacancy_id')->nullable()->constrained('job_vacancies')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('applied_role');
            $table->string('department');
            $table->string('resume_url')->nullable();
            // Stage: applied -> screening -> interview -> offering -> hired (onboarding) -> rejected
            $table->enum('stage', ['applied', 'screening', 'interview', 'offering', 'hired', 'rejected'])->default('applied');
            $table->text('notes')->nullable();
            $table->date('interview_date')->nullable();
            $table->date('expected_join_date')->nullable();
            
            // Onboarding Checklist Tracking (JSON or step status)
            $table->boolean('onboarding_doc_submitted')->default(false);
            $table->boolean('onboarding_device_allocated')->default(false);
            $table->boolean('onboarding_orientation_done')->default(false);
            $table->foreignId('converted_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Tabel Offboarding Karyawan
        Schema::create('offboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('type', ['Resign', 'PHK', 'Habis Kontrak', 'Pensiun'])->default('Resign');
            $table->text('reason')->nullable();
            $table->date('request_date');
            $table->date('effective_date'); // Last working day
            // Status: pending, processing, approved, completed, rejected
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            
            // Offboarding Exit Checklist
            $table->boolean('asset_returned')->default(false);      // Pengembalian Laptop/ID Card/Kendaraan ke GA
            $table->boolean('finance_cleared')->default(false);     // Penyelesaian kasbon/reimburse ke Finance
            $table->boolean('handover_completed')->default(false);  // Handover pekerjaan ke Departemen
            $table->boolean('exit_interview_done')->default(false); // Sesi exit interview oleh HR
            $table->text('hr_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offboardings');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_vacancies');
    }
};
