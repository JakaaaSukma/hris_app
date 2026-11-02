@extends('layouts.app')

@section('title', 'Presensi & Log Kehadiran')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    filterDate: '{{ $date }}', 
    showAdmsModal: false, 
    showResetModal: false,
    showRejectModal: false,
    rejectTargetId: null,
    resetScope: 'all_time',
    photoPreviewModal: false,
    photoPreviewUrl: '',
    photoPreviewTitle: '',
    photoPreviewGps: '',
    photoPreviewMapsUrl: ''
}">

    <!-- Header Actions & Date Picker -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-secondary-fixed"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Attendance Management</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Presensi & Kehadiran Karyawan</h1>
            <p class="font-mono text-xs text-tertiary">
                @if(auth()->user()->isDirektur())
                    Cakupan: Seluruh Perusahaan • Akses Direksi
                @elseif(auth()->user()->isHR())
                    Cakupan: Seluruh Perusahaan • Otoritas Departemen HR
                @elseif(auth()->user()->isManager())
                    Cakupan: Tim Departemen {{ auth()->user()->department }} & Manajer
                @else
                    Cakupan: Catatan Presensi Pribadi Anda
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form action="{{ route('attendance.index') }}" method="GET" class="flex flex-wrap items-center gap-2 bg-surface-container-low p-1.5 rounded-xl border border-[#333539]">
                <div class="flex items-center gap-1.5 px-2">
                    <span class="material-symbols-outlined text-tertiary text-sm">calendar_month</span>
                    <input type="date" 
                           name="start_date" 
                           value="{{ $startDate }}" 
                           class="bg-transparent border-0 text-xs font-mono text-on-surface focus:outline-none focus:ring-0 p-1"
                           title="Tanggal Mulai"/>
                    <span class="text-xs text-tertiary font-mono">s/d</span>
                    <input type="date" 
                           name="end_date" 
                           value="{{ $endDate }}" 
                           class="bg-transparent border-0 text-xs font-mono text-on-surface focus:outline-none focus:ring-0 p-1"
                           title="Tanggal Akhir (Maksimal 31 Hari)"/>
                </div>
                
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif

                <button type="submit" class="px-3 py-1.5 bg-surface-container-high hover:bg-surface-bright text-xs font-mono text-primary font-semibold rounded-lg transition-colors border border-primary/20">
                    Filter Rentang
                </button>
            </form>

            <!-- Tombol Panduan Cloud Server Solution X609 -->
            @if(auth()->user()->isHR() || auth()->user()->isDirektur())
                <button type="button" @click="showAdmsModal = true" class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-high hover:bg-surface-bright text-xs font-mono text-amber-400 border border-amber-500/30 rounded-lg transition-colors shadow-sm" title="Pengaturan Mesin Finger Solution X609">
                    <span class="material-symbols-outlined text-base">cloud_sync</span>
                    <span>Setup Finger (ADMS)</span>
                </button>
            @endif

            <!-- Tombol Export PDF Laporan Presensi -->
            <a href="{{ route('reports.attendance.pdf', ['start_date' => $startDate, 'end_date' => $endDate, 'status' => request('status')]) }}" target="_blank" class="flex items-center gap-1.5 px-3.5 py-2 bg-slate-800/80 hover:bg-slate-700/80 text-xs font-mono text-rose-300 border border-rose-500/30 rounded-lg transition-all shadow-sm" title="Unduh & Cetak PDF Laporan Presensi (Rentang Tanggal Terpilih)">
                <span class="material-symbols-outlined text-base">picture_as_pdf</span>
                <span>Export PDF</span>
            </a>

            <!-- Tombol Absen Biometrik Face ID & GPS -->
            @if(auth()->user()->canFaceClock())
                <button type="button" @click="$dispatch('open-face-modal', { type: 'auto' })" 
                        class="flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs font-bold font-mono transition-all shadow-md shadow-emerald-500/20 active:scale-95" 
                        title="Presensi Wajah Mandiri dengan Verifikasi Biometrik & GPS">
                    <span class="material-symbols-outlined text-base">face_retouching_natural</span>
                    <span>Absen Face ID & Lokasi</span>
                </button>
            @endif

            <!-- Tombol Perizinan Kehadiran (Izin Terlambat, Dinas Luar, Tidak Absen Pulang) -->
            <a href="{{ route('attendance.excuses.index') }}" class="flex items-center gap-1.5 px-3.5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 rounded-lg text-xs font-semibold font-mono transition-all shadow-sm" title="Kelola & Ajukan Izin Terlambat, Dinas Luar, dan Tidak Absen Pulang">
                <span class="material-symbols-outlined text-base text-amber-400">event_upcoming</span>
                <span>Perizinan Presensi</span>
            </a>

            <!-- Tombol Pengajuan Reset Kehadiran (Manager HRD & Direktur) -->
            @if($canRequestReset)
                <button type="button" @click="showResetModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 border border-rose-500/40 rounded-lg text-xs font-semibold font-mono transition-all shadow-sm" title="Ajukan Reset Kehadiran Seluruh Karyawan & Manager">
                    <span class="material-symbols-outlined text-base text-rose-400">history_toggle_off</span>
                    <span>Reset Kehadiran</span>
                </button>
            @endif

            @if(auth()->user()->canQuickClock())
                <button @click="showClockInModal = true" class="flex items-center gap-2 px-4 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container rounded-lg text-xs font-semibold transition-all shadow-md">
                    <span class="material-symbols-outlined text-base">add</span>
                    <span>Catat Presensi</span>
                </button>
            @endif
        </div>
    </div>

    @if(session('warning'))
        <div class="flex items-center gap-3 p-4 bg-amber-950/40 border border-amber-500/40 rounded-xl text-amber-300 text-xs font-mono">
            <span class="material-symbols-outlined text-lg">warning</span>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- Banner / Panel Pengajuan Reset Kehadiran Pending --}}
    @if($pendingResetRequests->isNotEmpty())
        <div class="space-y-3">
            @foreach($pendingResetRequests as $pReq)
                @php
                    $isRequester = ($pReq->requested_by === auth()->id());
                    $canReview = false;
                    if ($pReq->requester_role === 'manager_hr' && auth()->user()->isDirektur()) {
                        $canReview = true;
                    } elseif ($pReq->requester_role === 'direktur' && auth()->user()->isHR() && auth()->user()->isManager()) {
                        $canReview = true;
                    }
                @endphp

                <div class="p-4 bg-gradient-to-r from-amber-950/40 via-surface-container to-amber-950/20 border border-amber-500/40 rounded-xl shadow-md">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="p-2.5 rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                <span class="material-symbols-outlined text-2xl">pending_actions</span>
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        Menunggu Persetujuan
                                    </span>
                                    <span class="font-mono text-xs text-tertiary">
                                        Diajukan pada {{ $pReq->created_at->isoFormat('D MMM Y, HH:mm') }} WIB
                                    </span>
                                </div>
                                <h3 class="font-headline font-bold text-base text-on-surface mt-1">
                                    Pengajuan Reset Data Kehadiran Seluruh Karyawan & Manager
                                </h3>
                                <p class="text-xs text-on-surface-variant mt-0.5">
                                    Pemohon: <strong class="text-amber-300">{{ $pReq->requester?->name ?? 'User' }}</strong> ({{ $pReq->requester_role === 'direktur' ? 'Direktur Utama' : 'Manager HRD' }}) • 
                                    Cakupan: <strong class="text-on-surface">{{ $pReq->scope === 'all_time' ? 'Seluruh Waktu (All Time)' : 'Rentang ' . $pReq->start_date?->isoFormat('D MMM Y') . ' s/d ' . $pReq->end_date?->isoFormat('D MMM Y') }}</strong>
                                </p>
                                <div class="mt-2 p-2.5 bg-black/30 rounded-lg border border-white/5 text-xs text-tertiary">
                                    <span class="text-amber-400 font-semibold">Alasan Reset:</span> "{{ $pReq->reason }}"
                                </div>
                            </div>
                        </div>

                        {{-- Tombol Aksi Approval / Status Menunggu --}}
                        <div class="flex items-center gap-2 md:self-center shrink-0">
                            @if($canReview)
                                {{-- Tombol Setujui --}}
                                <form action="{{ route('attendance.approveReset', $pReq->id) }}" method="POST" onsubmit="return confirm('PERINGATAN KRUSIAL:\nApakah Anda yakin menyetujui reset data kehadiran ini?\nData kehadiran seluruh karyawan & manager sesuai cakupan akan DIHAPUS PERMANEN dan pengumuman resmi akan disiarkan ke seluruh akun!');">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold font-mono transition-all shadow-md">
                                        <span class="material-symbols-outlined text-sm">check_circle</span>
                                        <span>Setujui & Reset</span>
                                    </button>
                                </form>

                                {{-- Tombol Tolak --}}
                                <button type="button" @click="rejectTargetId = {{ $pReq->id }}; showRejectModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 border border-rose-500/40 rounded-lg text-xs font-semibold font-mono transition-all">
                                    <span class="material-symbols-outlined text-sm">cancel</span>
                                    <span>Tolak Pengajuan</span>
                                </button>
                            @elseif($isRequester)
                                <span class="px-3 py-2 rounded-lg bg-surface-container-high border border-amber-500/30 text-amber-300 font-mono text-xs flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm animate-spin">sync</span>
                                    <span>Menunggu Persetujuan {{ $pReq->requester_role === 'manager_hr' ? 'Direktur Utama' : 'Manager HRD' }}</span>
                                </span>
                            @else
                                <span class="px-3 py-2 rounded-lg bg-surface-container-high border border-white/10 text-tertiary font-mono text-xs flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-sm">hourglass_top</span>
                                    <span>Dalam Proses Peninjauan</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Attendance Metric Summary for selected date range -->
    @php
        $presentPercentage = $totalPresent > 0 ? 100 : 0;
        $onTimePercentage = $totalPresent > 0 ? round(($totalOnTime / $totalPresent) * 100, 1) : 0;
        $latePercentage = $totalPresent > 0 ? round(($totalLate / $totalPresent) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">
                        {{ $isRange ? 'Hadir Pada Rentang Ini' : 'Hadir Pada Tanggal Ini' }}
                    </span>
                    @if(auth()->user()->isDirektur())
                        <span class="px-1.5 py-0.2 rounded bg-surface-container text-[10px] font-mono text-tertiary">All Dept</span>
                    @endif
                </div>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1 flex items-baseline gap-2">
                    <span>{{ $totalPresent }} Log</span>
                </div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-secondary">
                <span class="material-symbols-outlined text-xl">how_to_reg</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Tepat Waktu (&le; 09:15)</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        {{ $onTimePercentage }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1 flex items-baseline gap-2">
                    <span>{{ $totalOnTime }} Presensi</span>
                    <span class="text-xs font-mono text-tertiary">dari {{ $totalPresent }} total</span>
                </div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">done_all</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Terlambat (&gt; 09:15)</span>
                    <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/30 text-primary-fixed font-mono text-[10px] font-bold">
                        {{ $latePercentage }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-primary-fixed mt-1 flex items-baseline gap-2">
                    <span>{{ $totalLate }} Presensi</span>
                    <span class="text-xs font-mono text-tertiary">dari {{ $totalPresent }} total</span>
                </div>
            </div>
            <div class="p-2.5 rounded-lg bg-red-950/40 text-primary border border-red-500/20">
                <span class="material-symbols-outlined text-xl">alarm_on</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-rose-400 uppercase font-semibold">Alpa / Tidak Hadir</span>
                </div>
                <div class="font-headline text-2xl font-bold text-rose-400 mt-1 flex items-baseline gap-2">
                    <span>{{ $totalAbsent }} Pegawai</span>
                </div>
            </div>
            <div class="p-2.5 rounded-lg bg-rose-950/40 text-rose-400 border border-rose-500/20">
                <span class="material-symbols-outlined text-xl">person_off</span>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <!-- Status filter tabs -->
        <div class="p-4 border-b border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 bg-surface-container/40">
            <div class="flex items-center gap-1 font-mono text-xs">
                <a href="{{ route('attendance.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ empty($status) ? 'bg-surface-container-highest text-on-surface font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Semua ({{ $totalPresent }})
                </a>
                <a href="{{ route('attendance.index', ['start_date' => $startDate, 'end_date' => $endDate, 'status' => 'on_time']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'on_time' ? 'bg-surface-container-highest text-emerald-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Tepat Waktu ({{ $totalOnTime }})
                </a>
                <a href="{{ route('attendance.index', ['start_date' => $startDate, 'end_date' => $endDate, 'status' => 'late']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'late' ? 'bg-surface-container-highest text-primary-fixed font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Terlambat ({{ $totalLate }})
                </a>
                <a href="{{ route('attendance.index', ['start_date' => $startDate, 'end_date' => $endDate, 'status' => 'absent']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'absent' ? 'bg-surface-container-highest text-rose-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Alpa ({{ $totalAbsent }})
                </a>
            </div>

            <div class="flex items-center gap-2 font-mono text-xs text-tertiary bg-surface-container-lowest/50 px-3 py-1.5 rounded-lg border border-[#282a2e]/40">
                <span class="material-symbols-outlined text-sm text-secondary">date_range</span>
                <span>
                    @if($isRange)
                        Rentang: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') }}</strong> s/d <strong class="text-on-surface">{{ \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y') }}</strong> ({{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1 }} Hari)
                    @else
                        Tanggal: <strong class="text-on-surface">{{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }}</strong>
                    @endif
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                        <th class="px-5 py-3.5">Karyawan</th>
                        @if($isRange)
                            <th class="px-5 py-3.5">Tanggal</th>
                        @endif
                        <th class="px-5 py-3.5">Jam Masuk</th>
                        <th class="px-5 py-3.5">Jam Keluar</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Verifikasi & Foto</th>
                        <th class="px-5 py-3.5">Mode Kerja</th>
                        <th class="px-5 py-3.5">Lokasi & GPS</th>
                        <th class="px-5 py-3.5">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/30 font-body">
                    @forelse($attendances as $att)
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-5 py-4 flex items-center gap-3">
                                <img src="{{ $att->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" 
                                     alt="avatar" 
                                     class="w-8 h-8 rounded-full object-cover"/>
                                <div>
                                    <a href="{{ route('employees.show', $att->employee->id) }}" class="font-semibold text-on-surface hover:text-primary transition-colors text-sm">
                                        {{ $att->employee->name }}
                                    </a>
                                    <div class="font-mono text-[10px] text-tertiary">{{ $att->employee->nip }} • {{ $att->employee->department }}</div>
                                </div>
                            </td>
                            @if($isRange)
                                <td class="px-5 py-4 font-mono text-xs text-secondary-fixed font-medium whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($att->date)->translatedFormat('d M Y') }}
                                </td>
                            @endif
                            <td class="px-5 py-4 font-mono text-xs text-on-surface font-semibold">
                                {{ $att->clock_in ? substr($att->clock_in, 0, 8) . ' WIB' : '— Tidak Scan' }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                {{ $att->clock_out ? substr($att->clock_out, 0, 8) . ' WIB' : ($att->status === 'absent' ? '— Alpa' : '— Belum Pulang') }}
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $isDispensationApproved = str_contains($att->notes ?? '', 'Dispensasi') && (str_contains($att->notes ?? '', 'Resmi') || str_contains($att->notes ?? '', 'Disetujui'));
                                    $isDispensationPending = str_contains($att->notes ?? '', 'Dispensasi') && str_contains($att->notes ?? '', 'Menunggu');
                                @endphp
                                @if($att->status === 'on_time')
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="px-2.5 py-1 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] font-semibold">
                                            Tepat Waktu
                                        </span>
                                        @if($isDispensationApproved)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-amber-950/70 border border-amber-500/30 text-amber-300 font-mono text-[9px] font-semibold">
                                                <span class="material-symbols-outlined text-[11px] text-amber-400">verified</span>
                                                Dispensasi
                                            </span>
                                        @endif
                                    </div>
                                @elseif($att->status === 'late')
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="px-2.5 py-1 rounded bg-amber-950/60 border border-amber-500/20 text-amber-400 font-mono text-[10px] font-semibold">
                                            Terlambat
                                        </span>
                                        @if($isDispensationPending)
                                            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-yellow-950/70 border border-yellow-500/30 text-yellow-300 font-mono text-[9px]">
                                                <span class="material-symbols-outlined text-[11px] text-yellow-400">hourglass_top</span>
                                                Dispensasi Menunggu
                                            </span>
                                        @endif
                                    </div>
                                @elseif($att->status === 'absent')
                                    <span class="px-2.5 py-1 rounded bg-red-950/80 border border-red-500/30 text-rose-400 font-mono text-[10px] font-bold">
                                        Alpa (Absent)
                                    </span>
                                @elseif($att->status === 'leave')
                                    <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-500/20 text-blue-400 font-mono text-[10px] font-semibold">
                                        Cuti
                                    </span>
                                @elseif($att->status === 'permit')
                                    <span class="px-2.5 py-1 rounded bg-purple-950/60 border border-purple-500/20 text-purple-400 font-mono text-[10px] font-semibold">
                                        Izin/Sakit
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded bg-surface-container text-tertiary font-mono text-[10px]">
                                        {{ strtoupper($att->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-1.5 items-start">
                                    @if($att->verification_method === 'face_location')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-950/70 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-semibold">
                                            <span class="material-symbols-outlined text-[13px] text-emerald-400">face_retouching_natural</span>
                                            <span>Face ID + GPS</span>
                                        </span>
                                    @elseif($att->verification_method === 'adms_fingerprint')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/20 text-blue-300 font-mono text-[10px]">
                                            <span class="material-symbols-outlined text-[12px] text-blue-400">fingerprint</span>
                                            <span>Finger ADMS</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]">
                                            <span class="material-symbols-outlined text-[12px]">desktop_windows</span>
                                            <span>Web Manual</span>
                                        </span>
                                    @endif

                                    <!-- Foto Snapshot Masuk & Pulang -->
                                    <div class="flex items-center gap-1.5">
                                        @if($att->photo_in)
                                            <button type="button" 
                                                    @click="photoPreviewUrl = '{{ $att->photo_in_url }}'; photoPreviewTitle = 'Foto Wajah Masuk (Clock In) - {{ $att->employee->name }}'; photoPreviewGps = '{{ $att->latitude && $att->longitude ? $att->latitude . ', ' . $att->longitude : '' }}'; photoPreviewMapsUrl = '{{ $att->google_maps_url }}'; photoPreviewModal = true;"
                                                    class="group relative inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-[10px] font-mono text-slate-200 transition-all shadow-sm">
                                                <img src="{{ $att->photo_in_url }}" class="w-4 h-4 rounded object-cover" alt="Face In"/>
                                                <span>Foto Masuk</span>
                                            </button>
                                        @endif
                                        @if($att->photo_out)
                                            <button type="button" 
                                                    @click="photoPreviewUrl = '{{ $att->photo_out_url }}'; photoPreviewTitle = 'Foto Wajah Pulang (Clock Out) - {{ $att->employee->name }}'; photoPreviewGps = '{{ $att->latitude && $att->longitude ? $att->latitude . ', ' . $att->longitude : '' }}'; photoPreviewMapsUrl = '{{ $att->google_maps_url }}'; photoPreviewModal = true;"
                                                    class="group relative inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-[10px] font-mono text-slate-200 transition-all shadow-sm">
                                                <img src="{{ $att->photo_out_url }}" class="w-4 h-4 rounded object-cover" alt="Face Out"/>
                                                <span>Foto Pulang</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 rounded bg-surface-container text-xs font-medium text-on-surface">
                                    {{ $att->work_mode }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-on-surface font-medium">{{ $att->location_name ?? 'Headquarters' }}</div>
                                @if($att->latitude && $att->longitude)
                                    <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                        <a href="{{ $att->google_maps_url }}" target="_blank" class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] hover:underline" title="Buka di Google Maps">
                                            <span class="material-symbols-outlined text-[11px]">pin_drop</span>
                                            <span>{{ number_format($att->latitude, 4) }}, {{ number_format($att->longitude, 4) }}</span>
                                            <span class="material-symbols-outlined text-[10px]">open_in_new</span>
                                        </a>
                                        @if($att->distance_meters !== null)
                                            <span class="text-[10px] font-mono text-slate-400">({{ $att->distance_meters }}m)</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="font-mono text-[10px] text-tertiary">{{ $att->ip_address ?? '127.0.0.1' }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs max-w-sm">
                                @if($att->notes)
                                    @php
                                        $hasManualTag = str_contains($att->notes, '[Diabsenkan') || str_contains($att->notes, '[Absen');
                                        $isDispensation = str_contains($att->notes, 'Dispensasi');
                                    @endphp
                                    @if($isDispensation)
                                        @php
                                            $dispColor = str_contains($att->notes, 'DITOLAK') 
                                                ? 'bg-rose-950/60 border-rose-500/30 text-rose-300' 
                                                : (str_contains($att->notes, 'Resmi') || str_contains($att->notes, 'Disetujui') 
                                                    ? 'bg-amber-950/60 border-amber-500/30 text-amber-300' 
                                                    : 'bg-yellow-950/60 border-yellow-500/30 text-yellow-300');
                                            $dispIcon = str_contains($att->notes, 'DITOLAK') 
                                                ? 'cancel' 
                                                : (str_contains($att->notes, 'Resmi') || str_contains($att->notes, 'Disetujui') 
                                                    ? 'verified' 
                                                    : 'schedule');
                                        @endphp
                                        <div class="inline-flex items-start gap-1.5 px-2.5 py-1 rounded border {{ $dispColor }} font-sans text-xs leading-relaxed">
                                            <span class="material-symbols-outlined text-sm mt-0.5 shrink-0">{{ $dispIcon }}</span>
                                            <span>{{ $att->notes }}</span>
                                        </div>
                                    @elseif($hasManualTag)
                                        <div class="space-y-1">
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/30 text-blue-300 font-mono text-[10px] leading-tight">
                                                <span class="material-symbols-outlined text-xs text-blue-400">admin_panel_settings</span>
                                                <span>{{ $att->notes }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-tertiary italic">{{ $att->notes }}</span>
                                    @endif
                                @else
                                    <span class="text-tertiary font-mono">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isRange ? 8 : 7 }}" class="px-5 py-10 text-center text-tertiary font-mono">
                                Tidak ada log kehadiran untuk {{ $isRange ? 'rentang tanggal terpilih' : 'tanggal ini' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="p-4 border-t border-[#282a2e]/40">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Panduan Konfigurasi Cloud Server / ADMS Solution X609 -->
    <div x-cloak x-show="showAdmsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 ">
        <div class="bg-surface-container rounded-2xl border border-amber-500/30 w-full max-w-2xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="showAdmsModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">cloud_sync</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Konfigurasi ADMS / Cloud Server Mesin Solution X609</h3>
                        <p class="text-xs text-tertiary font-mono">Sinkronisasi Log Presensi Otomatis Real-time Langsung ke Sistem</p>
                    </div>
                </div>
                <button @click="showAdmsModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="mt-4 space-y-4 text-xs font-mono">
                <!-- Info Status Endpoint -->
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/70 space-y-2">
                    <span class="text-amber-400 font-semibold block uppercase">1. Parameter Pengaturan di Mesin Solution X609</span>
                    <p class="text-tertiary font-sans">
                        Buka layar mesin: Masuk ke <strong>Menu Utama &rarr; COMM. Settings &rarr; Cloud Server Setting (atau ADMS)</strong>. Masukkan nilai berikut:
                    </p>
                    <div class="space-y-1.5 pt-1">
                        <div class="flex items-center justify-between p-2 rounded bg-surface-container border border-[#333539]">
                            <span class="text-tertiary">Enable Cloud Server / ADMS:</span>
                            <span class="font-bold text-emerald-400">ON (Centang/Aktifkan)</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-surface-container border border-[#333539]">
                            <span class="text-tertiary">Server Mode:</span>
                            <span class="font-bold text-on-surface">ADMS</span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-surface-container border border-[#333539]">
                            <span class="text-tertiary">Server Address / IP:</span>
                            <span class="font-bold text-amber-300">192.168.1.39 <span class="text-tertiary font-normal">(atau domain server Anda)</span></span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-surface-container border border-[#333539]">
                            <span class="text-tertiary">Server Port:</span>
                            <span class="font-bold text-amber-300">8000 <span class="text-tertiary font-normal">(atau 80 jika di hosting)</span></span>
                        </div>
                        <div class="flex items-center justify-between p-2 rounded bg-surface-container border border-[#333539]">
                            <span class="text-tertiary">Enable Proxy Server:</span>
                            <span class="text-tertiary">OFF</span>
                        </div>
                    </div>
                </div>

                <!-- Jalur URL & Format Data -->
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/70 space-y-2 font-sans">
                    <span class="text-emerald-400 font-mono font-semibold block uppercase">2. Cara Kerja Pencatatan Real-Time</span>
                    <ul class="list-disc list-inside space-y-1.5 text-tertiary text-xs">
                        <li><strong>ID Pegawai (PIN) di Mesin:</strong> Pastikan ID Karyawan yang didaftarkan di mesin Solution X609 sesuai dengan <strong>NIP</strong> atau <strong>ID Karyawan</strong> di sistem HRIS.</li>
                        <li><strong>Auto-Push:</strong> Saat karyawan menempelkan sidik jari/fingerprint, mesin Solution X609 secara otomatis mengirim data HTTP POST ke <code class="text-amber-400 font-mono text-[11px]">/iclock/cdata</code>.</li>
                        <li><strong>Jam Kerja:</strong> Jam masuk adalah <strong>09:00 WIB</strong> (dengan toleransi 15 menit hingga <strong>09:15 WIB</strong>) dan jam pulang adalah <strong>18:00 WIB</strong>.</li>
                        <li><strong>Auto Status:</strong> Jika absen masuk dilakukan sebelum/tepat jam <strong>09:15 WIB</strong> otomatis berstatus <strong>Tepat Waktu</strong>, dan di atas jam <strong>09:15 WIB</strong> otomatis <strong>Terlambat</strong>.</li>
                        <li>Absen scan pertama hari itu otomatis tercatat sebagai <strong>Clock In</strong>, dan scan berikutnya saat jam pulang (mendekati/setelah jam 18:00 WIB atau saat checkout) otomatis memperbarui <strong>Clock Out</strong>.</li>
                    </ul>
                </div>

                <!-- Test Connection -->
                <div class="p-3 bg-surface-container-highest/40 rounded-xl border border-[#333539] flex items-center justify-between">
                    <div>
                        <span class="text-on-surface font-semibold block font-sans">Status Protokol ADMS Server:</span>
                        <span class="text-[10px] text-emerald-400 flex items-center gap-1 mt-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Endpoint /iclock/cdata Siap Menerima Koneksi
                        </span>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-950/60 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold">
                        ACTIVE LISTENER
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-[#282a2e] mt-4">
                <button type="button" @click="showAdmsModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                    Selesai & Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- Modal Pengajuan Reset Kehadiran (Manager HRD / Direktur) --}}
    @if($canRequestReset)
        <div x-cloak x-show="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
            <div class="bg-surface-container rounded-2xl border border-rose-500/40 w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="showResetModal = false">
                <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30">
                            <span class="material-symbols-outlined text-xl">history_toggle_off</span>
                        </span>
                        <div>
                            <h3 class="font-headline font-bold text-base text-on-surface">Form Pengajuan Reset Kehadiran</h3>
                            <p class="text-xs text-tertiary font-mono">
                                Membutuhkan persetujuan {{ auth()->user()->isDirektur() ? 'Manager HRD' : 'Direktur Utama' }}
                            </p>
                        </div>
                    </div>
                    <button @click="showResetModal = false" class="text-tertiary hover:text-on-surface p-1">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form action="{{ route('attendance.resetRequest') }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <!-- Alert Ketentuan Approval -->
                    <div class="p-3 bg-amber-950/30 border border-amber-500/30 rounded-xl text-amber-200 text-xs space-y-1">
                        <div class="font-bold flex items-center gap-1.5 text-amber-300">
                            <span class="material-symbols-outlined text-sm">security</span>
                            <span>Ketentuan Keamanan & Prosedur Dua Arah:</span>
                        </div>
                        <p class="text-[11px] text-tertiary leading-relaxed">
                            @if(auth()->user()->isDirektur())
                                Pengajuan yang Anda buat akan diteruskan ke <strong>Manager HRD</strong> untuk ditinjau dan disetujui. Setelah disetujui, notifikasi pengumuman resmi akan otomatis dikirimkan ke seluruh karyawan dan manager.
                            @else
                                Pengajuan yang Anda buat akan diteruskan ke <strong>Direktur Utama</strong> untuk ditinjau dan disetujui. Setelah disetujui, notifikasi pengumuman resmi akan otomatis dikirimkan ke seluruh karyawan dan manager.
                            @endif
                        </p>
                    </div>

                    <!-- Pilihan Cakupan Reset -->
                    <div>
                        <label class="block text-xs font-mono text-tertiary uppercase mb-2">Cakupan Waktu Reset <span class="text-rose-400">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                                   :class="resetScope === 'all_time' ? 'bg-rose-950/40 border-rose-500/60 text-rose-200' : 'bg-surface-container-low border-[#282a2e] text-tertiary'">
                                <input type="radio" name="scope" value="all_time" x-model="resetScope" class="text-rose-500 focus:ring-0">
                                <div>
                                    <span class="block text-xs font-semibold">Semua Waktu</span>
                                    <span class="block text-[10px] text-tertiary">Seluruh data presensi</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                                   :class="resetScope === 'date_range' ? 'bg-rose-950/40 border-rose-500/60 text-rose-200' : 'bg-surface-container-low border-[#282a2e] text-tertiary'">
                                <input type="radio" name="scope" value="date_range" x-model="resetScope" class="text-rose-500 focus:ring-0">
                                <div>
                                    <span class="block text-xs font-semibold">Rentang Tanggal</span>
                                    <span class="block text-[10px] text-tertiary">Periode tanggal tertentu</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Input Rentang Tanggal (Kondisional jika date_range) -->
                    <div x-show="resetScope === 'date_range'" x-cloak class="grid grid-cols-2 gap-3 p-3 bg-surface-container-low rounded-xl border border-[#282a2e]">
                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Tanggal Mulai <span class="text-rose-400">*</span></label>
                            <input type="date" name="start_date" class="w-full bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-rose-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Tanggal Akhir <span class="text-rose-400">*</span></label>
                            <input type="date" name="end_date" class="w-full bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-rose-500 font-mono">
                        </div>
                    </div>

                    <!-- Alasan Pengajuan (Wajib) -->
                    <div>
                        <label class="block text-xs font-mono text-tertiary uppercase mb-1">
                            Alasan / Keterangan Reset <span class="text-rose-400">*</span>
                        </label>
                        <textarea name="reason" rows="3" required minlength="10" placeholder="Jelaskan alasan keperluan reset data presensi ini (minimal 10 karakter)..." class="w-full bg-surface-container-low border border-[#282a2e] rounded-xl px-3.5 py-2 text-xs text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#282a2e]">
                        <button type="button" @click="showResetModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                            Batal
                        </button>
                        <button type="submit" class="flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold font-mono transition-colors shadow-md">
                            <span class="material-symbols-outlined text-sm">send</span>
                            <span>Kirim Pengajuan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Form Penolakan (Reject Modal) --}}
    <div x-cloak x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-rose-500/40 w-full max-w-md p-6 shadow-2xl relative" @click.away="showRejectModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30">
                        <span class="material-symbols-outlined text-xl">block</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Tolak Pengajuan Reset</h3>
                        <p class="text-xs text-tertiary font-mono">Sertakan alasan penolakan resmi</p>
                    </div>
                </div>
                <button @click="showRejectModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'/attendance/reset-requests/' + rejectTargetId + '/reject'" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary uppercase mb-1">
                        Alasan Penolakan <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="rejection_reason" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan secara jelas..." class="w-full bg-surface-container-low border border-[#282a2e] rounded-xl px-3.5 py-2 text-xs text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold font-mono transition-colors shadow-md">
                        <span class="material-symbols-outlined text-sm">cancel</span>
                        <span>Konfirmasi Tolak</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Preview Foto Wajah Face ID & GPS -->
    <div x-cloak x-show="photoPreviewModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-md p-5 shadow-2xl relative"
             @click.away="photoPreviewModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-400 text-xl">face</span>
                    <h3 class="font-headline font-bold text-sm text-white" x-text="photoPreviewTitle"></h3>
                </div>
                <button type="button" @click="photoPreviewModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>

            <div class="mt-4 flex flex-col items-center">
                <div class="w-full aspect-[4/3] rounded-xl overflow-hidden bg-black border border-slate-700 flex items-center justify-center">
                    <img :src="photoPreviewUrl" alt="Face Snapshot" class="w-full h-full object-cover"/>
                </div>

                <template x-if="photoPreviewGps">
                    <div class="w-full mt-3 p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between font-mono text-xs">
                        <div class="flex items-center gap-2 text-slate-300">
                            <span class="material-symbols-outlined text-emerald-400 text-sm">location_on</span>
                            <span x-text="'GPS: ' + photoPreviewGps"></span>
                        </div>
                        <a :href="photoPreviewMapsUrl" target="_blank" class="px-2 py-1 rounded bg-emerald-500/10 text-emerald-400 hover:underline text-[11px] font-semibold flex items-center gap-1 border border-emerald-500/20">
                            <span>Google Maps</span>
                            <span class="material-symbols-outlined text-xs">open_in_new</span>
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

