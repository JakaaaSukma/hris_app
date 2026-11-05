<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SalarySlip;
use App\Models\Employee;

class FinanceSalarySlipDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
    }

    public function test_karyawan_cannot_delete_salary_slip()
    {
        $karyawan = new User([
            'id' => 9991,
            'name' => 'Staff Finance Test',
            'email' => 'staff.finance@gyokai.com',
            'role' => 'karyawan',
            'department' => 'Finance',
        ]);

        $response = $this->actingAs($karyawan)->delete(route('finance.salarySlips.destroy', 1));
        $response->assertStatus(403);
    }

    public function test_hr_manager_cannot_delete_salary_slip()
    {
        $hrManager = new User([
            'id' => 9992,
            'name' => 'HR Manager Test',
            'email' => 'hr.manager@gyokai.com',
            'role' => 'manager',
            'department' => 'HR',
            'sub_department' => 'HR',
        ]);

        $response = $this->actingAs($hrManager)->delete(route('finance.salarySlips.destroy', 1));
        $response->assertStatus(403);
    }
}

