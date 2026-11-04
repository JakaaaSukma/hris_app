<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');
    }

    public function test_auth_login_and_me_and_logout()
    {
        // 1. Ambil atau buat akun karyawan
        $user = User::whereNotNull('employee_id')->first();
        if (!$user) {
            $employee = Employee::first() ?? Employee::create([
                'nip' => 'EMP999',
                'name' => 'Test Employee',
                'department' => 'HR',
                'position' => 'Staff',
                'annual_leave_quota' => 12,
                'annual_leave_used' => 0,
            ]);
            $user = User::create([
                'name' => 'Test Employee',
                'email' => 'test_emp@gyokai.id',
                'password' => Hash::make('password123'),
                'role' => 'karyawan',
                'department' => 'HR',
                'employee_id' => $employee->id,
            ]);
        } else {
            $user->password = Hash::make('password123');
            $user->save();
        }

        // Test Login
        $loginRes = $this->postJson(route('api.auth.login'), [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'Flutter Test Device',
        ]);

        $loginRes->assertStatus(200);
        $loginRes->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'token_type',
                'user' => ['id', 'name', 'email', 'role', 'department'],
                'employee' => ['id', 'nip', 'name', 'position', 'annual_leave_quota', 'annual_leave_remaining'],
                'permissions',
            ],
        ]);

        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // Test GET /api/auth/me with Bearer Token
        $meRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.auth.me'));

        $meRes->assertStatus(200);
        $meRes->assertJsonPath('data.user.email', $user->email);

        // Test POST /api/auth/logout
        $logoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.auth.logout'));

        $logoutRes->assertStatus(200);
        $logoutRes->assertJson(['success' => true]);
    }

    public function test_attendance_config_and_today()
    {
        $user = User::whereNotNull('employee_id')->first();
        $token = $user->createToken('test')->plainTextToken;

        // Config endpoint
        $configRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.attendance.config'));

        $configRes->assertStatus(200);
        $configRes->assertJsonStructure([
            'success',
            'data' => [
                'office_name',
                'latitude',
                'longitude',
                'radius_meters',
                'work_start_limit',
            ],
        ]);

        // Today endpoint
        $todayRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.attendance.today'));

        $todayRes->assertStatus(200);
        $todayRes->assertJsonStructure([
            'success',
            'data' => [
                'date',
                'is_clocked_in',
                'is_clocked_out',
            ],
        ]);
    }

    public function test_attendance_face_record_with_anti_mock_gps()
    {
        $user = User::whereNotNull('employee_id')->first();
        $token = $user->createToken('test')->plainTextToken;

        $fakeBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        // 1. Anti-Mock GPS Test: is_mocked = true harus DITOLAK
        $mockedRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.attendance.faceRecord'), [
                'photo' => $fakeBase64,
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'is_mocked' => true,
            ]);

        $mockedRes->assertStatus(422);
        $mockedRes->assertJson([
            'success' => false,
        ]);
        $this->assertStringContainsString('Fake GPS', $mockedRes->json('message'));

        // 2. Normal Clock In
        // Bersihkan data presensi hari ini untuk user ini
        Attendance::where('employee_id', $user->employee_id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->delete();

        $clockInRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson(route('api.attendance.faceRecord'), [
                'photo' => $fakeBase64,
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'type' => 'clock_in',
                'is_mocked' => false,
                'device_info' => 'Samsung Galaxy S23 (HRIS Mobile)',
            ]);

        $clockInRes->assertStatus(200);
        $clockInRes->assertJson([
            'success' => true,
            'action' => 'clock_in',
        ]);
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'date' => Carbon::today()->toDateString(),
            'verification_method' => 'face_location',
        ]);

        // 3. History
        $histRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.attendance.history'));

        $histRes->assertStatus(200);
        $histRes->assertJsonStructure([
            'success',
            'data' => [
                'statistics' => ['total_present', 'total_on_time', 'total_late'],
                'records',
            ],
        ]);
    }

    public function test_leave_store_and_anti_self_approval()
    {
        $applicant = User::whereNotNull('employee_id')->where('role', 'karyawan')->first();
        if (!$applicant) {
            $applicant = User::whereNotNull('employee_id')->first();
        }

        $applicantToken = $applicant->createToken('applicant')->plainTextToken;

        // Bersihkan data cuti untuk testing rentang ini
        LeaveRequest::where('employee_id', $applicant->employee_id)->forceDelete();

        $startDate = Carbon::today()->addDays(20)->toDateString();
        $endDate = Carbon::today()->addDays(21)->toDateString();

        // 1. Submit Cuti Tahunan
        $leaveRes = $this->withHeader('Authorization', 'Bearer ' . $applicantToken)
            ->postJson(route('api.leave.store'), [
                'type' => 'Cuti Tahunan',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Keperluan keluarga penting dan mendesak',
            ]);

        if ($leaveRes->status() !== 201) {
            dump($leaveRes->json());
        }
        $leaveRes->assertStatus(201);
        $leaveId = $leaveRes->json('data.id');

        // 2. Anti Self-Approval: Pemohon DILARANG approve cuti miliknya sendiri
        $selfApproveRes = $this->withHeader('Authorization', 'Bearer ' . $applicantToken)
            ->postJson(route('api.leave.approve', ['id' => $leaveId]));

        $selfApproveRes->assertStatus(403);
        $this->assertStringContainsString('tidak dapat menyetujui', $selfApproveRes->json('message'));

        // 3. Direktur / HR Approve
        $approver = User::where('role', 'direktur')->where('id', '!=', $applicant->id)->first();
        if (!$approver) {
            $approver = User::where('role', 'manager')->where('department', 'HR')->where('id', '!=', $applicant->id)->first();
        }

        if ($approver) {
            app('auth')->forgetGuards();
            $approverToken = $approver->createToken('approver')->plainTextToken;
            $approveRes = $this->withHeader('Authorization', 'Bearer ' . $approverToken)
                ->postJson(route('api.leave.approve', ['id' => $leaveId]));

            $approveRes->assertStatus(200);
            $this->assertTrue($approveRes->json('success'));
        }
    }

    public function test_dashboard_summary_and_notifications()
    {
        $user = User::whereNotNull('employee_id')->first();
        $token = $user->createToken('dashboard-test')->plainTextToken;

        // Dashboard Summary
        $dashRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.dashboard.summary'));

        $dashRes->assertStatus(200);
        $dashRes->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'date_info',
                'today_attendance',
                'monthly_attendance',
                'leave_quota',
                'approval_badge',
                'notifications_count',
                'office_config',
            ],
        ]);

        // Notifications
        $notifRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson(route('api.dashboard.notifications'));

        $notifRes->assertStatus(200);
        $notifRes->assertJsonStructure(['success', 'unread_count', 'data']);
    }
}
