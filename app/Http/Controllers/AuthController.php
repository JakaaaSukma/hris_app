<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\LoginLog;
use App\Models\ActivityLog;
use App\Services\DeviceDetector;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $deviceName = DeviceDetector::parseDeviceName($userAgent);

        // Cari user berdasarkan email
        $user = User::where('email', $credentials['email'])->first();

        // Autentikasi kredensial (tanpa 'remember' agar tidak tersimpan permanen saat browser ditutup)
        if (Auth::attempt($credentials, false)) {
            $request->session()->regenerate();
            $newSessionId = $request->session()->getId();

            // ==========================================
            // KONTROL MAKSIMAL 3 DEVICE AKTIF
            // ==========================================
            $activeDevices = UserDevice::where('user_id', $user->id)
                ->orderBy('last_active_at', 'asc')
                ->get();

            // Jika sudah mencapai 3 device atau lebih, kick / terminasi device yang paling lama (oldest)
            if ($activeDevices->count() >= 3) {
                $devicesToRemoveCount = $activeDevices->count() - 2; // Sediakan 1 slot untuk device baru ini (total jadi 3)
                $devicesToTerminate = $activeDevices->take($devicesToRemoveCount);

                foreach ($devicesToTerminate as $oldDevice) {
                    // Hapus file session lama jika ada di storage/framework/sessions
                    $sessionFilePath = storage_path('framework/sessions/' . $oldDevice->session_id);
                    if (File::exists($sessionFilePath)) {
                        @File::delete($sessionFilePath);
                    }

                    // Catat log terminasi sesi
                    LoginLog::create([
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'status' => 'terminated',
                        'ip_address' => $oldDevice->ip_address,
                        'user_agent' => $oldDevice->user_agent,
                        'device_name' => $oldDevice->device_name,
                        'failure_reason' => 'Sesi diakhiri otomatis karena batas maksimal 3 perangkat tercapai saat login baru.',
                    ]);

                    $oldDevice->delete();
                }
            }

            // Daftarkan session device yang sedang login ini
            UserDevice::updateOrCreate(
                ['session_id' => $newSessionId],
                [
                    'user_id' => $user->id,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device_name' => $deviceName,
                    'last_active_at' => now(),
                ]
            );

            // Simpan status device saat ini ke session
            $request->session()->put('current_device_session', $newSessionId);

            // 1. Catat Log Berhasil Login
            LoginLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'success',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_name' => $deviceName,
            ]);

            // 2. Catat Log Aktivitas Akun
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'LOGIN',
                'module' => 'Autentikasi',
                'description' => "Pengguna berhasil login dari perangkat {$deviceName} (IP: {$ip}).",
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'properties' => [
                    'session_id' => $newSessionId,
                    'device' => $deviceName,
                ],
            ]);

            $role = Auth::user()->role;
            $nama = Auth::user()->name;

            return redirect()->intended(route('dashboard'))
                ->with('success', "Selamat datang kembali, {$nama}! Anda masuk sebagai " . strtoupper($role) . ".");
        }

        // Catat Log Gagal Login
        LoginLog::create([
            'user_id' => $user?->id,
            'email' => $credentials['email'],
            'status' => 'failed',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
            'failure_reason' => 'Kombinasi email atau kata sandi tidak cocok.',
        ]);

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $deviceName = DeviceDetector::parseDeviceName($userAgent);

        if ($user) {
            // Hapus session device dari daftar aktif
            UserDevice::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->delete();

            // 1. Catat Log Logout
            LoginLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'logout',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_name' => $deviceName,
            ]);

            // 2. Catat Log Aktivitas Akun
            ActivityLog::create([
                'user_id' => $user->id,
                'action' => 'LOGOUT',
                'module' => 'Autentikasi',
                'description' => "Pengguna keluar (logout) dari sesi perangkat {$deviceName}.",
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout dari sesi HRIS.');
    }
}
