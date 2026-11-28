@extends('layouts.app')

@section('title', 'Dashboard Operasional HR')

@section('content')
<div class="flex flex-col gap-7">

    {{-- Asymmetric Executive Command Masthead --}}
    <section class="editorial-surface rounded-2xl p-6 sm:p-7" data-reveal>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2.5 max-w-2xl">
                <div class="flex flex-wrap items-center gap-2 text-xs font-mono text-[var(--color-text-muted)]">
                    <span class="px-2 py-0.5 rounded bg-rose-600/15 text-rose-500 font-semibold uppercase">
                        {{ auth()->user()->role ?? 'karyawan' }}
                    </span>
                    <span aria-hidden="true">/</span>
                    <span>{{ auth()->user()->department ?? 'Operasional Umum' }}</span>
                    <span aria-hidden="true">/</span>
                    <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                    <span class="font-semibold text-[var(--color-text-primary)] tabular-nums" id="live-clock">{{ now()->format('H:i:s') }} WIB</span>
                </div>

                <h1 class="font-headline font-extrabold text-2xl sm:text-3xl text-[var(--color-text-primary)] tracking-tight text-balance">
                    Halo, {{ auth()->user()->name }}
                </h1>

                <div class="flex flex-wrap items-center gap-5 pt-1 text-xs">
                    <x-skiper-link href="{{ route('calendar.index') }}" variant="000" class="font-medium text-[var(--color-text-secondary)] hover:text-rose-500">
                        Buka Kalender Agenda
                    </x-skiper-link>
                    <x-skiper-link href="{{ route('tasks.index') }}" variant="000" class="font-medium text-[var(--color-text-secondary)] hover:text-rose-500">
                        Daftar Tugas Aktif
                    </x-skiper-link>
                    @if(auth()->user()->canManageInvoices())
                        <x-skiper-link href="{{ route('finance.invoices.index') }}" variant="001" class="font-semibold text-rose-500">
                            Invoice &amp; Jatuh Tempo
                        </x-skiper-link>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="{{ route('leave.index') }}"
                   class="group min-h-[44px] flex items-center gap-2 px-4 py-2.5 bg-[var(--color-subcard-bg)] hover:bg-[var(--color-card-border)] text-[var(--color-text-primary)] rounded-xl text-xs font-semibold transition-colors border border-[var(--color-card-border)]">
                    <span class="material-symbols-outlined text-base text-rose-500" aria-hidden="true">event_note</span>
                    <span>Pengajuan Cuti</span>
                </a>

                @if(auth()->user()->canFaceClock())
                    @if($userTodayAttendance)
                        @if(!$userTodayAttendance->clock_out)
                            <button type="button"
                                    @click="$dispatch('open-face-modal', { type: 'clock_out' })"
                                    class="min-h-[44px] flex items-center gap-2 px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white keep-white rounded-xl text-xs font-bold transition-colors active:scale-[0.98]">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">face_retouching_natural</span>
                                <span>Clock Out Face ID (In: {{ substr($userTodayAttendance->clock_in, 0, 5) }})</span>
                            </button>
                        @else
                            <div class="min-h-[44px] flex items-center gap-2 px-4 py-2.5 bg-emerald-600/15 text-emerald-500 rounded-xl text-xs font-mono font-semibold border border-emerald-500/30">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">verified</span>
                                <span>Presensi Lengkap: {{ substr($userTodayAttendance->clock_out, 0, 5) }} WIB</span>
                            </div>
                        @endif
                    @else
                        <button type="button"
                                @click="$dispatch('open-face-modal', { type: 'clock_in' })"
                                class="group min-h-[44px] flex items-center gap-2 px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white keep-white rounded-xl text-xs font-bold transition-colors active:scale-[0.98]">
                            <span class="material-symbols-outlined text-base" aria-hidden="true">face_retouching_natural</span>
                            <span>Presensi Face ID &amp; Lokasi</span>
                            <x-animated-icon type="arrow" />
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </section>

    {{-- Asymmetric Metrics Deck (HR Manager & Direktur) --}}
    @if(auth()->user()->canViewCompanyMetrics())
        <section aria-label="Ringkasan Kehadiran & Persetujuan Perusahaan" class="grid grid-cols-1 lg:grid-cols-12 gap-4" data-reveal>
            {{-- Spotlight Attendance Hero (5 Cols) --}}
            <div class="lg:col-span-5 editorial-surface rounded-2xl p-6 flex flex-col justify-between gap-4 border-l-4 border-l-rose-600">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <span class="font-mono text-xs text-[var(--color-text-muted)] uppercase tracking-wider font-semibold">Kehadiran Hari Ini</span>
                        <div class="mt-2 flex items-baseline gap-2.5">
                            <span class="font-headline text-4xl font-extrabold text-[var(--color-text-primary)] tabular-nums"
                                  data-count-to="{{ $presentTodayCount }}">{{ number_format($presentTodayCount) }}</span>
                            <span class="font-mono text-xs px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-500 font-bold tabular-nums">
                                {{ $attendanceRate }}% dari {{ number_format($totalEmployees) }}
                            </span>
                        </div>
                    </div>
                    <span class="w-10 h-10 rounded-xl bg-rose-600/10 text-rose-500 flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl" aria-hidden="true">how_to_reg</span>
                    </span>
                </div>

                <div class="space-y-2">
                    <div class="w-full bg-[var(--color-subcard-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-600 transition-all duration-500" style="width: {{ min(100, $attendanceRate) }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-xs font-mono text-[var(--color-text-muted)]">
                        <span>Tepat: <strong class="text-emerald-500 tabular-nums">{{ $onTimeCount }}</strong></span>
                        <span>Telat: <strong class="text-rose-500 tabular-nums">{{ $lateCount }}</strong></span>
                        <x-skiper-link href="{{ route('attendance.index') }}" variant="000" class="text-rose-500 font-semibold">
                            Log Lengkap
                        </x-skiper-link>
                    </div>
                </div>
            </div>

            {{-- Supporting Metric Strip (7 Cols) --}}
            <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="editorial-surface rounded-2xl p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase tracking-wider font-semibold">Total Karyawan</span>
                        <span class="material-symbols-outlined text-lg text-[var(--color-text-muted)]" aria-hidden="true">groups</span>
                    </div>
                    <div class="my-3">
                        <span class="font-headline text-3xl font-extrabold text-[var(--color-text-primary)] tabular-nums"
                              data-count-to="{{ $totalEmployees }}">{{ number_format($totalEmployees) }}</span>
                        <span class="text-xs text-[var(--color-text-muted)] font-mono ml-1">Personel</span>
                    </div>
                    <div class="pt-2.5 border-t border-[var(--color-card-border)] flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                        <span>Rekrutmen bulan ini</span>
                        <strong class="text-emerald-500 tabular-nums">+{{ $newEmployeesThisMonth }}</strong>
                    </div>
                </div>

                <div class="editorial-surface rounded-2xl p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase tracking-wider font-semibold">Cuti &amp; Dispensasi</span>
                        <span class="material-symbols-outlined text-lg text-[var(--color-text-muted)]" aria-hidden="true">beach_access</span>
                    </div>
                    <div class="my-3 flex items-baseline gap-2">
                        <span class="font-headline text-3xl font-extrabold text-[var(--color-text-primary)] tabular-nums"
                              data-count-to="{{ $leaveCount }}">{{ $leaveCount }}</span>
                        <span class="text-xs text-[var(--color-text-muted)] font-mono">Karyawan</span>
                    </div>
                    <div class="pt-2.5 border-t border-[var(--color-card-border)] flex items-center justify-between text-[11px] font-mono">
                        <span class="text-[var(--color-text-muted)]">{{ $totalEmployees > 0 ? round(($leaveCount / $totalEmployees) * 100, 1) : 0 }}% rasio</span>
                        <x-skiper-link href="{{ route('leave.index') }}" variant="000" class="text-rose-500 font-semibold">
                            Detail
                        </x-skiper-link>
                    </div>
                </div>

                <div class="editorial-surface rounded-2xl p-5 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase tracking-wider font-semibold">Perlu Persetujuan</span>
                        <span class="px-2 py-0.5 bg-rose-600/15 text-rose-500 font-mono text-[10px] rounded font-bold">Review</span>
                    </div>
                    <div class="my-3">
                        <span class="font-headline text-3xl font-extrabold text-[var(--color-text-primary)] tabular-nums"
                              data-count-to="{{ $pendingApprovals->count() }}">{{ $pendingApprovals->count() }}</span>
                        <span class="text-xs text-[var(--color-text-muted)] font-mono ml-1">Antrean</span>
                    </div>
                    <div class="pt-2.5 border-t border-[var(--color-card-border)] flex items-center justify-between text-[11px] font-mono">
                        <span class="text-[var(--color-text-muted)] truncate">Menunggu aksi</span>
                        <x-skiper-link href="{{ route('leave.index', ['status' => 'pending']) }}" variant="000" class="text-rose-500 font-semibold">
                            Tinjau
                        </x-skiper-link>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Skiper49: Inverted Perspective 3D Division & Module Carousel --}}
    @php
        $carouselSlides = [];
        if (!empty($departmentKpis)) {
            foreach ($departmentKpis as $deptCode => $deptData) {
                $targetUrl = match(strtolower($deptCode)) {
                    'hr' => auth()->user()->canAccessHRFeatures() ? route('employees.index') : route('tasks.index'),
                    'finance' => auth()->user()->canManageInvoices() ? route('finance.invoices.index') : route('finance.index'),
                    'bizdev' => auth()->user()->isBisnisDevelopment() ? route('bizdev.index') : route('tasks.index'),
                    'ga' => (auth()->user()->canAccessGAFeatures() || auth()->user()->canApproveInventoryRequests()) ? route('ga.index') : route('facilities.index'),
                    default => route('tasks.index'),
                };
                $carouselSlides[] = [
                    'tag' => 'Divisi',
                    'title' => $deptData['name'],
                    'score' => $deptData['score'] . '%',
                    'subtitle' => 'Skor Kinerja Periode',
                    'metrics' => array_slice($deptData['metrics'], 0, 2),
                    'url' => $targetUrl,
                    'cta' => 'Buka Workspace Divisi',
                    'icon' => $deptData['icon'] ?? 'monitoring',
                ];
            }
        }
        $carouselSlides[] = [
            'tag' => 'Operasional',
            'title' => 'Kalender & Agenda Kerja',
            'score' => now()->format('d M'),
            'subtitle' => 'Jadwal lintas departemen',
            'metrics' => [
                ['label' => 'Bulan Berjalan', 'value' => now()->locale('id')->translatedFormat('F Y')],
                ['label' => 'Hari Ini', 'value' => now()->locale('id')->translatedFormat('l')],
            ],
            'url' => route('calendar.index'),
            'cta' => 'Lihat Kalender',
            'icon' => 'calendar_month',
        ];
        $carouselSlides[] = [
            'tag' => 'Layanan Mandiri',
            'title' => 'Keuangan & Reimbursement',
            'score' => 'IDR',
            'subtitle' => 'Slip gaji & klaim biaya',
            'metrics' => [
                ['label' => 'Akses', 'value' => auth()->user()->canManageInvoices() ? 'Penuh (Finance)' : 'Mandiri'],
                ['label' => 'Status', 'value' => 'Aktif'],
            ],
            'url' => auth()->user()->canManageInvoices() ? route('finance.invoices.index') : route('finance.index'),
            'cta' => auth()->user()->canManageInvoices() ? 'Kelola Invoice' : 'Lihat Keuangan',
            'icon' => 'payments',
        ];
        if (auth()->user()->canManageClientContracts()) {
            $carouselSlides[] = [
                'tag' => 'Sekretariat',
                'title' => 'Kontrak MoU & Surat Resmi',
                'score' => 'Legal',
                'subtitle' => 'Arsip hukum & penomoran surat',
                'metrics' => [
                    ['label' => 'Modul Kontrak', 'value' => 'Aktif'],
                    ['label' => 'Otorisasi', 'value' => 'Eksekutif'],
                ],
                'url' => route('secretary.contracts.index'),
                'cta' => 'Monitor Kontrak',
                'icon' => 'handshake',
            ];
        }
    @endphp

    @if(count($carouselSlides) >= 2)
        <div data-reveal>
            <x-perspective-carousel
                heading="Eksplorasi Divisi & Workspace Aktif"
                subheading="Geser atau gunakan panah untuk berpindah langsung ke modul operasional berdasarkan hak akses Anda."
                :initial-slide="count($carouselSlides) > 2 ? 1 : 0">
                @foreach($carouselSlides as $slide)
                    <div class="swiper-slide">
                        <div class="rounded-2xl p-5 bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] flex flex-col justify-between gap-4 min-h-[225px]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-9 h-9 rounded-xl bg-rose-600/10 text-rose-500 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-lg" aria-hidden="true">{{ $slide['icon'] }}</span>
                                    </span>
                                    <div class="min-w-0">
                                        <span class="font-mono text-[10px] uppercase tracking-wider text-rose-500 font-semibold block">{{ $slide['tag'] }}</span>
                                        <h3 class="font-headline font-bold text-sm text-[var(--color-text-primary)] truncate">{{ $slide['title'] }}</h3>
                                    </div>
                                </div>
                                <span class="font-headline font-extrabold text-xl text-[var(--color-text-primary)] tabular-nums shrink-0">{{ $slide['score'] }}</span>
                            </div>

                            <div class="space-y-2 pt-2 border-t border-[var(--color-card-border)] text-xs font-mono">
                                @foreach($slide['metrics'] as $m)
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[var(--color-text-muted)] truncate">{{ $m['label'] }}</span>
                                        <strong class="text-[var(--color-text-primary)] tabular-nums shrink-0">{{ $m['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pt-2 flex items-center justify-between border-t border-[var(--color-card-border)]">
                                <span class="text-[11px] text-[var(--color-text-muted)]">{{ $slide['subtitle'] }}</span>
                                <x-skiper-link href="{{ $slide['url'] }}" variant="001" class="text-xs font-semibold text-rose-500">
                                    {{ $slide['cta'] }}
                                </x-skiper-link>
                            </div>
                        </div>
                    </div>
                @endforeach
            </x-perspective-carousel>
        </div>
    @endif

    {{-- SECTION 1: KPI INDIVIDU --}}
    @if(!empty($individualKpi))
        <section class="editorial-surface rounded-2xl p-6 space-y-5" x-data="{ kpiPeriod: 'overall' }" data-reveal>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[var(--color-card-border)]">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="font-headline font-bold text-base sm:text-lg text-[var(--color-text-primary)]">Grafik KPI Individu Saya</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/15 text-emerald-500 font-mono text-xs font-bold tabular-nums"
                              x-text="kpiPeriod === 'daily' ? 'Harian: {{ $individualKpi['daily']['score'] }} pts' : (kpiPeriod === 'weekly' ? 'Mingguan: {{ $individualKpi['weekly']['score'] }} pts' : 'Akumulasi: {{ $individualKpi['overallScore'] }} / 100')">
                        </span>
                    </div>
                    <p class="font-mono text-xs text-[var(--color-text-muted)] mt-1">Pencapaian target tugas individu, kedisiplinan presensi, dan evaluasi berkala</p>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 bg-[var(--color-subcard-bg)] p-1 rounded-xl border border-[var(--color-card-border)] text-xs font-mono">
                    <button type="button"
                            @click="kpiPeriod = 'daily'"
                            :class="kpiPeriod === 'daily' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                        <span>KPI Daily</span>
                    </button>
                    <button type="button"
                            @click="kpiPeriod = 'weekly'"
                            :class="kpiPeriod === 'weekly' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                        <span>KPI Weekly</span>
                    </button>
                    <button type="button"
                            @click="kpiPeriod = 'overall'"
                            :class="kpiPeriod === 'overall' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5">
                        <span>Bulanan (Overall)</span>
                    </button>

                    <a href="{{ route('reports.kpi.pdf') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="min-h-[38px] px-3 py-1.5 rounded-lg bg-[var(--color-card-bg)] hover:bg-rose-600 hover:text-white text-[var(--color-text-primary)] border border-[var(--color-card-border)] transition-colors flex items-center gap-1.5 font-semibold"
                       title="Buka & Cetak PDF Laporan KPI Lengkap">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">picture_as_pdf</span>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            {{-- KPI Daily --}}
            <div x-show="kpiPeriod === 'daily'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Skor KPI Harian</span>
                        <span class="font-mono font-bold text-rose-500 tabular-nums">{{ $individualKpi['daily']['score'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['daily']['score'] }}</span>
                        <span class="font-mono text-[11px] {{ $individualKpi['daily']['score'] >= 80 ? 'text-emerald-500' : 'text-amber-500' }}">Target: 80</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-600 transition-all duration-500" style="width: {{ $individualKpi['daily']['score'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Presensi Hari Ini</span>
                        <span class="material-symbols-outlined text-sm {{ $individualKpi['daily']['isClockedIn'] ? 'text-emerald-500' : 'text-amber-500' }}" aria-hidden="true">fingerprint</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-xl font-bold {{ $individualKpi['daily']['isClockedIn'] ? 'text-emerald-500' : 'text-amber-500' }}">{{ $individualKpi['daily']['status'] }}</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">WIB</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ $individualKpi['daily']['isClockedIn'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $individualKpi['daily']['isClockedIn'] ? 100 : 25 }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Progres Tugas Harian</span>
                        <span class="font-mono font-bold text-rose-500 tabular-nums">{{ $individualKpi['daily']['taskProgress'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['daily']['taskProgress'] }}%</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Aktivitas</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-500 transition-all duration-500" style="width: {{ $individualKpi['daily']['taskProgress'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Fokus Harian</span>
                        <span class="font-mono text-[11px] text-emerald-500 font-bold">Terjadwal</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-base font-bold text-[var(--color-text-primary)]">Eksekusi Harian</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">{{ \Carbon\Carbon::now()->translatedFormat('d M') }}</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: 90%"></div>
                    </div>
                </div>
            </div>

            {{-- KPI Weekly --}}
            <div x-show="kpiPeriod === 'weekly'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Skor KPI Mingguan</span>
                        <span class="font-mono font-bold text-rose-500 tabular-nums">{{ $individualKpi['weekly']['score'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['weekly']['score'] }}</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Senin - Minggu</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-600 transition-all duration-500" style="width: {{ $individualKpi['weekly']['score'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Kehadiran Minggu Ini</span>
                        <span class="font-mono font-bold text-emerald-500 tabular-nums">{{ $individualKpi['weekly']['attendedDays'] }} Hari</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['weekly']['attendedDays'] }}</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Target: 5 hari</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ min(100, ($individualKpi['weekly']['attendedDays'] / 5) * 100) }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Ketepatan Mingguan</span>
                        <span class="font-mono font-bold text-emerald-500 tabular-nums">{{ $individualKpi['weekly']['punctualRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['weekly']['punctualRate'] }}%</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">On Time WIB</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $individualKpi['weekly']['punctualRate'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Penyelesaian Task</span>
                        <span class="font-mono font-bold text-rose-500 tabular-nums">{{ $individualKpi['weekly']['taskRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['weekly']['taskRate'] }}%</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Target selesai</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-500 transition-all duration-500" style="width: {{ $individualKpi['weekly']['taskRate'] }}%"></div>
                    </div>
                </div>
            </div>

            {{-- KPI Overall --}}
            <div x-show="kpiPeriod === 'overall'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Tugas Selesai</span>
                        <span class="font-mono font-bold text-rose-500 tabular-nums">{{ $individualKpi['taskRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['completedTasks'] }}</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">dari {{ $individualKpi['totalTasks'] }} tugas</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-rose-600 transition-all duration-500" style="width: {{ $individualKpi['taskRate'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Rata Progress Tugas</span>
                        <span class="font-mono font-bold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['avgProgress'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['avgProgress'] }}%</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Rata berjalan</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-slate-500 transition-all duration-500" style="width: {{ $individualKpi['avgProgress'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Disiplin Waktu (WIB)</span>
                        <span class="font-mono font-bold text-emerald-500 tabular-nums">{{ $individualKpi['punctualityRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-[var(--color-text-primary)] tabular-nums">{{ $individualKpi['punctualityRate'] }}%</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">{{ $individualKpi['attendanceCount'] }} hari hadir</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $individualKpi['punctualityRate'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-rose-500/35 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)] uppercase font-semibold">Overall KPI Score</span>
                        <span class="font-mono font-bold text-emerald-500 tabular-nums">{{ $individualKpi['overallScore'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-rose-500 tabular-nums">{{ $individualKpi['overallScore'] }}</span>
                        <span class="font-mono text-[11px] text-[var(--color-text-muted)]">Target: 80.0</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $individualKpi['overallScore'] }}%"></div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- SECTION 2: GRAFIK KPI DEPARTEMEN --}}
    @if(!empty($departmentKpis))
        <section class="editorial-surface rounded-2xl p-6 space-y-5" x-data="{ deptKpiTab: 'overall' }" data-reveal>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-[var(--color-card-border)]">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="font-headline font-bold text-base sm:text-lg text-[var(--color-text-primary)]">
                            @if(auth()->user()->isDirektur() || auth()->user()->isSekretaris())
                                Indeks KPI Departemen Perusahaan
                            @elseif(auth()->user()->isHR() && auth()->user()->isManager())
                                Indeks KPI Departemen HR &amp; Pengawasan GA
                            @else
                                Indeks KPI Departemen {{ auth()->user()->department ?? 'Saya' }}
                            @endif
                        </h2>
                        <span class="px-2 py-0.5 rounded-full bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] text-[var(--color-text-secondary)] font-mono text-[11px] font-semibold tabular-nums">
                            {{ count($departmentKpis) }} Divisi
                        </span>
                    </div>
                    <p class="font-mono text-xs text-[var(--color-text-muted)] mt-1">
                        Perbandingan pencapaian operasional lintas departemen dalam periode aktif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 bg-[var(--color-subcard-bg)] p-1 rounded-xl border border-[var(--color-card-border)] text-xs font-mono">
                    <button type="button"
                            @click="deptKpiTab = 'daily'"
                            :class="deptKpiTab === 'daily' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors">
                        KPI Daily
                    </button>
                    <button type="button"
                            @click="deptKpiTab = 'weekly'"
                            :class="deptKpiTab === 'weekly' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors">
                        KPI Weekly
                    </button>
                    <button type="button"
                            @click="deptKpiTab = 'overall'"
                            :class="deptKpiTab === 'overall' ? 'bg-rose-600 text-white keep-white font-bold' : 'text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]'"
                            class="min-h-[38px] px-3 py-1.5 rounded-lg transition-colors">
                        Akumulasi Divisi
                    </button>
                    <a href="{{ route('reports.kpi.pdf') }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="min-h-[38px] px-3 py-1.5 rounded-lg bg-[var(--color-card-bg)] hover:bg-rose-600 hover:text-white text-[var(--color-text-primary)] border border-[var(--color-card-border)] transition-colors flex items-center gap-1.5 font-semibold">
                        <span class="material-symbols-outlined text-sm" aria-hidden="true">picture_as_pdf</span>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 {{ count($departmentKpis) > 1 ? 'md:grid-cols-2 lg:grid-cols-4' : 'max-w-xl' }} gap-4">
                @foreach($departmentKpis as $code => $dept)
                    <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] flex flex-col justify-between space-y-4 hover:border-rose-500/45 transition-colors">
                        <div>
                            <div class="flex items-center justify-between mb-3 gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-8 h-8 rounded-lg bg-[var(--color-card-bg)] border border-[var(--color-card-border)] text-rose-500 flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $dept['icon'] }}</span>
                                    </span>
                                    <span class="font-headline font-bold text-xs text-[var(--color-text-primary)] truncate">{{ $dept['name'] }}</span>
                                </div>
                                <span class="font-mono text-xs font-extrabold text-rose-500 tabular-nums shrink-0"
                                      x-text="deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }}%' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }}%' : '{{ $dept['score'] }}%')">
                                    {{ $dept['score'] }}%
                                </span>
                            </div>

                            <div class="space-y-1.5 mb-4">
                                <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                                    <span x-text="deptKpiTab === 'daily' ? 'Harian' : (deptKpiTab === 'weekly' ? 'Mingguan' : 'Akumulasi')">Skor KPI</span>
                                    <span class="font-bold text-[var(--color-text-primary)] tabular-nums"
                                          x-text="deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }} / 100' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }} / 100' : '{{ $dept['score'] }} / 100')">
                                        {{ $dept['score'] }} / 100
                                    </span>
                                </div>
                                <div class="w-full bg-[var(--color-card-bg)] rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full bg-rose-600 transition-all duration-500"
                                         :style="'width: ' + (deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }}%' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }}%' : '{{ $dept['score'] }}%'))"></div>
                                </div>
                            </div>

                            <div class="space-y-2 pt-2.5 border-t border-[var(--color-card-border)] text-[11px] font-mono">
                                @foreach($dept['metrics'] as $m)
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[var(--color-text-muted)] truncate">{{ $m['label'] }}</span>
                                        <strong class="text-[var(--color-text-primary)] tabular-nums shrink-0">{{ $m['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-[var(--color-card-border)] flex items-center justify-between text-[11px] font-mono">
                            <span class="text-[var(--color-text-muted)]">Evaluasi</span>
                            @if($dept['score'] >= 80)
                                <span class="text-emerald-500 font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">check_circle</span>
                                    Memenuhi Target
                                </span>
                            @else
                                <span class="text-amber-500 font-bold flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">trending_up</span>
                                    Optimalisasi
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Executive Analytical Percentage Bar (Direktur & Sekretaris Direktur) --}}
    @if((auth()->user()->isDirektur() || auth()->user()->isSekretaris()) && !empty($directorMetrics))
        <section class="editorial-surface rounded-2xl p-6 space-y-4" data-reveal>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-[var(--color-card-border)]">
                <div>
                    <h2 class="font-headline font-bold text-base text-[var(--color-text-primary)]">Executive Analytical Index &amp; Rasio Kinerja</h2>
                    <p class="font-mono text-xs text-[var(--color-text-muted)] mt-0.5">Ringkasan rasio persentase operasional perusahaan untuk tinjauan cepat Direksi</p>
                </div>
                <x-skiper-link href="{{ route('tasks.index') }}" variant="001" class="text-xs font-mono font-semibold text-rose-500">
                    Lihat Rincian Tugas
                </x-skiper-link>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-[var(--color-text-muted)] uppercase">Disiplin Presensi</span>
                        <span class="font-bold text-emerald-500 tabular-nums">{{ $directorMetrics['onTimeRate'] }}%</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $directorMetrics['onTimeRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                        <span>Hadir: <strong class="text-[var(--color-text-primary)] tabular-nums">{{ $attendanceRate }}%</strong></span>
                        <span>Telat: <strong class="text-rose-500 tabular-nums">{{ $directorMetrics['lateRate'] }}%</strong></span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-[var(--color-text-muted)] uppercase">Penyelesaian Tugas</span>
                        <span class="font-bold text-rose-500 tabular-nums">{{ $directorMetrics['tasksCompletionRate'] }}%</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-rose-600" style="width: {{ $directorMetrics['tasksCompletionRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                        <span>Selesai: <strong class="text-[var(--color-text-primary)] tabular-nums">{{ $directorMetrics['completedTasks'] }}/{{ $directorMetrics['totalTasks'] }}</strong></span>
                        <span>Jalan: <strong class="text-amber-500 tabular-nums">{{ $directorMetrics['inProgressTasks'] }}</strong></span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-[var(--color-text-muted)] uppercase">Realisasi Reimburse</span>
                        <span class="font-bold text-emerald-500 tabular-nums">{{ $directorMetrics['paidReimburseRate'] }}%</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $directorMetrics['paidReimburseRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                        <span>Pending: <strong class="text-amber-500 tabular-nums">{{ $directorMetrics['pendingReimburseCount'] }}</strong></span>
                        <span>Total: <strong class="text-[var(--color-text-primary)] tabular-nums">{{ $directorMetrics['totalReimburseCount'] }}</strong></span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-[var(--color-subcard-bg)] border border-[var(--color-card-border)] space-y-2">
                    <div class="flex items-center justify-between text-xs font-mono">
                        <span class="text-[var(--color-text-muted)] uppercase">Resolusi Kendala GA</span>
                        <span class="font-bold text-emerald-500 tabular-nums">{{ $directorMetrics['ticketResolvedRate'] }}%</span>
                    </div>
                    <div class="w-full bg-[var(--color-card-bg)] rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ $directorMetrics['ticketResolvedRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-[var(--color-text-muted)]">
                        <span>Hired: <strong class="text-rose-500 tabular-nums">{{ $directorMetrics['hireConversionRate'] }}%</strong></span>
                        <span>Tiket: <strong class="text-[var(--color-text-primary)] tabular-nums">{{ $directorMetrics['totalTickets'] }}</strong></span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Main Grid: Live Attendance Stream & Pending Approval Queue --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" data-reveal>

        {{-- Left 7 Cols: Live Today Attendance Log --}}
        <section class="lg:col-span-7 editorial-surface rounded-2xl flex flex-col overflow-hidden">
            <div class="p-5 border-b border-[var(--color-card-border)] flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-headline text-base font-bold text-[var(--color-text-primary)]">Log Kehadiran Masuk Hari Ini</h2>
                    <p class="font-mono text-xs text-[var(--color-text-muted)]">
                        @if(auth()->user()->isDirektur())
                            Cakupan: Seluruh Karyawan Perusahaan
                        @elseif(auth()->user()->isManager())
                            Cakupan: Tim Departemen {{ auth()->user()->department }} &amp; Manajer
                        @else
                            Cakupan: Presensi Pribadi Anda
                        @endif
                    </p>
                </div>
                <x-skiper-link href="{{ route('attendance.index') }}" variant="000" class="text-xs font-semibold text-rose-500">
                    Semua Log
                </x-skiper-link>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[var(--color-table-divider)] text-[var(--color-text-muted)] font-mono text-[11px] uppercase bg-[var(--color-table-header-bg)]">
                            <th class="px-5 py-3">Karyawan</th>
                            <th class="px-5 py-3">Waktu Masuk</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Mode &amp; Lokasi</th>
                            <th class="px-5 py-3 text-right">Pulang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--color-table-divider)]">
                        @forelse($recentAttendances as $att)
                            <tr class="hover:bg-[var(--color-table-row-hover)] transition-colors">
                                <td class="px-5 py-3.5 flex items-center gap-3">
                                    <img src="{{ $att->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}"
                                         alt="{{ $att->employee->name }}"
                                         width="28"
                                         height="28"
                                         class="w-7 h-7 rounded-full object-cover shrink-0"/>
                                    <div class="min-w-0">
                                        <a href="{{ route('employees.show', $att->employee->id) }}" class="font-semibold text-[var(--color-text-primary)] hover:text-rose-500 transition-colors block truncate">
                                            {{ $att->employee->name }}
                                        </a>
                                        <div class="font-mono text-[10px] text-[var(--color-text-muted)]">{{ $att->employee->nip }} • {{ $att->employee->department }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-[var(--color-text-primary)] font-semibold tabular-nums whitespace-nowrap">
                                    {{ substr($att->clock_in, 0, 8) }} WIB
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($att->status === 'on_time')
                                        <span class="px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-500 font-mono text-[10px] font-semibold">
                                            Tepat Waktu
                                        </span>
                                    @elseif($att->status === 'late')
                                        <span class="px-2 py-0.5 rounded bg-rose-500/15 text-rose-500 font-mono text-[10px] font-semibold">
                                            Terlambat
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-[var(--color-subcard-bg)] text-[var(--color-text-muted)] font-mono text-[10px]">
                                            {{ strtoupper($att->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="text-[var(--color-text-primary)] font-medium">{{ $att->work_mode }}</div>
                                    <div class="font-mono text-[10px] text-[var(--color-text-muted)] truncate max-w-[140px]">{{ $att->location_name ?? 'Kantor Utama' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs text-[var(--color-text-muted)] tabular-nums whitespace-nowrap">
                                    {{ $att->clock_out ? substr($att->clock_out, 0, 5) . ' WIB' : 'Belum Pulang' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center">
                                    <p class="text-xs font-mono text-[var(--color-text-muted)] mb-2">Belum ada catatan presensi masuk untuk tanggal ini.</p>
                                    <x-skiper-link href="{{ route('attendance.index') }}" variant="000" class="text-xs font-semibold text-rose-500">
                                        Buka Modul Kehadiran
                                    </x-skiper-link>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Right 5 Cols: Pending Approval List --}}
        <section class="lg:col-span-5 editorial-surface rounded-2xl flex flex-col">
            <div class="p-5 border-b border-[var(--color-card-border)] flex items-center justify-between gap-2">
                <div>
                    <h2 class="font-headline text-base font-bold text-[var(--color-text-primary)]">Persetujuan Menunggu</h2>
                    <p class="font-mono text-xs text-[var(--color-text-muted)]">
                        @if(auth()->user()->isDirektur() || auth()->user()->isHR())
                            Permohonan izin &amp; cuti butuh aksi
                        @else
                            Status pengajuan izin &amp; cuti Anda
                        @endif
                    </p>
                </div>
                <span class="font-mono text-xs px-2.5 py-0.5 bg-rose-600/15 text-rose-500 rounded-md font-semibold tabular-nums">
                    {{ $pendingApprovals->count() }} Antrean
                </span>
            </div>

            <div class="p-5 flex flex-col gap-4 flex-1">
                @forelse($pendingApprovals->take(4) as $item)
                    <div class="p-4 bg-[var(--color-subcard-bg)] rounded-xl border border-[var(--color-card-border)] flex flex-col gap-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <img src="{{ $item->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}"
                                     alt="{{ $item->employee->name }}"
                                     width="28"
                                     height="28"
                                     class="w-7 h-7 rounded-full object-cover shrink-0"/>
                                <div class="min-w-0">
                                    <span class="font-semibold text-xs text-[var(--color-text-primary)] block truncate">{{ $item->employee->name }}</span>
                                    <span class="font-mono text-[10px] text-[var(--color-text-muted)]">{{ $item->employee->department }}</span>
                                </div>
                            </div>
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-[var(--color-card-bg)] border border-[var(--color-card-border)] text-[var(--color-text-secondary)] shrink-0">
                                {{ $item->type }}
                            </span>
                        </div>

                        <p class="text-xs text-[var(--color-text-muted)] line-clamp-2">"{{ $item->reason }}"</p>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-[var(--color-card-border)] text-xs">
                            <span class="font-mono text-[10px] text-[var(--color-text-muted)] tabular-nums">
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }} ({{ $item->duration_days }} hari)
                            </span>

                            @php
                                $myEmpId = auth()->user()->employee_id ?? (auth()->user()->employee?->id);
                                $isOwnRequest = $myEmpId && ((int)$item->employee_id === (int)$myEmpId);
                            @endphp

                            @if($isOwnRequest)
                                <span class="font-mono text-[10px] text-amber-500 bg-amber-500/15 px-2 py-0.5 rounded flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs" aria-hidden="true">lock</span>
                                    <span>Pengajuan Anda</span>
                                </span>
                            @elseif($item->status === 'pending_manager')
                                @if(auth()->user()->canApproveDepartmentLeave($item))
                                    <div class="flex items-center gap-2">
                                        <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST" onsubmit="
                                            let reason = prompt('Masukkan alasan penolakan permohonan:');
                                            if (!reason || reason.trim() === '') {
                                                alert('Alasan penolakan wajib diisi!');
                                                return false;
                                            }
                                            this.querySelector('input[name=rejection_note]').value = reason;
                                            return true;
                                        ">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="rejected">
                                            <input type="hidden" name="rejection_note" value="">
                                            <button type="submit" aria-label="Tolak permohonan" class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg text-[var(--color-text-muted)] hover:text-rose-500 hover:bg-rose-500/10 transition-colors" title="Tolak">
                                                <span class="material-symbols-outlined text-lg" aria-hidden="true">close</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" onclick="return confirm('Setujui permohonan ini dan teruskan ke Manager HR?')" class="min-h-[36px] px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white keep-white rounded-lg text-[11px] font-semibold transition-colors flex items-center gap-1">
                                                <span>Setujui &amp; Ke HR</span>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="font-mono text-[10px] text-amber-500 bg-amber-500/15 px-2 py-0.5 rounded">
                                        Menunggu Manager Dept
                                    </span>
                                @endif
                            @elseif(auth()->user()->canApproveLeaves())
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST" onsubmit="
                                        let reason = prompt('Masukkan alasan penolakan permohonan:');
                                        if (!reason || reason.trim() === '') {
                                            alert('Alasan penolakan wajib diisi!');
                                            return false;
                                        }
                                        this.querySelector('input[name=rejection_note]').value = reason;
                                        return true;
                                    ">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <input type="hidden" name="rejection_note" value="">
                                        <button type="submit" aria-label="Tolak permohonan" class="min-h-[36px] min-w-[36px] flex items-center justify-center rounded-lg text-[var(--color-text-muted)] hover:text-rose-500 hover:bg-rose-500/10 transition-colors" title="Tolak">
                                            <span class="material-symbols-outlined text-lg" aria-hidden="true">close</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" onclick="return confirm('Setujui final permohonan cuti ini?')" class="min-h-[36px] px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white keep-white rounded-lg text-[11px] font-semibold transition-colors flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm" aria-hidden="true">check</span>
                                            <span>Setujui HR</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="font-mono text-[10px] text-[var(--color-text-secondary)] bg-[var(--color-card-bg)] px-2 py-0.5 rounded border border-[var(--color-card-border)]">
                                    Menunggu Review HR
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 flex flex-col items-center justify-center text-center text-[var(--color-text-muted)]">
                        <span class="material-symbols-outlined text-3xl mb-2 text-emerald-500" aria-hidden="true">task_alt</span>
                        <p class="text-xs font-mono">Semua permohonan cuti telah ditinjau.</p>
                    </div>
                @endforelse

                @if($pendingApprovals->count() > 4)
                    <x-skiper-link href="{{ route('leave.index', ['status' => 'pending']) }}" variant="000" class="mt-auto justify-center py-2 font-mono text-xs text-rose-500 font-semibold">
                        Lihat {{ $pendingApprovals->count() - 4 }} permohonan lainnya
                    </x-skiper-link>
                @endif
            </div>
        </section>

    </div>

</div>
@endsection
