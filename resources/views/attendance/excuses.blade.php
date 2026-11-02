@extends('layouts.app')

@section('title', 'Perizinan Presensi & Kehadiran')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openExcuseModal: false,
    showRejectModal: false,
    rejectTargetId: null,
    selectedType: 'late_permission'
}">

    <!-- Header & Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Attendance Exceptions & Permits</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Perizinan & Dispensasi Kehadiran</h1>
            <p class="font-mono text-xs text-tertiary">
                Pengajuan Izin Terlambat, Izin Dinas Luar, dan Keterangan Tidak Absen Pulang kepada Tim HR
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('attendance.index') }}" class="flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-low hover:bg-surface-container text-xs font-mono text-tertiary hover:text-on-surface border border-[#333539] rounded-lg transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                <span>Log Presensi</span>
            </a>

            <button @click="openExcuseModal = true" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white rounded-lg text-xs font-semibold transition-all shadow-md active:scale-95">
                <span class="material-symbols-outlined text-base">post_add</span>
                <span>Ajukan Izin / Keterangan</span>
            </button>
        </div>
    </div>

    <!-- 4-Grid Metrik Status & Jenis Izin -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Menunggu HR -->
        <a href="{{ route('attendance.excuses.index', ['status' => 'pending']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-amber-500/40 transition-all flex items-center justify-between group">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Menunggu HR</span>
                    @if($pendingCount > 0)
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    @endif
                </div>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $pendingCount }} Pengajuan</div>
                <div class="text-[10px] font-mono text-tertiary mt-0.5">Perlu ditinjau HR</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-xl">pending_actions</span>
            </div>
        </a>

        <!-- Izin Terlambat -->
        <a href="{{ route('attendance.excuses.index', ['type' => 'late_permission']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-orange-500/40 transition-all flex items-center justify-between group">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Izin Terlambat</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $lateCount }} Pending</div>
                <div class="text-[10px] font-mono text-tertiary mt-0.5">Dispensasi jam masuk</div>
            </div>
            <div class="p-2.5 rounded-lg bg-orange-950/40 text-orange-400 border border-orange-500/20 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-xl">alarm_on</span>
            </div>
        </a>

        <!-- Izin Dinas Luar -->
        <a href="{{ route('attendance.excuses.index', ['type' => 'duty_trip']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-blue-500/40 transition-all flex items-center justify-between group">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Izin Dinas Luar</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $dutyCount }} Pending</div>
                <div class="text-[10px] font-mono text-tertiary mt-0.5">Tugas dinas eksternal</div>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-xl">commute</span>
            </div>
        </a>

        <!-- Tidak Absen Pulang -->
        <a href="{{ route('attendance.excuses.index', ['type' => 'missed_clock_out']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-purple-500/40 transition-all flex items-center justify-between group">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Tidak Absen Pulang</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $missedOutCount }} Pending</div>
                <div class="text-[10px] font-mono text-tertiary mt-0.5">Koreksi scan keluar</div>
            </div>
            <div class="p-2.5 rounded-lg bg-purple-950/40 text-purple-400 border border-purple-500/20 group-hover:scale-110 transition-transform">
                <span class="material-symbols-outlined text-xl">logout</span>
            </div>
        </a>
    </div>

    <!-- Table Section -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <!-- Filter Tabs -->
        <div class="p-4 border-b border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 bg-surface-container/30">
            <!-- Filter Status -->
            <div class="flex flex-wrap items-center gap-1 font-mono text-xs">
                <a href="{{ route('attendance.excuses.index') }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ empty($status) && empty($type) ? 'bg-surface-container-highest text-on-surface font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Semua
                </a>
                <a href="{{ route('attendance.excuses.index', ['status' => 'pending']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'pending' ? 'bg-surface-container-highest text-amber-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Menunggu ({{ $pendingCount }})
                </a>
                <a href="{{ route('attendance.excuses.index', ['status' => 'approved']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'approved' ? 'bg-surface-container-highest text-emerald-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Disetujui ({{ $approvedCount }})
                </a>
                <a href="{{ route('attendance.excuses.index', ['status' => 'rejected']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'rejected' ? 'bg-surface-container-highest text-rose-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Ditolak ({{ $rejectedCount }})
                </a>
            </div>

            <!-- Filter Kategori Tipe -->
            <div class="flex items-center gap-1 font-mono text-xs">
                <span class="text-tertiary text-[11px] mr-1 hidden sm:inline">Jenis:</span>
                <a href="{{ route('attendance.excuses.index', ['type' => 'late_permission', 'status' => $status]) }}" 
                   class="px-2.5 py-1 rounded-md text-[11px] transition-colors {{ $type === 'late_permission' ? 'bg-amber-500/20 text-amber-300 font-bold border border-amber-500/30' : 'text-tertiary hover:text-on-surface' }}">
                    Izin Terlambat
                </a>
                <a href="{{ route('attendance.excuses.index', ['type' => 'duty_trip', 'status' => $status]) }}" 
                   class="px-2.5 py-1 rounded-md text-[11px] transition-colors {{ $type === 'duty_trip' ? 'bg-blue-500/20 text-blue-300 font-bold border border-blue-500/30' : 'text-tertiary hover:text-on-surface' }}">
                    Dinas Luar
                </a>
                <a href="{{ route('attendance.excuses.index', ['type' => 'missed_clock_out', 'status' => $status]) }}" 
                   class="px-2.5 py-1 rounded-md text-[11px] transition-colors {{ $type === 'missed_clock_out' ? 'bg-purple-500/20 text-purple-300 font-bold border border-purple-500/30' : 'text-tertiary hover:text-on-surface' }}">
                    Tidak Absen Pulang
                </a>
            </div>
        </div>

        <!-- Table Data -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                        <th class="px-5 py-3.5">Karyawan / Pemohon</th>
                        <th class="px-5 py-3.5">Jenis Perizinan</th>
                        <th class="px-5 py-3.5">Tanggal Izin</th>
                        <th class="px-5 py-3.5">Jam Terkait</th>
                        <th class="px-5 py-3.5">Alasan / Keterangan</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi HR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/30 font-body">
                    @forelse($excuses as $item)
                        @php
                            $isMyRequest = $currentUser && $item->employee_id === $currentUser->id;
                            $canApprove = $authUser->canApproveLeaves() && !$isMyRequest;
                        @endphp
                        <tr class="hover:bg-surface-container/60 transition-colors">
                            <!-- Karyawan -->
                            <td class="px-5 py-4">
                                <div class="font-semibold text-on-surface text-sm">
                                    {{ $item->employee->name ?? 'Pegawai' }}
                                    @if($isMyRequest)
                                        <span class="ml-1 text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700">Saya</span>
                                    @endif
                                </div>
                                <div class="font-mono text-[10px] text-tertiary">
                                    {{ $item->employee->nip ?? '—' }} • {{ $item->employee->department ?? 'General' }}
                                </div>
                            </td>

                            <!-- Jenis Perizinan -->
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md font-mono text-[11px] font-semibold {{ $item->type_badge_class }}">
                                    <span class="material-symbols-outlined text-sm">{{ $item->type_icon }}</span>
                                    <span>{{ $item->type_name }}</span>
                                </span>
                            </td>

                            <!-- Tanggal Izin -->
                            <td class="px-5 py-4 font-mono text-xs text-on-surface font-medium whitespace-nowrap">
                                {{ $item->date ? $item->date->translatedFormat('d M Y') : '—' }}
                                <div class="text-[10px] text-tertiary">{{ $item->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Jam Terkait -->
                            <td class="px-5 py-4 font-mono text-xs text-tertiary whitespace-nowrap">
                                @if($item->expected_time)
                                    <span class="text-on-surface font-semibold">{{ substr($item->expected_time, 0, 5) }} WIB</span>
                                    <div class="text-[10px]">
                                        {{ $item->type === 'late_permission' ? 'Perkiraan Tiba' : ($item->type === 'missed_clock_out' ? 'Jam Pulang Aktual' : 'Jam Operasional') }}
                                    </div>
                                @else
                                    <span>— Standar</span>
                                @endif
                            </td>

                            <!-- Alasan -->
                            <td class="px-5 py-4 text-xs text-tertiary max-w-xs">
                                <div class="text-on-surface line-clamp-2" title="{{ $item->reason }}">{{ $item->reason }}</div>
                                @if($item->status === 'rejected' && $item->rejection_note)
                                    <div class="mt-1 text-[11px] font-mono text-rose-400 bg-rose-950/40 p-1.5 rounded border border-rose-500/20">
                                        <strong>Catatan Penolakan:</strong> {{ $item->rejection_note }}
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($item->status === 'pending_manager')
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-semibold w-max">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                            Menunggu Mgr Dept
                                        </span>
                                        <span class="font-mono text-[9px] text-amber-300/80">Dept: {{ $item->employee->department ?? '-' }}</span>
                                    </div>
                                @elseif($item->status === 'pending_hr')
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-indigo-950/60 border border-indigo-500/30 text-indigo-300 font-mono text-[10px] font-semibold w-max">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                            Menunggu Mgr HR
                                        </span>
                                        @if($item->managerApprover)
                                            <span class="font-mono text-[9px] text-emerald-400/90 flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[10px]">check</span>
                                                <span>Ok Dept: {{ $item->managerApprover->name }}</span>
                                            </span>
                                        @endif
                                    </div>
                                @elseif($item->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                        Menunggu Review HR
                                    </span>
                                @elseif($item->status === 'approved')
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-semibold w-max">
                                            <span class="material-symbols-outlined text-[12px]">verified</span>
                                            Disetujui HR
                                        </span>
                                        <div class="font-mono text-[9px] text-tertiary">HR: {{ $item->approver?->name ?? 'HR / Direktur' }}</div>
                                        @if($item->managerApprover)
                                            <div class="font-mono text-[9px] text-slate-400">Dept: {{ $item->managerApprover->name }}</div>
                                        @endif
                                    </div>
                                @elseif($item->status === 'rejected')
                                    <div class="flex flex-col gap-0.5">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-red-950/60 border border-red-500/30 text-rose-400 font-mono text-[10px] font-semibold w-max">
                                            <span class="material-symbols-outlined text-[12px]">cancel</span>
                                            Ditolak
                                        </span>
                                        @if($item->approver)
                                            <div class="font-mono text-[9px] text-tertiary">Oleh: {{ $item->approver->name }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Aksi Approval Berjenjang -->
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                @php
                                    $isPendingStage = in_array($item->status, ['pending', 'pending_manager', 'pending_hr']);
                                @endphp

                                @if($isPendingStage)
                                    @if($isMyRequest)
                                        <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-1 rounded border border-amber-500/30 inline-flex items-center gap-1" title="Anda tidak dapat menyetujui izin milik sendiri">
                                            <span class="material-symbols-outlined text-xs">lock</span>
                                            <span>Pengajuan Sendiri</span>
                                        </span>
                                    @elseif($item->status === 'pending_manager')
                                        @if($authUser->canApproveDepartmentLeave($item))
                                            <div class="inline-flex items-center gap-1.5">
                                                <!-- Tolak Button -->
                                                <button type="button" 
                                                        @click="rejectTargetId = {{ $item->id }}; showRejectModal = true" 
                                                        class="p-1.5 rounded-lg bg-surface-container-high hover:bg-rose-950/50 text-tertiary hover:text-rose-400 border border-[#333539] hover:border-rose-500/30 transition-all" 
                                                        title="Tolak Pengajuan">
                                                    <span class="material-symbols-outlined text-base">close</span>
                                                </button>

                                                <!-- Setujui & Ke HR Button -->
                                                <form action="{{ route('attendance.excuses.updateStatus', $item->id) }}" method="POST" onsubmit="return confirm('Setujui izin ini dan teruskan ke Manager HR?');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white font-mono text-xs font-semibold shadow-sm transition-all active:scale-95" title="Setujui dan teruskan ke Manager HR">
                                                        <span class="material-symbols-outlined text-sm">forward</span>
                                                        <span>Setujui & Ke HR</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-1 rounded border border-amber-500/20">
                                                Menunggu Mgr {{ $item->employee->department ?? 'Dept' }}
                                            </span>
                                        @endif
                                    @elseif($authUser->canApproveLeaves())
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Tolak Button -->
                                            <button type="button" 
                                                    @click="rejectTargetId = {{ $item->id }}; showRejectModal = true" 
                                                    class="p-1.5 rounded-lg bg-surface-container-high hover:bg-rose-950/50 text-tertiary hover:text-rose-400 border border-[#333539] hover:border-rose-500/30 transition-all" 
                                                    title="Tolak Pengajuan">
                                                <span class="material-symbols-outlined text-base">close</span>
                                            </button>

                                            <!-- Setujui Final HR Button -->
                                            <form action="{{ route('attendance.excuses.updateStatus', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin menyetujui pengajuan perizinan kehadiran ini? Catatan presensi karyawan terkait akan disinkronisasikan otomatis.');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-mono text-xs font-semibold shadow-sm transition-all active:scale-95" title="Setujui Final HR">
                                                    <span class="material-symbols-outlined text-sm">check</span>
                                                    <span>Setujui Final HR</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="font-mono text-[10px] text-indigo-300 bg-indigo-950/60 px-2 py-1 rounded border border-indigo-500/20">
                                            Menunggu Persetujuan HR
                                        </span>
                                    @endif
                                @else
                                    <span class="font-mono text-[10px] text-tertiary">
                                        {{ $item->action_at ? $item->action_at->translatedFormat('d M, H:i') : 'Selesai' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-tertiary font-mono">
                                Belum ada riwayat pengajuan izin keterlambatan, dinas luar, atau keterangan tidak absen pulang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($excuses->hasPages())
            <div class="p-4 border-t border-[#282a2e]/40">
                {{ $excuses->links() }}
            </div>
        @endif
    </div>

    <!-- Modal Form: Ajukan Izin Kehadiran Baru -->
    <div x-cloak x-show="openExcuseModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         @keydown.escape.window="openExcuseModal = false">
        
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative"
             @click.away="openExcuseModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-amber-400 text-2xl">event_upcoming</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Form Pengajuan Perizinan Presensi</h3>
                        <p class="text-xs text-tertiary font-mono">Diajukan ke Tim HR untuk verifikasi & sinkronisasi log</p>
                    </div>
                </div>
                <button @click="openExcuseModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('attendance.excuses.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf

                <!-- Pemohon Karyawan -->
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Nama Pegawai Pemohon</label>
                    @if($authUser->canApproveLeaves())
                        <select name="employee_id" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-amber-400">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $currentUser && $currentUser->id === $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->nip }}) — {{ $emp->department }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" readonly value="{{ $currentUser ? $currentUser->name . ' (' . $currentUser->nip . ') — ' . $currentUser->department : $authUser->name }}" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-tertiary cursor-not-allowed">
                        <input type="hidden" name="employee_id" value="{{ $currentUser->id ?? '' }}">
                    @endif
                </div>

                <!-- Jenis Perizinan -->
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Jenis Perizinan Presensi <span class="text-rose-400">*</span></label>
                    <select name="type" x-model="selectedType" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-amber-400 font-mono">
                        <option value="late_permission">1. Izin Terlambat (Dispensasi Keterlambatan Masuk Kantor)</option>
                        <option value="duty_trip">2. Izin Dinas Luar (Meeting Client / Operasional Lapangan)</option>
                        <option value="missed_clock_out">3. Keterangan Tidak Absen Pulang (Lupa Tap / Kendala Mesin)</option>
                    </select>
                </div>

                <!-- Tanggal & Jam -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Tanggal Permohonan <span class="text-rose-400">*</span></label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-amber-400 font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5" x-text="selectedType === 'late_permission' ? 'Perkiraan Jam Tiba' : (selectedType === 'missed_clock_out' ? 'Jam Keluar Aktual' : 'Jam Mulai Dinas')">Jam Terkait</label>
                        <input type="time" name="expected_time" value="09:30" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-amber-400 font-mono"/>
                    </div>
                </div>

                <!-- Alasan / Keterangan Lengkap -->
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">
                        Alasan / Keterangan Lengkap <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Contoh kendala: Ban kendaraan bocor di jalan tol, meeting client di SCBD, atau mesin finger error saat jam pulang kantor..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-amber-400"></textarea>
                </div>

                <!-- Info Prosedur -->
                <div class="p-3 bg-slate-900/80 border border-slate-800 rounded-xl text-[11px] font-mono text-slate-400 space-y-1.5">
                    <div class="text-slate-300 font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs text-amber-400">info</span>
                        <span>Catatan Otomasi & Prosedur:</span>
                    </div>
                    <p class="leading-relaxed text-slate-300">
                        <strong class="text-amber-300">Khusus Izin Terlambat (Dispensasi):</strong> Saat diajukan, log presensi Anda otomatis tercatat dengan jam masuk sesuai perkiraan jam tiba. Setelah disetujui berjenjang oleh <strong>Manager Departemen</strong> dan <strong>Manager HR</strong>, status kehadiran akan resmi disahkan <span class="text-emerald-400 font-bold">Tepat Waktu</span> beserta catatan alasan dispensasi.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openExcuseModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-rose-600 to-rose-700 hover:from-rose-500 hover:to-rose-600 text-white text-xs font-bold font-mono rounded-lg transition-all shadow-md active:scale-95">
                        Kirim Pengajuan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Penolakan (Reject Modal) -->
    <div x-cloak x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-rose-500/40 w-full max-w-md p-6 shadow-2xl relative" @click.away="showRejectModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30">
                        <span class="material-symbols-outlined text-xl">block</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Tolak Pengajuan Perizinan</h3>
                        <p class="text-xs text-tertiary font-mono">Sertakan alasan penolakan untuk pemohon</p>
                    </div>
                </div>
                <button @click="showRejectModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'/attendance/excuses/' + rejectTargetId + '/status'" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">
                        Alasan Penolakan <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="rejection_note" rows="3" required minlength="5" placeholder="Tuliskan alasan penolakan secara jelas dan formal..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface placeholder:text-tertiary/60 focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="showRejectModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-bold font-mono transition-colors shadow-md">
                        Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
