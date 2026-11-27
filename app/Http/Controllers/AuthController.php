<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LoginLog;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceDetector;
use App\Services\TwoFactorOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Langkah 1 Login: Validasi email + password, lalu kirim kode OTP 6-digit via SMTP.
     */
    public function login(Request $request, TwoFactorOtpService $otpService)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $deviceName = DeviceDetector::parseDeviceName($userAgent);

        $user = User::where('email', $credentials['email'])->first();

        // Verifikasi password tanpa membuat sesi login penuh terlebih dahulu
        if ($user && Auth::validate($credentials)) {
            $sendResult = $otpService->sendOtp($user, 'login_2fa', $ip, $userAgent);

            if (!$sendResult['success']) {
                return back()->withErrors([
                    'email' => $sendResult['message'],
                ])->onlyInput('email');
            }

            $request->session()->put([
                '2fa_pending_user_id' => $user->id,
                '2fa_pending_email' => $user->email,
                '2fa_pending_expires_at' => now()->addMinutes(TwoFactorOtpService::EXPIRY_MINUTES)->timestamp,
            ]);

            return redirect()->route('login.otp.form')
                ->with('success', $sendResult['message']);
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

    /**
     * Langkah 2 Login: Tampilkan Halaman Input Kode OTP 2FA.
     */
    public function showOtpForm(Request $request, TwoFactorOtpService $otpService)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $pendingUserId = $request->session()->get('2fa_pending_user_id');
        $pendingExpires = (int) $request->session()->get('2fa_pending_expires_at', 0);

        if (!$pendingUserId || $pendingExpires < now()->timestamp) {
            $request->session()->forget(['2fa_pending_user_id', '2fa_pending_email', '2fa_pending_expires_at']);
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi verifikasi 2 langkah telah berakhir. Silakan masukkan email dan kata sandi Anda kembali.',
            ]);
        }

        $email = (string) $request->session()->get('2fa_pending_email', '');
        $maskedEmail = $otpService->maskEmail($email);

        return view('auth.verify_otp', compact('maskedEmail'));
    }

    /**
     * Langkah 2 Login: Verifikasi Kode OTP & Terbitkan Sesi Login Penuh.
     */
    public function verifyOtp(Request $request, TwoFactorOtpService $otpService)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'Masukkan 6 digit kode OTP yang dikirim ke email Anda.',
            'otp.digits' => 'Kode OTP harus berupa tepat 6 digit angka.',
        ]);

        $pendingUserId = $request->session()->get('2fa_pending_user_id');
        $pendingExpires = (int) $request->session()->get('2fa_pending_expires_at', 0);

        if (!$pendingUserId || $pendingExpires < now()->timestamp) {
            $request->session()->forget(['2fa_pending_user_id', '2fa_pending_email', '2fa_pending_expires_at']);
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi verifikasi 2 langkah telah kedaluwarsa. Silakan login ulang.',
            ]);
        }

        $user = User::find($pendingUserId);
        if (!$user) {
            $request->session()->forget(['2fa_pending_user_id', '2fa_pending_email', '2fa_pending_expires_at']);
            return redirect()->route('login')->withErrors([
                'email' => 'Akun pengguna tidak ditemukan.',
            ]);
        }

        $verifyResult = $otpService->verifyOtp($user, 'login_2fa', $request->input('otp'));
        if (!$verifyResult['valid']) {
            return back()->withErrors(['otp' => $verifyResult['message']]);
        }

        $request->session()->forget(['2fa_pending_user_id', '2fa_pending_email', '2fa_pending_expires_at']);

        return $this->completeUserLogin($request, $user);
    }

    /**
     * Kirim Ulang Kode OTP Login (Dengan Cooldown).
     */
    public function resendOtp(Request $request, TwoFactorOtpService $otpService)
    {
        $pendingUserId = $request->session()->get('2fa_pending_user_id');
        if (!$pendingUserId) {
            return redirect()->route('login')->withErrors([
                'email' => 'Sesi verifikasi tidak ditemukan. Silakan login kembali.',
            ]);
        }

        $user = User::find($pendingUserId);
        if (!$user) {
            return redirect()->route('login');
        }

        $sendResult = $otpService->sendOtp($user, 'login_2fa', $request->ip(), $request->userAgent(), true);
        if (!$sendResult['success']) {
            return back()->withErrors(['otp' => $sendResult['message']]);
        }

        $request->session()->put('2fa_pending_expires_at', now()->addMinutes(TwoFactorOtpService::EXPIRY_MINUTES)->timestamp);

        return back()->with('success', $sendResult['message']);
    }

    /**
     * Batalkan Verifikasi OTP & Kembali ke Form Login.
     */
    public function cancelOtp(Request $request)
    {
        $request->session()->forget(['2fa_pending_user_id', '2fa_pending_email', '2fa_pending_expires_at']);
        return redirect()->route('login');
    }

    /**
     * Tampilkan Halaman Reset Password Mandiri via Email OTP.
     */
    public function showForgotPasswordForm(Request $request, TwoFactorOtpService $otpService)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $pendingEmail = $request->session()->get('forgot_password_email');
        $maskedEmail = $pendingEmail ? $otpService->maskEmail($pendingEmail) : null;

        return view('auth.forgot_password', compact('pendingEmail', 'maskedEmail'));
    }

    /**
     * Langkah 1 Lupa Password: Kirim Kode OTP ke Email Terdaftar.
     */
    public function sendForgotPasswordOtp(Request $request, TwoFactorOtpService $otpService)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Email tersebut tidak terdaftar dalam sistem HRIS.',
            ])->withInput();
        }

        $sendResult = $otpService->sendOtp($user, 'forgot_password', $request->ip(), $request->userAgent(), true);
        if (!$sendResult['success']) {
            return back()->withErrors(['email' => $sendResult['message']])->withInput();
        }

        $request->session()->put('forgot_password_email', $user->email);

        return redirect()->route('password.request')->with('success', $sendResult['message']);
    }

    /**
     * Langkah 2 Lupa Password: Verifikasi OTP & Simpan Kata Sandi Baru.
     */
    public function resetPasswordWithOtp(Request $request, TwoFactorOtpService $otpService)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'otp.required' => 'Masukkan 6 digit kode OTP verifikasi.',
            'otp.digits' => 'Kode OTP harus terdiri dari 6 digit angka.',
            'password.min' => 'Kata sandi baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            return back()->withErrors(['email' => 'Akun pengguna tidak ditemukan.'])->withInput();
        }

        $verifyResult = $otpService->verifyOtp($user, 'forgot_password', $validated['otp']);
        if (!$verifyResult['valid']) {
            return back()->withErrors(['otp' => $verifyResult['message']])->withInput();
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'RESET_PASSWORD_2FA',
            'module' => 'Keamanan Akun',
            'description' => 'Pengguna mereset kata sandi secara mandiri menggunakan verifikasi OTP email SMTP.',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $request->session()->forget('forgot_password_email');

        return redirect()->route('login')->with('success', 'Kata sandi Anda berhasil direset setelah verifikasi 2 langkah. Silakan login dengan kata sandi baru.');
    }

    /**
     * Selesaikan Proses Login Setelah 2FA Terverifikasi (Regenerasi Sesi, Maks 3 Perangkat, Audit Log).
     */
    private function completeUserLogin(Request $request, User $user)
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $deviceName = DeviceDetector::parseDeviceName($userAgent);

        Auth::login($user, false);
        $request->session()->regenerate();
        $newSessionId = $request->session()->getId();

        // Kontrol Maksimal 3 Device Aktif
        $activeDevices = UserDevice::where('user_id', $user->id)
            ->orderBy('last_active_at', 'asc')
            ->get();

        if ($activeDevices->count() >= 3) {
            $devicesToRemoveCount = $activeDevices->count() - 2;
            $devicesToTerminate = $activeDevices->take($devicesToRemoveCount);

            foreach ($devicesToTerminate as $oldDevice) {
                $sessionFilePath = storage_path('framework/sessions/' . $oldDevice->session_id);
                if (File::exists($sessionFilePath)) {
                    @File::delete($sessionFilePath);
                }

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

        $request->session()->put('current_device_session', $newSessionId);

        LoginLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'status' => 'success',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_name' => $deviceName,
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'LOGIN',
            'module' => 'Autentikasi',
            'description' => "Pengguna berhasil login (Verifikasi 2 Langkah Email OTP) dari perangkat {$deviceName} (IP: {$ip}).",
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'properties' => [
                'session_id' => $newSessionId,
                'device' => $deviceName,
                'two_factor' => 'email_otp',
            ],
        ]);

        return redirect()->intended(route('dashboard'))
            ->with('success', "Selamat datang kembali, {$user->name}! Anda masuk sebagai " . strtoupper($user->role) . ".");
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        $sessionId = $request->session()->getId();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $deviceName = DeviceDetector::parseDeviceName($userAgent);

        if ($user) {
            UserDevice::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->delete();

            LoginLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => 'logout',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_name' => $deviceName,
            ]);

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
