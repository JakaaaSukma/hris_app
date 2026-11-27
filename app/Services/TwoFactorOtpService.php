<?php

namespace App\Services;

use App\Mail\TwoFactorOtpMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TwoFactorOtpService
{
    public const EXPIRY_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 45;

    /**
     * Buat dan kirim kode OTP 6-digit ke email pengguna via SMTP.
     */
    public function sendOtp(User $user, string $purpose, ?string $ip = null, ?string $userAgent = null, bool $enforceCooldown = false): array
    {
        if (empty($user->email)) {
            return [
                'success' => false,
                'message' => 'Akun tidak memiliki alamat email yang valid untuk menerima kode OTP.',
            ];
        }

        if ($enforceCooldown) {
            $recentCode = EmailVerificationCode::where('user_id', $user->id)
                ->where('purpose', $purpose)
                ->whereNull('used_at')
                ->orderBy('id', 'desc')
                ->first();

            if ($recentCode && $recentCode->created_at) {
                $elapsed = $recentCode->created_at->diffInSeconds(now());
                if ($elapsed < self::RESEND_COOLDOWN_SECONDS) {
                    $wait = self::RESEND_COOLDOWN_SECONDS - $elapsed;
                    return [
                        'success' => false,
                        'message' => "Mohon tunggu {$wait} detik sebelum meminta pengiriman ulang kode OTP.",
                        'cooldown' => $wait,
                    ];
                }
            }
        }

        // Tandai OTP lama yang belum dipakai sebagai tidak berlaku
        EmailVerificationCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otpCode = (string) random_int(100000, 999999);

        $record = EmailVerificationCode::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($otpCode),
            'attempts' => 0,
            'ip_address' => $ip,
            'user_agent' => $userAgent ? substr($userAgent, 0, 500) : null,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        try {
            Mail::to($user->email)->send(new TwoFactorOtpMail($user, $otpCode, $purpose, $ip));

            return [
                'success' => true,
                'message' => 'Kode OTP verifikasi 6-digit telah dikirim ke ' . $this->maskEmail($user->email) . '.',
                'masked_email' => $this->maskEmail($user->email),
                'expires_in_minutes' => self::EXPIRY_MINUTES,
            ];
        } catch (\Throwable $e) {
            Log::error("SMTP TwoFactorOtpMail gagal dikirim untuk {$user->email} ({$purpose}): " . $e->getMessage());
            // Hapus record yang gagal terkirim agar tidak memblokir cooldown berikutnya
            $record->delete();

            return [
                'success' => false,
                'message' => 'Gagal mengirim email kode verifikasi OTP melalui SMTP. Pastikan konfigurasi SMTP dan koneksi internet aktif.',
            ];
        }
    }

    /**
     * Verifikasi kode OTP 6-digit yang diinput pengguna.
     */
    public function verifyOtp(User $user, string $purpose, string $inputCode): array
    {
        $cleanCode = preg_replace('/\s+/', '', trim($inputCode));

        $record = EmailVerificationCode::where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->orderBy('id', 'desc')
            ->first();

        if (!$record) {
            return [
                'valid' => false,
                'message' => 'Kode verifikasi OTP belum diminta atau sudah terpakai. Silakan kirim ulang kode OTP.',
            ];
        }

        if ($record->isExpired()) {
            $record->update(['used_at' => now()]);
            return [
                'valid' => false,
                'message' => 'Kode OTP telah kedaluwarsa (melebihi 10 menit). Silakan minta kode OTP baru.',
            ];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->update(['used_at' => now()]);
            return [
                'valid' => false,
                'message' => 'Batas maksimal percobaan OTP tercapai. Silakan minta kode OTP baru.',
            ];
        }

        if (!Hash::check($cleanCode, $record->code_hash)) {
            $record->increment('attempts');
            $remaining = max(0, self::MAX_ATTEMPTS - $record->attempts);
            return [
                'valid' => false,
                'message' => $remaining > 0
                    ? "Kode OTP tidak sesuai. Sisa percobaan: {$remaining} kali."
                    : 'Kode OTP tidak sesuai dan batas percobaan telah habis. Silakan kirim ulang OTP baru.',
            ];
        }

        $record->update(['used_at' => now()]);

        return [
            'valid' => true,
            'message' => 'Kode OTP berhasil diverifikasi.',
        ];
    }

    /**
     * Masking email untuk ditampilkan secara aman di layar verifikasi.
     */
    public function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $visible = strlen($local) <= 2 ? substr($local, 0, 1) : substr($local, 0, 2);
        return $visible . str_repeat('*', max(3, strlen($local) - strlen($visible))) . '@' . $domain;
    }
}
