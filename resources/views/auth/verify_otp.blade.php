<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Verifikasi 2 Langkah (OTP) - Gyokai HR Enterprise</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
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
<body class="bg-[#0b0e14] font-body text-slate-200 antialiased min-h-screen flex items-center justify-center p-4"
      x-data="{
          cooldown: 45,
          init() {
              const t = setInterval(() => {
                  if (this.cooldown > 0) this.cooldown--;
                  else clearInterval(t);
              }, 1000);
          }
      }">

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/5 border border-slate-700/80 p-2 shadow-xl shadow-rose-600/20 mb-3">
                <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
            </div>
            <h1 class="font-headline font-extrabold text-xl text-white tracking-tight">
                GYOKAI <span class="text-rose-500">2FA</span>
            </h1>
            <p class="font-mono text-[11px] text-slate-400 mt-1 uppercase tracking-widest">Verifikasi 2 Langkah Email</p>
        </div>

        <div class="glass-card rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-800 bg-[#121622]">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">mark_email_read</span>
                </div>
                <div>
                    <h2 class="font-headline font-bold text-base text-white">Masukkan Kode OTP</h2>
                    <p class="text-xs text-slate-400">Dikirim ke <strong class="font-mono text-emerald-400">{{ $maskedEmail }}</strong></p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 flex items-center gap-2 p-3 bg-emerald-950/50 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs">
                    <span class="material-symbols-outlined text-base text-emerald-400">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-950/50 border border-red-500/30 rounded-xl text-red-300 text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-red-400">error</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form action="{{ route('login.otp.verify') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-2">Kode Verifikasi (6 Digit)</label>
                    <input type="text"
                           name="otp"
                           inputmode="numeric"
                           pattern="[0-9]{6}"
                           maxlength="6"
                           autofocus
                           required
                           autocomplete="one-time-code"
                           placeholder="000000"
                           class="w-full py-3 px-4 bg-[#0b0e14] border border-slate-700 rounded-xl text-center text-2xl font-mono font-extrabold tracking-[0.6em] text-white placeholder:text-slate-700 focus:outline-none focus:border-rose-500"/>
                    <p class="text-[11px] text-slate-400 font-mono mt-2">Kode OTP berlaku selama 10 menit.</p>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-rose-600/20 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">verified_user</span>
                    <span>Verifikasi & Masuk Dashboard</span>
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-800 flex items-center justify-between gap-2 text-xs">
                <form action="{{ route('login.otp.resend') }}" method="POST">
                    @csrf
                    <button type="submit"
                            :disabled="cooldown > 0"
                            :class="cooldown > 0 ? 'text-slate-500 cursor-not-allowed' : 'text-emerald-400 hover:text-emerald-300'"
                            class="font-mono font-semibold flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-sm">refresh</span>
                        <span x-show="cooldown > 0" x-text="'Kirim ulang (' + cooldown + 's)'"></span>
                        <span x-show="cooldown === 0">Kirim Ulang OTP</span>
                    </button>
                </form>

                <form action="{{ route('login.otp.cancel') }}" method="POST">
                    @csrf
                    <button type="submit" class="font-mono text-slate-400 hover:text-white flex items-center gap-1 transition-colors">
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        <span>Ganti Akun</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
