<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Reset Kata Sandi Mandiri (2FA) - Gyokai HR Enterprise</title>
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
<body class="bg-[#0b0e14] font-body text-slate-200 antialiased min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md relative z-10">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/5 border border-slate-700/80 p-2 shadow-xl shadow-rose-600/20 mb-3">
                <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
            </div>
            <h1 class="font-headline font-extrabold text-xl text-white tracking-tight">
                Reset Password Mandiri
            </h1>
            <p class="font-mono text-[11px] text-slate-400 mt-1 uppercase tracking-widest">Verifikasi 2 Langkah via Email SMTP</p>
        </div>

        <div class="rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-800 bg-[#121622] space-y-5">

            @if(session('success'))
                <div class="flex items-center gap-2 p-3 bg-emerald-950/50 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs">
                    <span class="material-symbols-outlined text-base text-emerald-400">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="p-3 bg-red-950/50 border border-red-500/30 rounded-xl text-red-300 text-xs space-y-1">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-red-400">error</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Langkah 1: Minta OTP ke Email -->
            <form action="{{ route('password.emailOtp') }}" method="POST" class="space-y-3 pb-4 border-b border-slate-800">
                @csrf
                <div class="text-[11px] font-mono font-bold uppercase text-rose-400 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">looks_one</span>
                    <span>Kirim Kode OTP ke Email Terdaftar</span>
                </div>
                <div class="flex gap-2">
                    <input type="email"
                           name="email"
                           value="{{ old('email', $pendingEmail ?? '') }}"
                           required
                           placeholder="nama@gyokai.com"
                           class="flex-1 px-3 py-2 bg-[#0b0e14] border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500"/>
                    <button type="submit" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shrink-0 transition-colors">
                        Kirim OTP
                    </button>
                </div>
                @if($maskedEmail)
                    <p class="text-[11px] text-emerald-400 font-mono">OTP aktif untuk: {{ $maskedEmail }}</p>
                @endif
            </form>

            <!-- Langkah 2: Verifikasi OTP & Set Password Baru -->
            <form action="{{ route('password.resetWithOtp') }}" method="POST" class="space-y-3.5">
                @csrf
                <input type="hidden" name="email" value="{{ old('email', $pendingEmail ?? '') }}">

                <div class="text-[11px] font-mono font-bold uppercase text-emerald-400 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">looks_two</span>
                    <span>Verifikasi OTP & Kata Sandi Baru</span>
                </div>

                <div>
                    <label class="block text-[11px] font-mono text-slate-400 mb-1">Kode OTP (6 Digit dari Email)</label>
                    <input type="text"
                           name="otp"
                           inputmode="numeric"
                           maxlength="6"
                           required
                           placeholder="000000"
                           class="w-full py-2 px-3 bg-[#0b0e14] border border-slate-700 rounded-xl text-center text-lg font-mono font-bold tracking-[0.4em] text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <div>
                    <label class="block text-[11px] font-mono text-slate-400 mb-1">Kata Sandi Baru</label>
                    <input type="password"
                           name="password"
                           required
                           minlength="6"
                           placeholder="Minimal 6 karakter"
                           class="w-full py-2 px-3 bg-[#0b0e14] border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <div>
                    <label class="block text-[11px] font-mono text-slate-400 mb-1">Konfirmasi Kata Sandi Baru</label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           minlength="6"
                           placeholder="Ulangi kata sandi baru"
                           class="w-full py-2 px-3 bg-[#0b0e14] border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold text-xs shadow-lg transition-all flex items-center justify-center gap-1.5">
                    <span class="material-symbols-outlined text-base">lock_reset</span>
                    <span>Verifikasi OTP & Simpan Password Baru</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="{{ route('login') }}" class="text-xs font-mono text-slate-400 hover:text-white inline-flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    <span>Kembali ke Halaman Login</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
