<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Http\Controllers\Api\AdmsAttendanceController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class AdmsAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adms_handshake_and_ota_query_history()
    {
        Cache::forget('adms_query_history');

        // Normal handshake
        $res = $this->get('/iclock/cdata?SN=SOLUTION_X609');
        $res->assertStatus(200);
        $this->assertStringContainsString('ServerName=GyokaiHR-Cloud', $res->getContent());
        $this->assertStringNotContainsString('ATTLOGStamp=0', $res->getContent());

        // When sync history is active
        Cache::put('adms_query_history', true, now()->addHour());

        $resWithSync = $this->get('/iclock/cdata?SN=SOLUTION_X609');
        $resWithSync->assertStatus(200);
        $this->assertStringContainsString('ATTLOGStamp=0', $resWithSync->getContent());

        // getRequest sends command to read history
        $reqRes = $this->get('/iclock/getrequest?SN=SOLUTION_X609');
        $reqRes->assertStatus(200);
        $this->assertStringContainsString('DATA QUERY ATTLOG', $reqRes->getContent());

        // deviceCmd clears the queue
        $cmdRes = $this->call('POST', '/iclock/devicecmd?SN=SOLUTION_X609', [], [], [], [
            'CONTENT_TYPE' => 'text/plain',
        ], 'ID=123&Return=0&CMD=DATA QUERY ATTLOG');
        $cmdRes->assertStatus(200);

        $this->assertFalse(Cache::has('adms_query_history'));
    }

    public function test_process_attlog_content_records_clock_in_and_updates_clock_out()
    {
        $employee = \App\Models\Employee::create([
            'nip' => 'EMP001',
            'name' => 'Budi Santoso',
            'email' => 'budi@gyokai.com',
            'role_title' => 'Staff IT',
            'department' => 'IT & Engineering',
            'join_date' => '2025-01-01',
        ]);
        $user = User::factory()->create([
            'employee_id' => $employee->id,
            'role' => 'karyawan',
        ]);

        // 1. Clock In pagi
        $logLine1 = "EMP001\t2026-11-05 08:30:00\t0\t1\t0";
        $res1 = AdmsAttendanceController::processAttlogContent($logLine1);

        $this->assertEquals(1, $res1['success_count']);
        $att = Attendance::where('employee_id', $employee->id)->whereDate('date', '2026-11-05')->first();
        $this->assertNotNull($att);
        $this->assertEquals('08:30:00', $att->clock_in);
        $this->assertNull($att->clock_out);

        // 2. Clock Out sore (meskipun status=0 tanpa menekan tombol Out di mesin)
        $logLine2 = "EMP001\t2026-11-05 17:15:00\t0\t1\t0";
        $res2 = AdmsAttendanceController::processAttlogContent($logLine2);

        $this->assertEquals(1, $res2['success_count']);
        $att->refresh();
        $this->assertEquals('08:30:00', $att->clock_in);
        $this->assertEquals('17:15:00', $att->clock_out);

        // 3. Scan lembur malam berikutnya memperbarui clock out ke jam terakhir
        $logLine3 = "EMP001 2026-11-05 18:45:00 0 1";
        $res3 = AdmsAttendanceController::processAttlogContent($logLine3);

        $this->assertEquals(1, $res3['success_count']);
        $att->refresh();
        $this->assertEquals('18:45:00', $att->clock_out);
    }
}