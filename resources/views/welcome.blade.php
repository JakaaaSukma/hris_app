<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Gyokai HR Enterprise - Portal SDM &amp; Operasional Perusahaan</title>

    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
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
</head>
<body class="bg-[var(--color-page-bg)] font-body text-[var(--color-page-text)] antialiased min-h-screen flex flex-col selection:bg-rose-600 selection:text-white"
      x-data="{
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

    <header class="sticky top-0 z-30 w-full px-6 py-4 flex items-center justify-between border-b border-[var(--color-card-border)] bg-[var(--color-header-bg)]">
        <div id="top-scroll-rail" aria-hidden="true" class="pointer-events-none absolute left-0 bottom-0 h-[2px] w-full bg-rose-600 origin-left scale-x-0"></div>

        <div class="flex items-center gap-3">
            <img src="{{ asset('images/gyokai_logo.png') }}" alt="Logo Gyokai HR" width="32" height="32" class="w-8 h-8 object-contain"/>
            <span class="font-headline font-extrabold text-sm tracking-tight text-[var(--color-text-primary)]">
                GYOKAI <span class="text-rose-500">HR</span>
            </span>
        </div>

        <div class="flex items-center gap-3">
            <button @click="toggleTheme()"
                    type="button"
                    aria-label="Ubah tema terang atau gelap"
                    class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] transition-colors">
                <x-animated-icon type="theme" dark-expr="isDarkMode" />
            </button>

            @auth
                <a href="{{ route('dashboard') }}" class="group min-h-[44px] px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white keep-white text-xs font-semibold flex items-center gap-2 transition-colors">
                    <span>Masuk Dashboard</span>
                    <x-animated-icon type="arrow" />
                </a>
            @else
                <a href="{{ route('login') }}" class="group min-h-[44px] px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white keep-white text-xs font-semibold flex items-center gap-2 transition-colors">
                    <span>Masuk Portal HR</span>
                    <x-animated-icon type="arrow" />
                </a>
            @endauth
        </div>
    </header>

    <main class="flex-1 w-full max-w-6xl mx-auto px-4 sm:px-6 py-12 space-y-16 relative" data-skiper-scroll-container>
        <x-scroll-flow variant="ribbon" initial-progress="0.2" />

        {{-- Hero Editorial Section --}}
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center" data-reveal>
            <div class="lg:col-span-7 space-y-6">
                <span class="inline-block font-mono text-xs font-semibold text-rose-500 tracking-wide">
                    LPK Gyokai Indonesia
                </span>

                <h1 class="font-headline font-extrabold text-3xl sm:text-5xl leading-[1.1] tracking-tight text-[var(--color-text-primary)] text-balance">
                    Platform Terpadu SDM, Keuangan &amp; Tata Kelola Perusahaan.
                </h1>

                <p class="text-sm sm:text-base text-[var(--color-text-secondary)] leading-relaxed max-w-xl">
                    Kelola presensi karyawan, pengajuan cuti berjenjang, pelacakan invoice jatuh tempo, surat resmi, serta kontrak kerjasama dalam satu sistem dengan otorisasi peran berlapis dan verifikasi 2FA.
                </p>

                <div class="flex flex-wrap items-center gap-4 pt-2">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                       class="group min-h-[46px] px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white keep-white text-sm font-semibold flex items-center gap-2 transition-colors">
                        <span>{{ auth()->check() ? 'Buka Dashboard Aktif' : 'Masuk ke Akun Anda' }}</span>
                        <x-animated-icon type="arrow" />
                    </a>

                    <x-skiper-link href="{{ route('password.request') }}" variant="001" class="text-sm font-medium text-[var(--color-text-secondary)]">
                        Pemulihan Kata Sandi 2FA
                    </x-skiper-link>
                </div>
            </div>

            <div class="lg:col-span-5 relative rounded-2xl overflow-hidden editorial-surface p-6 min-h-[260px] flex flex-col justify-between">
                <x-scroll-flow variant="orbital" initial-progress="0.58" class="absolute inset-0 opacity-85" />
                <div class="relative z-10 space-y-2">
                    <span class="font-mono text-xs uppercase text-rose-500 font-semibold">Arsitektur Modul</span>
                    <h2 class="font-headline font-bold text-xl text-[var(--color-text-primary)]">Terstruktur per Divisi</h2>
                    <p class="text-xs text-[var(--color-text-muted)] leading-relaxed">
                        Setiap departemen memiliki hak akses khusus: HR &amp; Rekrutmen, Finance &amp; Invoice, General Affairs, Bisnis Development, Resepsionis, serta Sekretariat Direksi.
                    </p>
                </div>
                <div class="relative z-10 pt-4 border-t border-[var(--color-card-border)] flex items-center justify-between text-xs font-mono text-[var(--color-text-muted)]">
                    <span>Keamanan: SMTP 2FA OTP</span>
                    <span>Mode: Terang &amp; Gelap</span>
                </div>
            </div>
        </section>

        {{-- Skiper49 Inverted Perspective Showcase --}}
        <div data-reveal>
            <x-perspective-carousel
                heading="Cakupan Modul Utama HRIS"
                subheading="Geser kartu untuk melihat kapabilitas operasional utama yang tersedia di dalam portal.">
                @foreach([
                    ['tag' => 'Keamanan', 'title' => 'Autentikasi 2 Langkah (OTP)', 'desc' => 'Validasi login dan perubahan kata sandi mandiri menggunakan kode OTP email.', 'icon' => 'verified_user'],
                    ['tag' => 'HR & SDM', 'title' => 'Presensi Face ID & Cuti', 'desc' => 'Rekam kehadiran biometrik, izin dinas, dan persetujuan cuti anti self-approval.', 'icon' => 'how_to_reg'],
                    ['tag' => 'Finance', 'title' => 'Invoice & Slip Gaji', 'desc' => 'Penerbitan tagihan, pantauan jatuh tempo, pembayaran cicilan, serta slip gaji.', 'icon' => 'receipt_long'],
                    ['tag' => 'General Affairs', 'title' => 'Aset, ATK & Fasilitas', 'desc' => 'Pencatatan inventaris kantor, kontrol stok ATK, dan helpdesk perbaikan gedung.', 'icon' => 'inventory_2'],
                    ['tag' => 'Sekretariat', 'title' => 'Surat Keluar & Kontrak MoU', 'desc' => 'Penomoran surat resmi otomatis dan pengingat berakhirnya kontrak kemitraan.', 'icon' => 'handshake'],
                ] as $item)
                    <div class="swiper-slide">
                        <div class="rounded-2xl p-5 bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] flex flex-col justify-between gap-4 min-h-[210px]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="font-mono text-[10px] uppercase tracking-wider text-rose-500 font-semibold block">{{ $item['tag'] }}</span>
                                    <h3 class="font-headline font-bold text-base text-[var(--color-text-primary)] mt-1">{{ $item['title'] }}</h3>
                                </div>
                                <span class="w-9 h-9 rounded-xl bg-rose-600/10 text-rose-500 flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $item['icon'] }}</span>
                                </span>
                            </div>
                            <p class="text-xs text-[var(--color-text-muted)] leading-relaxed">{{ $item['desc'] }}</p>
                            <div class="pt-2 border-t border-[var(--color-card-border)]">
                                <x-skiper-link href="{{ auth()->check() ? route('dashboard') : route('login') }}" variant="000" class="text-xs font-semibold text-rose-500">
                                    Akses Modul
                                </x-skiper-link>
                            </div>
                        </div>
                    </div>
                @endforeach
            </x-perspective-carousel>
        </div>
    </main>

    <footer class="border-t border-[var(--color-card-border)] py-6 px-6 text-center text-xs font-mono text-[var(--color-text-muted)]">
        &copy; {{ date('Y') }} LPK Gyokai Indonesia • GYOKAI HR Enterprise
    </footer>
</body>
</html>
