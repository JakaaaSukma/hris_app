<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Reset Kata Sandi Mandiri (2FA) - Gyokai HR Enterprise</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (function() {
            const savedTheme = localStorage.getItem('gyokai_theme');
            if (savedTheme === 'light') {
                document.documentElement.classList.remove('dark');
            } else {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-[var(--color-page-bg)] font-body text-[var(--color-page-text)] antialiased min-h-screen flex items-center justify-center p-4 selection:bg-rose-600 selection:text-white">

    <div class="w-full max-w-md relative z-10" data-reveal>
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[var(--color-card-bg)] border border-[var(--color-card-border)] p-2.5 mb-3">
                <img src="{{ asset('images/gyokai_logo.png') }}" alt="Logo Gyokai HR" width="36" height="36" class="w-full h-full object-contain"/>
            </div>
            <h1 class="font-headline font-extrabold text-xl text-[var(--color-text-primary)] tracking-tight">
                Reset Password Mandiri
            </h1>
            <p class="font-mono text-xs text-[var(--color-text-muted)] mt-1">Verifikasi 2 Langkah via Email SMTP</p>
        </div>

        <div class="glass-card rounded-2xl p-6 sm:p-8 space-y-5">

            @if(session('success'))
                <div role="status" aria-live="polite" class="flex items-center gap-2 p-3 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs">
                    <span class="material-symbols-outlined text-base shrink-0" aria-hidden="true">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" aria-live="assertive" class="p-3 bg-red-950/40 border border-red-500/30 rounded-xl text-rose-400 text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm shrink-0" aria-hidden="true">error</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Langkah 1: Minta OTP ke Email --}}
            <form action="{{ route('password.emailOtp') }}" method="POST" class="space-y-3 pb-4 border-b border-[var(--color-card-border)]">
                @csrf
                <label for="forgot-email" class="block text-xs font-mono font-bold uppercase text-rose-500">
                    1. Kirim Kode OTP ke Email Terdaftar
                </label>
                <div class="flex gap-2">
                    <input id="forgot-email"
                           type="email"
                           name="email"
                           autocomplete="email"
                           spellcheck="false"
                           value="{{ old('email', $pendingEmail ?? '') }}"
                           required
                           placeholder="nama@gyokai.com"
                           class="flex-1 min-h-[42px] px-3 py-2 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-xs text-[var(--color-text-primary)] focus:border-rose-500"/>
                    <button type="submit" class="min-h-[42px] px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white keep-white rounded-xl text-xs font-bold shrink-0 transition-colors">
                        Kirim OTP
                    </button>
                </div>
                @if($maskedEmail)
                    <p class="text-[11px] text-emerald-500 font-mono">OTP aktif untuk: {{ $maskedEmail }}</p>
                @endif
            </form>

            {{-- Langkah 2: Verifikasi OTP & Set Password Baru --}}
            <form action="{{ route('password.resetWithOtp') }}" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', $pendingEmail ?? '') }}">

                <div class="text-xs font-mono font-bold uppercase text-emerald-500">
                    2. Verifikasi OTP &amp; Kata Sandi Baru
                </div>

                <div>
                    <label for="reset-otp" class="block text-xs font-mono text-[var(--color-text-muted)] mb-1">Kode OTP (6 Digit dari Email)</label>
                    <input id="reset-otp"
                           type="text"
                           name="otp"
                           inputmode="numeric"
                           maxlength="6"
                           autocomplete="one-time-code"
                           required
                           placeholder="000000"
                           class="w-full min-h-[44px] py-2 px-3 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-center text-lg font-mono font-bold tracking-[0.4em] tabular-nums text-[var(--color-text-primary)] focus:border-emerald-500"/>
                </div>

                <div>
                    <label for="reset-password" class="block text-xs font-mono text-[var(--color-text-muted)] mb-1">Kata Sandi Baru</label>
                    <input id="reset-password"
                           type="password"
                           name="password"
                           autocomplete="new-password"
                           required
                           minlength="6"
                           placeholder="Minimal 6 karakter…"
                           class="w-full min-h-[42px] py-2 px-3 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-xs text-[var(--color-text-primary)] focus:border-emerald-500"/>
                </div>

                <div>
                    <label for="reset-password-confirm" class="block text-xs font-mono text-[var(--color-text-muted)] mb-1">Konfirmasi Kata Sandi Baru</label>
                    <input id="reset-password-confirm"
                           type="password"
                           name="password_confirmation"
                           autocomplete="new-password"
                           required
                           minlength="6"
                           placeholder="Ulangi kata sandi baru…"
                           class="w-full min-h-[42px] py-2 px-3 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-xs text-[var(--color-text-primary)] focus:border-emerald-500"/>
                </div>

                <button type="submit" class="w-full min-h-[44px] py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white keep-white rounded-xl font-bold text-xs transition-colors flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">lock_reset</span>
                    <span>Verifikasi OTP &amp; Simpan Password Baru</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <x-skiper-link href="{{ route('login') }}" variant="000" class="text-xs font-mono text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
                    Kembali ke Halaman Login
                </x-skiper-link>
            </div>
        </div>
    </div>
</body>
</html>
