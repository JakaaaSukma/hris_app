<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    /**
     * Mobile Login API via Bearer Token (Sanctum)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 401);
        }

        // Load relasi employee jika ada
        $user->load('employee');

        // Buat Sanctum Personal Access Token
        $deviceName = $request->device_name ?: 'Android HRIS Mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil. Selamat datang di HRIS Mobile.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'sub_department' => $user->sub_department,
                    'employee_id' => $user->employee_id,
                ],
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'nip' => $user->employee->nip,
                    'name' => $user->employee->name,
                    'position' => $user->employee->position,
                    'department' => $user->employee->department,
                    'phone' => $user->employee->phone,
                    'address' => $user->employee->address,
                    'join_date' => $user->employee->join_date,
                    'annual_leave_quota' => $user->employee->annual_leave_quota,
                    'annual_leave_used' => $user->employee->annual_leave_used,
                    'annual_leave_remaining' => max(0, ($user->employee->annual_leave_quota ?? 12) - ($user->employee->annual_leave_used ?? 0)),
                ] : null,
                'permissions' => [
                    'can_face_clock' => $user->canFaceClock(),
                    'can_approve_leaves' => $user->canApproveLeaves(),
                    'is_manager' => $user->isManager(),
                    'is_direktur' => $user->isDirektur(),
                    'is_karyawan' => $user->isKaryawan(),
                    'is_sekretaris' => $user->isSekretaris(),
                ],
            ],
        ]);
    }

    /**
     * Get Current Authenticated User & Employee Profile
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('employee');

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department,
                    'sub_department' => $user->sub_department,
                    'employee_id' => $user->employee_id,
                ],
                'employee' => $user->employee ? [
                    'id' => $user->employee->id,
                    'nip' => $user->employee->nip,
                    'name' => $user->employee->name,
                    'position' => $user->employee->position,
                    'department' => $user->employee->department,
                    'phone' => $user->employee->phone,
                    'address' => $user->employee->address,
                    'join_date' => $user->employee->join_date,
                    'annual_leave_quota' => $user->employee->annual_leave_quota,
                    'annual_leave_used' => $user->employee->annual_leave_used,
                    'annual_leave_remaining' => max(0, ($user->employee->annual_leave_quota ?? 12) - ($user->employee->annual_leave_used ?? 0)),
                ] : null,
                'permissions' => [
                    'can_face_clock' => $user->canFaceClock(),
                    'can_approve_leaves' => $user->canApproveLeaves(),
                    'is_manager' => $user->isManager(),
                    'is_direktur' => $user->isDirektur(),
                    'is_karyawan' => $user->isKaryawan(),
                    'is_sekretaris' => $user->isSekretaris(),
                ],
            ],
        ]);
    }

    /**
     * Update Profile Data (Phone / Address)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 422);
        }

        $request->validate([
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $employee->update($request->only(['phone', 'address']));

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'phone' => $employee->phone,
                'address' => $employee->address,
            ],
        ]);
    }

    /**
     * Store or Update FCM Device Token for Push Notifications
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM berhasil diperbarui.',
        ]);
    }

    /**
     * Logout and Revoke Current Sanctum Token
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke current access token
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout. Sesi token telah dicabut.',
        ]);
    }
}

