<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    /**
     * Dashboard Summary for HRIS Mobile App
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 422);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 1. Kehadiran Hari Ini
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        // 2. Statistik Kehadiran Bulan Berjalan
        $monthlyAttendances = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $now->year)
            ->whereMonth('date', $now->month)
            ->get();

        $monthlyStats = [
            'total_present' => $monthlyAttendances->whereNotNull('clock_in')->count(),
            'total_on_time' => $monthlyAttendances->where('status', 'on_time')->count(),
            'total_late' => $monthlyAttendances->where('status', 'late')->count(),
        ];

        // 3. Kuota Cuti Tahunan
        $myQuota = (int) $employee->annual_leave_quota;
        $myUsed = (int) $employee->annual_leave_used;
        $myPending = (int) LeaveRequest::where('employee_id', $employee->id)
            ->where('type', 'Cuti Tahunan')
            ->whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
            ->sum('duration_days');
        $myRemaining = max(0, $myQuota - $myUsed - $myPending);

        // 4. Hitung Pending Approval jika punya hak otorisasi
        $pendingApprovalsCount = 0;
        if ($user->isDirektur() || ($user->isHR() && $user->isManager())) {
            $pendingApprovalsCount = LeaveRequest::whereIn('status', ['pending', 'pending_manager', 'pending_hr'])
                ->where('employee_id', '!=', $employee->id)
                ->count();
        } elseif ($user->isManager()) {
            $userDept = $user->department;
            $pendingApprovalsCount = LeaveRequest::where('status', 'pending_manager')
                ->whereHas('employee', function ($q) use ($userDept) {
                    $q->where('department', $userDept);
                })
                ->where('employee_id', '!=', $employee->id)
                ->count();
        }

        // 5. Sinkronisasi Reminder Otomatis & Notifikasi Belum Dibaca
        try {
            \App\Services\ReminderService::processReminders($user->id);
        } catch (\Throwable $e) {
            \Log::warning("Process reminders error: " . $e->getMessage());
        }
        $unreadNotifCount = $user->unreadNotifications()->count();

        // 6. Hitung Agenda Jadwal Kalender Hari Ini & Mendatang
        $todayStr = $today;
        $todaySchedulesCount = \App\Models\CalendarSchedule::whereDate('date', $todayStr)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_company_wide', true)
                  ->orWhere('department', $user->department);
            })
            ->count();

        // 7. Geofence Configuration
        $officeLat = (float) config('app.office_latitude', env('OFFICE_LATITUDE', -6.2088));
        $officeLng = (float) config('app.office_longitude', env('OFFICE_LONGITUDE', 106.8456));
        $officeRadius = (int) config('app.office_radius_meters', env('OFFICE_RADIUS_METERS', 500));

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'department' => $user->department,
                    'position' => $employee->position,
                    'nip' => $employee->nip,
                ],
                'date_info' => [
                    'date' => $today,
                    'formatted' => $now->locale('id')->isoFormat('dddd, D MMMM Y'),
                    'server_time' => $now->format('H:i:s'),
                ],
                'today_attendance' => [
                    'is_clocked_in' => (bool) ($todayAttendance && $todayAttendance->clock_in),
                    'is_clocked_out' => (bool) ($todayAttendance && $todayAttendance->clock_out),
                    'clock_in' => $todayAttendance && $todayAttendance->clock_in ? substr($todayAttendance->clock_in, 0, 5) : null,
                    'clock_out' => $todayAttendance && $todayAttendance->clock_out ? substr($todayAttendance->clock_out, 0, 5) : null,
                    'status' => $todayAttendance ? $todayAttendance->status : null,
                    'status_label' => $todayAttendance ? ($todayAttendance->status === 'on_time' ? 'Tepat Waktu' : ($todayAttendance->status === 'late' ? 'Terlambat' : 'Hadir')) : 'Belum Presensi',
                    'work_mode' => $todayAttendance ? $todayAttendance->work_mode : null,
                    'location_name' => $todayAttendance ? $todayAttendance->location_name : null,
                    'photo_in_url' => $todayAttendance ? $todayAttendance->photo_in_url : null,
                    'photo_out_url' => $todayAttendance ? $todayAttendance->photo_out_url : null,
                ],
                'monthly_attendance' => $monthlyStats,
                'leave_quota' => [
                    'quota' => $myQuota,
                    'used' => $myUsed,
                    'pending' => $myPending,
                    'remaining' => $myRemaining,
                ],
                'approval_badge' => [
                    'can_approve' => ($user->isManager() || $user->isDirektur() || ($user->isHR() && $user->isManager())),
                    'pending_count' => $pendingApprovalsCount,
                ],
                'notifications_count' => $unreadNotifCount,
                'today_schedules_count' => $todaySchedulesCount,
                'office_config' => [
                    'office_name' => 'Kantor Pusat LPK Gyokai Indonesia',
                    'latitude' => $officeLat,
                    'longitude' => $officeLng,
                    'radius_meters' => $officeRadius,
                ],
            ],
        ]);
    }

    /**
     * Get User Notifications List
     */
    public function notifications(Request $request)
    {
        $user = $request->user();

        // Otomatis sinkronisasi reminder jadwal (H-1) dan reminder tugas aktif
        try {
            \App\Services\ReminderService::processReminders($user->id);
        } catch (\Throwable $e) {
            \Log::warning("Process reminders error: " . $e->getMessage());
        }

        $notifications = $user->notifications()->latest()->paginate(30);

        return response()->json([
            'success' => true,
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => $notifications,
        ]);
    }

    /**
     * Mark Specific Notification as Read
     */
    public function markNotificationAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi telah ditandai sebagai dibaca.',
        ]);
    }

    /**
     * Mark All Notifications as Read
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah ditandai sebagai dibaca.',
        ]);
    }
}

