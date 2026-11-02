@extends('layouts.app')

@section('title', 'Dashboard Operasional HR')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Greeting & Operational Quick Actions Bar -->
    <div class="p-6 rounded-2xl bg-[#151922] border border-slate-800 shadow-lg">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-mono text-[10px] font-semibold tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Realtime Sync
                    </span>
                    <span class="font-mono text-[10px] uppercase font-bold px-2 py-0.5 rounded-md bg-slate-800/80 border border-slate-700/60 text-slate-300">
                        Akses: {{ auth()->user()->role ?? 'karyawan' }}
                    </span>
                </div>
                <h1 class="font-headline font-extrabold text-2xl lg:text-3xl text-white tracking-tight">
                    Halo, {{ auth()->user()->name }} 👋
                </h1>
                <div class="flex items-center gap-2 text-slate-400 font-mono text-xs">
                    <span class="material-symbols-outlined text-sm text-rose-400">schedule</span>
                    <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }} • <span class="font-semibold text-slate-200" id="live-clock">{{ now()->format('H:i:s') }} WIB</span></span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('leave.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800/80 hover:bg-slate-700/80 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700/60 shadow-sm active:scale-95">
                    <span class="material-symbols-outlined text-base text-rose-400">event_note</span>
                    <span>Pengajuan Cuti</span>
                </a>

                @if(auth()->user()->canQuickClock())
                    @if($userTodayAttendance)
                        @if(!$userTodayAttendance->clock_out)
                            <form action="{{ route('attendance.clockOut') }}" method="POST">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $currentUser->id }}">
                                <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-orange-500/20 active:scale-95">
                                    <span class="material-symbols-outlined text-base">logout</span>
                                    <span>Clock Out (In: {{ substr($userTodayAttendance->clock_in, 0, 5) }})</span>
                                </button>
                            </form>
                        @else
                            <div class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/10 text-emerald-400 rounded-xl text-xs font-mono font-medium border border-emerald-500/25">
                                <span class="material-symbols-outlined text-base">verified</span>
                                <span>Selesai: {{ substr($userTodayAttendance->clock_out, 0, 5) }}</span>
                            </div>
                        @endif
                    @else
                        <button @click="showClockInModal = true" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-rose-600 via-rose-500 to-red-600 hover:from-rose-500 hover:to-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-rose-600/30 active:scale-95 ring-1 ring-white/20">
                            <span class="material-symbols-outlined text-base">fingerprint</span>
                            <span>Presensi Masuk</span>
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    @if(auth()->user()->canViewCompanyMetrics())
    <!-- 4-Grid Operational Metric Cards with Glassmorphism & Hover Lift (Khusus Manager HR & Direktur) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Karyawan -->
        <div class="glass-card glass-card-hover rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Total Karyawan</span>
                <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">groups</span>
                </div>
            </div>
            <div class="my-3">
                <span class="font-headline text-3xl text-white font-extrabold tracking-tight">{{ number_format($totalEmployees) }}</span>
                <span class="text-xs text-slate-400 ml-1 font-mono">Personel</span>
            </div>
            <div class="flex items-center gap-2 pt-2 border-t border-slate-800/80">
                <span class="inline-flex items-center font-mono text-[10px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-full font-semibold">
                    <span class="material-symbols-outlined text-[12px] mr-0.5">trending_up</span>+{{ $newEmployeesThisMonth }}
                </span>
                <span class="font-mono text-[10px] text-slate-400">rekrutmen bulan ini</span>
            </div>
        </div>

        <!-- Hadir Hari Ini -->
        <div class="glass-card glass-card-hover rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Kehadiran Hari Ini</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">how_to_reg</span>
                </div>
            </div>
            <div class="my-3 flex items-baseline gap-2">
                <span class="font-headline text-3xl text-white font-extrabold tracking-tight">{{ number_format($presentTodayCount) }}</span>
                <span class="font-mono text-xs px-2 py-0.5 bg-emerald-500/10 text-emerald-400 font-bold rounded-md border border-emerald-500/20">{{ $attendanceRate }}%</span>
            </div>
            <div class="flex items-center justify-between font-mono text-[10px] text-slate-400 pt-2 border-t border-slate-800/80">
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Tepat: <strong class="text-slate-200">{{ $onTimeCount }}</strong> @if($presentTodayCount > 0) <span class="text-emerald-400">({{ round(($onTimeCount/$presentTodayCount)*100) }}%)</span> @endif</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                    <span>Telat: <strong class="text-rose-400">{{ $lateCount }}</strong> @if($presentTodayCount > 0) <span class="text-rose-400">({{ round(($lateCount/$presentTodayCount)*100) }}%)</span> @endif</span>
                </div>
            </div>
        </div>

        <!-- Karyawan Cuti / Dispensasi -->
        <div class="glass-card glass-card-hover rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Cuti & Dispensasi</span>
                <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">beach_access</span>
                </div>
            </div>
            <div class="my-3 flex items-baseline gap-2">
                <span class="font-headline text-3xl text-white font-extrabold tracking-tight">{{ $leaveCount }}</span>
                <span class="text-xs text-slate-400 font-mono">Karyawan</span>
                @if($totalEmployees > 0)
                    <span class="font-mono text-xs px-2 py-0.5 bg-amber-500/10 text-amber-300 font-bold rounded-md border border-amber-500/25">
                        {{ round(($leaveCount / $totalEmployees) * 100, 1) }}%
                    </span>
                @endif
            </div>
            <div class="flex items-center justify-between font-mono text-[10px] text-slate-400 pt-2 border-t border-slate-800/80">
                <span>Rasio Tidak Masuk Cuti</span>
                <a href="{{ route('leave.index') }}" class="text-rose-400 hover:text-rose-300 flex items-center gap-0.5 font-semibold">
                    <span>Detail</span>
                    <span class="material-symbols-outlined text-[12px]">chevron_right</span>
                </a>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="glass-card glass-card-hover rounded-2xl p-5 flex flex-col justify-between relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <span class="font-mono text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Perlu Persetujuan</span>
                <span class="px-2 py-0.5 bg-rose-500/10 border border-rose-500/25 text-rose-300 font-mono text-[9px] uppercase tracking-wider rounded-md font-bold">Review</span>
            </div>
            <div class="my-3">
                <span class="font-headline text-3xl text-white font-extrabold tracking-tight">{{ $pendingApprovals->count() }}</span>
                <span class="text-xs text-slate-400 ml-1 font-mono">Permohonan</span>
            </div>
            <div class="flex items-center justify-between font-mono text-[10px] text-slate-400 pt-2 border-t border-slate-800/80">
                <span class="truncate">Tiket permohonan tertunda</span>
                <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="text-rose-400 font-semibold hover:text-rose-300">Tinjau &rarr;</a>
            </div>
        </div>
    </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION 1: GRAFIK KPI INDIVIDU (PERSONAL EMPLOYEE PERFORMANCE)           -->
    <!-- ========================================================================= -->
    <!-- ========================================================================= -->
    <!-- SECTION 1: GRAFIK KPI INDIVIDU (PERSONAL EMPLOYEE PERFORMANCE)           -->
    <!-- ========================================================================= -->
    @if(!empty($individualKpi))
        <div class="p-6 rounded-2xl bg-[#151922] border border-slate-800 shadow-sm space-y-4" x-data="{ kpiPeriod: 'overall' }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-indigo-600 flex items-center justify-center text-white shadow-md">
                        <span class="material-symbols-outlined text-xl">speed</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-headline font-bold text-base text-white">Grafik KPI Individu Saya</h2>
                            <!-- Skor Badge Dinamis Sesuai Tab -->
                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 font-mono text-[10px] font-bold"
                                  x-text="kpiPeriod === 'daily' ? 'Harian: {{ $individualKpi['daily']['score'] }} pts' : (kpiPeriod === 'weekly' ? 'Mingguan: {{ $individualKpi['weekly']['score'] }} pts' : 'Akumulasi: {{ $individualKpi['overallScore'] }} / 100')">
                            </span>
                        </div>
                        <p class="font-mono text-xs text-slate-400">Pencapaian target tugas individu, kedisiplinan presensi, dan evaluasi berkala</p>
                    </div>
                </div>

                <!-- Toggle Tab Periode KPI: Harian (Daily), Mingguan (Weekly), Akumulasi Bulanan -->
                <div class="flex items-center bg-slate-900/90 p-1 rounded-xl border border-slate-800 text-xs font-mono">
                    <button type="button" 
                            @click="kpiPeriod = 'daily'" 
                            :class="kpiPeriod === 'daily' ? 'bg-gradient-to-r from-rose-600 to-red-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">today</span>
                        <span>KPI Daily</span>
                    </button>
                    <button type="button" 
                            @click="kpiPeriod = 'weekly'" 
                            :class="kpiPeriod === 'weekly' ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">date_range</span>
                        <span>KPI Weekly</span>
                    </button>
                    <button type="button" 
                            @click="kpiPeriod = 'overall'" 
                            :class="kpiPeriod === 'overall' ? 'bg-slate-800 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">calendar_month</span>
                        <span>Bulanan (Overall)</span>
                    </button>

                    <!-- Tombol Export PDF Laporan KPI -->
                    <a href="{{ route('reports.kpi.pdf') }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/40 transition-all flex items-center gap-1.5 font-bold shadow-sm" title="Buka & Cetak PDF Laporan KPI Lengkap">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <!-- TAMPILAN 1: KPI HARIAN (DAILY) -->
            <div x-show="kpiPeriod === 'daily'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                <div class="p-4 rounded-xl bg-slate-900/60 border border-rose-500/30 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Skor KPI Harian</span>
                        <span class="font-mono font-bold text-rose-400">{{ $individualKpi['daily']['score'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['daily']['score'] }}</span>
                        <span class="font-mono text-[11px] {{ $individualKpi['daily']['score'] >= 80 ? 'text-emerald-400' : 'text-amber-400' }}">Target: 80</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-rose-500 to-red-500 transition-all duration-500" style="width: {{ $individualKpi['daily']['score'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Status Presensi Hari Ini</span>
                        <span class="material-symbols-outlined text-sm {{ $individualKpi['daily']['isClockedIn'] ? 'text-emerald-400' : 'text-amber-400' }}">fingerprint</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-xl font-bold {{ $individualKpi['daily']['isClockedIn'] ? 'text-emerald-400' : 'text-amber-400' }}">{{ $individualKpi['daily']['status'] }}</span>
                        <span class="font-mono text-[11px] text-slate-400">Jam Masuk (WIB)</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full {{ $individualKpi['daily']['isClockedIn'] ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $individualKpi['daily']['isClockedIn'] ? 100 : 25 }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Progres Tugas Hari Ini</span>
                        <span class="font-mono font-bold text-indigo-400">{{ $individualKpi['daily']['taskProgress'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['daily']['taskProgress'] }}%</span>
                        <span class="font-mono text-[11px] text-slate-400">Aktivitas update</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" style="width: {{ $individualKpi['daily']['taskProgress'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Fokus Harian (Daily Goal)</span>
                        <span class="font-mono text-[10px] text-emerald-400 font-bold uppercase">Optimal</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-base font-bold text-white">Eksekusi Harian</span>
                        <span class="font-mono text-[10px] text-slate-400">{{ \Carbon\Carbon::now()->translatedFormat('l, d M') }}</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-500" style="width: 90%"></div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 2: KPI MINGGUAN (WEEKLY) -->
            <div x-show="kpiPeriod === 'weekly'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                <div class="p-4 rounded-xl bg-slate-900/60 border border-indigo-500/30 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Skor KPI Mingguan</span>
                        <span class="font-mono font-bold text-indigo-400">{{ $individualKpi['weekly']['score'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['weekly']['score'] }}</span>
                        <span class="font-mono text-[11px] text-slate-400">Senin - Minggu</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" style="width: {{ $individualKpi['weekly']['score'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Kehadiran Minggu Ini</span>
                        <span class="font-mono font-bold text-emerald-400">{{ $individualKpi['weekly']['attendedDays'] }} Hari</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['weekly']['attendedDays'] }}</span>
                        <span class="font-mono text-[11px] text-slate-400">Target: 5 hari kerja</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500" style="width: {{ min(100, ($individualKpi['weekly']['attendedDays'] / 5) * 100) }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Ketepatan Presensi Mingguan</span>
                        <span class="font-mono font-bold text-teal-400">{{ $individualKpi['weekly']['punctualRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['weekly']['punctualRate'] }}%</span>
                        <span class="font-mono text-[11px] text-slate-400">On Time WIB</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 transition-all duration-500" style="width: {{ $individualKpi['weekly']['punctualRate'] }}%"></div>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Penyelesaian Task Mingguan</span>
                        <span class="font-mono font-bold text-rose-400">{{ $individualKpi['weekly']['taskRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['weekly']['taskRate'] }}%</span>
                        <span class="font-mono text-[11px] text-slate-400">Target selesai</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-rose-500 to-pink-500 transition-all duration-500" style="width: {{ $individualKpi['weekly']['taskRate'] }}%"></div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 3: KPI BULANAN / OVERALL -->
            <div x-show="kpiPeriod === 'overall'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-1">
                <!-- KPI 1: Task Completion Rate -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Tugas Selesai</span>
                        <span class="font-mono font-bold text-rose-400">{{ $individualKpi['taskRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['completedTasks'] }}</span>
                        <span class="font-mono text-[11px] text-slate-400">dari {{ $individualKpi['totalTasks'] }} tugas</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-rose-500 to-red-500 transition-all duration-500" style="width: {{ $individualKpi['taskRate'] }}%"></div>
                    </div>
                </div>

                <!-- KPI 2: Rata-Rata Progress Tugas -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Rata Progress Tugas</span>
                        <span class="font-mono font-bold text-indigo-400">{{ $individualKpi['avgProgress'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['avgProgress'] }}%</span>
                        <span class="font-mono text-[11px] text-slate-400">Rata-rata berjalan</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-500" style="width: {{ $individualKpi['avgProgress'] }}%"></div>
                    </div>
                </div>

                <!-- KPI 3: Ketepatan Waktu Presensi -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Disiplin Waktu (WIB)</span>
                        <span class="font-mono font-bold text-emerald-400">{{ $individualKpi['punctualityRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-white">{{ $individualKpi['punctualityRate'] }}%</span>
                        <span class="font-mono text-[11px] text-slate-400">{{ $individualKpi['attendanceCount'] }} hari hadir</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500" style="width: {{ $individualKpi['punctualityRate'] }}%"></div>
                    </div>
                </div>

                <!-- KPI 4: Indeks Gabungan Evaluasi -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-[11px] text-slate-400 uppercase font-semibold">Overall KPI Score</span>
                        <span class="font-mono font-bold text-teal-400">{{ $individualKpi['overallScore'] }} pts</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-headline text-2xl font-extrabold text-teal-400">{{ $individualKpi['overallScore'] }}</span>
                        <span class="font-mono text-[11px] text-slate-400">Target: 80.0</span>
                    </div>
                    <div class="w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full bg-gradient-to-r from-teal-500 to-cyan-500 transition-all duration-500" style="width: {{ $individualKpi['overallScore'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SECTION 2: GRAFIK KPI MASING-MASING DEPARTEMEN                            -->
    <!-- ========================================================================= -->
    @if(!empty($departmentKpis))
        <div class="p-6 rounded-2xl bg-[#151922] border border-slate-800 shadow-sm space-y-5" x-data="{ deptKpiTab: 'overall' }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-rose-600 flex items-center justify-center text-white shadow-md">
                        <span class="material-symbols-outlined text-xl">monitoring</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-headline font-bold text-base text-white">
                                @if(auth()->user()->isDirektur())
                                    Grafik Indeks KPI Departemen Perusahaan
                                @elseif(auth()->user()->isHR() && auth()->user()->isManager())
                                    Grafik Indeks KPI Departemen HR & Pengawasan GA
                                @else
                                    Grafik Indeks KPI Departemen {{ auth()->user()->department ?? 'Saya' }}
                                @endif
                            </h2>
                            <span class="px-2 py-0.5 rounded-full bg-slate-800 border border-slate-700 text-slate-300 font-mono text-[10px] font-semibold">
                                {{ count($departmentKpis) }} Divisi
                            </span>
                        </div>
                        <p class="font-mono text-xs text-slate-400">
                            @if(auth()->user()->isDirektur())
                                Indikator kinerja kunci seluruh divisi: HR, Finance, Bisnis Development, dan General Affairs
                            @elseif(auth()->user()->isHR() && auth()->user()->isManager())
                                Indikator pencapaian kinerja Departemen HR dan pengawasan performa operasional Departemen General Affairs (GA)
                            @else
                                Indikator pencapaian target dan efektivitas operasional divisi Anda
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Toggle Tab Periode KPI Departemen -->
                <div class="flex items-center bg-slate-900/90 p-1 rounded-xl border border-slate-800 text-xs font-mono">
                    <button type="button" 
                            @click="deptKpiTab = 'daily'" 
                            :class="deptKpiTab === 'daily' ? 'bg-gradient-to-r from-amber-600 to-rose-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">today</span>
                        <span>KPI Daily</span>
                    </button>
                    <button type="button" 
                            @click="deptKpiTab = 'weekly'" 
                            :class="deptKpiTab === 'weekly' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">date_range</span>
                        <span>KPI Weekly</span>
                    </button>
                    <button type="button" 
                            @click="deptKpiTab = 'overall'" 
                            :class="deptKpiTab === 'overall' ? 'bg-slate-800 text-white font-bold shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-3 py-1.5 rounded-lg transition-all flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">bar_chart</span>
                        <span>Akumulasi Divisi</span>
                    </button>

                    <!-- Tombol Export PDF Laporan Departemen -->
                    <a href="{{ route('reports.kpi.pdf') }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/40 transition-all flex items-center gap-1.5 font-bold shadow-sm" title="Buka & Cetak PDF Laporan KPI Divisi">
                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                        <span>Export PDF</span>
                    </a>
                </div>
            </div>

            <!-- Grid Card Grafik KPI Departemen (Responsive: 1 full-width jika akun divisi, 4 grid jika Direktur) -->
            <div class="grid grid-cols-1 {{ count($departmentKpis) > 1 ? 'md:grid-cols-2 lg:grid-cols-4' : 'max-w-xl' }} gap-4">
                @foreach($departmentKpis as $code => $dept)
                    @php
                        $colorMap = [
                            'rose' => ['border' => 'border-rose-500/30', 'bg' => 'bg-rose-500/10', 'text' => 'text-rose-400', 'bar' => 'from-rose-600 to-pink-500'],
                            'emerald' => ['border' => 'border-emerald-500/30', 'bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'bar' => 'from-emerald-600 to-teal-500'],
                            'blue' => ['border' => 'border-blue-500/30', 'bg' => 'bg-blue-500/10', 'text' => 'text-blue-400', 'bar' => 'from-blue-600 to-cyan-500'],
                            'amber' => ['border' => 'border-amber-500/30', 'bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'bar' => 'from-amber-600 to-orange-500'],
                        ];
                        $c = $colorMap[$dept['color']] ?? $colorMap['rose'];
                    @endphp
                    <div class="p-4 rounded-xl bg-slate-900/60 border {{ $c['border'] }} flex flex-col justify-between space-y-4 relative overflow-hidden group hover:border-slate-600 transition-all">
                        <div>
                            <!-- Header Dept -->
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg {{ $c['bg'] }} {{ $c['text'] }} flex items-center justify-center">
                                        <span class="material-symbols-outlined text-base">{{ $dept['icon'] }}</span>
                                    </div>
                                    <span class="font-headline font-bold text-xs text-white">{{ $dept['name'] }}</span>
                                </div>
                                <span class="font-mono text-xs font-extrabold {{ $c['text'] }}"
                                      x-text="deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }}%' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }}%' : '{{ $dept['score'] }}%')">
                                    {{ $dept['score'] }}%
                                </span>
                            </div>

                            <!-- Bar Grafik Score Dinamis -->
                            <div class="space-y-1.5 mb-4">
                                <div class="flex items-center justify-between text-[10px] font-mono text-slate-400">
                                    <span x-text="deptKpiTab === 'daily' ? 'Skor KPI Harian Divisi' : (deptKpiTab === 'weekly' ? 'Skor KPI Mingguan Divisi' : 'Skor Pencapaian KPI Divisi')">Skor Pencapaian KPI</span>
                                    <span class="font-bold text-slate-200"
                                          x-text="deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }} / 100' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }} / 100' : '{{ $dept['score'] }} / 100')">
                                        {{ $dept['score'] }} / 100
                                    </span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                    <!-- Dynamic Bar Width via Alpine -->
                                    <div class="h-2.5 rounded-full bg-gradient-to-r {{ $c['bar'] }} transition-all duration-700" 
                                         :style="'width: ' + (deptKpiTab === 'daily' ? '{{ $dept['dailyScore'] }}%' : (deptKpiTab === 'weekly' ? '{{ $dept['weeklyScore'] }}%' : '{{ $dept['score'] }}%'))"></div>
                                </div>
                            </div>

                            <!-- Sub-Metrics List -->
                            <div class="space-y-2 pt-2 border-t border-slate-800 text-[11px] font-mono">
                                @foreach($dept['metrics'] as $m)
                                    <div class="flex items-center justify-between">
                                        <span class="text-slate-400 truncate max-w-[140px]">{{ $m['label'] }}</span>
                                        <strong class="text-slate-200">{{ $m['value'] }}</strong>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Indikator Khusus Daily / Weekly -->
                            <div class="mt-3 p-2 rounded-lg bg-slate-950/50 border border-slate-800/80 flex items-center justify-between text-[10px] font-mono">
                                <span class="text-slate-400" x-text="deptKpiTab === 'daily' ? 'Operasional Hari Ini:' : (deptKpiTab === 'weekly' ? 'Minggu Berjalan:' : 'Target Periode:')">Status:</span>
                                <span class="font-bold text-emerald-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span x-text="deptKpiTab === 'daily' ? 'Aktif & Terkendali' : (deptKpiTab === 'weekly' ? 'On Track' : 'Memenuhi Kuota')">Aktif</span>
                                </span>
                            </div>
                        </div>

                        <!-- Footer Status Evaluasi -->
                        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[10px] font-mono">
                            <span class="text-slate-500">Evaluasi</span>
                            @if($dept['score'] >= 80)
                                <span class="text-emerald-400 font-bold flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[12px]">check_circle</span>
                                    Memenuhi Target
                                </span>
                            @else
                                <span class="text-amber-400 font-bold flex items-center gap-0.5">
                                    <span class="material-symbols-outlined text-[12px]">trending_up</span>
                                    Optimalisasi
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif


    @if(auth()->user()->isDirektur() && !empty($directorMetrics))
        <!-- EXECUTIVE ANALYTICAL PERCENTAGE BAR (KHUSUS DIREKTUR) -->
        <div class="p-5 bg-gradient-to-r from-surface-container to-surface-container-high rounded-xl border border-amber-500/30 shadow-md">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-[#282a2e]/60">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-lg">analytics</span>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-headline font-bold text-base text-on-surface">Executive Analytical Index & KPI Persentase</h2>
                            <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-semibold uppercase">
                                Prioritas Direksi
                            </span>
                        </div>
                        <p class="font-mono text-xs text-tertiary">Ringkasan rasio performa operasional seluruh divisi perusahaan untuk mempermudah evaluasi cepat</p>
                    </div>
                </div>
                <a href="{{ route('tasks.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-surface-container-highest hover:bg-surface-bright text-xs font-mono text-on-surface border border-[#333539] transition-all">
                    <span>Lihat Rincian Tugas</span>
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                <!-- KPI 1: Kehadiran & Ketepatan Waktu -->
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase text-tertiary">Disiplin Presensi</span>
                        <span class="text-xs font-mono font-bold text-emerald-400">{{ $directorMetrics['onTimeRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xl font-bold font-headline text-on-surface">{{ $directorMetrics['onTimeRate'] }}%</span>
                        <span class="text-[10px] font-mono text-tertiary">Tepat Waktu</span>
                    </div>
                    <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-emerald-400" style="width: {{ $directorMetrics['onTimeRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono text-tertiary pt-1">
                        <span>Hadir Total: <strong class="text-on-surface">{{ $attendanceRate }}%</strong></span>
                        <span>Telat: <strong class="text-red-400">{{ $directorMetrics['lateRate'] }}%</strong></span>
                    </div>
                </div>

                <!-- KPI 2: Eksekusi Target Tugas -->
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase text-tertiary">Penyelesaian Tugas</span>
                        <span class="text-xs font-mono font-bold text-blue-400">{{ $directorMetrics['tasksCompletionRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xl font-bold font-headline text-on-surface">{{ $directorMetrics['tasksCompletionRate'] }}%</span>
                        <span class="text-[10px] font-mono text-tertiary">{{ $directorMetrics['completedTasks'] }}/{{ $directorMetrics['totalTasks'] }} Done</span>
                    </div>
                    <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-blue-400" style="width: {{ $directorMetrics['tasksCompletionRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono text-tertiary pt-1">
                        <span>Rata Progres: <strong class="text-blue-300">{{ $directorMetrics['avgTaskProgress'] }}%</strong></span>
                        <span>Jalan: <strong class="text-amber-400">{{ $directorMetrics['inProgressTasks'] }}</strong></span>
                    </div>
                </div>

                <!-- KPI 3: Pencairan Reimbursement -->
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase text-tertiary">Realisasi Reimburse</span>
                        <span class="text-xs font-mono font-bold text-purple-400">{{ $directorMetrics['paidReimburseRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xl font-bold font-headline text-on-surface">{{ $directorMetrics['paidReimburseRate'] }}%</span>
                        <span class="text-[10px] font-mono text-tertiary">Dicairkan (Paid)</span>
                    </div>
                    <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-purple-400" style="width: {{ $directorMetrics['paidReimburseRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono text-tertiary pt-1">
                        <span>Pending: <strong class="text-amber-400">{{ $directorMetrics['pendingReimburseCount'] }} Klaim</strong></span>
                        <span>Total: <strong class="text-on-surface">{{ $directorMetrics['totalReimburseCount'] }}</strong></span>
                    </div>
                </div>

                <!-- KPI 4: Resolusi Kendala GA & Fasilitas -->
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/70 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase text-tertiary">Resolusi Kendala GA</span>
                        <span class="text-xs font-mono font-bold text-emerald-400">{{ $directorMetrics['ticketResolvedRate'] }}%</span>
                    </div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-xl font-bold font-headline text-on-surface">{{ $directorMetrics['ticketResolvedRate'] }}%</span>
                        <span class="text-[10px] font-mono text-tertiary">Tertangani</span>
                    </div>
                    <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full bg-emerald-400" style="width: {{ $directorMetrics['ticketResolvedRate'] }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] font-mono text-tertiary pt-1">
                        <span>Conversion Pelamar: <strong class="text-rose-400">{{ $directorMetrics['hireConversionRate'] }}%</strong></span>
                        <span>Tiket: <strong class="text-on-surface">{{ $directorMetrics['totalTickets'] }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Grid: Live Attendance Stream & Pending Approval Requests -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Live Today Attendance Log -->
        <div class="lg:col-span-2 bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm flex flex-col">
            <div class="p-5 border-b border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <h2 class="font-headline text-base font-semibold text-on-surface">Log Kehadiran Masuk Hari Ini</h2>
                    <p class="font-mono text-xs text-tertiary">
                        @if(auth()->user()->isDirektur())
                            Cakupan: Seluruh Karyawan Perusahaan (Akses Direksi)
                        @elseif(auth()->user()->isManager())
                            Cakupan: Tim Departemen {{ auth()->user()->department }} & Manajer
                        @else
                            Cakupan: Presensi Pribadi Anda
                        @endif
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-surface-container font-mono text-xs text-secondary">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        Live
                    </span>
                    <a href="{{ route('attendance.index') }}" class="text-xs text-tertiary hover:text-on-surface hover:underline flex items-center gap-1">
                        <span>Semua log</span>
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/40">
                            <th class="px-5 py-3">Karyawan</th>
                            <th class="px-5 py-3">Waktu Masuk</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Mode & Lokasi</th>
                            <th class="px-5 py-3 text-right">Pulang</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($recentAttendances as $att)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-3.5 flex items-center gap-3">
                                    <img src="{{ $att->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" 
                                         alt="avatar" 
                                         class="w-7 h-7 rounded-full object-cover"/>
                                    <div>
                                        <a href="{{ route('employees.show', $att->employee->id) }}" class="font-medium text-on-surface hover:text-primary transition-colors">
                                            {{ $att->employee->name }}
                                        </a>
                                        <div class="font-mono text-[10px] text-tertiary">{{ $att->employee->nip }} • {{ $att->employee->department }}</div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-on-surface font-semibold">
                                    {{ substr($att->clock_in, 0, 8) }} WIB
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($att->status === 'on_time')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] font-medium">
                                            Tepat Waktu
                                        </span>
                                    @elseif($att->status === 'late')
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/20 text-red-400 font-mono text-[10px] font-medium">
                                            Terlambat
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary font-mono text-[10px]">
                                            {{ strtoupper($att->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="text-on-surface font-medium">{{ $att->work_mode }}</div>
                                    <div class="font-mono text-[10px] text-tertiary truncate max-w-[140px]">{{ $att->location_name ?? 'Kantor Utama' }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-right font-mono text-xs text-tertiary">
                                    {{ $att->clock_out ? substr($att->clock_out, 0, 5) . ' WIB' : '— Belum Pulang' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-tertiary font-mono">
                                    Belum ada data presensi yang masuk untuk hari ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right Col: Pending Approval List & Quick Action -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm flex flex-col">
            <div class="p-5 border-b border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <h2 class="font-headline text-base font-semibold text-on-surface">Persetujuan Menunggu</h2>
                    <p class="font-mono text-xs text-tertiary">
                        @if(auth()->user()->isDirektur() || auth()->user()->isHR())
                            Permohonan izin & cuti butuh aksi
                        @else
                            Status pengajuan izin & cuti Anda
                        @endif
                    </p>
                </div>
                <span class="font-mono text-xs px-2 py-0.5 bg-red-950/60 text-primary-fixed border border-red-500/20 rounded">
                    {{ $pendingApprovals->count() }} Antrean
                </span>
            </div>

            <div class="p-5 flex flex-col gap-4 flex-1">
                @forelse($pendingApprovals->take(4) as $item)
                    <div class="p-3.5 bg-surface-container rounded-lg border border-[#282a2e]/60 flex flex-col gap-2">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ $item->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" 
                                     alt="emp" 
                                     class="w-7 h-7 rounded-full object-cover"/>
                                <div>
                                    <span class="font-semibold text-xs text-on-surface block">{{ $item->employee->name }}</span>
                                    <span class="font-mono text-[10px] text-tertiary">{{ $item->employee->department }}</span>
                                </div>
                            </div>
                            <span class="font-mono text-[10px] px-2 py-0.5 rounded bg-surface-container-high text-tertiary">
                                {{ $item->type }}
                            </span>
                        </div>

                        <p class="text-xs text-tertiary italic line-clamp-2">"{{ $item->reason }}"</p>

                        <div class="flex items-center justify-between pt-1 border-t border-[#282a2e]/40 text-xs">
                            <span class="font-mono text-[10px] text-tertiary">
                                {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }} ({{ $item->duration_days }} hari)
                            </span>

                            @php
                                $myEmpId = auth()->user()->employee_id ?? (auth()->user()->employee?->id);
                                $isOwnRequest = $myEmpId && ((int)$item->employee_id === (int)$myEmpId);
                            @endphp

                            @if($isOwnRequest)
                                <span class="font-mono text-[9px] text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-500/20 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">lock</span>
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
                                            <button type="submit" class="p-1 text-tertiary hover:text-red-400 transition-colors" title="Tolak">
                                                <span class="material-symbols-outlined text-lg">close</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" onclick="return confirm('Setujui permohonan ini dan teruskan ke Manager HR?')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded text-[11px] font-semibold transition-all flex items-center gap-1 shadow-sm" title="Setujui Tahap Manager Dept">
                                                <span class="material-symbols-outlined text-sm">forward</span>
                                                <span>Setujui & Ke HR</span>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-500/20">
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
                                        <button type="submit" class="p-1 text-tertiary hover:text-red-400 transition-colors" title="Tolak">
                                            <span class="material-symbols-outlined text-lg">close</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('leave.updateStatus', $item->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" onclick="return confirm('Setujui final permohonan cuti ini?')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] font-semibold transition-all flex items-center gap-1 shadow-sm" title="Setujui Final HR">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                            <span>Setujui HR</span>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="font-mono text-[10px] text-indigo-300 bg-indigo-950/60 px-2 py-0.5 rounded border border-indigo-500/20">
                                    Menunggu Review HR
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-12 flex flex-col items-center justify-center text-center text-tertiary">
                        <span class="material-symbols-outlined text-3xl mb-2 text-emerald-400">task_alt</span>
                        <p class="text-xs font-mono">Semua permohonan telah ditinjau!</p>
                    </div>
                @endforelse

                @if($pendingApprovals->count() > 4)
                    <a href="{{ route('leave.index', ['status' => 'pending']) }}" class="mt-auto block text-center py-2 bg-surface-container hover:bg-surface-container-high rounded-lg font-mono text-xs text-tertiary hover:text-on-surface transition-colors">
                        Lihat {{ $pendingApprovals->count() - 4 }} permohonan lainnya &rarr;
                    </a>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
