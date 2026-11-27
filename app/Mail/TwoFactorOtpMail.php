<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TwoFactorOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otpCode,
        public string $purpose,
        public ?string $ipAddress = null
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'login_2fa' => "[Gyokai HRIS] Kode Verifikasi Login (2FA): {$this->otpCode}",
            'forgot_password' => "[Gyokai HRIS] Kode Reset Password Mandiri: {$this->otpCode}",
            'change_password' => "[Gyokai HRIS] Kode Verifikasi Ganti Password: {$this->otpCode}",
            default => "[Gyokai HRIS] Kode Verifikasi Keamanan: {$this->otpCode}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.two_factor_otp',
        );
    }
}
