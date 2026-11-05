<?php

use App\Http\Controllers\Api\AttendanceApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\LeaveApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HRIS Mobile REST API Routes (Laravel Sanctum Protected)
|--------------------------------------------------------------------------
*/

// Public Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:6,1')->name('api.auth.login');
});

// Authenticated Routes (Requires Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    // Current User Session & Profile
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthApiController::class, 'me'])->name('api.auth.me');
        Route::post('/update-profile', [AuthApiController::class, 'updateProfile'])->name('api.auth.updateProfile');
        Route::post('/logout', [AuthApiController::class, 'logout'])->name('api.auth.logout');
    });

    // Attendance & Biometric Face ID + GPS
    Route::prefix('attendance')->group(function () {
        Route::get('/today', [AttendanceApiController::class, 'today'])->name('api.attendance.today');
        Route::post('/face-record', [AttendanceApiController::class, 'faceRecord'])->name('api.attendance.faceRecord');
        Route::get('/history', [AttendanceApiController::class, 'history'])->name('api.attendance.history');
        Route::get('/config', [AttendanceApiController::class, 'config'])->name('api.attendance.config');
    });

    // Leave & Permission Management (Two-Tier Approval)
    Route::prefix('leave')->group(function () {
        Route::get('/', [LeaveApiController::class, 'index'])->name('api.leave.index');
        Route::post('/', [LeaveApiController::class, 'store'])->name('api.leave.store');
        Route::get('/{id}', [LeaveApiController::class, 'show'])->name('api.leave.show');
        Route::post('/{id}/approve', [LeaveApiController::class, 'approve'])->name('api.leave.approve');
        Route::post('/{id}/reject', [LeaveApiController::class, 'reject'])->name('api.leave.reject');
    });

    // Calendar & Schedules Management
    Route::prefix('calendar')->group(function () {
        Route::get('/schedules', [CalendarApiController::class, 'index'])->name('api.calendar.schedules');
        Route::post('/schedules', [CalendarApiController::class, 'store'])->name('api.calendar.store');
        Route::get('/schedules/{id}', [CalendarApiController::class, 'show'])->name('api.calendar.show');
    });

    // Dashboard Overview & Notifications
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary', [DashboardApiController::class, 'summary'])->name('api.dashboard.summary');
        Route::get('/notifications', [DashboardApiController::class, 'notifications'])->name('api.dashboard.notifications');
        Route::post('/notifications/{id}/read', [DashboardApiController::class, 'markNotificationAsRead'])->name('api.dashboard.notifications.read');
        Route::post('/notifications/mark-all-read', [DashboardApiController::class, 'markAllNotificationsAsRead'])->name('api.dashboard.notifications.markAllRead');
    });

    // Default Sanctum User Endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
