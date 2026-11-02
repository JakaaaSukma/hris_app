<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FaceAttendanceTest extends TestCase
{
    public function test_employee_can_clock_in_via_face_and_location()
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');

        $user = User::whereNotNull('employee_id')->first();
        if (!$user) {
            $emp = Employee::first();
            $user = User::factory()->create(['employee_id' => $emp->id]);
        }

        // Clean any existing attendance today for this employee
        Attendance::where('employee_id', $user->employee_id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->delete();

        // 1x1 transparent pixel png base64
        $fakeBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->actingAs($user)->postJson(route('attendance.faceRecord'), [
            'photo' => $fakeBase64,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'type' => 'clock_in',
            'location_name' => 'Kantor Pusat Jakarta',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'action' => 'clock_in',
        ]);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $user->employee_id,
            'date' => Carbon::today()->toDateString(),
            'verification_method' => 'face_location',
        ]);

        $createdAttendance = Attendance::where('employee_id', $user->employee_id)
            ->whereDate('date', Carbon::today()->toDateString())
            ->first();

        $this->assertNotNull($createdAttendance->photo_in);
        $this->assertNotNull($createdAttendance->photo_in_url);

        // Test showPhoto route streams the file correctly
        $photoResponse = $this->actingAs($user)->get($createdAttendance->photo_in_url);
        $photoResponse->assertStatus(200);
        $this->assertTrue(str_contains($photoResponse->headers->get('content-type'), 'image/'));
    }
}
