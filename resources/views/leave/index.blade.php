@extends('layouts.app')

@section('title', 'Manajemen Cuti & Izin')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openRequestModal: false,
    activeTab: '{{ request('tab', 'requests') }}',
    openEditQuotaModal: false,
    editEmployee: { id: null, name: '', nip: '', quota: 12, used: 0 },
    searchQuery: '',
    selectedDepartment: '',
    selectedStatus: '',

    openEditModal(emp) {
        this.editEmployee = {
            id: emp.id,
            name: emp.name,
            nip: emp.nip,
            quota: emp.quota,
            used: emp.used
        };
        this.openEditQuotaModal = true;
    }
}">

    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Leave Management & Quota Control</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Manajemen Cuti & Izin Karyawan</h1>
            <p class="font-mono text-xs text-tertiary">Kelola permohonan istirahat tahunan, izin sakit, dispensasi dinas, serta kontrol sisa kuota cuti</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button @click="openRequestModal = true" class="flex items-center gap-2 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-rose-600/25 active:scale-95">
                <span class="material-symbols-outlined text-base">add</span>
                <span>Ajukan Cuti Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Success Notification -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-3 font-mono">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs flex items-center gap-3 font-mono">
            <span class="material-symbols-outlined text-lg">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif


    <!-- Main Navigation Tabs: Permohonan Cuti VS Control Sisa Cuti -->
    <div class="flex items-center justify-between border-b border-[#282a2e]/60 pb-1">
        <div class="flex items-center gap-2">
            <!-- Tab 1: Permohonan Cuti -->
            <button type="button" 
                    @click="activeTab = 'requests'" 
                    :class="activeTab === 'requests' 
                        ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 border-rose-500' 
                        : 'bg-surface-container text-tertiary hover:text-white hover:bg-surface-container-high border-[#282a2e]'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all border">
                <span class="material-symbols-outlined text-base">format_list_bulleted</span>
                <span>Daftar Permohonan Cuti</span>
                <span class="px-1.5 py-0.2 rounded-md bg-black/30 font-mono text-[10px]">{{ $pendingCount + $approvedCount + $rejectedCount }}</span>
            </button>

            <!-- Tab 2: Control Sisa Cuti Tahunan -->
            <button type="button" 
                    @click="activeTab = 'balances'" 
                    :class="activeTab === 'balances' 
                        ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20 border-rose-500' 
                        : 'bg-surface-container text-tertiary hover:text-white hover:bg-surface-container-high border-[#282a2e]'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all border">
                <span class="material-symbols-outlined text-base">manage_accounts</span>
                <span>Control Sisa Cuti Tahunan</span>
                @if($authUser->canApproveLeaves())
                    <span class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-mono text-[10px] font-bold">
                        Seluruh Karyawan ({{ $allEmployeesLeave ? $allEmployeesLeave->count() : 30 }})
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-md bg-black/30 font-mono text-[10px] text-emerald-300">
                        {{ $myRemaining }} Hari Sisa
                    </span>
                @endif
            </button>
        </div>

        <span class="hidden sm:block text-xs font-mono text-tertiary">
            HRIS PT. Gyokai Indonesia Global
        </span>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1 CONTENT: Permohonan Cuti & Tabel Review                             -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'requests'" class="w-full space-y-6">
        <!-- Status Metric Cards + Quick Personal Quota Widget -->
        @php
            $totalLeaveReqs = $pendingCount + $approvedCount + $rejectedCount;
            $pendingLeavePct = $totalLeaveReqs > 0 ? round(($pendingCount / $totalLeaveReqs) * 100, 1) : 0;
            $approvedLeavePct = $totalLeaveReqs > 0 ? round(($approvedCount / $totalLeaveReqs) * 100, 1) : 0;
            $rejectedLeavePct = $totalLeaveReqs > 0 ? round(($rejectedCount / $totalLeaveReqs) * 100, 1) : 0;
        @endphp
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Menunggu Review -->
            <a href="{{ route('leave.index', ['status' => 'pending', 'tab' => 'requests']) }}" 
               class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-primary-container/40 transition-all flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-tertiary uppercase">Menunggu Review</span>
                        <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/30 text-primary-fixed font-mono text-[10px] font-bold">
                            {{ $pendingLeavePct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-primary-fixed mt-1">{{ $pendingCount }} Permohonan</div>
                </div>
                <div class="p-2.5 rounded-lg bg-red-950/40 text-primary border border-red-500/20">
                    <span class="material-symbols-outlined text-xl">pending_actions</span>
                </div>
            </a>

            <!-- 2. Disetujui -->
            <a href="{{ route('leave.index', ['status' => 'approved', 'tab' => 'requests']) }}" 
               class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-emerald-500/40 transition-all flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-tertiary uppercase">Disetujui</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                            {{ $approvedLeavePct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $approvedCount }} Disetujui</div>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                    <span class="material-symbols-outlined text-xl">check_circle</span>
                </div>
            </a>

            <!-- 3. Ditolak -->
            <a href="{{ route('leave.index', ['status' => 'rejected', 'tab' => 'requests']) }}" 
               class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-red-500/40 transition-all flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-tertiary uppercase">Ditolak</span>
                        <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary font-mono text-[10px] font-bold">
                            {{ $rejectedLeavePct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-tertiary mt-1">{{ $rejectedCount }} Ditolak</div>
                </div>
                <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                    <span class="material-symbols-outlined text-xl">cancel</span>
                </div>
            </a>

            <!-- 4. Quick Quota Balance Tile -->
            <div class="p-4 rounded-xl bg-gradient-to-br from-surface-container-low to-surface-container border border-rose-500/30 flex items-center justify-between shadow-sm">
                <div>
                    <span class="font-mono text-[10px] text-rose-400 uppercase font-bold block">Sisa Kuota Cuti Anda</span>
                    <div class="font-headline text-2xl font-bold text-white mt-0.5">
                        <span class="text-emerald-400">{{ $myRemaining }}</span> <span class="text-xs font-normal text-tertiary font-mono">/ {{ $myQuota }} Hari</span>
                    </div>
                    <button type="button" @click="activeTab = 'balances'" class="text-[11px] text-rose-400 hover:text-rose-300 font-mono underline inline-flex items-center gap-1 mt-1">
                        <span>Kontrol Saldo Cuti</span>
                        <span class="material-symbols-outlined text-xs">arrow_forward</span>
                    </button>
                </div>
                <div class="p-2.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                    <span class="material-symbols-outlined text-xl">account_balance_wallet</span>
                </div>
            </div>
        </div>

        <!-- Leave Requests Table Card -->
        <div class="w-full bg-surface-container-low rounded-2xl border border-[#282a2e]/60 shadow-md overflow-hidden">
            <!-- Filter Tabs -->
            <div class="p-4 border-b border-[#282a2e]/50 flex items-center justify-between bg-surface-container/30">
                <div class="flex items-center gap-1 font-mono text-xs">
                    <a href="{{ route('leave.index', ['tab' => 'requests']) }}" 
                       class="px-3 py-1.5 rounded-lg transition-colors {{ empty($status) ? 'bg-surface-container-highest text-on-surface font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                        Semua
                    </a>
                    <a href="{{ route('leave.index', ['status' => 'pending', 'tab' => 'requests']) }}" 
                       class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'pending' ? 'bg-surface-container-highest text-primary-fixed font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                        Menunggu ({{ $pendingCount }})
                    </a>
                    <a href="{{ route('leave.index', ['status' => 'approved', 'tab' => 'requests']) }}" 
                       class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'approved' ? 'bg-surface-container-highest text-emerald-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                        Disetujui ({{ $approvedCount }})
                    </a>
                    <a href="{{ route('leave.index', ['status' => 'rejected', 'tab' => 'requests']) }}" 
                       class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'rejected' ? 'bg-surface-container-highest text-red-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                        Ditolak ({{ $rejectedCount }})
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full min-w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                            <th class="px-5 py-3.5 whitespace-nowrap">Karyawan</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Jenis Permohonan</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Rentang Tanggal</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Durasi</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Alasan</th>
                            <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                            <th class="px-5 py-3.5 text-right whitespace-nowrap">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($leaveRequests as $req)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4 flex items-center gap-3">
                                    <img src="{{ $req->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" 
                                         alt="avatar" 
                                         class="w-8 h-8 rounded-full object-cover flex-shrink-0"/>
                                    <div>
                                        <span class="font-semibold text-on-surface block text-sm">{{ $req->employee->name }}</span>
                                        <span class="font-mono text-[10px] text-tertiary">{{ $req->employee->nip }} • Sisa: {{ $req->employee->annual_leave_quota - $req->employee->annual_leave_used }} hari</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded bg-surface-container text-xs font-mono text-on-surface">
                                        {{ $req->type }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-on-surface whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($req->start_date)->format('d M Y') }} - {{ \Carbon\Carbon::parse($req->end_date)->format('d M Y') }}
                                </td>
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-on-surface whitespace-nowrap">
                                    {{ $req->duration_days }} Hari Kerja
                                </td>
                                <td class="px-5 py-4 text-tertiary max-w-xs truncate" title="{{ $req->reason }}">
                                    {{ $req->reason }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    @if($req->status === 'approved')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] font-semibold w-max flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">verified</span>
                                                <span>Disetujui HR</span>
                                            </span>
                                            <span class="text-[10px] text-tertiary font-mono">
                                                HR: <strong class="text-on-surface">{{ $req->approver?->name ?? 'HR / Direktur' }}</strong>
                                            </span>
                                            @if($req->managerApprover)
                                                <span class="text-[9px] text-slate-400 font-mono">
                                                    Dept: <strong class="text-slate-300">{{ $req->managerApprover->name }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                    @elseif($req->status === 'pending_manager')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/20 text-amber-400 font-mono text-[10px] font-semibold w-max flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                <span>Menunggu Mgr Dept</span>
                                            </span>
                                            <span class="text-[10px] text-amber-300/80 font-mono">
                                                Dept: <strong>{{ $req->employee->department ?? '-' }}</strong>
                                            </span>
                                        </div>
                                    @elseif($req->status === 'pending_hr')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-indigo-950/60 border border-indigo-500/20 text-indigo-300 font-mono text-[10px] font-semibold w-max flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-pulse"></span>
                                                <span>Menunggu Mgr HR</span>
                                            </span>
                                            @if($req->managerApprover)
                                                <span class="text-[9px] text-emerald-400/90 font-mono flex items-center gap-0.5">
                                                    <span class="material-symbols-outlined text-[10px]">check</span>
                                                    <span>Ok Dept: {{ $req->managerApprover->name }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    @elseif($req->status === 'pending')
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/20 text-amber-400 font-mono text-[10px] font-semibold">
                                            Menunggu Review HR
                                        </span>
                                    @else
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/20 text-red-400 font-mono text-[10px] font-semibold w-max flex items-center gap-1">
                                                <span class="material-symbols-outlined text-xs">cancel</span>
                                                <span>Ditolak</span>
                                            </span>
                                            <span class="text-[10px] text-tertiary font-mono">
                                                Oleh: <strong class="text-on-surface">{{ $req->approver?->name ?? 'Penyetuju' }}</strong>
                                            </span>
                                            @if($req->rejection_note)
                                                <span class="text-[10px] text-red-300/90 italic bg-red-950/40 px-2 py-0.5 rounded border border-red-500/20 mt-0.5 max-w-xs">
                                                    Alasan: "{{ $req->rejection_note }}"
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    @php
                                        $myEmpId = auth()->user()->employee_id ?? (auth()->user()->employee?->id);
                                        $isOwnRequest = $myEmpId && ((int)$req->employee_id === (int)$myEmpId);
                                        $isPending = in_array($req->status, ['pending', 'pending_manager', 'pending_hr']);
                                    @endphp

                                    @if($isPending)
                                        @if($isOwnRequest)
                                            <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-1 rounded border border-amber-500/30 flex items-center gap-1 justify-end w-max ml-auto" title="Anda tidak dapat meng-approve pengajuan cuti milik sendiri">
                                                <span class="material-symbols-outlined text-xs">lock</span>
                                                <span>Pengajuan Sendiri</span>
                                            </span>
                                        @elseif($req->status === 'pending_manager')
                                            @if(auth()->user()->canApproveDepartmentLeave($req))
                                                <div class="flex items-center justify-end gap-2">
                                                    <form action="{{ route('leave.updateStatus', $req->id) }}" method="POST" onsubmit="
                                                        let reason = prompt('Masukkan alasan penolakan permohonan cuti (wajib diisi):');
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
                                                        <button type="submit" class="p-1 rounded hover:bg-surface-container-high text-tertiary hover:text-red-400 transition-colors" title="Tolak Permohonan (Wajib Alasan)">
                                                            <span class="material-symbols-outlined text-lg">cancel</span>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('leave.updateStatus', $req->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="approved">
                                                        <button type="submit" onclick="return confirm('Setujui pengajuan cuti ini dan teruskan ke Manager HR?')" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded text-xs font-semibold transition-all flex items-center gap-1 shadow-sm" title="Setujui dan teruskan ke Manager HR">
                                                            <span class="material-symbols-outlined text-sm">forward</span>
                                                            <span>Setujui & Ke HR</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-0.5 rounded border border-amber-500/20">
                                                    Menunggu Manager {{ $req->employee->department }}
                                                </span>
                                            @endif
                                        @elseif(auth()->user()->canApproveLeaves())
                                            <div class="flex items-center justify-end gap-2">
                                                <form action="{{ route('leave.updateStatus', $req->id) }}" method="POST" onsubmit="
                                                    let reason = prompt('Masukkan alasan penolakan permohonan cuti (wajib diisi):');
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
                                                    <button type="submit" class="p-1 rounded hover:bg-surface-container-high text-tertiary hover:text-red-400 transition-colors" title="Tolak Permohonan (Wajib Alasan)">
                                                        <span class="material-symbols-outlined text-lg">cancel</span>
                                                    </button>
                                                </form>
                                                <form action="{{ route('leave.updateStatus', $req->id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" onclick="return confirm('Setujui final permohonan cuti ini? Kuota cuti tahunan akan dipotong otomatis.')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-xs font-semibold transition-all flex items-center gap-1 shadow-sm" title="Setujui Final HR">
                                                        <span class="material-symbols-outlined text-sm">check</span>
                                                        <span>Setujui Final HR</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="font-mono text-[10px] text-indigo-300 bg-indigo-950/60 px-2 py-0.5 rounded border border-indigo-500/20">
                                                Menunggu Persetujuan HR
                                            </span>
                                        @endif
                                    @else
                                        <div class="text-[10px] font-mono text-tertiary">
                                            {{ $req->action_at ? \Carbon\Carbon::parse($req->action_at)->format('d M, H:i') : 'Selesai Ditinjau' }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">
                                    Tidak ada data pengajuan cuti yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($leaveRequests->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">
                    {{ $leaveRequests->appends(['tab' => 'requests', 'status' => $status])->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2 CONTENT: Control Sisa Cuti Tahunan (Pusat Kontrol Estetik & Lengkap)-->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'balances'" class="w-full space-y-6">

        @if($authUser->canApproveLeaves())
            <!-- ===================================================================== -->
            <!-- TAMPILAN KHUSUS MANAGER HR & DIREKTUR: Monitoring Seluruh 30 Personel -->
            <!-- ===================================================================== -->
            
            <!-- Global HR Metrics Overview Cards -->
            <div class="w-full grid grid-cols-2 md:grid-cols-5 gap-3.5">
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase block">Total Personel</span>
                        <div class="font-headline text-2xl font-bold text-white mt-1">{{ $companyLeaveMetrics['total_employees'] }} <span class="text-xs font-normal text-tertiary font-mono">Akun</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Karyawan & Manager</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <span class="material-symbols-outlined text-xl">groups</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase block">Total Kuota Perusahaan</span>
                        <div class="font-headline text-2xl font-bold text-white mt-1">{{ $companyLeaveMetrics['total_quota'] }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Jatah akumulasi tahunan</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <span class="material-symbols-outlined text-xl">calendar_today</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase block">Telah Diambil</span>
                        <div class="font-headline text-2xl font-bold text-rose-400 mt-1">{{ $companyLeaveMetrics['total_used'] }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Disetujui tahun ini</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <span class="material-symbols-outlined text-xl">event_busy</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase block">Sisa Kuota Perusahaan</span>
                        <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $companyLeaveMetrics['total_remaining'] }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Rata-rata: {{ $companyLeaveMetrics['avg_remaining'] }} hr/org</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="material-symbols-outlined text-xl">event_available</span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between col-span-2 md:col-span-1">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase block">Kuota Kritis (≤ 3 hr)</span>
                        <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $companyLeaveMetrics['critical_count'] }} <span class="text-xs font-normal text-tertiary font-mono">Orang</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Hampir / sudah habis</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <span class="material-symbols-outlined text-xl">warning</span>
                    </div>
                </div>
            </div>

            <!-- Main Table Card: Controlling All Employees -->
            <div class="w-full p-5 rounded-2xl bg-surface-container border border-[#282a2e]/60 shadow-md space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-3 border-b border-[#282a2e]/60">
                    <div>
                        <h3 class="font-headline font-bold text-base text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-400">admin_panel_settings</span>
                            <span>Monitoring Sisa Cuti Seluruh Karyawan & Manager</span>
                        </h3>
                        <p class="text-xs text-tertiary font-mono mt-0.5">
                            Kontrol saldo hak cuti tahunan 30 personel lintas departemen. Anda dapat memantau dan menyesuaikan kuota karyawan jika dibutuhkan.
                        </p>
                    </div>

                    <!-- Search & Filter Controls -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Search Box -->
                        <div class="relative min-w-[200px]">
                            <span class="material-symbols-outlined absolute left-2.5 top-2 text-slate-500 text-sm">search</span>
                            <input type="text" 
                                   x-model="searchQuery" 
                                   placeholder="Cari nama, NIP, jabatan..." 
                                   class="w-full pl-8 pr-3 py-1.5 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 font-mono">
                        </div>

                        <!-- Filter Departemen -->
                        <select x-model="selectedDepartment" class="bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-rose-500 font-mono">
                            <option value="">Semua Departemen</option>
                            @foreach($departmentsList as $dept)
                                <option value="{{ $dept }}">{{ $dept }}</option>
                            @endforeach
                        </select>

                        <!-- Filter Status Sisa -->
                        <select x-model="selectedStatus" class="bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-rose-500 font-mono">
                            <option value="">Semua Status Sisa</option>
                            <option value="aman">Aman (> 5 Hari)</option>
                            <option value="menipis">Menipis (4 - 5 Hari)</option>
                            <option value="kritis">Kritis (1 - 3 Hari)</option>
                            <option value="habis">Habis (0 Hari)</option>
                        </select>

                        <button type="button" 
                                @click="searchQuery = ''; selectedDepartment = ''; selectedStatus = ''" 
                                class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white border border-slate-700 hover:bg-slate-700 transition-colors" 
                                title="Reset Filter">
                            <span class="material-symbols-outlined text-sm">filter_alt_off</span>
                        </button>
                    </div>
                </div>

                <!-- Table of 30 Employees -->
                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#282a2e]/60 text-[11px] font-mono text-tertiary uppercase tracking-wider bg-surface-container-lowest/30">
                                <th class="py-3 px-3 whitespace-nowrap">Personel</th>
                                <th class="py-3 px-3 whitespace-nowrap">Departemen & Jabatan</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Hak Kuota</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Terpakai</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Pending</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Sisa Efektif</th>
                                <th class="py-3 px-3 min-w-[140px] whitespace-nowrap">Progres Kuota</th>
                                <th class="py-3 px-3 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-3 text-right whitespace-nowrap">Aksi HR</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/40 font-body">
                            @foreach($allEmployeesLeave as $emp)
                                <tr x-show="
                                    (!searchQuery || '{{ strtolower($emp['name'] . ' ' . $emp['nip'] . ' ' . $emp['role_title']) }}'.includes(searchQuery.toLowerCase())) &&
                                    (!selectedDepartment || '{{ $emp['department'] }}' === selectedDepartment) &&
                                    (!selectedStatus || '{{ $emp['status_key'] }}' === selectedStatus)
                                " class="hover:bg-slate-800/30 transition-colors">
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <img src="{{ $emp['avatar_url'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" 
                                                 alt="{{ $emp['name'] }}" 
                                                 class="w-8 h-8 rounded-full object-cover border border-slate-700 flex-shrink-0"/>
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold text-white text-xs block">{{ $emp['name'] }}</span>
                                                    @if($emp['user_role'] === 'direktur')
                                                        <span class="px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[9px] font-mono font-bold">Direksi</span>
                                                    @elseif($emp['user_role'] === 'manager')
                                                        <span class="px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-[9px] font-mono font-bold">Manager</span>
                                                    @else
                                                        <span class="px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 border border-slate-700 text-[9px] font-mono">Karyawan</span>
                                                    @endif
                                                </div>
                                                <span class="font-mono text-[10px] text-tertiary block">{{ $emp['nip'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-semibold text-slate-300 block text-xs">{{ $emp['department'] }}</span>
                                        <span class="text-[10px] text-slate-500 font-mono truncate max-w-[180px] block" title="{{ $emp['role_title'] }}">{{ $emp['role_title'] }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-slate-200 whitespace-nowrap">
                                        {{ $emp['quota'] }} hr
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-rose-400 whitespace-nowrap">
                                        {{ $emp['used'] }} hr
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-amber-400 whitespace-nowrap">
                                        {{ $emp['pending'] }} hr
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="font-mono font-extrabold text-sm {{ $emp['remaining'] > 5 ? 'text-emerald-400' : ($emp['remaining'] > 0 ? 'text-amber-400' : 'text-red-400') }}">
                                            {{ $emp['remaining'] }}
                                        </span>
                                        <span class="text-[10px] text-slate-500 font-mono block">hari sisa</span>
                                    </td>
                                    <td class="py-3 px-3 min-w-[140px]">
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between text-[10px] font-mono text-slate-400">
                                                <span>Terpakai: {{ $emp['pct_used'] }}%</span>
                                                <span>{{ $emp['used'] }}/{{ $emp['quota'] }}</span>
                                            </div>
                                            <div class="w-full h-2 rounded-full bg-slate-900 border border-slate-700 overflow-hidden flex">
                                                <div class="h-full bg-rose-500" style="width: {{ $emp['pct_used'] }}%"></div>
                                                <div class="h-full bg-amber-400" style="width: {{ $emp['pct_pending'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full border text-[10px] font-mono font-bold inline-block {{ $emp['status_class'] }}">
                                            {{ $emp['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <button type="button" 
                                                @click="openEditModal({{ json_encode($emp) }})" 
                                                class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-rose-600/20 text-slate-300 hover:text-rose-400 border border-slate-700 hover:border-rose-500/30 transition-all font-mono text-[11px] font-semibold flex items-center gap-1 ml-auto"
                                                title="Sesuaikan Kuota Cuti">
                                            <span class="material-symbols-outlined text-sm">tune</span>
                                            <span>Edit Kuota</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- ========================================================================= -->
        <!-- HERO DASHBOARD: Pusat Kontrol Sisa Cuti Tahunan Pribadi                   -->
        <!-- (Ditampilkan untuk Karyawan & Non-HR Manager, serta ringkasan akun HR)   -->
        <!-- ========================================================================= -->
        <div class="w-full p-6 rounded-2xl bg-gradient-to-br from-surface-container via-surface-container-low to-surface-container border border-[#282a2e]/70 shadow-lg space-y-6">
            <!-- Header Kartu: Identitas Karyawan & Status Kuota -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-[#282a2e]/60">
                <div class="flex items-center gap-3.5">
                    <img src="{{ $currentUser?->avatar_url ?? (auth()->user()->employee?->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120') }}" 
                         alt="{{ $currentUser?->name ?? $authUser->name }}" 
                         class="w-12 h-12 rounded-2xl object-cover ring-2 ring-rose-500/30 shadow-md flex-shrink-0"/>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-headline font-bold text-lg text-white">
                                Saldo Cuti Tahunan: {{ $currentUser?->name ?? $authUser->name }}
                            </h2>
                            <span class="px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-mono font-bold">
                                Periode {{ date('Y') }}
                            </span>
                        </div>
                        <p class="text-xs text-tertiary font-mono mt-0.5">
                            {{ $currentUser?->nip ?? 'NIP: -' }} • {{ $currentUser?->role_title ?? 'Staff' }} (Departemen: {{ $currentUser?->department ?? $authUser->department }})
                        </p>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="flex items-center gap-2">
                    @if($myRemaining > 5)
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-950/60 text-emerald-400 border border-emerald-500/30 text-xs font-mono font-bold shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>Kuota Aman ({{ $myRemaining }} Hari Tersisa)</span>
                        </span>
                    @elseif($myRemaining > 0)
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-950/60 text-amber-400 border border-amber-500/30 text-xs font-mono font-bold shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                            <span>Kuota Menipis ({{ $myRemaining }} Hari Tersisa)</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-950/60 text-red-400 border border-red-500/30 text-xs font-mono font-bold shadow-sm">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                            <span>Kuota Habis (0 Hari Tersisa)</span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- 4 Grid Stat Personal (Full Width, Symmetrical & Aesthetic) -->
            <div class="w-full grid grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Total Hak Kuota -->
                <div class="p-4 rounded-xl bg-surface-container-lowest/50 border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider block">Hak Kuota Tahunan</span>
                        <div class="font-headline text-3xl font-bold text-white mt-1">{{ $myQuota }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block">Jatah resmi tahun {{ date('Y') }}</span>
                    </div>
                    <div class="p-3 rounded-xl bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                        <span class="material-symbols-outlined text-2xl">calendar_month</span>
                    </div>
                </div>

                <!-- 2. Kuota Terpakai (Approved) -->
                <div class="p-4 rounded-xl bg-surface-container-lowest/50 border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider block">Telah Digunakan</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-rose-500/20 text-rose-300 font-bold">{{ $myPctUsed }}%</span>
                        </div>
                        <div class="font-headline text-3xl font-bold text-rose-400 mt-1">{{ $myUsed }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block">Disetujui HR & Direksi</span>
                    </div>
                    <div class="p-3 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <span class="material-symbols-outlined text-2xl">event_busy</span>
                    </div>
                </div>

                <!-- 3. Sedang Diajukan (Pending) -->
                <div class="p-4 rounded-xl bg-surface-container-lowest/50 border border-[#282a2e]/60 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider block">Sedang Diajukan</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-300 font-bold">{{ $myPctPending }}%</span>
                        </div>
                        <div class="font-headline text-3xl font-bold text-amber-400 mt-1">{{ $myPending }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-slate-500 font-mono mt-1 block">Menunggu verifikasi</span>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <span class="material-symbols-outlined text-2xl">hourglass_top</span>
                    </div>
                </div>

                <!-- 4. Sisa Efektif -->
                <div class="p-4 rounded-xl bg-surface-container-lowest/50 border border-emerald-500/30 flex items-center justify-between bg-emerald-950/10">
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-[10px] text-emerald-400 uppercase tracking-wider font-bold block">Sisa Kuota Efektif</span>
                            <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 font-bold">{{ $myPctRemaining }}%</span>
                        </div>
                        <div class="font-headline text-3xl font-bold text-emerald-400 mt-1">{{ $myRemaining }} <span class="text-xs font-normal text-tertiary font-mono">Hari</span></div>
                        <span class="text-[10px] text-emerald-300/70 font-mono mt-1 block">Dapat diajukan sekarang</span>
                    </div>
                    <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                        <span class="material-symbols-outlined text-2xl">verified</span>
                    </div>
                </div>
            </div>

            <!-- Multi-segment Visual Progress Bar -->
            <div class="w-full pt-4 border-t border-[#282a2e]/50">
                <div class="flex items-center justify-between text-xs font-mono text-tertiary mb-2">
                    <span class="flex items-center gap-1.5 text-slate-300">
                        <span class="material-symbols-outlined text-sm text-rose-400">pie_chart</span>
                        <span>Distribusi Pemakaian Hak Cuti Tahunan ({{ $myQuota }} Hari Jatah)</span>
                    </span>
                    <span class="text-slate-300">
                        <strong class="text-emerald-400">{{ $myRemaining }} Hari Tersedia</strong> untuk diambil
                    </span>
                </div>
                
                <div class="w-full h-3.5 rounded-full bg-slate-900 border border-slate-700/80 overflow-hidden flex shadow-inner">
                    <!-- Terpakai -->
                    <div class="h-full bg-gradient-to-r from-rose-600 to-rose-500 transition-all duration-300" 
                         style="width: {{ $myPctUsed }}%" 
                         title="Terpakai: {{ $myUsed }} hari ({{ $myPctUsed }}%)"></div>
                    <!-- Pending -->
                    <div class="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-300" 
                         style="width: {{ $myPctPending }}%" 
                         title="Pending: {{ $myPending }} hari ({{ $myPctPending }}%)"></div>
                    <!-- Tersedia -->
                    <div class="h-full bg-gradient-to-r from-emerald-600 to-emerald-500 transition-all duration-300" 
                         style="width: {{ $myPctRemaining }}%" 
                         title="Sisa: {{ $myRemaining }} hari ({{ $myPctRemaining }}%)"></div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 mt-3 text-xs font-mono">
                    <div class="flex flex-wrap items-center gap-5">
                        <span class="flex items-center gap-2 text-slate-300">
                            <span class="w-3 h-3 rounded-md bg-rose-500 shadow-sm"></span>
                            <span>Terpakai ({{ $myUsed }} Hari - {{ $myPctUsed }}%)</span>
                        </span>
                        <span class="flex items-center gap-2 text-slate-300">
                            <span class="w-3 h-3 rounded-md bg-amber-400 shadow-sm"></span>
                            <span>Pending ({{ $myPending }} Hari - {{ $myPctPending }}%)</span>
                        </span>
                        <span class="flex items-center gap-2 text-slate-300">
                            <span class="w-3 h-3 rounded-md bg-emerald-500 shadow-sm"></span>
                            <span>Sisa Tersedia ({{ $myRemaining }} Hari - {{ $myPctRemaining }}%)</span>
                        </span>
                    </div>

                    <span class="text-[11px] text-slate-500 italic">
                        Hak cuti tahunan diperbarui setiap tanggal 1 Januari.
                    </span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- LOWER SECTION: Riwayat Cuti Pribadi (Kiri) + Ketentuan SOP (Kanan)       -->
        <!-- ========================================================================= -->
        <div class="w-full flex flex-col lg:flex-row items-start gap-6">
            
            <!-- Kolom Kiri: Riwayat Pengambilan Cuti Tahunan Pribadi (Full Responsive) -->
            <div class="w-full lg:flex-1 min-w-0 p-5 rounded-2xl bg-surface-container border border-[#282a2e]/60 shadow-md space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#282a2e]/60">
                    <div>
                        <h3 class="font-headline font-bold text-base text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-400">history_edu</span>
                            <span>Riwayat Cuti Tahunan Anda (Tahun {{ date('Y') }})</span>
                        </h3>
                        <p class="text-xs text-tertiary font-mono mt-0.5">
                            Daftar permohonan cuti tahunan resmi milik Anda beserta status verifikasinya
                        </p>
                    </div>
                    <span class="text-xs font-mono px-3 py-1 rounded-lg bg-slate-800 text-slate-300 border border-slate-700 w-max">
                        Total: {{ $myAnnualLeaveHistory->count() }} Pengambilan
                    </span>
                </div>

                <div class="overflow-x-auto w-full">
                    <table class="w-full min-w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                                <th class="py-3 px-3.5 whitespace-nowrap">Rentang Tanggal</th>
                                <th class="py-3 px-3.5 text-center whitespace-nowrap">Durasi</th>
                                <th class="py-3 px-3.5 whitespace-nowrap">Keperluan / Alasan</th>
                                <th class="py-3 px-3.5 text-center whitespace-nowrap">Status</th>
                                <th class="py-3 px-3.5 whitespace-nowrap">Persetujuan HR</th>
                                <th class="py-3 px-3.5 text-right whitespace-nowrap">Tanggal Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/30 font-body">
                            @forelse($myAnnualLeaveHistory as $hist)
                                <tr class="hover:bg-slate-800/20 transition-colors">
                                    <td class="py-3.5 px-3.5 font-mono text-slate-200 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($hist->start_date)->locale('id')->translatedFormat('d M Y') }} - 
                                        {{ \Carbon\Carbon::parse($hist->end_date)->locale('id')->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="py-3.5 px-3.5 text-center font-mono font-bold text-rose-400 whitespace-nowrap">
                                        {{ $hist->duration_days }} Hari
                                    </td>
                                    <td class="py-3.5 px-3.5 text-slate-300 max-w-xs truncate" title="{{ $hist->reason }}">
                                        {{ $hist->reason }}
                                    </td>
                                    <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                                        @if($hist->status === 'approved')
                                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                                                Disetujui
                                            </span>
                                        @elseif($hist->status === 'pending_manager')
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold" title="Menunggu persetujuan Manager Departemen">
                                                Review Mgr Dept
                                            </span>
                                        @elseif($hist->status === 'pending_hr')
                                            <span class="px-2.5 py-0.5 rounded-full bg-indigo-950/60 border border-indigo-500/30 text-indigo-300 font-mono text-[10px] font-bold" title="Telah disetujui Manager Dept, menunggu HR">
                                                Review Mgr HR
                                            </span>
                                        @elseif($hist->status === 'pending')
                                            <span class="px-2.5 py-0.5 rounded-full bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold">
                                                Menunggu
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full bg-red-950/60 border border-red-500/30 text-red-400 font-mono text-[10px] font-bold">
                                                Ditolak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3.5 whitespace-nowrap">
                                        @if($hist->approver)
                                            <span class="font-medium text-slate-200 block text-xs">{{ $hist->approver->name }}</span>
                                            <span class="text-[10px] text-tertiary font-mono">Penyetuju Final HR</span>
                                        @elseif($hist->managerApprover)
                                            <span class="font-medium text-slate-200 block text-xs">{{ $hist->managerApprover->name }}</span>
                                            <span class="text-[10px] text-emerald-400 font-mono">Disetujui Manager Dept</span>
                                        @else
                                            <span class="text-tertiary font-mono text-xs">Menunggu Peninjauan</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3.5 text-right font-mono text-slate-400 text-[11px] whitespace-nowrap">
                                        {{ $hist->action_at ? \Carbon\Carbon::parse($hist->action_at)->locale('id')->translatedFormat('d M Y, H:i') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 px-4 text-center">
                                        <div class="flex flex-col items-center justify-center max-w-md mx-auto space-y-3">
                                            <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center shadow-inner">
                                                <span class="material-symbols-outlined text-3xl">verified_user</span>
                                            </div>
                                            <div>
                                                <h4 class="font-headline font-bold text-base text-white">Seluruh Kuota Cuti Anda Masih Utuh!</h4>
                                                <p class="text-xs text-slate-400 font-mono mt-1 leading-relaxed">
                                                    Anda belum pernah mengambil cuti tahunan pada tahun {{ date('Y') }}. Anda memiliki <strong>{{ $myRemaining }} hari kerja</strong> yang dapat Anda ajukan kapan saja sesuai kebutuhan.
                                                </p>
                                            </div>
                                            <button type="button" 
                                                    @click="openRequestModal = true" 
                                                    class="mt-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md flex items-center gap-1.5">
                                                <span class="material-symbols-outlined text-sm">add</span>
                                                <span>Ajukan Cuti Tahunan Baru</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Kebijakan & Hak Cuti Pribadi (Fixed Width di Desktop) -->
            <div class="w-full lg:w-80 xl:w-96 shrink-0 space-y-4">
                <!-- Card Ketentuan SOP -->
                <div class="p-5 rounded-2xl bg-surface-container border border-[#282a2e]/60 shadow-md space-y-3.5">
                    <div class="flex items-center gap-2 text-rose-400 font-bold text-sm border-b border-[#282a2e]/60 pb-3">
                        <span class="material-symbols-outlined text-lg">info</span>
                        <span>Ketentuan & SOP Cuti Tahunan</span>
                    </div>

                    <ul class="text-xs text-slate-300 space-y-2.5 font-mono leading-relaxed">
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>Hak cuti tahunan diberikan <strong>{{ $myQuota }} hari kerja</strong> per tahun kalender berjalan.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>Permohonan wajib diajukan minimal <strong>H-3</strong> sebelum hari pelaksanaan cuti.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>Persetujuan diverifikasi oleh <strong>Manager HR</strong> dan disahkan <strong>Direktur Utama</strong>.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base shrink-0 mt-0.5">check_circle</span>
                            <span>Sisa kuota tahunan Anda saat ini: <strong class="text-emerald-400">{{ $myRemaining }} hari kerja</strong>.</span>
                        </li>
                    </ul>

                    <div class="pt-2">
                        <button type="button" 
                                @click="openRequestModal = true" 
                                class="w-full py-2.5 px-4 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-rose-600/25 flex items-center justify-center gap-2 active:scale-95">
                            <span class="material-symbols-outlined text-base">add_circle</span>
                            <span>Ajukan Cuti Tahunan Sekarang</span>
                        </button>
                    </div>
                </div>

                <!-- Privacy Protection Notice -->
                <div class="p-5 rounded-2xl bg-slate-900/90 border border-slate-800 text-xs font-mono text-slate-400 space-y-2 shadow-inner">
                    <div class="flex items-center gap-2 text-amber-400 font-bold">
                        <span class="material-symbols-outlined text-lg">privacy_tip</span>
                        <span>Privasi Saldo Cuti</span>
                    </div>
                    <p class="leading-relaxed text-[11px]">
                        Berdasarkan kebijakan sistem HRIS PT. Gyokai Indonesia Global, setiap karyawan dan manager hanya dapat memantau data sisa cuti milik masing-masing. Akses pantauan seluruh kuota personel hanya dipegang secara terpusat oleh <strong>Manager HR</strong> dan <strong>Direktur Utama</strong>.
                    </p>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: Penyesuaian Kuota Cuti Karyawan (Khusus Manager HR / Direktur)     -->
    <!-- ========================================================================= -->
    @if($authUser->canApproveLeaves())
        <div x-cloak x-show="openEditQuotaModal" 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-md p-6 shadow-2xl relative"
                 @click.away="openEditQuotaModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#282a2e]">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center">
                            <span class="material-symbols-outlined text-xl">tune</span>
                        </div>
                        <div>
                            <h3 class="font-headline font-bold text-base text-white">Penyesuaian Kuota Cuti</h3>
                            <p class="text-xs text-tertiary font-mono" x-text="editEmployee.name + ' (' + editEmployee.nip + ')'"></p>
                        </div>
                    </div>
                    <button @click="openEditQuotaModal = false" class="text-tertiary hover:text-white">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form :action="'{{ url('leave/employees') }}/' + editEmployee.id + '/quota'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-300 mb-1">
                            Total Hak Kuota Tahunan (Hari) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="annual_leave_quota" 
                               x-model="editEmployee.quota" 
                               min="0" 
                               max="100" 
                               required 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500 font-mono font-bold"/>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Standar perusahaan: 12 hari (karyawan) atau 14 hari (manager)</span>
                    </div>

                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-300 mb-1">
                            Cuti yang Telah Digunakan (Hari) <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" 
                               name="annual_leave_used" 
                               x-model="editEmployee.used" 
                               min="0" 
                               max="100" 
                               required 
                               class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-rose-500 font-mono font-bold"/>
                        <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Dapat disesuaikan jika terdapat koreksi audit presensi</span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-xs font-mono flex items-center justify-between">
                        <span class="text-slate-400">Estimasi Sisa Kuota Baru:</span>
                        <span class="font-bold text-emerald-400 text-sm" x-text="(Math.max(0, editEmployee.quota - editEmployee.used)) + ' Hari'"></span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#282a2e]">
                        <button type="button" @click="openEditQuotaModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 rounded-xl font-semibold transition-colors">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-rose-600/25">
                            Simpan Penyesuaian
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL: Form Pengajuan Cuti Baru                                           -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="openRequestModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative"
             @click.away="openRequestModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-rose-500 text-2xl">event_available</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Form Pengajuan Cuti / Izin</h3>
                        <p class="text-xs text-tertiary">Permohonan akan diverifikasi oleh HR & Atasan</p>
                    </div>
                </div>
                <button @click="openRequestModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('leave.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Karyawan Pemohon</label>
                    <select name="employee_id" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-rose-500">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">
                                {{ $emp->name }} ({{ $emp->nip }}) — {{ $emp->department }} (Sisa Cuti: {{ $emp->annual_leave_quota - $emp->annual_leave_used }} hari)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Jenis Permohonan</label>
                    <select name="type" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-rose-500">
                        <option value="Cuti Tahunan">Cuti Tahunan (Annual Leave)</option>
                        <option value="Cuti Sakit">Cuti Sakit (Medical Leave)</option>
                        <option value="Cuti Melahirkan">Cuti Melahirkan / Parental</option>
                        <option value="Izin Khusus">Izin Khusus (Keperluan Mendesak)</option>
                        <option value="Dispensasi">Dispensasi / Tugas Luar</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Mulai Tanggal</label>
                        <input type="date" name="start_date" value="{{ now()->addDays(1)->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-rose-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Sampai Tanggal</label>
                        <input type="date" name="end_date" value="{{ now()->addDays(2)->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-rose-500"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1.5">Alasan Lengkap</label>
                    <textarea name="reason" rows="3" required placeholder="Jelaskan kebutuhan pengajuan cuti secara ringkas dan jelas..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-sm text-on-surface focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openRequestModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition-all shadow-md">
                        Kirim Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
