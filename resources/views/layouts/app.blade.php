<!DOCTYPE html>
<html class="dark" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'GYOKAI HR') - Enterprise HRIS</title>

    <link rel="icon" type="image/png" href="{{ asset('images/gyokai_logo.png') }}"/>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"/>

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

    <style>
        :root {
            --color-page-bg: #f6f7f9;
            --color-page-text: #0f172a;
            --color-sidebar-bg: #ffffff;
            --color-sidebar-border: #dfe4ee;
            --color-header-bg: #ffffff;
            --color-header-border: #dfe4ee;
            --color-card-bg: #ffffff;
            --color-card-border: #dfe4ee;
            --color-subcard-bg: #eef1f6;
            --color-subcard-border: #cbd5e1;
            --color-input-bg: #ffffff;
            --color-input-border: #cbd5e1;
            --color-text-primary: #0f172a;
            --color-text-secondary: #334155;
            --color-text-muted: #475569;
            --color-table-header-bg: #f1f5f9;
            --color-table-row-hover: #f8fafc;
            --color-table-divider: #e2e8f0;
            --accent-primary: #dc2626;
        }
        html.dark {
            --color-page-bg: #0B0D11;
            --color-page-text: #f1f5f9;
            --color-sidebar-bg: #121620;
            --color-sidebar-border: #252B38;
            --color-header-bg: #121620;
            --color-header-border: #252B38;
            --color-card-bg: #161A23;
            --color-card-border: #252B38;
            --color-subcard-bg: #1C212C;
            --color-subcard-border: #2E3647;
            --color-input-bg: #0F131B;
            --color-input-border: #2E3647;
            --color-text-primary: #f8fafc;
            --color-text-secondary: #cbd5e1;
            --color-text-muted: #94a3b8;
            --color-table-header-bg: #121620;
            --color-table-row-hover: #1D222E;
            --color-table-divider: #252B38;
            --accent-primary: #e11d48;
        }
        [x-cloak] { display: none !important; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
        }
        aside[aria-label="Navigasi Utama"] {
            background-color: var(--color-sidebar-bg);
            border-color: var(--color-sidebar-border);
        }
        header.app-topbar {
            position: sticky !important;
            top: 0 !important;
            z-index: 40 !important;
            background-color: var(--color-header-bg);
            border-bottom: 1px solid var(--color-header-border);
        }
        main#main-content {
            background-color: var(--color-page-bg);
        }
        #global-nav-search {
            background-color: var(--color-subcard-bg);
            border-color: var(--color-card-border);
            color: var(--color-text-primary);
        }
        .app-notif-dropdown {
            background-color: var(--color-card-bg);
            border-color: var(--color-card-border);
        }
        .app-notif-chrome,
        .app-notif-icon {
            background-color: var(--color-subcard-bg);
            border-color: var(--color-card-border);
        }
        .app-notif-list > * + * {
            border-color: var(--color-table-divider);
        }
        .app-topbar-pill {
            background-color: var(--color-subcard-bg);
            border-color: var(--color-card-border);
        }
        .app-topbar-btn {
            border-color: var(--color-card-border);
        }
        .editorial-surface,
        .glass-card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-card-border);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-[var(--color-page-bg)] font-body text-[var(--color-page-text)] antialiased min-h-screen flex flex-col selection:bg-rose-600 selection:text-white"
      x-data="{
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
      }"
      @keydown.escape.window="mobileMenuOpen = false; showClockInModal = false">

    {{-- Keyboard Skip Link --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-rose-600 focus:text-white focus:text-xs focus:font-semibold">
        Lewati ke konten utama
    </a>

    {{-- Mobile Overlay Backdrop --}}
    <div x-cloak
         x-show="mobileMenuOpen"
         @click="mobileMenuOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/75 z-40 lg:hidden"
         aria-hidden="true"></div>

    {{-- Sidebar Navigation --}}
    <aside aria-label="Navigasi Utama"
           class="fixed left-0 top-0 h-full w-64 bg-[var(--color-sidebar-bg)] z-50 flex flex-col border-r border-[var(--color-sidebar-border)] transition-transform duration-200 ease-out lg:translate-x-0"
           :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

        {{-- Brand Header --}}
        <div class="h-16 px-4 flex items-center justify-between border-b border-[var(--color-sidebar-border)] shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group min-w-0">
                <div class="w-9 h-9 rounded-xl bg-rose-600/10 border border-rose-500/25 p-1.5 flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/gyokai_logo.png') }}"
                         alt="Logo Gyokai HR"
                         width="28"
                         height="28"
                         class="w-full h-full object-contain"/>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="font-headline font-extrabold text-sm tracking-tight text-[var(--color-text-primary)] truncate">
                        GYOKAI <span class="text-rose-500">HR</span>
                    </span>
                    <span class="font-mono text-[10px] text-[var(--color-text-muted)] tracking-wider truncate">Enterprise Portal</span>
                </div>
            </a>

            <button type="button"
                    @click="mobileMenuOpen = false"
                    aria-label="Tutup Menu Navigasi"
                    class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)] lg:hidden transition-colors">
                <x-animated-icon type="menu" open-expr="true" />
            </button>
        </div>

        @php
            $navBase = 'group relative flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-colors duration-150';
            $navInactive = 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)]';
            $navActive = 'nav-active keep-white bg-rose-600 text-white font-semibold shadow-sm';
        @endphp

        {{-- Scrollable Nav Items --}}
        <div class="flex-1 overflow-y-auto min-h-0">
            <nav class="flex flex-col gap-1 px-3 py-4" aria-label="Menu Modul">
                <a href="{{ route('dashboard') }}"
                   class="{{ $navBase }} {{ request()->routeIs('dashboard') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">grid_view</span>
                    <span class="flex-1 truncate">Dashboard</span>
                    <x-animated-icon type="arrow" class="opacity-0 group-hover:opacity-90 transition-opacity" />
                </a>

                <div class="flex flex-col gap-1">
                    <a href="{{ route('attendance.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('attendance.index') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">schedule</span>
                        <span class="flex-1 truncate">Kehadiran</span>
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/15 dark:bg-white/5 {{ request()->routeIs('attendance.index') ? 'text-white' : 'text-[var(--color-text-muted)]' }}">Log</span>
                    </a>
                    <a href="{{ route('attendance.excuses.index') }}"
                       class="group flex items-center gap-2.5 pl-9 pr-3 py-2 rounded-lg text-[11px] font-medium transition-colors {{ request()->routeIs('attendance.excuses.*') ? 'bg-rose-600/15 text-rose-500 font-semibold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)]' }}">
                        <span class="material-symbols-outlined text-[15px]" aria-hidden="true">event_upcoming</span>
                        <span class="flex-1 truncate">Izin &amp; Dispensasi</span>
                    </a>
                </div>

                <a href="{{ route('calendar.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('calendar.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">calendar_month</span>
                    <span class="flex-1 truncate">Kalender &amp; Jadwal</span>
                    <x-animated-icon type="arrow" class="opacity-0 group-hover:opacity-90 transition-opacity" />
                </a>

                <a href="{{ route('tasks.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('tasks.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">task_alt</span>
                    <span class="flex-1 truncate">{{ auth()->user() && auth()->user()->isDirektur() ? 'Monitoring Tugas' : 'Tugas &amp; Progres' }}</span>
                    @if(auth()->user() && auth()->user()->isDirektur())
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/15 dark:bg-white/5 {{ request()->routeIs('tasks.*') ? 'text-white' : 'text-[var(--color-text-muted)]' }}">Direksi</span>
                    @endif
                </a>

                <a href="{{ route('leave.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('leave.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">event_busy</span>
                    <span class="flex-1 truncate">Cuti &amp; Izin</span>
                    @if(auth()->user() && auth()->user()->canApproveLeaves())
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/15 dark:bg-white/5 {{ request()->routeIs('leave.*') ? 'text-white' : 'text-rose-500' }}">Review</span>
                    @endif
                </a>

                @if(auth()->user()->isManager())
                    <div x-data="{ chatMenuOpen: {{ request()->routeIs('chat.*') ? 'true' : 'false' }} }" class="flex flex-col">
                        <button @click="chatMenuOpen = !chatMenuOpen"
                                type="button"
                                :aria-expanded="chatMenuOpen.toString()"
                                class="{{ $navBase }} justify-between w-full text-left {{ request()->routeIs('chat.*') ? $navActive : $navInactive }}">
                            <span class="flex items-center gap-3 min-w-0">
                                <span class="material-symbols-outlined text-[19px]" aria-hidden="true">forum</span>
                                <span class="truncate">Menu Chat</span>
                            </span>
                            <span class="material-symbols-outlined text-base transition-transform duration-150" :class="chatMenuOpen ? 'rotate-180' : ''" aria-hidden="true">expand_more</span>
                        </button>

                        <div x-show="chatMenuOpen" x-cloak class="flex flex-col gap-1 pl-4 pt-1 pb-1 border-l border-[var(--color-sidebar-border)] ml-5 mt-1">
                            <a href="{{ route('chat.index') }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request()->routeIs('chat.*') && request('tab') !== 'direksi' ? 'bg-rose-600/15 text-rose-500 font-semibold' : $navInactive }}">
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">groups</span>
                                <span class="truncate">Chat Departemen</span>
                            </a>
                            <a href="{{ route('chat.index', ['tab' => 'direksi']) }}"
                               class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition-colors {{ request('tab') === 'direksi' ? 'bg-rose-600/15 text-rose-500 font-semibold' : $navInactive }}">
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">support_agent</span>
                                <span class="truncate">Sekretaris Direktur</span>
                            </a>
                        </div>
                    </div>
                @else
                    <a href="{{ route('chat.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('chat.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">
                            {{ auth()->user()->isDirektur() ? 'support_agent' : (auth()->user()->isSekretaris() ? 'hub' : 'forum') }}
                        </span>
                        <span class="flex-1 truncate">{{ auth()->user()->isDirektur() ? 'Sekretaris Direktur' : (auth()->user()->isSekretaris() ? 'Koordinasi Eksekutif' : 'Chat Departemen') }}</span>
                    </a>
                @endif

                <a href="{{ route('finance.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('finance.index') || request()->routeIs('finance.salarySlips.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">payments</span>
                    <span class="flex-1 truncate">Keuangan &amp; Slip Gaji</span>
                </a>

                <a href="{{ route('facilities.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('facilities.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">handyman</span>
                    <span class="flex-1 truncate">Laporan Kendala (GA)</span>
                </a>

                <a href="{{ route('notifications.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('notifications.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">notifications</span>
                    <span class="flex-1 truncate">Notifikasi</span>
                    @php
                        $sidebarUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                    @endphp
                    @if($sidebarUnreadCount > 0)
                        <span class="text-[10px] font-mono px-1.5 py-0.5 rounded-full bg-rose-600 text-white font-bold tabular-nums">
                            {{ $sidebarUnreadCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('profile.edit') }}"
                   class="{{ $navBase }} {{ request()->routeIs('profile.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">manage_accounts</span>
                    <span class="flex-1 truncate">Pengaturan Profil</span>
                </a>

                <a href="{{ route('guide.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('guide.*') ? $navActive : $navInactive }}">
                    <span class="material-symbols-outlined text-[19px]" aria-hidden="true">menu_book</span>
                    <span class="flex-1 truncate">Panduan &amp; Alur</span>
                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/15 dark:bg-white/5 {{ request()->routeIs('guide.*') ? 'text-white' : 'text-[var(--color-text-muted)]' }}">SOP</span>
                </a>

                {{-- Modul Divisi --}}
                <div class="pt-4 pb-1 px-3">
                    <span class="text-[10px] font-mono uppercase tracking-wider text-[var(--color-text-muted)] font-semibold">Modul Divisi</span>
                </div>

                @if(auth()->user() && auth()->user()->canAccessHRFeatures())
                    <a href="{{ route('employees.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('employees.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">badge</span>
                        <span class="flex-1 truncate">Direktori Pegawai</span>
                    </a>

                    <a href="{{ route('hr.recruitment.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('hr.recruitment.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">person_search</span>
                        <span class="flex-1 truncate">Rekrutmen &amp; Onboarding</span>
                    </a>

                    <a href="{{ route('hr.offboarding.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('hr.offboarding.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">exit_to_app</span>
                        <span class="flex-1 truncate">Offboarding Karyawan</span>
                    </a>

                    <a href="{{ route('hr.paklaring.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('hr.paklaring.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">workspace_premium</span>
                        <span class="flex-1 truncate">Surat Keterangan / Paklaring</span>
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->isBisnisDevelopment())
                    <a href="{{ route('bizdev.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('bizdev.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">trending_up</span>
                        <span class="flex-1 truncate">Pipeline Deals (BizDev)</span>
                    </a>
                @endif

                @if(auth()->user() && (auth()->user()->canAccessGAFeatures() || auth()->user()->canApproveInventoryRequests()))
                    <a href="{{ route('ga.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('ga.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">inventory_2</span>
                        <span class="flex-1 truncate">Inventaris &amp; Aset (GA)</span>
                    </a>
                @endif

                @if(auth()->user() && (auth()->user()->isResepsionis() || auth()->user()->isDirektur()))
                    <a href="{{ route('receptionist.members.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('receptionist.members.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">groups</span>
                        <span class="flex-1 truncate">Data Member</span>
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->canManageInvoices())
                    @php
                        $urgentInvoicesCount = \Illuminate\Support\Facades\Schema::hasTable('finance_invoices')
                            ? \App\Models\FinanceInvoice::whereIn('status', ['issued', 'partial'])
                                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())
                                ->count()
                            : 0;
                    @endphp
                    <a href="{{ route('finance.invoices.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('finance.invoices.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">receipt_long</span>
                        <span class="flex-1 truncate">Invoice &amp; Tagihan</span>
                        @if($urgentInvoicesCount > 0)
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-500 font-bold tabular-nums" title="{{ $urgentInvoicesCount }} Invoice jatuh tempo / segera jatuh tempo">
                                {{ $urgentInvoicesCount }} Tempo
                            </span>
                        @endif
                    </a>

                    <a href="{{ route('finance.dispatch.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('finance.dispatch.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">forward_to_inbox</span>
                        <span class="flex-1 truncate">Kirim Slip Gaji</span>
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->canManageExecutiveLetters())
                    <a href="{{ route('secretary.letters.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('secretary.letters.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">mark_email_read</span>
                        <span class="flex-1 truncate">Surat Keluar &amp; Agenda</span>
                    </a>
                @endif

                @if(auth()->user() && auth()->user()->canManageClientContracts())
                    @php
                        $expiringContractsCount = \Illuminate\Support\Facades\Schema::hasTable('client_contracts')
                            ? \App\Models\ClientContract::where('status', 'active')
                                ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                                ->count()
                            : 0;
                    @endphp
                    <a href="{{ route('secretary.contracts.index') }}"
                       class="{{ $navBase }} {{ request()->routeIs('secretary.contracts.*') ? $navActive : $navInactive }}">
                        <span class="material-symbols-outlined text-[19px]" aria-hidden="true">handshake</span>
                        <span class="flex-1 truncate">Kontrak &amp; MoU Klien</span>
                        @if($expiringContractsCount > 0)
                            <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-500 font-bold tabular-nums" title="{{ $expiringContractsCount }} Kontrak segera habis">
                                {{ $expiringContractsCount }}
                            </span>
                        @endif
                    </a>
                @endif
            </nav>
        </div>

        {{-- Compact System Footer --}}
        <div class="p-3 m-3 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] flex items-center justify-between gap-2">
            <div class="min-w-0">
                <span class="font-headline text-xs font-bold text-[var(--color-text-primary)] block truncate">Gyokai Core</span>
                <span class="font-mono text-[10px] text-[var(--color-text-muted)] block truncate">ADMS &amp; 2FA Aktif</span>
            </div>
            <span class="inline-flex items-center gap-1.5 font-mono text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-500 font-semibold shrink-0">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Online
            </span>
        </div>
    </aside>

    {{-- Main Workspace Container --}}
    <div class="w-full lg:pl-64 min-h-screen flex flex-col relative" data-skiper-scroll-container>
        {{-- Sticky Topbar Header --}}
        <header class="app-topbar sticky top-0 w-full h-16 bg-[var(--color-header-bg)] border-b border-[var(--color-header-border)] z-40 px-3 sm:px-6 flex items-center justify-between gap-2 sm:gap-3 shrink-0">
            {{-- Skiper19-style Horizontal Scroll Rail --}}
            <div id="top-scroll-rail"
                 aria-hidden="true"
                 class="pointer-events-none absolute left-0 bottom-0 h-[2px] w-full bg-rose-600 origin-left scale-x-0 transition-transform duration-75"></div>

            {{-- Left: Mobile Menu Morphing Toggle + Global Search --}}
            <div class="flex items-center gap-2 sm:gap-2.5 flex-1 max-w-md min-w-0">
                <button type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        aria-label="Buka atau tutup navigasi menu"
                        :aria-expanded="mobileMenuOpen.toString()"
                        class="app-topbar-btn min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)] lg:hidden border border-[var(--color-card-border)] transition-colors shrink-0">
                    <x-animated-icon type="menu" open-expr="mobileMenuOpen" />
                </button>

                <form action="{{ route('employees.index') }}" method="GET" class="relative flex items-center flex-1 min-w-0" role="search">
                    <label for="global-nav-search" class="sr-only">Cari karyawan, NIP, atau divisi</label>
                    <span class="material-symbols-outlined absolute left-3 text-[var(--color-text-muted)] text-[18px] pointer-events-none" aria-hidden="true">search</span>
                    <input id="global-nav-search"
                           type="search"
                           name="search"
                           autocomplete="off"
                           value="{{ request('search') }}"
                           placeholder="Cari pegawai…"
                           class="w-full min-w-0 min-h-[40px] pl-9 pr-3 xl:pr-12 py-1.5 bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] focus:border-rose-500 rounded-xl text-xs text-[var(--color-text-primary)] placeholder:text-[var(--color-text-muted)] transition-colors"/>
                    <kbd class="app-topbar-pill hidden xl:inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono text-[var(--color-text-muted)] bg-[var(--color-card-bg)] border border-[var(--color-card-border)] absolute right-2.5 pointer-events-none">⌘K</kbd>
                </form>
            </div>

            {{-- Right: Quick Presensi Triggers, Morph Theme Toggle, Notifications, Profile --}}
            <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                <div class="app-topbar-pill hidden xl:flex items-center gap-2 text-[var(--color-text-muted)] font-mono text-xs px-3 py-1.5 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] shrink-0">
                    <span class="material-symbols-outlined text-sm text-rose-500" aria-hidden="true">calendar_today</span>
                    <span id="header-date">{{ now()->locale('id')->translatedFormat('d M Y') }}</span>
                </div>

                @if(auth()->check() && auth()->user()->canFaceClock())
                    <button type="button"
                            @click="$dispatch('open-face-modal', { type: 'auto' })"
                            class="group min-h-[40px] hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600/10 hover:bg-emerald-600 hover:text-white text-xs font-semibold text-emerald-500 border border-emerald-500/30 transition-colors active:scale-[0.98]"
                            title="Presensi Wajah & Lokasi (Face ID + GPS)">
                        <span class="material-symbols-outlined text-[17px]" aria-hidden="true">face_retouching_natural</span>
                        <span>Absen Face ID</span>
                    </button>
                @endif

                @if(auth()->check() && auth()->user()->canQuickClock())
                    <button type="button"
                            @click="showClockInModal = true"
                            class="group min-h-[40px] hidden sm:flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-xs font-semibold text-white keep-white transition-colors active:scale-[0.98]"
                            title="Presensi Cepat Karyawan">
                        <span class="material-symbols-outlined text-[17px]" aria-hidden="true">fingerprint</span>
                        <span>Presensi Cepat</span>
                    </button>
                @endif

                {{-- Theme Toggle (Skiper Animated Theme SVG) --}}
                <button @click="toggleTheme()"
                        type="button"
                        aria-label="Ubah tema terang atau gelap"
                        class="app-topbar-btn min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] transition-colors active:scale-95"
                        :title="isDarkMode ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'">
                    <x-animated-icon type="theme" dark-expr="isDarkMode" />
                </button>

                {{-- Notifications Dropdown --}}
                @php
                    if (auth()->check()) {
                        \App\Services\ReminderService::processReminders(auth()->id());
                    }
                    $authUserUnreadCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0;
                    $authUserLatestNotifs = auth()->check() ? auth()->user()->notifications()->take(5)->get() : collect();
                @endphp
                <div class="relative" x-data="{ openNotif: false }" @keydown.escape.window="openNotif = false">
                    <button @click="openNotif = !openNotif"
                            type="button"
                            aria-label="Buka Notifikasi Sistem"
                            :aria-expanded="openNotif.toString()"
                            class="app-topbar-btn group relative min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)] hover:bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] transition-colors active:scale-95">
                        <x-animated-icon type="bell" class="{{ $authUserUnreadCount > 0 ? 'text-rose-500' : '' }}" />
                        @if($authUserUnreadCount > 0)
                            <span class="absolute top-1.5 right-1.5 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-rose-600 text-[9px] font-mono font-bold text-white tabular-nums">
                                {{ $authUserUnreadCount > 99 ? '99+' : $authUserUnreadCount }}
                            </span>
                        @endif
                    </button>

                    <div x-cloak
                         x-show="openNotif"
                         @click.away="openNotif = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="app-notif-dropdown absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-[var(--color-card-bg)] border border-[var(--color-card-border)] shadow-2xl z-50 overflow-hidden">

                        <div class="app-notif-chrome p-3.5 bg-[var(--color-subcard-bg)] border-b border-[var(--color-card-border)] flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="font-headline font-bold text-xs text-[var(--color-text-primary)]">Notifikasi</span>
                                @if($authUserUnreadCount > 0)
                                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-rose-600/15 text-rose-500 tabular-nums">
                                        {{ $authUserUnreadCount }} Baru
                                    </span>
                                @endif
                            </div>

                            @if($authUserUnreadCount > 0)
                                <form action="{{ route('notifications.markAllRead') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-mono text-[var(--color-text-muted)] hover:text-rose-500 transition-colors">
                                        Tandai Semua Dibaca
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="app-notif-list max-h-80 overflow-y-auto divide-y divide-[var(--color-table-divider)]">
                            @forelse($authUserLatestNotifs as $nItem)
                                @php
                                    $nData = $nItem->data;
                                    $nUnread = is_null($nItem->read_at);
                                    $nIcon = $nData['icon'] ?? 'notifications';
                                @endphp
                                <a href="{{ route('notifications.read', $nItem->id) }}"
                                   class="p-3.5 flex items-start gap-3 transition-colors {{ $nUnread ? 'bg-rose-600/5 hover:bg-rose-600/10' : 'hover:bg-[var(--color-subcard-bg)]' }} block text-left">
                                    <span class="app-notif-icon w-8 h-8 rounded-lg border border-[var(--color-card-border)] bg-[var(--color-subcard-bg)] text-rose-500 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $nIcon }}</span>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <h5 class="font-headline font-semibold text-xs truncate text-[var(--color-text-primary)]">
                                                {{ $nData['title'] ?? 'Notifikasi' }}
                                            </h5>
                                            @if($nUnread)
                                                <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0" aria-label="Belum dibaca"></span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] text-[var(--color-text-muted)] line-clamp-2 mt-0.5 leading-snug">
                                            {{ $nData['message'] ?? '' }}
                                        </p>
                                        <span class="text-[10px] font-mono text-[var(--color-text-muted)] block mt-1">
                                            {{ $nItem->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </a>
                            @empty
                                <div class="py-8 px-4 text-center">
                                    <p class="text-xs text-[var(--color-text-muted)]">Belum ada notifikasi baru.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="app-notif-chrome p-2.5 bg-[var(--color-subcard-bg)] border-t border-[var(--color-card-border)] text-center">
                            <x-skiper-link href="{{ route('notifications.index') }}" variant="000" class="text-xs font-semibold text-rose-500">
                                Lihat Semua Notifikasi
                            </x-skiper-link>
                        </div>
                    </div>
                </div>

                {{-- User Profile + Logout --}}
                <div class="flex items-center gap-2 pl-1">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2.5 group text-left p-1 rounded-xl hover:bg-[var(--color-subcard-bg)] transition-colors"
                       title="Pengaturan Profil Saya">
                        <div class="flex flex-col text-right hidden md:flex">
                            <div class="flex items-center justify-end gap-1.5">
                                <span class="font-headline text-xs font-bold text-[var(--color-text-primary)] group-hover:text-rose-500 transition-colors">{{ auth()->user()->name ?? 'Pengguna' }}</span>
                                @php
                                    $role = auth()->user()->role ?? 'karyawan';
                                    $roleLabel = match($role) {
                                        'sekretaris', 'sekretaris_direktur' => 'Sekretaris',
                                        'asisten_manager', 'asisten manager' => 'Asisten Mgr',
                                        default => ucfirst($role),
                                    };
                                @endphp
                                <span class="font-mono text-[10px] px-1.5 py-0.5 rounded bg-rose-600/15 text-rose-500 font-semibold">
                                    {{ $roleLabel }}
                                </span>
                            </div>
                            <span class="font-mono text-[10px] text-[var(--color-text-muted)]">{{ auth()->user()->employee->role_title ?? (auth()->user()->department ?? 'Staff') }}</span>
                        </div>
                        <img src="{{ auth()->user()->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}"
                             alt="{{ auth()->user()->name ?? 'Avatar Pengguna' }}"
                             width="36"
                             height="36"
                             class="w-9 h-9 rounded-xl border border-[var(--color-card-border)] object-cover shrink-0"/>
                    </a>

                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                aria-label="Logout dari sesi"
                                title="Keluar dari Akun"
                                class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-muted)] hover:text-rose-500 hover:bg-rose-600/10 transition-colors">
                            <span class="material-symbols-outlined text-[19px]" aria-hidden="true">logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Skiper19 SVG Follow-Scroll Architectural Track --}}
        <x-scroll-flow variant="ribbon" initial-progress="0.22" />

        {{-- Main Body --}}
        <main id="main-content" tabindex="-1" class="w-full bg-[var(--color-page-bg)] flex-1 px-4 sm:px-6 lg:pr-12 py-6 pb-16">
            @if(session('success'))
                <div role="status" aria-live="polite" class="mb-5 flex items-center gap-3 p-3.5 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-400 text-sm">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">check_circle</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div role="alert" aria-live="assertive" class="mb-5 flex items-center gap-3 p-3.5 bg-red-950/40 border border-red-500/30 rounded-xl text-rose-400 text-sm">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">error</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div role="alert" aria-live="assertive" class="mb-5 p-3.5 bg-red-950/40 border border-red-500/30 rounded-xl text-rose-400 text-sm">
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

    {{-- Quick Clock In / Clock Out Modal (Manager HR & Direktur) --}}
    @if(auth()->check() && auth()->user()->canQuickClock())
    <div x-cloak
         x-show="showClockInModal"
         role="dialog"
         aria-modal="true"
         aria-labelledby="quick-clock-title"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-[var(--color-card-bg)] rounded-2xl border border-[var(--color-card-border)] w-full max-w-md p-6 shadow-2xl relative"
             @click.away="showClockInModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[var(--color-card-border)]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-rose-500 text-2xl" aria-hidden="true">fingerprint</span>
                    <div>
                        <h3 id="quick-clock-title" class="font-headline font-semibold text-base text-[var(--color-text-primary)]">Pencatatan Presensi</h3>
                        <p class="text-xs text-[var(--color-text-muted)]">Rekam kehadiran real-time hari ini</p>
                    </div>
                </div>
                <button type="button"
                        @click="showClockInModal = false"
                        aria-label="Tutup dialog pencatatan presensi"
                        class="min-h-[44px] min-w-[44px] flex items-center justify-center rounded-xl text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
                    <span class="material-symbols-outlined text-xl" aria-hidden="true">close</span>
                </button>
            </div>

            <form action="{{ route('attendance.clockIn') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="quick-clock-emp" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-1.5">Pilih Karyawan</label>
                    <select id="quick-clock-emp" name="employee_id" class="w-full min-h-[42px] bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl px-3 py-2 text-sm text-[var(--color-text-primary)]">
                        @foreach(\App\Models\Employee::select('id', 'name', 'nip', 'role_title')->orderBy('name')->limit(50)->get() as $emp)
                            <option value="{{ $emp->id }}" {{ isset($currentUser) && $currentUser->id == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->nip }}) - {{ $emp->role_title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="quick-clock-mode" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-1.5">Mode Kerja</label>
                        <select id="quick-clock-mode" name="work_mode" class="w-full min-h-[42px] bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl px-3 py-2 text-sm text-[var(--color-text-primary)]">
                            <option value="WFO">WFO (Office)</option>
                            <option value="WFH">WFH (Remote)</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div>
                        <label for="quick-clock-loc" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-1.5">Lokasi / Lantai</label>
                        <input id="quick-clock-loc" type="text" name="location_name" value="HQ Jakarta - Lt. 5" class="w-full min-h-[42px] bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl px-3 py-2 text-sm text-[var(--color-text-primary)]"/>
                    </div>
                </div>

                <div>
                    <label for="quick-clock-notes" class="block text-xs font-mono uppercase tracking-wider text-[var(--color-text-muted)] mb-1.5">Catatan (Opsional)</label>
                    <textarea id="quick-clock-notes" name="notes" rows="2" placeholder="Catatan tugas harian atau kendala…" class="w-full bg-[var(--color-subcard-bg)] border border-[var(--color-input-border)] rounded-xl px-3 py-2 text-sm text-[var(--color-text-primary)]"></textarea>
                </div>

                <button type="submit" class="w-full min-h-[44px] py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white keep-white rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">login</span>
                    <span>Clock In Masuk</span>
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-[var(--color-card-border)]">
                <form action="{{ route('attendance.clockOut') }}" method="POST" class="flex items-center justify-between gap-3">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $currentUser->id ?? 1 }}">
                    <div class="text-xs text-[var(--color-text-muted)]">
                        Selesai jam kantor untuk <span class="text-[var(--color-text-primary)] font-semibold">{{ $currentUser->name ?? (auth()->user()->name ?? 'Karyawan') }}</span>?
                    </div>
                    <button type="submit" class="min-h-[40px] py-1.5 px-3 bg-[var(--color-subcard-bg)] hover:bg-rose-600 hover:text-white text-xs text-[var(--color-text-primary)] rounded-xl border border-[var(--color-card-border)] transition-colors flex items-center gap-1.5 shrink-0">
                        <span class="material-symbols-outlined text-sm text-amber-500" aria-hidden="true">logout</span>
                        <span>Clock Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <x-face-attendance-modal />

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
