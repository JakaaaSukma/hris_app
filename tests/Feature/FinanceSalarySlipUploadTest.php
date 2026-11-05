<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\SalarySlip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FinanceSalarySlipUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');
    }

    public function test_unauthorized_user_cannot_upload_salary_slips()
    {
        $karyawan = new User([
            'id' => 9991,
            'name' => 'Karyawan Biasa',
            'email' => 'karyawan@gyokai.com',
            'role' => 'karyawan',
            'department' => 'HR',
        ]);

        $file = UploadedFile::fake()->create('slip.pdf', 100, 'application/pdf');

        $response = $this->actingAs($karyawan)->post(route('finance.salarySlips.uploadPdfZip'), [
            'slip_file' => $file,
            'period_month' => 'November 2026',
            'payment_date' => '2026-11-05',
        ]);

        $response->assertStatus(403);
    }

    public function test_dangerous_file_type_is_rejected()
    {
        $managerFinance = new User([
            'id' => 9992,
            'name' => 'Manager Finance',
            'email' => 'mgr.finance@gyokai.com',
            'role' => 'manager',
            'department' => 'Finance',
        ]);

        $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $response = $this->actingAs($managerFinance)->post(route('finance.salarySlips.uploadPdfZip'), [
            'slip_file' => $file,
            'period_month' => 'November 2026',
            'payment_date' => '2026-11-05',
        ]);

        $response->assertSessionHasErrors('slip_file');
    }
}

