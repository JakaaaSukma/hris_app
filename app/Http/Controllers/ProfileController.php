<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $employee = $user->employee;

        // Ambil daftar perangkat aktif akun saat ini
        $activeDevices = \App\Models\UserDevice::where('user_id', $user->id)
            ->orderBy('last_active_at', 'desc')
            ->get();

        // Ambil riwayat log login akun (15 terakhir)
        $loginLogs = \App\Models\LoginLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        // Ambil riwayat log aktivitas akun (20 terakhir)
        $activityLogs = \App\Models\ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        $currentSessionId = session()->getId();

        return view('profile.edit', compact(
            'user', 
            'employee', 
            'activeDevices', 
            'loginLogs', 
            'activityLogs',
            'currentSessionId'
        ));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:30',
            'avatar_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'avatar_url' => 'nullable|string|max:500',
            'current_password' => 'nullable|string',
            'new_password' => 'nullable|string|min:6|confirmed',
        ]);

        $passwordChanged = false;

        // Cek jika user ingin mengubah password
        if ($request->filled('new_password')) {
            if (!$request->filled('current_password')) {
                return back()->withErrors(['current_password' => 'Masukkan password saat ini untuk memverifikasi penggantian password.']);
            }

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini yang Anda masukkan salah.']);
            }

            $user->password = Hash::make($request->new_password);
            $passwordChanged = true;
        }

        // Handle Avatar Upload File
        $avatarPath = null;
        if ($request->hasFile('avatar_file')) {
            $file = $request->file('avatar_file');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);
            $avatarPath = asset('uploads/avatars/' . $filename);
        } elseif ($request->filled('avatar_url')) {
            $avatarPath = $request->avatar_url;
        }

        // Update User Model
        $oldEmail = $user->email;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        // Update Employee Model jika terhubung
        if ($employee) {
            $employee->name = $request->name;
            $employee->email = $request->email;
            if ($request->filled('phone')) {
                $employee->phone = $request->phone;
            }
            if ($avatarPath) {
                $employee->avatar_url = $avatarPath;
            }
            $employee->save();
        }

        // Catat Log Aktivitas
        if ($passwordChanged) {
            \App\Models\ActivityLog::record(
                'UPDATE_PASSWORD',
                'Keamanan Akun',
                'Pengguna berhasil memperbarui kata sandi akun.',
                ['ip' => $request->ip()]
            );
        }

        \App\Models\ActivityLog::record(
            'UPDATE_PROFILE',
            'Profil Pengguna',
            'Pengguna memperbarui informasi profil akun (Nama/Email/Foto).',
            [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_updated' => !is_null($avatarPath),
            ]
        );

        return back()->with('success', 'Profil dan informasi akun Anda berhasil diperbarui.');
    }

    /**
     * Terminasi / Kick Sesi Perangkat Tertentu Secara Manual
     */
    public function terminateDevice(Request $request, $id)
    {
        $user = Auth::user();
        $device = \App\Models\UserDevice::where('user_id', $user->id)->findOrFail($id);

        // Jangan izinkan kick sesi yang sedang aktif digunakan sekarang via tombol ini (harus lewat logout biasa)
        if ($device->session_id === session()->getId()) {
            return back()->with('error', 'Untuk keluar dari perangkat saat ini, silakan gunakan tombol Logout.');
        }

        $deviceName = $device->device_name;
        $sessionId = $device->session_id;

        // Hapus file sesi fisik jika ada
        $sessionFilePath = storage_path('framework/sessions/' . $sessionId);
        if (\Illuminate\Support\Facades\File::exists($sessionFilePath)) {
            @\Illuminate\Support\Facades\File::delete($sessionFilePath);
        }

        $device->delete();

        // Catat log
        \App\Models\LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'terminated',
            'ip_address' => $device->ip_address,
            'user_agent' => $device->user_agent,
            'device_name' => $deviceName,
            'failure_reason' => 'Sesi diakhiri secara manual oleh pengguna melalui Pengaturan Akun.',
        ]);

        \App\Models\ActivityLog::record(
            'TERMINATE_DEVICE',
            'Keamanan Akun',
            "Pengguna memutuskan sesi perangkat '{$deviceName}'.",
            ['session_id' => $sessionId]
        );

        return back()->with('success', "Sesi perangkat {$deviceName} berhasil dihentikan.");
    }
}
