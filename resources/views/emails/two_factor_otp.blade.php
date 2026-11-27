<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kode Verifikasi Keamanan Gyokai HRIS</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:'Segoe UI', Arial, sans-serif; color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:540px; background-color:#ffffff; border-radius:16px; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.05);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#be123c,#881337); padding:24px 28px; color:#ffffff;">
                            <div style="font-size:11px; letter-spacing:1.5px; text-transform:uppercase; opacity:0.85; font-weight:700;">Gyokai HRIS Autentikator</div>
                            <h1 style="margin:6px 0 0 0; font-size:20px; font-weight:800;">Verifikasi Keamanan 2 Langkah</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px 0; font-size:14px;">Halo <strong>{{ $user->name }}</strong>,</p>
                            <p style="margin:0 0 20px 0; font-size:14px; line-height:1.6; color:#4b5563;">
                                @if($purpose === 'login_2fa')
                                    Terdapat permintaan <strong>login masuk</strong> ke akun HRIS Anda. Masukkan 6 digit kode verifikasi berikut untuk melanjutkan:
                                @elseif($purpose === 'forgot_password')
                                    Anda mengajukan permintaan <strong>reset kata sandi mandiri</strong>. Gunakan kode OTP di bawah ini untuk mengatur ulang kata sandi akun Anda:
                                @else
                                    Anda mengajukan permintaan <strong>pergantian kata sandi mandiri</strong> di pengaturan profil. Masukkan kode verifikasi berikut untuk mengonfirmasi:
                                @endif
                            </p>

                            <div style="background-color:#f8fafc; border:2px dashed #cbd5e1; border-radius:12px; padding:18px; text-align:center; margin:0 0 22px 0;">
                                <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#64748b; margin-bottom:6px; font-weight:600;">Kode Verifikasi OTP</div>
                                <div style="font-size:34px; font-weight:800; letter-spacing:8px; color:#be123c; font-family:'Courier New', monospace;">{{ $otpCode }}</div>
                                <div style="font-size:11px; color:#64748b; margin-top:8px;">Berlaku selama <strong>10 menit</strong> • Jangan bagikan kode ini kepada siapa pun.</div>
                            </div>

                            <div style="background-color:#fef2f2; border-left:4px solid #e11d48; padding:12px 14px; border-radius:6px; font-size:12px; color:#7f1d1d; line-height:1.5;">
                                <strong>Peringatan Keamanan:</strong> Jika Anda tidak merasa melakukan permintaan ini
                                @if($ipAddress) (dari IP: <code>{{ $ipAddress }}</code>) @endif,
                                abaikan email ini dan segera hubungi tim HR/IT serta perbarui kata sandi Anda.
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px; background-color:#f9fafb; border-top:1px solid #e5e7eb; font-size:11px; color:#6b7280; text-align:center;">
                            Email otomatis dari <strong>Gyokai HRIS Autentikator</strong> • LPK Gyokai Indonesia
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
