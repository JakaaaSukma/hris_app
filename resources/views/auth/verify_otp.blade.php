<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Verifikasi 2 Langkah (OTP) - Gyokai HR Enterprise</title>
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
<body class="bg-[var(--color-page-bg)] font-body text-[var(--color-page-text)] antialiased min-h-screen flex items-center justify-center p-4 selection:bg-rose-600 selection:text-white"
      x-data="{
          cooldown: 45,
          init() {
              const t = setInterval(() => {
                  if (this.cooldown > 0) this.cooldown--;
                  else clearInterval(t);
              }, 1000);
          }
      }">

    <div class="w-full max-w-md relative z-10" data-reveal>
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[var(--color-card-bg)] border border-[var(--color-card-border)] p-2.5 mb-3">
                <img src="{{ asset('images/gyokai_logo.png') }}" alt="Logo Gyokai HR" width="36" height="36" class="w-full h-full object-contain"/>
            </div>
            <h1 class="font-headline font-extrabold text-xl text-[var(--color-text-primary)] tracking-tight">
                GYOKAI <span class="text-rose-500">2FA</span>
            </h1>
            <p class="font-mono text-xs text-[var(--color-text-muted)] mt-1">Verifikasi 2 Langkah Email</p>
        </div>

        <div class="glass-card rounded-2xl p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-5">
                <div class="w-11 h-11 rounded-xl bg-rose-600/10 border border-rose-500/25 text-rose-500 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">mark_email_read</span>
                </div>
                <div>
                    <h2 class="font-headline font-bold text-base text-[var(--color-text-primary)]">Masukkan Kode OTP</h2>
                    <p class="text-xs text-[var(--color-text-muted)]">Dikirim ke <strong class="font-mono text-emerald-500">{{ $maskedEmail }}</strong></p>
                </div>
            </div>

            @if(session('success'))
                <div role="status" aria-live="polite" class="mb-4 flex items-center gap-2 p-3 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs">
                    <span class="material-symbols-outlined text-base shrink-0" aria-hidden="true">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" aria-live="assertive" class="mb-4 p-3 bg-red-950/40 border border-red-500/30 rounded-xl text-rose-400 text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm shrink-0" aria-hidden="true">error</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.otp.verify') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="otp-code" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-2">Kode Verifikasi (6 Digit)</label>
                    <input id="otp-code"
                           type="text"
                           name="otp"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           maxlength="6"
                           autofocus
                           required
                           autocomplete="one-time-code"
                           placeholder="000000"
                           class="w-full min-h-[54px] py-3 px-4 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-center text-2xl font-mono font-extrabold tracking-[0.6em] tabular-nums text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)]/40 focus:border-rose-500 transition-colors"/>
                    <p class="text-[11px] text-[var(--color-text-muted)] font-mono mt-2">Kode OTP berlaku selama 10 menit.</p>
                </div>

                <button type="submit"
                        class="group w-full min-h-[46px] py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white keep-white rounded-xl font-bold text-sm transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">verified_user</span>
                    <span>Verifikasi &amp; Masuk Dashboard</span>
                    <x-animated-icon type="arrow" />
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-[var(--color-card-border)] flex items-center justify-between gap-2 text-xs">
                <form action="{{ route('login.otp.resend') }}" method="POST">
                    @csrf
                    <button type="submit"
                            :disabled="cooldown > 0"
                            :class="cooldown > 0 ? 'text-[var(--color-text-muted)] cursor-not-allowed' : 'text-emerald-500 hover:text-emerald-400'"
                            class="group min-h-[40px] font-mono font-semibold flex items-center gap-1.5 transition-colors tabular-nums">
                        <x-animated-icon type="sync" />
                        <span x-show="cooldown > 0" x-text="'Kirim ulang (' + cooldown + 's)'"></span>
                        <span x-show="cooldown === 0">Kirim Ulang OTP</span>
                    </button>
                </form>

                <form action="{{ route('login.otp.cancel') }}" method="POST">
                    @csrf
                    <button type="submit" class="group min-h-[40px] font-mono text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] flex items-center gap-1 transition-colors">
                        <x-animated-icon type="arrow-left" />
                        <span>Ganti Akun</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
