<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login Masuk - Gyokai HR Enterprise</title>

    <!-- Favicon Icon Tab Browser -->
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}?v={{ time() }}"/>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ time() }}"/>

    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark/Light Theme Script -->
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

    <style>
        [x-cloak] { display: none !important; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
    </style>
</head>
<body class="bg-[#0b0e14] font-body text-slate-200 antialiased min-h-screen flex items-center justify-center p-4 selection:bg-rose-500 selection:text-white"
      x-data="{
          email: '{{ old('email', '') }}',
          password: '',
          showForgotModal: false,
          isDarkMode: localStorage.getItem('gyokai_theme') !== 'light',
          toggleTheme() {
              this.isDarkMode = !this.isDarkMode;
              if (this.isDarkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('gyokai_theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('gyokai_theme', 'light');
              }
          }
      }">

    <!-- Theme toggle floating button in login screen -->
    <button @click="toggleTheme()" 
            type="button" 
            class="fixed top-5 right-5 p-2.5 rounded-2xl bg-white/10 dark:bg-slate-800/80 border border-slate-700/60 shadow-lg text-slate-400 hover:text-amber-400 backdrop-blur-md transition-all z-50 active:scale-95" 
            :title="isDarkMode ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'">
        <template x-if="isDarkMode">
            <span class="material-symbols-outlined text-xl text-amber-400">light_mode</span>
        </template>
        <template x-if="!isDarkMode">
            <span class="material-symbols-outlined text-xl text-indigo-500">dark_mode</span>
        </template>
    </button>

    <!-- Background glowing ambient effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -left-40 w-[500px] h-[500px] bg-rose-500/10 rounded-full blur-[140px]"></div>
        <div class="absolute -bottom-40 -right-40 w-[500px] h-[500px] bg-blue-500/10 rounded-full blur-[140px]"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/5 rounded-full blur-[160px]"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/5 border border-slate-700/80 p-2 shadow-xl shadow-rose-600/20 mb-3">
                <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight flex items-center justify-center gap-1.5">
                GYOKAI <span class="text-rose-500 font-extrabold">HR</span>
            </h1>
            <p class="font-mono text-xs text-slate-400 mt-1 uppercase tracking-widest font-semibold">Human Resource Information System</p>
        </div>

        <!-- Login Card with Glassmorphism -->
        <div class="glass-card rounded-3xl p-6 sm:p-8 backdrop-blur-2xl shadow-2xl">
            
            <div class="mb-6">
                <h2 class="font-headline font-bold text-lg text-white">Selamat Datang Kembali</h2>
                <p class="text-xs text-slate-400 mt-0.5">Silakan masuk menggunakan kredensial akun terdaftar Anda</p>
            </div>

            <!-- Alerts -->
            @if(session('success'))
                <div class="mb-4 flex items-center gap-2.5 p-3 bg-emerald-950/50 border border-emerald-500/30 rounded-xl text-emerald-300 text-xs">
                    <span class="material-symbols-outlined text-base text-emerald-400">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-red-950/50 border border-red-500/30 rounded-xl text-red-300 text-xs">
                    @foreach($errors->all() as $err)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm text-red-400">error</span>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Form -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Email Perusahaan</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-lg">alternate_email</span>
                        <input type="email" 
                               name="email" 
                               x-model="email"
                               required 
                               placeholder="nama@gyokai.com" 
                               class="w-full pl-10 pr-3 py-2 bg-surface-container-low border border-[#333539] rounded-lg text-sm text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-primary-container transition-all"/>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary">Kata Sandi</label>
                        <button type="button" @click="showForgotModal = true" class="text-[11px] font-mono text-rose-400 hover:text-rose-300 transition-colors">
                            Lupa Kata Sandi?
                        </button>
                    </div>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-lg">lock</span>
                        <input type="password" 
                               name="password" 
                               x-model="password"
                               required 
                               placeholder="••••••••" 
                               class="w-full pl-10 pr-3 py-2 bg-surface-container-low border border-[#333539] rounded-lg text-sm text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-primary-container transition-all"/>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <span class="flex items-center gap-1.5 text-slate-400 text-[11px] font-mono">
                        <span class="material-symbols-outlined text-xs text-rose-500">lock</span>
                        <span>Sesi aman (Otomatis logout saat keluar)</span>
                    </span>
                    <span class="font-mono text-[11px] text-tertiary/70">Maks. 3 Perangkat</span>
                </div>

                <button type="submit" 
                        class="w-full mt-2 py-2.5 px-4 bg-primary-container hover:bg-red-700 text-on-primary-container rounded-lg font-semibold text-sm transition-all shadow-lg shadow-primary-container/20 flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">login</span>
                    <span>Masuk ke Dashboard</span>
                </button>
            </form>

        </div>

        <!-- Footer Notice -->
        <div class="text-center mt-6 text-xs text-slate-500 font-mono">
            <span>&copy; {{ date('Y') }} Gyokai HRIS • All Rights Reserved</span>
        </div>
    </div>

    <!-- Modal Lupa Kata Sandi (Zero Backdrop Blur for High Performance) -->
    <div x-show="showForgotModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80"
         @keydown.escape.window="showForgotModal = false">
        <div class="w-full max-w-md bg-[#121622] border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative"
             @click.away="showForgotModal = false">
            <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-2xl">lock_reset</span>
            </div>
            <h3 class="text-lg font-bold text-white mb-1.5 font-headline">Panduan Reset Kata Sandi</h3>
            <p class="text-xs text-slate-400 leading-relaxed mb-5">
                Demi standar keamanan data karyawan internal PT. Gyokai Indonesia, pengaturan ulang kata sandi dilakukan secara terverifikasi melalui Tim HR & IT Support.
            </p>

            <div class="space-y-3 p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-xs font-mono text-slate-300 mb-6">
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-sm text-rose-400 mt-0.5">mail</span>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase">Hubungi Email HR</div>
                        <span class="font-bold text-white">hr@gyokai.co.id</span>
                    </div>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="material-symbols-outlined text-sm text-emerald-400 mt-0.5">badge</span>
                    <div>
                        <div class="text-[10px] text-slate-500 uppercase">Informasi yang Diperlukan</div>
                        <span>Sertakan Nama Lengkap, NIP, & Departemen Anda</span>
                    </div>
                </div>
            </div>

            <button type="button" 
                    @click="showForgotModal = false" 
                    class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition-all border border-slate-700 active:scale-95">
                Saya Mengerti, Tutup
            </button>
        </div>
    </div>

</body>
</html>
