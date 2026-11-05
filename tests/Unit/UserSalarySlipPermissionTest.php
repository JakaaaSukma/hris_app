<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\User;

class UserSalarySlipPermissionTest extends TestCase
{
    public function test_direktur_can_delete_salary_slips()
    {
        $user = new User(['role' => 'direktur', 'department' => 'Executive']);
        $this->assertTrue($user->canDeleteSalarySlips());
    }

    public function test_manager_finance_can_delete_salary_slips()
    {
        $user = new User(['role' => 'manager', 'department' => 'Finance']);
        $this->assertTrue($user->canDeleteSalarySlips());
    }

    public function test_karyawan_finance_cannot_delete_salary_slips()
    {
        $user = new User(['role' => 'karyawan', 'department' => 'Finance']);
        $this->assertFalse($user->canDeleteSalarySlips());
    }

    public function test_manager_hr_cannot_delete_salary_slips()
    {
        $user = new User(['role' => 'manager', 'department' => 'HR']);
        $this->assertFalse($user->canDeleteSalarySlips());
    }

    public function test_karyawan_hr_cannot_delete_salary_slips()
    {
        $user = new User(['role' => 'karyawan', 'department' => 'HR']);
        $this->assertFalse($user->canDeleteSalarySlips());
    }

    public function test_sekretaris_cannot_delete_salary_slips()
    {
        $user = new User(['role' => 'sekretaris', 'department' => 'Executive']);
        $this->assertFalse($user->canDeleteSalarySlips());
    }
}

