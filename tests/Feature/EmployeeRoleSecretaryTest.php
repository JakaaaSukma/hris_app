<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;

class EmployeeRoleSecretaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
    }

    public function test_direktur_can_view_secretary_options_in_employee_modals()
    {
        $direktur = User::where('role', 'direktur')->first();
        if (!$direktur) {
            $direktur = User::factory()->create(['role' => 'direktur', 'department' => 'Executive']);
        }

        $response = $this->actingAs($direktur)->get(route('employees.index'));
        $response->assertStatus(200);

        // Assert role option 'sekretaris' is present in account management & add employee modals
        $response->assertSee('value="sekretaris"', false);
        $response->assertSee('Sekretaris Direktur', false);

        // Assert department option 'Executive' is present
        $response->assertSee('value="Executive"', false);
        $response->assertSee('Executive (Sekretariat Direksi)', false);
    }

    public function test_direktur_can_update_employee_account_to_secretary()
    {
        $direktur = User::where('role', 'direktur')->first() ?? User::factory()->create(['role' => 'direktur']);

        $emp = Employee::factory()->create([
            'name' => 'Aulia Sekretaris Test',
            'email' => 'aulia.test@gyokai.com',
            'department' => 'HR',
            'role_title' => 'Staf HR',
        ]);

        $user = User::factory()->create([
            'name' => $emp->name,
            'email' => $emp->email,
            'role' => 'karyawan',
            'department' => 'HR',
            'employee_id' => $emp->id,
        ]);

        $response = $this->actingAs($direktur)->patch(route('employees.updateAccount', $emp->id), [
            'email' => 'aulia.sekretaris@gyokai.com',
            'role' => 'sekretaris',
            'department' => 'Executive',
        ]);

        $response->assertSessionHas('success');

        $user->refresh();
        $emp->refresh();

        $this->assertEquals('sekretaris', $user->role);
        $this->assertEquals('Executive', $user->department);
        $this->assertEquals('Sekretariat', $user->sub_department);
        $this->assertEquals('Executive', $emp->department);
        $this->assertEquals('Sekretariat', $emp->sub_department);
        $this->assertTrue($user->isSekretaris());
        $this->assertTrue($user->canManageExecutiveLetters());
    }

    public function test_hr_or_direktur_can_create_employee_with_secretary_role()
    {
        $hrManager = User::where('department', 'HR')->where('role', 'manager')->first() ?? 
                     User::where('role', 'direktur')->first();

        $nip = 'NEX-' . rand(80000, 89999);
        $response = $this->actingAs($hrManager)->post(route('employees.store'), [
            'nip' => $nip,
            'name' => 'Risa Sekretaris Baru',
            'email' => 'risa.sec' . rand(100, 999) . '@gyokai.com',
            'role_title' => 'Sekretaris Direktur',
            'department' => 'Executive',
            'employment_type' => 'Tetap (FTE)',
            'join_date' => now()->toDateString(),
            'account_role' => 'sekretaris',
        ]);

        $response->assertSessionHas('success');

        $newEmp = Employee::where('nip', $nip)->first();
        $this->assertNotNull($newEmp);
        $this->assertEquals('Executive', $newEmp->department);
        $this->assertEquals('Sekretariat', $newEmp->sub_department);

        $newUser = User::where('employee_id', $newEmp->id)->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('sekretaris', $newUser->role);
        $this->assertEquals('Executive', $newUser->department);
        $this->assertEquals('Sekretariat', $newUser->sub_department);
    }
}
