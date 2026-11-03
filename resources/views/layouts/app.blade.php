<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'GYOKAI HR') - Enterprise HRIS</title>

    <!-- Favicon Icon Tab Browser -->
    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}?v={{ time() }}"/>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ time() }}"/>

    <!-- Google Fonts with font-display: swap for instant rendering -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet"/>

    <!-- Vite CSS & JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark/Light Theme Script (Instant Execution to prevent flash of wrong theme) -->
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

        /* LIGHTWEIGHT MODAL OPTIMIZATION:
           Backdrop-blur causes heavy GPU repaints and frame drops during transitions.
           We override backdrop-blur on fixed overlay modals and use pure CSS opacity transitions */
        .fixed.inset-0.z-50 {
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            contain: layout style;
        }

        /* Hardware accelerated modal card without heavy redraw */
        .fixed.inset-0.z-50 > div {
            will-change: transform, opacity;
            transform: translateZ(0);
        }

        /* Instant, snappy modal appearance without delay */
        [x-transition] {
            transition-duration: 75ms !important;
        }

        /* HARDWARE STICKY HEADER FIX:
           Menggunakan sticky alih-alih fixed agar header mengambil ruang fisik dokumen,
           sehingga konten halaman apa pun (kalender, dashboard, laporan) tidak akan pernah
           tertutup oleh header bar saat di-scroll ke paling atas. */
        header.app-topbar {
            position: sticky !important;
            top: 0 !important;
            z-index: 30 !important;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[#0f1117] font-body text-slate-200 antialiased min-h-screen flex flex-col selection:bg-rose-500 selection:text-white" x-data="{
    mobileMenuOpen: false,
    showClockInModal: false,
    showLeaveModal: false,
    showEmployeeModal: false,
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

    <!-- Mobile Overlay Backdrop -->
    <div x-cloak 
         x-show="mobileMenuOpen" 
         @click="mobileMenuOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 z-40 lg:hidden"></div>
         
    <!-- Sidebar Navigation (Desktop Fixed & Mobile Slide Drawer with Smooth Scrollable Menu) -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-[#141822] z-50 flex flex-col border-r border-slate-800 shadow-2xl transition-transform duration-200 ease-in-out lg:translate-x-0"
           :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        
        <!-- Brand / Logo & Close Mobile Button (Fixed Top) -->
        <div class="h-16 px-4 flex items-center justify-between bg-[#161b26]/90 border-b border-slate-800/80 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/5 border border-slate-700/80 p-1 flex items-center justify-center shadow-md">
                    <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
                </div>
                <div class="flex flex-col">
                    <span class="font-headline font-extrabold text-[15px] tracking-wide text-white flex items-center gap-1.5">
                        GYOKAI <span class="text-rose-500 font-extrabold">HR</span>
                    </span>
                    <span class="font-mono text-[9px] text-slate-400 uppercase tracking-widest font-semibold">Human Resource System</span>
                </div>
            </div>

            <!-- Tombol Tutup Sidebar di Mobile -->
            <button type="button" 
                    @click="mobileMenuOpen = false" 
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 lg:hidden transition-colors"
                    title="Tutup Menu">
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>

        <!-- Scrollable Navigation Items Container -->
        <div class="flex-1 overflow-y-auto min-h-0 custom-scrollbar">
            <!-- Main Nav Items -->
            <nav class="flex flex-col gap-1 px-3 py-4">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px]">grid_view</span>
                    <span>Dashboard</span>
                </a>

                <div class="flex flex-col gap-1">
                    <a href="{{ route('attendance.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('attendance.index') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px]">schedule</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Kehadiran</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-slate-800 text-slate-300 border border-slate-700/60 rounded-md">Log</span>
                        </div>
                    </a>
                    <a href="{{ route('attendance.excuses.index') }}" 
                       class="flex items-center gap-2.5 pl-9 pr-3 py-1.5 rounded-lg text-[11px] font-medium transition-all {{ request()->routeIs('attendance.excuses.*') ? 'bg-amber-500/15 text-amber-300 font-bold border border-amber-500/25' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                        <span class="material-symbols-outlined text-[14px] text-amber-400">event_upcoming</span>
                        <span>Izin & Dispensasi</span>
                    </a>
                </div>

                <!-- Kalender & Catatan/Jadwal: Tersedia untuk SEMUA akun -->
                <a href="{{ route('calendar.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('calendar.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('calendar.*') ? 'text-white' : 'text-rose-400' }}">calendar_month</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Kalender & Jadwal</span>
                        <span class="text-[9px] font-mono px-1.5 py-0.5 bg-rose-500/10 text-rose-300 border border-rose-500/20 rounded-md">Agenda</span>
                    </div>
                </a>

                <!-- Tugas / Tasks: Skala Tim & Individu / Executive Monitoring -->
                <a href="{{ route('tasks.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('tasks.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('tasks.*') ? 'text-white' : 'text-indigo-400' }}">task</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>{{ auth()->user() && auth()->user()->isDirektur() ? 'Monitoring Tugas' : 'Tugas & Progres' }}</span>
                        @if(auth()->user() && auth()->user()->isDirektur())
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md">Direksi</span>
                        @endif
                    </div>
                </a>

                <!-- Cuti & Izin: Seluruh karyawan & manager dapat mengajukan -->
                <a href="{{ route('leave.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('leave.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('leave.*') ? 'text-white' : 'text-rose-400' }}">event_busy</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Cuti & Izin</span>
                        @if(auth()->user() && auth()->user()->canApproveLeaves())
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-md">Review</span>
                        @endif
                    </div>
                </a>

                <!-- Chat Menu: Dropdown untuk Manager (Chat Departemen & Chat Direksi) -->
                @if(auth()->user()->isManager())
                    <div x-data="{ chatMenuOpen: {{ request()->routeIs('chat.*') ? 'true' : 'false' }} }" class="flex flex-col">
                        <button @click="chatMenuOpen = !chatMenuOpen" 
                                type="button"
                                class="flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('chat.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('chat.*') ? 'text-white' : 'text-cyan-400' }}">forum</span>
                                <span>Menu Chat</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                    Manager
                                </span>
                                <span class="material-symbols-outlined text-sm transition-transform duration-150" :class="chatMenuOpen ? 'rotate-180' : ''">expand_more</span>
                            </div>
                        </button>

                        <!-- Sub-menu Dropdown untuk Manager -->
                        <div x-show="chatMenuOpen" x-cloak class="flex flex-col gap-1 pl-4 pt-1 pb-1 border-l border-slate-800 ml-5 mt-1">
                            <!-- 1. Chat Departemen -->
                            <a href="{{ route('chat.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-all {{ request()->routeIs('chat.*') && request('tab') !== 'direksi' ? 'bg-rose-500/20 text-white font-semibold border border-rose-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                <span class="material-symbols-outlined text-[16px] {{ request()->routeIs('chat.*') && request('tab') !== 'direksi' ? 'text-cyan-400' : 'text-slate-500' }}">groups</span>
                                <span>Chat Departemen</span>
                            </a>

                            <!-- 2. Chat Sekretaris Direktur -->
                            <a href="{{ route('chat.index', ['tab' => 'direksi']) }}" 
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-medium transition-all {{ request('tab') === 'direksi' ? 'bg-amber-500/20 text-white font-semibold border border-amber-500/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/40' }}">
                                <div class="flex items-center gap-2.5">
                                    <span class="material-symbols-outlined text-[16px] {{ request('tab') === 'direksi' ? 'text-amber-400' : 'text-slate-500' }}">support_agent</span>
                                    <span>Sekretaris Direktur</span>
                                </div>
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Chat Departemen (Karyawan) / Chat Eksekutif (Direktur & Sekretaris) -->
                    <a href="{{ route('chat.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('chat.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('chat.*') ? 'text-white' : ((auth()->user()->isDirektur() || auth()->user()->isSekretaris()) ? 'text-amber-400' : 'text-cyan-400') }}">
                            {{ auth()->user()->isDirektur() ? 'support_agent' : (auth()->user()->isSekretaris() ? 'hub' : 'forum') }}
                        </span>
                        <div class="flex items-center justify-between flex-1">
                            <span>{{ auth()->user()->isDirektur() ? 'Sekretaris Direktur' : (auth()->user()->isSekretaris() ? 'Koordinasi Eksekutif' : 'Chat Departemen') }}</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md {{ (auth()->user()->isDirektur() || auth()->user()->isSekretaris()) ? 'bg-amber-500/10 text-amber-300 border border-amber-500/20' : 'bg-cyan-500/10 text-cyan-300 border border-cyan-500/20' }}">
                                {{ auth()->user()->isDirektur() ? 'Sekretaris' : (auth()->user()->isSekretaris() ? 'Direksi & Mgr' : (auth()->user()->department ?? 'Tim')) }}
                            </span>
                        </div>
                    </a>
                @endif

                <!-- Keuangan & Slip Gaji: Pelaporan Slip Gaji & Reimbursement -->
                <a href="{{ route('finance.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('finance.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-600/25 border border-emerald-400/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('finance.*') ? 'text-white' : 'text-emerald-400' }}">payments</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Keuangan & Slip Gaji</span>
                        @if(auth()->user() && auth()->user()->canApproveReimbursements())
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-md">Finance</span>
                        @endif
                    </div>
                </a>

                <!-- Laporan Kerusakan Fasilitas GA: Bisa diakses seluruh karyawan & manager -->
                <a href="{{ route('facilities.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('facilities.*') ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg shadow-amber-600/25 border border-amber-400/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('facilities.*') ? 'text-white' : 'text-amber-400' }}">handyman</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Laporan Kendala (GA)</span>
                        @if(auth()->user() && auth()->user()->canControlGA())
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md">
                                {{ auth()->user()->isAsistenManager() ? 'Asisten Mgr' : (auth()->user()->isGA() ? 'Tim GA' : (auth()->user()->isManager() ? 'Manager HR' : 'Direktur')) }}
                            </span>
                        @endif
                    </div>
                </a>

                <!-- Notifikasi Saya: Tersedia untuk SEMUA akun -->
                <a href="{{ route('notifications.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('notifications.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('notifications.*') ? 'text-white' : 'text-rose-400' }}">notifications</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Notifikasi</span>
                        @php
                            $sidebarUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                        @endphp
                        @if($sidebarUnreadCount > 0)
                            <span class="text-[9px] font-mono px-1.5 py-0.5 bg-rose-500 text-white font-bold rounded-full">
                                {{ $sidebarUnreadCount }}
                            </span>
                        @endif
                    </div>
                </a>

                <!-- Pengaturan Profil Saya (Nama, Email, Password, Foto) -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('profile.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('profile.*') ? 'text-white' : 'text-sky-400' }}">manage_accounts</span>
                    <span>Pengaturan Profil</span>
                </a>

                <!-- Panduan & Alur Sistem (Tersedia untuk SEMUA akun) -->
                <a href="{{ route('guide.index') }}" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('guide.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                    <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('guide.*') ? 'text-white' : 'text-teal-400' }}">menu_book</span>
                    <div class="flex items-center justify-between flex-1">
                        <span>Panduan & Alur</span>
                        <span class="text-[9px] font-mono px-1.5 py-0.5 bg-teal-500/10 text-teal-300 border border-teal-500/20 rounded-md">SOP</span>
                    </div>
                </a>

                <!-- SECTION: MODUL KHUSUS DEPARTEMEN -->
                <div class="pt-4 pb-1.5 px-3">
                    <span class="text-[9px] font-mono uppercase tracking-widest text-slate-500 font-semibold">Modul Divisi</span>
                </div>

                <!-- 1. Modul HR & Direktur (Direktori Pegawai, Rekrutmen, Offboarding, Paklaring) -->
                @if(auth()->user() && auth()->user()->canAccessHRFeatures())
                    <a href="{{ route('employees.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('employees.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('employees.*') ? 'text-white' : 'text-pink-400' }}">badge</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Direktori Pegawai</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-pink-500/10 text-pink-300 border border-pink-500/20">
                                Staff
                            </span>
                        </div>
                    </a>

                    <a href="{{ route('hr.recruitment.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('hr.recruitment.*') ? 'bg-gradient-to-r from-rose-800/90 to-pink-800/70 text-white shadow-md border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] text-pink-400">person_search</span>
                        <span>Rekrutmen & Onboarding</span>
                    </a>

                    <a href="{{ route('hr.offboarding.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('hr.offboarding.*') ? 'bg-gradient-to-r from-purple-800/90 to-indigo-800/70 text-white shadow-md border border-purple-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] text-purple-400">exit_to_app</span>
                        <span>Offboarding Karyawan</span>
                    </a>

                    <a href="{{ route('hr.paklaring.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('hr.paklaring.*') ? 'bg-gradient-to-r from-amber-600 to-rose-600 text-white shadow-md border border-amber-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] text-amber-400">workspace_premium</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Surat Keterangan / Paklaring</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/20">LPK</span>
                        </div>
                    </a>
                @endif

                <!-- 2. Bisnis Development Department (Deals Pipeline) -->
                @if(auth()->user() && auth()->user()->isBisnisDevelopment())
                    <a href="{{ route('bizdev.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('bizdev.*') ? 'bg-gradient-to-r from-blue-600 to-cyan-600 text-white shadow-lg shadow-blue-600/30 border border-blue-400/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] text-blue-400">trending_up</span>
                        <span>Pipeline Deals (BizDev)</span>
                    </a>
                @endif

                <!-- 3. General Affairs (Aset & Inventaris GA - Bagian dari Divisi HR & GA) -->
                @if(auth()->user() && (auth()->user()->canAccessGAFeatures() || auth()->user()->canApproveInventoryRequests()))
                    <a href="{{ route('ga.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('ga.*') ? 'bg-gradient-to-r from-amber-600 to-orange-600 text-white shadow-lg shadow-amber-600/30 border border-amber-400/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] text-amber-400">inventory_2</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Inventaris & Aset (GA)</span>
                            @if(auth()->user()->isHR() && auth()->user()->isManager())
                                <span class="text-[9px] font-mono px-1.5 py-0.5 bg-rose-500/20 text-rose-300 border border-rose-500/30 rounded-md">Manager HR</span>
                            @elseif(auth()->user()->isAsistenManager())
                                <span class="text-[9px] font-mono px-1.5 py-0.5 bg-violet-500/20 text-violet-300 border border-violet-500/30 rounded-md">Asisten Mgr</span>
                            @elseif(auth()->user()->isGA())
                                <span class="text-[9px] font-mono px-1.5 py-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-md">Divisi GA</span>
                            @elseif(auth()->user()->canApproveInventoryRequests())
                                <span class="text-[9px] font-mono px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded-md">Finance</span>
                            @endif
                        </div>
                    </a>
                @endif

                <!-- 4. Resepsionis / Front Desk (Bank Data Member Gyokai) -->
                @if(auth()->user() && (auth()->user()->isResepsionis() || auth()->user()->isDirektur()))
                    <a href="{{ route('receptionist.members.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('receptionist.members.*') ? 'bg-gradient-to-r from-rose-600/90 to-rose-600/70 text-white shadow-lg shadow-rose-600/25 border border-rose-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('receptionist.members.*') ? 'text-white' : 'text-rose-400' }}">groups</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Data Member</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                Gyokai
                            </span>
                        </div>
                    </a>
                @endif

                <!-- 5. Finance / Keuangan (Kirim Slip Gaji Massal / Email Dispatch) -->
                @if(auth()->user() && (auth()->user()->isFinance() || auth()->user()->isDirektur()))
                    <a href="{{ route('finance.dispatch.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('finance.dispatch.*') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-600/25 border border-emerald-400/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('finance.dispatch.*') ? 'text-white' : 'text-teal-400' }}">forward_to_inbox</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Kirim Slip Gaji</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-teal-500/20 text-teal-300 border border-teal-500/30">
                                Email
                            </span>
                        </div>
                    </a>
                @endif

                <!-- 6. Tata Persuratan Resmi (Sekretaris Direktur & Direktur Utama) -->
                @if(auth()->user() && auth()->user()->canManageExecutiveLetters())
                    <a href="{{ route('secretary.letters.index') }}" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition-all duration-75 {{ request()->routeIs('secretary.letters.*') ? 'bg-gradient-to-r from-amber-600 to-amber-700 text-white shadow-lg shadow-amber-600/25 border border-amber-500/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/50' }}">
                        <span class="material-symbols-outlined text-[20px] {{ request()->routeIs('secretary.letters.*') ? 'text-white' : 'text-amber-400' }}">mark_email_read</span>
                        <div class="flex items-center justify-between flex-1">
                            <span>Surat Keluar & Agenda</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.5 rounded-md bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                {{ auth()->user()->isSekretaris() ? 'Sekretaris' : 'Direksi' }}
                            </span>
                        </div>
                    </a>
                @endif
            </nav>
        </div>

        <!-- Footer / System status -->
        <div class="p-3 bg-[#11151f]/80 backdrop-blur-md m-3 rounded-xl border border-slate-800/80 flex flex-col gap-2 shadow-inner">
            <div class="flex items-center justify-between">
                <span class="font-headline text-[11px] text-white font-bold tracking-tight">Gyokai Core</span>
                <span class="font-mono text-[9px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-semibold">Online</span>
            </div>
            <div class="flex items-center gap-2 pt-1 border-t border-slate-800/60">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="font-mono text-[10px] text-slate-400">MySQL & ADMS Ready</span>
            </div>
        </div>
    </aside>

    <!-- Main Content wrapper (Responsive: full width on mobile, pl-64 on desktop) -->
    <div class="w-full lg:pl-64 min-h-screen flex flex-col">
        <!-- Top App Bar / Header (Sticky bar yang mengambil ruang fisik alami dokumen) -->
        <header class="app-topbar sticky top-0 w-full h-16 bg-[#11151f] border-b border-slate-800 z-30 px-4 sm:px-6 flex items-center justify-between gap-3 shrink-0">
            
            <!-- Left: Mobile Menu Toggle & Search Bar -->
            <div class="flex items-center gap-3 flex-1 max-w-md">
                <!-- Hamburger Menu Button (Mobile Only) -->
                <button type="button" 
                        @click="mobileMenuOpen = true"
                        class="p-2 rounded-xl text-slate-300 hover:text-white hover:bg-slate-800 lg:hidden border border-slate-700/60 shadow-sm active:scale-95 transition-all shrink-0"
                        title="Buka Navigasi Menu">
                    <span class="material-symbols-outlined text-2xl">menu</span>
                </button>

                <!-- Search Input -->
                <div class="w-full">
                    <form action="{{ route('employees.index') }}" method="GET" class="relative flex items-center">
                        <span class="material-symbols-outlined absolute left-3 text-slate-400 text-lg">search</span>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}"
                               placeholder="Cari karyawan, NIP, divisi..." 
                               class="w-full pl-9 pr-3 py-1.5 sm:py-2 bg-slate-900/60 border border-slate-700/60 focus:border-rose-500/60 focus:ring-2 focus:ring-rose-500/20 rounded-xl text-xs text-slate-200 placeholder:text-slate-500 focus:outline-none transition-all shadow-inner"/>
                    </form>
                </div>
            </div>

            <!-- Header Right Icons & User Profile -->
            <div class="flex items-center gap-4">
                <div class="hidden md:flex items-center gap-2 text-slate-400 font-mono text-xs bg-slate-900/40 px-3 py-1.5 rounded-lg border border-slate-800/80">
                    <span class="material-symbols-outlined text-sm text-rose-400">calendar_today</span>
                    <span id="header-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                </div>

                <div class="h-4 w-px bg-slate-800"></div>

                <!-- Presensi Biometrik Wajah (Face ID & Lokasi) untuk Seluruh Karyawan & Manager -->
                @if(auth()->check() && auth()->user()->canFaceClock())
                    <button @click="$dispatch('open-face-modal', { type: 'auto' })" 
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600/20 via-teal-600/20 to-emerald-600/20 hover:from-emerald-600/30 hover:to-teal-600/30 text-xs font-semibold text-emerald-300 transition-all border border-emerald-500/30 shadow-sm active:scale-95"
                            title="Presensi Wajah & Lokasi (Face ID + GPS)">
                        <span class="material-symbols-outlined text-base text-emerald-400">face_retouching_natural</span>
                        <span class="hidden sm:inline">Absen Face ID</span>
                    </button>
                @endif

                <!-- Clock in/out Quick Trigger (Hanya untuk Manager HR dan Direktur) -->
                @if(auth()->check() && auth()->user()->canQuickClock())
                    <button @click="showClockInModal = true" 
                            class="hidden sm:flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-rose-600/20 to-orange-600/20 hover:from-rose-600/30 hover:to-orange-600/30 text-xs font-semibold text-rose-300 transition-all border border-rose-500/30 shadow-sm active:scale-95"
                            title="Presensi Cepat Karyawan (Khusus Manager HR & Direktur)">
                        <span class="material-symbols-outlined text-base text-rose-400">fingerprint</span>
                        <span>Presensi Cepat</span>
                    </button>
                @endif

                <!-- Tombol Mode Terang / Gelap (Theme Toggle) -->
                <button @click="toggleTheme()" 
                        type="button"
                        class="p-2 rounded-xl text-slate-400 hover:text-amber-400 hover:bg-slate-800/80 transition-all flex items-center justify-center border border-slate-700/60 shadow-sm active:scale-95" 
                        :title="isDarkMode ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'">
                    <template x-if="isDarkMode">
                        <span class="material-symbols-outlined text-[19px] text-amber-400">light_mode</span>
                    </template>
                    <template x-if="!isDarkMode">
                        <span class="material-symbols-outlined text-[19px] text-indigo-500">dark_mode</span>
                    </template>
                </button>

                <!-- NOTIFICATION BELL & QUICK DROPDOWN (Tersedia untuk SEMUA akun) -->
                @php
                    if (auth()->check()) {
                        // Jalankan reminder otomatis saat user membuka aplikasi (idempotent, 1x per hari)
                        \App\Services\ReminderService::processReminders(auth()->id());
                    }
                    $authUserUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                    $authUserLatestNotifs = auth()->check() ? auth()->user()->notifications()->take(5)->get() : collect();
                @endphp
                <div class="relative" x-data="{ openNotif: false }">
                    <button @click="openNotif = !openNotif" 
                            type="button"
                            class="relative p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-all flex items-center justify-center border border-slate-700/60 shadow-sm active:scale-95"
                            title="Notifikasi Sistem">
                        <span class="material-symbols-outlined text-[20px] {{ $authUserUnreadCount > 0 ? 'text-rose-400' : 'text-slate-400' }}">notifications</span>
                        
                        @if($authUserUnreadCount > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-600 text-[9px] font-mono font-bold text-white ring-2 ring-[#11151f] shadow-md animate-pulse">
                                {{ $authUserUnreadCount > 99 ? '99+' : $authUserUnreadCount }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown Panel (snap transition, pure CSS) -->
                    <div x-cloak 
                         x-show="openNotif" 
                         @click.away="openNotif = false"
                         x-transition:enter="transition ease-out duration-75"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-[#141822] border border-slate-700 shadow-2xl z-50 overflow-hidden">
                        
                        <!-- Dropdown Header -->
                        <div class="p-3.5 bg-[#171d2a] border-b border-slate-800 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-rose-400 text-lg">notifications</span>
                                <span class="font-headline font-bold text-xs text-white">Notifikasi</span>
                                @if($authUserUnreadCount > 0)
                                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                        {{ $authUserUnreadCount }} Baru
                                    </span>
                                @endif
                            </div>

                            @if($authUserUnreadCount > 0)
                                <form action="{{ route('notifications.markAllRead') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[10px] font-mono text-slate-400 hover:text-rose-400 transition-colors">
                                        Tandai Semua Dibaca
                                    </button>
                                </form>
                            @endif
                        </div>

                        <!-- Dropdown Items -->
                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60">
                            @if($authUserLatestNotifs->isEmpty())
                                <div class="py-8 px-4 text-center">
                                    <span class="material-symbols-outlined text-2xl text-slate-600 block mb-1">notifications_off</span>
                                    <p class="text-xs text-slate-400">Belum ada notifikasi</p>
                                </div>
                            @else
                                @foreach($authUserLatestNotifs as $nItem)
                                    @php
                                        $nData = $nItem->data;
                                        $nUnread = is_null($nItem->read_at);
                                        $nIcon = $nData['icon'] ?? 'notifications';
                                        $nColor = $nData['color'] ?? 'rose';
                                        $nBg = match($nColor) {
                                            'emerald' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                                            'amber' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                                            'blue' => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
                                            'purple' => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
                                            default => 'bg-rose-500/15 text-rose-400 border-rose-500/30'
                                        };
                                    @endphp
                                    <a href="{{ route('notifications.read', $nItem->id) }}" 
                                       class="p-3 flex items-start gap-3 transition-colors {{ $nUnread ? 'bg-[#181d2a] hover:bg-[#1f2638]' : 'hover:bg-slate-800/40' }} block text-left">
                                        <span class="w-8 h-8 rounded-lg border flex items-center justify-center shrink-0 {{ $nBg }}">
                                            <span class="material-symbols-outlined text-base">{{ $nIcon }}</span>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <h5 class="font-headline font-bold text-xs truncate {{ $nUnread ? 'text-white' : 'text-slate-300' }}">
                                                    {{ $nData['title'] ?? 'Notifikasi' }}
                                                </h5>
                                                @if($nUnread)
                                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 shrink-0"></span>
                                                @endif
                                            </div>
                                            <p class="text-[11px] text-slate-400 line-clamp-2 mt-0.5 leading-snug">
                                                {{ $nData['message'] ?? '-' }}
                                            </p>
                                            <span class="text-[10px] font-mono text-slate-500 block mt-1">
                                                {{ $nItem->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>

                        <!-- Dropdown Footer -->
                        <div class="p-2.5 bg-[#12151d] border-t border-slate-800 text-center">
                            <a href="{{ route('notifications.index') }}" 
                               class="text-xs font-semibold text-rose-400 hover:text-rose-300 transition-colors flex items-center justify-center gap-1">
                                <span>Lihat Semua Notifikasi</span>
                                <span class="material-symbols-outlined text-xs">arrow_forward</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User profile & role badge -->
                <div class="flex items-center gap-3 pl-1">
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 group text-left transition-all" title="Klik untuk membuka Pengaturan Profil">
                        <div class="flex flex-col text-right hidden lg:flex">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="font-headline text-xs font-bold text-slate-200 group-hover:text-rose-400 transition-colors">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                                @php
                                    $role = auth()->user()->role ?? 'karyawan';
                                    $badgeColor = match($role) {
                                        'direktur' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                        'manager' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                        'asisten_manager', 'asisten manager' => 'bg-violet-500/20 text-violet-300 border-violet-500/30',
                                        default => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    };
                                    $roleLabel = match($role) {
                                        'asisten_manager', 'asisten manager' => 'Asisten Mgr',
                                        default => $role,
                                    };
                                @endphp
                                <span class="font-mono text-[9px] uppercase font-bold px-1.5 py-0.5 rounded border {{ $badgeColor }}">
                                    {{ $roleLabel }}
                                </span>
                                @if(auth()->user()->department)
                                    <span class="font-mono text-[9px] uppercase font-medium px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700/60">
                                        {{ auth()->user()->department }}{{ auth()->user()->sub_department ? ' • ' . auth()->user()->sub_department : '' }}
                                    </span>
                                @endif
                            </div>
                            <span class="font-mono text-[10px] text-slate-400">{{ auth()->user()->employee->role_title ?? (auth()->user()->department ?? 'Staff') }}</span>
                        </div>

                        <div class="relative group">
                            <img src="{{ auth()->user()->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                 alt="Avatar" 
                                 class="w-9 h-9 rounded-xl ring-2 ring-slate-700 group-hover:ring-rose-500/80 transition-all object-cover shadow-md"/>
                            <span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 bg-slate-900 rounded-full border border-slate-700 flex items-center justify-center text-[8px] text-rose-400">
                                <span class="material-symbols-outlined text-[10px]">settings</span>
                            </span>
                        </div>
                    </a>

                    <!-- Tombol Pengaturan Profil -->
                    <a href="{{ route('profile.edit') }}" 
                       class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-all {{ request()->routeIs('profile.*') ? 'text-rose-400 bg-slate-800' : '' }}" 
                       title="Pengaturan Profil & Akun">
                        <span class="material-symbols-outlined text-[19px]">manage_accounts</span>
                    </a>

                    <!-- Logout button -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-all" title="Logout dari Sesi">
                            <span class="material-symbols-outlined text-[19px]">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Main Body View Area (Responsive: px-4 sm:px-6 py-6 pb-16) -->
        <main class="w-full bg-[#111317] flex-1 px-4 sm:px-6 py-6 pb-16">
            <!-- Flash Message Alerts -->
            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 p-3.5 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm">
                    <span class="material-symbols-outlined text-emerald-400">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-center gap-3 p-3.5 bg-red-950/40 border border-red-500/30 rounded-xl text-red-300 text-sm">
                    <span class="material-symbols-outlined text-red-400">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 p-3.5 bg-red-950/40 border border-red-500/30 rounded-xl text-red-300 text-sm">
                    <div class="font-semibold mb-1">Periksa kembali data Anda:</div>
                    <ul class="list-disc list-inside text-xs space-y-0.5">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Quick Clock In / Clock Out Modal (Khusus Manager HR & Direktur) -->
    @if(auth()->check() && auth()->user()->canQuickClock())
    <div x-cloak x-show="showClockInModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-md p-6 shadow-2xl relative"
             @click.away="showClockInModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary-container text-2xl">fingerprint</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Pencatatan Presensi</h3>
                        <p class="text-xs text-tertiary">Rekam kehadiran real-time hari ini</p>
                    </div>
                </div>
                <button @click="showClockInModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Clock In Form -->
            <form action="{{ route('attendance.clockIn') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Pilih Karyawan</label>
                    <select name="employee_id" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                        @foreach(\App\Models\Employee::select('id', 'name', 'nip', 'role_title')->orderBy('name')->limit(50)->get() as $emp)
                            <option value="{{ $emp->id }}" {{ isset($currentUser) && $currentUser->id == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->nip }}) - {{ $emp->role_title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Mode Kerja</label>
                        <select name="work_mode" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-primary-container">
                            <option value="WFO">WFO (Office)</option>
                            <option value="WFH">WFH (Remote)</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Lokasi / Lantai</label>
                        <input type="text" name="location_name" value="HQ Jakarta - Lt. 5" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Catatan (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Catatan tugas harian atau kendala..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-primary-container"></textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="flex-1 py-2.5 px-4 bg-primary-container hover:bg-red-700 text-on-primary-container rounded-lg font-semibold text-sm transition-all shadow-md flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-lg">login</span>
                        <span>Clock In Masuk</span>
                    </button>
                </div>
            </form>

            <!-- Separator for Clock Out -->
            <div class="mt-5 pt-4 border-t border-[#282a2e]">
                <form action="{{ route('attendance.clockOut') }}" method="POST" class="flex items-center justify-between gap-3">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $currentUser->id ?? 1 }}">
                    <div class="text-xs text-tertiary">
                        Selesai jam kantor untuk <span class="text-on-surface font-semibold">{{ $currentUser->name ?? 'Siti Rahmawati' }}</span>?
                    </div>
                    <button type="submit" class="py-1.5 px-3 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg border border-[#333539] transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-amber-400">logout</span>
                        <span>Clock Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Biometric Face ID & Location Modal Component -->
    <x-face-attendance-modal />

    <!-- Real-time Clock Javascript -->
    <script>
        function updateClock() {
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                const now = new Date();
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    @stack('scripts')
</body>
</html>
