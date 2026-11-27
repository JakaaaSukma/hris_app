<?php

namespace Tests\Feature;

use App\Mail\TwoFactorOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Mail::fake();
    }

    public function test_login_requires_two_step_email_otp_verification()
    {
        $user = User::factory()->create([
            'email' => 'karyawan.2fa@example.com',
            'password' => Hash::make('Secret123!'),
            'role' => 'karyawan',
            'department' => 'Finance',
        ]);

        // Langkah 1: Submit email & password -> tidak langsung login, dialihkan ke halaman OTP
        $res = $this->post(route('login.post'), [
            'email' => 'karyawan.2fa@example.com',
            'password' => 'Secret123!',
        ]);

        $res->assertRedirect(route('login.otp.form'));
        $this->assertGuest();

        $capturedOtp = null;
        Mail::assertSent(TwoFactorOtpMail::class, function (TwoFactorOtpMail $mail) use ($user, &$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return $mail->hasTo($user->email) && $mail->purpose === 'login_2fa';
        });
        $this->assertNotNull($capturedOtp);
        $this->assertSame(6, strlen($capturedOtp));

        // Percobaan OTP salah ditolak & tetap guest
        $badRes = $this->post(route('login.otp.verify'), ['otp' => '000000']);
        $badRes->assertSessionHasErrors('otp');
        $this->assertGuest();

        // Langkah 2: Verifikasi OTP benar -> resmi login & masuk dashboard
        $okRes = $this->post(route('login.otp.verify'), ['otp' => $capturedOtp]);
        $okRes->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_self_service_forgot_password_with_email_otp()
    {
        $user = User::factory()->create([
            'email' => 'lupa.pwd@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        // Langkah 1: Minta kode OTP reset password
        $res = $this->post(route('password.emailOtp'), [
            'email' => 'lupa.pwd@example.com',
        ]);
        $res->assertRedirect(route('password.request'));

        $capturedOtp = null;
        Mail::assertSent(TwoFactorOtpMail::class, function (TwoFactorOtpMail $mail) use ($user, &$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return $mail->hasTo($user->email) && $mail->purpose === 'forgot_password';
        });
        $this->assertNotNull($capturedOtp);

        // Langkah 2: Reset password dengan OTP yang valid
        $resetRes = $this->post(route('password.resetWithOtp'), [
            'email' => 'lupa.pwd@example.com',
            'otp' => $capturedOtp,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $resetRes->assertRedirect(route('login'));
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456', $user->password));
    }

    public function test_profile_password_change_requires_email_otp_verification()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'profil.otp@example.com',
            'password' => Hash::make('CurrentPass123'),
        ]);

        // Mencoba ganti password tanpa OTP harus gagal
        $noOtpRes = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'profil.otp@example.com',
            'current_password' => 'CurrentPass123',
            'new_password' => 'UpdatedPass789',
            'new_password_confirmation' => 'UpdatedPass789',
        ]);
        $noOtpRes->assertSessionHasErrors('password_otp');
        $user->refresh();
        $this->assertTrue(Hash::check('CurrentPass123', $user->password));

        // Minta OTP terlebih dahulu
        $otpReq = $this->actingAs($user)->postJson(route('profile.password.sendOtp'), [
            'current_password' => 'CurrentPass123',
        ]);
        $otpReq->assertStatus(200)->assertJson(['success' => true]);

        $capturedOtp = null;
        Mail::assertSent(TwoFactorOtpMail::class, function (TwoFactorOtpMail $mail) use ($user, &$capturedOtp) {
            $capturedOtp = $mail->otpCode;
            return $mail->hasTo($user->email) && $mail->purpose === 'change_password';
        });
        $this->assertNotNull($capturedOtp);

        // Submit pergantian password dengan OTP valid
        $okRes = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'profil.otp@example.com',
            'current_password' => 'CurrentPass123',
            'password_otp' => $capturedOtp,
            'new_password' => 'UpdatedPass789',
            'new_password_confirmation' => 'UpdatedPass789',
        ]);
        $okRes->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('UpdatedPass789', $user->password));
    }
}
