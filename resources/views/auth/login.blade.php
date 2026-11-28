<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login Masuk - Gyokai HR Enterprise</title>

    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>

    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"/>
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
<body class="bg-[var(--color-page-bg)] font-body text-[var(--color-page-text)] antialiased min-h-screen flex flex-col selection:bg-rose-600 selection:text-white"
      x-data="{
          email: '{{ old('email', '') }}',
          password: '',
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

    {{-- Top Minimal Utility Bar --}}
    <header class="w-full px-6 py-4 flex items-center justify-between border-b border-[var(--color-card-border)] bg-[var(--color-card-bg)]">
        <div class="flex items-center gap-3">
            <img src="{{ asset('images/gyokai_logo.png') }}" alt="Logo Gyokai HR" width="32" height="32" class="w-8 h-8 object-contain"/>
            <span class="font-headline font-extrabold text-sm tracking-tight text-[var(--color-text-primary)]">
                GYOKAI <span class="text-rose-500">HR</span>
            </span>
        </div>

        <div class="flex items-center gap-4">
            <x-skiper-link href="{{ route('password.request') }}" variant="000" class="hidden sm:inline-flex text-xs font-medium text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
                Pemulihan Akun 2FA
            </x-skiper-link>
            <button @click="toggleTheme()"
                    type="button"
                    aria-label="Ubah tema terang atau gelap"
                    class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors active:scale-95">
                <x-animated-icon type="theme" dark-expr="isDarkMode" />
            </button>
        </div>
    </header>

    {{-- Editorial Split Container --}}
    <main class="flex-1 grid grid-cols-1 lg:grid-cols-12 w-full" data-skiper-scroll-container>
        {{-- Left Editorial Monolith with Skiper19 SVG Path --}}
        <section class="lg:col-span-6 relative flex flex-col justify-between p-8 sm:p-12 lg:p-16 border-b lg:border-b-0 lg:border-r border-[var(--color-card-border)] bg-[var(--color-subcard-bg)] overflow-hidden">
            <x-scroll-flow variant="orbital" initial-progress="0.72" class="absolute inset-0 opacity-90" />

            <div class="relative z-10 max-w-lg space-y-6 my-auto py-6" data-reveal>
                <span class="inline-block font-mono text-xs font-semibold text-rose-500 tracking-wide">
                    Portal Internal Perusahaan
                </span>

                <h1 class="font-headline font-extrabold text-3xl sm:text-4xl lg:text-[2.75rem] leading-[1.12] tracking-tight text-[var(--color-text-primary)] text-balance">
                    Operasional SDM, Keuangan &amp; Direksi dalam Satu Ruangan Kerja.
                </h1>

                <p class="text-sm sm:text-base text-[var(--color-text-secondary)] leading-relaxed max-w-md">
                    Kelola presensi karyawan, pengajuan cuti berjenjang, pelacakan invoice jatuh tempo, kontrak klien, dan evaluasi KPI lintas divisi dengan autentikasi 2 langkah.
                </p>

                <div class="pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 border-t border-[var(--color-card-border)] text-xs">
                    <div>
                        <div class="font-headline font-bold text-[var(--color-text-primary)] mb-1">Verifikasi OTP 2 Langkah</div>
                        <p class="text-[var(--color-text-muted)]">Kode 6-digit dikirim ke email terdaftar untuk menjaga akses akun dan pergantian sandi.</p>
                    </div>
                    <div>
                        <div class="font-headline font-bold text-[var(--color-text-primary)] mb-1">Kontrol Sesi Perangkat</div>
                        <p class="text-[var(--color-text-muted)]">Mendukung maksimal 3 perangkat aktif dengan pemantauan sesi real-time dari halaman profil.</p>
                    </div>
                </div>
            </div>

            <div class="relative z-10 pt-6 flex flex-wrap items-center justify-between gap-4 text-xs font-mono text-[var(--color-text-muted)]">
                <span>&copy; {{ date('Y') }} LPK Gyokai Indonesia</span>
                <x-skiper-link href="{{ route('password.request') }}" variant="001" class="text-rose-500 font-semibold">
                    Reset Kata Sandi Mandiri
                </x-skiper-link>
            </div>
        </section>

        {{-- Right Auth Card Column --}}
        <section class="lg:col-span-6 flex items-center justify-center p-6 sm:p-10 lg:p-16 bg-[var(--color-page-bg)]">
            <div class="w-full max-w-md space-y-6" data-reveal>
                <div class="glass-card rounded-2xl p-7 sm:p-9">
                    <div class="mb-6 space-y-1.5">
                        <h2 class="font-headline font-bold text-2xl text-[var(--color-text-primary)] tracking-tight">Selamat Datang Kembali</h2>
                        <p class="text-xs sm:text-sm text-[var(--color-text-muted)]">Masuk menggunakan akun Gyokai HR terdaftar Anda</p>
                    </div>

                    @if(session('success'))
                        <div role="status" aria-live="polite" class="mb-5 flex items-center gap-2.5 p-3.5 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs">
                            <span class="material-symbols-outlined text-base shrink-0" aria-hidden="true">check_circle</span>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" aria-live="assertive" class="mb-5 p-3.5 bg-red-950/40 border border-red-500/30 rounded-xl text-rose-400 text-xs space-y-1">
                            @foreach($errors->all() as $err)
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-sm shrink-0" aria-hidden="true">error</span>
                                    <span>{{ $err }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                        @csrf

                        <div>
                            <label for="login-email" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-1.5">Email Perusahaan</label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3.5 top-3 text-[var(--color-text-muted)] text-lg pointer-events-none" aria-hidden="true">alternate_email</span>
                                <input id="login-email"
                                       type="email"
                                       name="email"
                                       x-model="email"
                                       autocomplete="email"
                                       spellcheck="false"
                                       required
                                       placeholder="nama@gyokai.com"
                                       class="w-full min-h-[44px] pl-10 pr-3.5 py-2.5 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-sm text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] focus:border-rose-500 transition-colors"/>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="login-password" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)]">Kata Sandi</label>
                                <x-skiper-link href="{{ route('password.request') }}" variant="000" class="text-xs font-medium text-rose-500">
                                    Lupa Kata Sandi?
                                </x-skiper-link>
                            </div>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-3.5 top-3 text-[var(--color-text-muted)] text-lg pointer-events-none" aria-hidden="true">lock</span>
                                <input id="login-password"
                                       type="password"
                                       name="password"
                                       x-model="password"
                                       autocomplete="current-password"
                                       required
                                       placeholder="••••••••"
                                       class="w-full min-h-[44px] pl-10 pr-3.5 py-2.5 bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl text-sm text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] focus:border-rose-500 transition-colors"/>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)] pt-1">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-xs text-rose-500" aria-hidden="true">verified_user</span>
                                <span>Autentikasi 2FA Aktif</span>
                            </span>
                            <span>Maks. 3 Perangkat</span>
                        </div>

                        <button type="submit"
                                class="group w-full min-h-[46px] mt-2 py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white keep-white rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2 active:scale-[0.99]">
                            <span>Masuk ke Dashboard</span>
                            <x-animated-icon type="arrow" />
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
