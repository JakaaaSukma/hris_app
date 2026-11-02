@extends('layouts.app')

@section('title', 'Finance - Slip Gaji & Reimbursement')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    currentTab: '{{ $tab ?? 'slips' }}',
    openClaimModal: false,
    openSlipModal: false
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Department Feature • Finance & Payroll</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1">Keuangan, Payroll & Slip Gaji</h1>
            <p class="font-mono text-xs text-slate-400">Laporan slip gaji karyawan/manager, pengelolaan payroll bulanan, dan klaim reimbursement</p>
        </div>

        <div class="flex items-center gap-3">
            @if(auth()->user()->canApproveReimbursements())
                <button @click="openSlipModal = true" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-base">payments</span>
                    <span>Terbitkan Slip Gaji</span>
                </button>
            @endif

            <button @click="openClaimModal = true" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700 shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base text-emerald-400">receipt_long</span>
                <span>Ajukan Reimbursement</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs: Slip Gaji vs Reimbursement -->
    <div class="flex items-center gap-2 p-1 bg-[#11151f] rounded-xl border border-slate-800 w-fit">
        <button @click="currentTab = 'slips'" 
                :class="currentTab === 'slips' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all">
            <span class="material-symbols-outlined text-base">badge</span>
            <span>Laporan Slip Gaji (Payroll)</span>
            <span class="font-mono text-[10px] px-1.5 py-0.2 rounded-md bg-black/30 font-semibold">{{ $totalSlipsCount }}</span>
        </button>

        <button @click="currentTab = 'reimburse'" 
                :class="currentTab === 'reimburse' ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                class="flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all">
            <span class="material-symbols-outlined text-base">receipt_long</span>
            <span>Klaim Reimbursement</span>
            <span class="font-mono text-[10px] px-1.5 py-0.2 rounded-md bg-black/30 font-semibold">{{ $reimbursements->total() }}</span>
        </button>
    </div>

    <!-- TAB 1: LAPORAN SLIP GAJI KARYAWAN & MANAGER -->
    <div x-show="currentTab === 'slips'" class="space-y-6">
        
        <!-- Metric Cards Payroll -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Pengeluaran Gaji Disalurkan</span>
                    <div class="font-headline text-2xl font-extrabold text-emerald-400 mt-1">Rp {{ number_format($totalPayrollDisbursed, 0, ',', '.') }}</div>
                    <span class="font-mono text-[10px] text-slate-500">Semua periode terbayar</span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                </div>
            </div>

            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Dokumen Slip Gaji</span>
                    <div class="font-headline text-2xl font-extrabold text-white mt-1">{{ number_format($totalSlipsCount) }} <span class="text-xs text-slate-400 font-normal">Slip</span></div>
                    <span class="font-mono text-[10px] text-slate-500">Karyawan & Manager</span>
                </div>
                <div class="p-3 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <span class="material-symbols-outlined text-2xl">description</span>
                </div>
            </div>

            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Status Payroll Terakhir</span>
                    <div class="font-headline text-2xl font-extrabold text-teal-400 mt-1">100% PAIR</div>
                    <span class="font-mono text-[10px] text-slate-500">Bulan Oktober 2026 Disbursed</span>
                </div>
                <div class="p-3 rounded-xl bg-teal-500/10 text-teal-400 border border-teal-500/20">
                    <span class="material-symbols-outlined text-2xl">verified</span>
                </div>
            </div>
        </div>

        <!-- Table Slip Gaji -->
        <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/40">
                <div class="flex items-center gap-2">
                    <span class="font-headline font-bold text-sm text-white">Daftar Pelaporan Slip Gaji</span>
                    <span class="font-mono text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">
                        {{ auth()->user()->canApproveReimbursements() ? 'Seluruh Staff & Manager' : 'Arsip Pribadi Anda' }}
                    </span>
                </div>

                <!-- Filter Periode Bulan -->
                <form action="{{ route('finance.index') }}" method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="slips">
                    <select name="period" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:outline-none focus:border-emerald-500">
                        <option value="">Semua Periode</option>
                        @foreach($availablePeriods as $p)
                            <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 font-mono text-[11px] uppercase bg-slate-900/60">
                            <th class="px-5 py-3.5">Nomor Slip</th>
                            <th class="px-5 py-3.5">Karyawan / Manager</th>
                            <th class="px-5 py-3.5">Periode Gaji</th>
                            <th class="px-5 py-3.5">Total Pendapatan</th>
                            <th class="px-5 py-3.5">Potongan</th>
                            <th class="px-5 py-3.5">Gaji Bersih (THP)</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 font-body">
                        @forelse($salarySlips as $slip)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-4 font-mono font-bold text-slate-300">
                                    {{ $slip->slip_number }}
                                </td>
                                <td class="px-5 py-4 flex items-center gap-3">
                                    <img src="{{ $slip->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-700"/>
                                    <div>
                                        <span class="font-bold text-white block">{{ $slip->employee->name }}</span>
                                        <div class="flex items-center gap-1.5 font-mono text-[10px] text-slate-400">
                                            <span class="text-rose-400 font-medium">{{ $slip->employee->role_title }}</span>
                                            <span>•</span>
                                            <span>{{ $slip->employee->department }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-slate-300">
                                    <div>{{ $slip->period_month }}</div>
                                    <span class="text-[10px] text-slate-500">Tgl: {{ $slip->payment_date ? $slip->payment_date->translatedFormat('d M Y') : '-' }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono text-slate-200">
                                    Rp {{ number_format($slip->total_earnings, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 font-mono text-rose-400">
                                    - Rp {{ number_format($slip->total_deductions, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 font-mono text-sm font-extrabold text-emerald-400">
                                    Rp {{ number_format($slip->net_salary, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-mono text-[10px] font-bold uppercase">
                                        {{ $slip->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('finance.salarySlips.show', $slip->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-mono text-xs font-semibold transition-all border border-slate-700 active:scale-95">
                                        <span class="material-symbols-outlined text-sm text-emerald-400">visibility</span>
                                        <span>Lihat & Cetak</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-slate-400 font-mono">
                                    Belum ada data slip gaji untuk filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($salarySlips->hasPages())
                <div class="p-4 border-t border-slate-800">{{ $salarySlips->links() }}</div>
            @endif
        </div>
    </div>

    <!-- TAB 2: KLAIM REIMBURSEMENT -->
    <div x-show="currentTab === 'reimburse'" class="space-y-6">
        <!-- Metric Cards Reimbursement -->
        @php
            $grandTotalReimburse = $totalPending + $totalApproved + $totalPaid;
            $pendingPct = $grandTotalReimburse > 0 ? round(($totalPending / $grandTotalReimburse) * 100, 1) : 0;
            $approvedPct = $grandTotalReimburse > 0 ? round(($totalApproved / $grandTotalReimburse) * 100, 1) : 0;
            $paidPct = $grandTotalReimburse > 0 ? round(($totalPaid / $grandTotalReimburse) * 100, 1) : 0;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-slate-400 uppercase font-semibold">Pending Review</span>
                        <span class="px-2 py-0.5 rounded bg-amber-500/10 border border-amber-500/25 text-amber-400 font-mono text-[10px] font-bold">
                            {{ $pendingPct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-amber-400 mt-1">Rp {{ number_format($totalPending, 0, ',', '.') }}</div>
                </div>
                <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="material-symbols-outlined text-xl">hourglass_empty</span>
                </div>
            </div>

            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-slate-400 uppercase font-semibold">Disetujui (Siap Cair)</span>
                        <span class="px-2 py-0.5 rounded bg-blue-500/10 border border-blue-500/25 text-blue-400 font-mono text-[10px] font-bold">
                            {{ $approvedPct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-blue-400 mt-1">Rp {{ number_format($totalApproved, 0, ',', '.') }}</div>
                </div>
                <div class="p-2.5 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <span class="material-symbols-outlined text-xl">check_circle</span>
                </div>
            </div>

            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-slate-400 uppercase font-semibold">Total Dicairkan (Paid)</span>
                        <span class="px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-mono text-[10px] font-bold">
                            {{ $paidPct }}%
                        </span>
                    </div>
                    <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
                </div>
                <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <span class="material-symbols-outlined text-xl">payments</span>
                </div>
            </div>
        </div>

        <!-- Table Reimbursement -->
        <div class="bg-[#151922] rounded-2xl border border-slate-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
                <div class="flex items-center gap-1 font-mono text-xs">
                    <a href="{{ route('finance.index', ['tab' => 'reimburse']) }}" class="px-3 py-1.5 rounded-lg transition-colors {{ empty($status) ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white' }}">
                        Semua Status
                    </a>
                    <a href="{{ route('finance.index', ['tab' => 'reimburse', 'status' => 'pending']) }}" class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'pending' ? 'bg-slate-800 text-amber-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                        Pending
                    </a>
                    <a href="{{ route('finance.index', ['tab' => 'reimburse', 'status' => 'approved']) }}" class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'approved' ? 'bg-slate-800 text-blue-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                        Approved
                    </a>
                    <a href="{{ route('finance.index', ['tab' => 'reimburse', 'status' => 'paid']) }}" class="px-3 py-1.5 rounded-lg transition-colors {{ $status === 'paid' ? 'bg-slate-800 text-emerald-400 font-bold' : 'text-slate-400 hover:text-white' }}">
                        Paid
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 font-mono text-[11px] uppercase bg-slate-900/60">
                            <th class="px-5 py-3.5">Karyawan</th>
                            <th class="px-5 py-3.5">Judul Pengeluaran</th>
                            <th class="px-5 py-3.5">Kategori</th>
                            <th class="px-5 py-3.5">Nominal Klaim</th>
                            <th class="px-5 py-3.5">Tgl Kuitansi</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Tindakan Finance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80 font-body">
                        @forelse($reimbursements as $item)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="px-5 py-4 flex items-center gap-3">
                                    <img src="{{ $item->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100' }}" class="w-8 h-8 rounded-full object-cover ring-1 ring-slate-700"/>
                                    <div>
                                        <span class="font-bold text-white block text-sm">{{ $item->employee->name }}</span>
                                        <span class="font-mono text-[10px] text-slate-400">{{ $item->employee->nip }} • {{ $item->employee->department }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-medium text-slate-200">
                                    <div>{{ $item->title }}</div>
                                    @if($item->description)
                                        <span class="text-xs text-slate-400 italic">{{ $item->description }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-xs font-mono text-slate-300 border border-slate-700/60">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs font-bold text-white">
                                    Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-slate-400">
                                    {{ \Carbon\Carbon::parse($item->expense_date)->format('d M Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    @if($item->status === 'pending')
                                        <span class="px-2 py-0.5 rounded-full bg-amber-500/10 border border-amber-500/25 text-amber-400 font-mono text-[10px] font-semibold">PENDING</span>
                                    @elseif($item->status === 'approved')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded-full bg-blue-500/10 border border-blue-500/25 text-blue-400 font-mono text-[10px] font-semibold w-max">APPROVED</span>
                                            <span class="text-[9px] text-slate-400 font-mono">
                                                Oleh: <strong class="text-white">{{ $item->reviewer?->name ?? 'Finance' }}</strong>
                                            </span>
                                        </div>
                                    @elseif($item->status === 'paid')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 font-mono text-[10px] font-semibold w-max">PAID</span>
                                            <span class="text-[9px] text-slate-400 font-mono">
                                                Oleh: <strong class="text-white">{{ $item->reviewer?->name ?? 'Finance' }}</strong>
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded-full bg-rose-500/10 border border-rose-500/25 text-rose-400 font-mono text-[10px] font-semibold w-max">REJECTED</span>
                                            <span class="text-[9px] text-slate-400 font-mono">
                                                 Oleh: <strong class="text-white">{{ $item->reviewer?->name ?? 'Finance' }}</strong>
                                            </span>
                                            @if($item->notes)
                                                <span class="text-[9px] text-rose-300 italic bg-rose-950/40 px-2 py-0.5 rounded border border-rose-500/20 mt-0.5 max-w-xs">
                                                    Alasan: "{{ $item->notes }}"
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @php
                                        $myEmpId = auth()->user()->employee_id ?? (auth()->user()->employee?->id);
                                        $isOwnClaim = $myEmpId && ((int)$item->employee_id === (int)$myEmpId);
                                    @endphp

                                    @if($isOwnClaim && in_array($item->status, ['pending', 'approved']))
                                        <span class="font-mono text-[10px] text-amber-400 bg-amber-500/10 px-2 py-1 rounded border border-amber-500/25 inline-flex items-center gap-1 justify-end ml-auto" title="Anda tidak dapat meng-approve klaim reimbursement Anda sendiri">
                                            <span class="material-symbols-outlined text-xs">lock</span>
                                            <span>Klaim Sendiri</span>
                                        </span>
                                    @elseif(auth()->user()->canApproveReimbursements())
                                        @if($item->status === 'pending')
                                            <div class="flex items-center justify-end gap-1.5">
                                                <form action="{{ route('finance.updateStatus', $item->id) }}" method="POST" onsubmit="
                                                    let reason = prompt('Masukkan alasan penolakan klaim reimbursement (wajib diisi):');
                                                    if (!reason || reason.trim() === '') {
                                                        alert('Alasan penolakan wajib diisi!');
                                                        return false;
                                                    }
                                                    this.querySelector('input[name=notes]').value = reason;
                                                    return true;
                                                ">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="rejected">
                                                    <input type="hidden" name="notes" value="">
                                                    <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors" title="Tolak Klaim (Wajib Alasan)">
                                                        <span class="material-symbols-outlined text-base">close</span>
                                                    </button>
                                                </form>
                                                <form action="{{ route('finance.updateStatus', $item->id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" onclick="return confirm('Setujui klaim reimbursement ini?')" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[11px] font-bold shadow-sm">
                                                        Approve
                                                    </button>
                                                </form>
                                            </div>
                                        @elseif($item->status === 'approved')
                                            <form action="{{ route('finance.updateStatus', $item->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="paid">
                                                <button type="submit" onclick="return confirm('Tandai reimbursement ini telah dicairkan (PAID)?')" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold shadow-sm">
                                                    Tandai Cair (Paid)
                                                </button>
                                            </form>
                                        @else
                                            <span class="font-mono text-[10px] text-slate-500">Selesai Ditinjau</span>
                                        @endif
                                    @else
                                        <span class="font-mono text-[10px] text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                                            Menunggu Review
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-slate-400 font-mono">Belum ada catatan reimbursement klaim.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reimbursements->hasPages())
                <div class="p-4 border-t border-slate-800">{{ $reimbursements->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal Form: Penerbitan Slip Gaji Baru (Khusus Finance/Direktur) -->
    @if(auth()->user()->canApproveReimbursements())
        <div x-cloak x-show="openSlipModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
             x-data="{
                 isBatchMode: false,
                 searchEmp: '',
                 filterDept: '',
                 selectedEmpIds: [],
                 allEmpIds: [{{ $employees->pluck('id')->implode(',') }}],
                 toggleSelectAll() {
                     if (this.selectedEmpIds.length === this.allEmpIds.length) {
                         this.selectedEmpIds = [];
                     } else {
                         this.selectedEmpIds = [...this.allEmpIds];
                     }
                 }
             }">
            <div class="bg-[#151922] rounded-2xl border border-slate-800 w-full max-w-xl p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="openSlipModal = false">
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-emerald-400 text-xl">payments</span>
                        <div>
                            <h3 class="font-headline font-bold text-base text-white">Terbitkan Slip Gaji Karyawan / Manager</h3>
                            <p class="text-[11px] font-mono text-slate-400">Penerbitan slip gaji tunggal atau masal secara bersamaan</p>
                        </div>
                    </div>
                    <button @click="openSlipModal = false" class="text-slate-400 hover:text-white">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form action="{{ route('finance.salarySlips.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf

                    <!-- Mode Selector: Satuan vs Lebih dari 1 Orang (Masal) -->
                    <div class="p-1 bg-slate-900/90 rounded-xl border border-slate-800 flex items-center gap-1 font-mono text-xs">
                        <button type="button" 
                                @click="isBatchMode = false"
                                :class="!isBatchMode ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                                class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">person</span>
                            <span>1 Orang Karyawan</span>
                        </button>
                        <button type="button" 
                                @click="isBatchMode = true; if(selectedEmpIds.length === 0) selectedEmpIds = [{{ $employees->first()->id ?? 0 }}];"
                                :class="isBatchMode ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-white'"
                                class="flex-1 py-1.5 px-3 rounded-lg transition-all flex items-center justify-center gap-1.5">
                            <span class="material-symbols-outlined text-sm">groups</span>
                            <span>Lebih Dari 1 Orang (Masal)</span>
                        </button>
                    </div>

                    <!-- Single Mode: Dropdown 1 Orang -->
                    <div x-show="!isBatchMode">
                        <label class="block text-xs font-mono text-slate-400 mb-1">Pilih Karyawan / Manager</label>
                        <select name="employee_id" :disabled="isBatchMode" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->name }} ({{ $emp->nip }}) - {{ $emp->role_title }} [{{ $emp->department }}]
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Batch Mode: Clean Multi-Select Dropdown Component -->
                    <div x-show="isBatchMode" class="space-y-1.5" x-data="{ openDropdown: false }">
                        <label class="block text-xs font-mono text-slate-400">
                            Pilih Penerima Slip Gaji Masal:
                        </label>

                        <!-- Dropdown Trigger Button -->
                        <div class="relative" @click.away="openDropdown = false">
                            <button type="button" 
                                    @click="openDropdown = !openDropdown"
                                    class="w-full bg-slate-900 border border-slate-700 hover:border-emerald-500/80 rounded-xl px-3.5 py-2.5 text-xs text-left flex items-center justify-between transition-colors focus:outline-none focus:border-emerald-500 shadow-sm">
                                <div class="flex items-center gap-2 truncate pr-2">
                                    <span class="material-symbols-outlined text-base text-emerald-400">group_add</span>
                                    <template x-if="selectedEmpIds.length === 0">
                                        <span class="text-slate-400 font-mono">-- Klik untuk memilih karyawan/manager --</span>
                                    </template>
                                    <template x-if="selectedEmpIds.length > 0 && selectedEmpIds.length === allEmpIds.length">
                                        <span class="text-emerald-400 font-semibold font-mono">
                                            Seluruh Karyawan & Manager Terpilih (<span x-text="allEmpIds.length"></span> Orang)
                                        </span>
                                    </template>
                                    <template x-if="selectedEmpIds.length > 0 && selectedEmpIds.length < allEmpIds.length">
                                        <span class="text-white font-medium">
                                            <strong class="text-emerald-400 font-mono" x-text="selectedEmpIds.length"></strong> Orang Karyawan/Manager Terpilih
                                        </span>
                                    </template>
                                </div>
                                <span class="material-symbols-outlined text-slate-400 transition-transform duration-200 text-lg"
                                      :class="openDropdown ? 'rotate-180 text-emerald-400' : ''">
                                    expand_more
                                </span>
                            </button>

                            <!-- Dropdown Panel Menu -->
                            <div x-show="openDropdown" 
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                 class="absolute z-30 left-0 right-0 mt-1.5 p-3 bg-[#11151f] rounded-xl border border-slate-700 shadow-2xl space-y-2.5">
                                
                                <!-- Top Bar Actions: Select All & Counter -->
                                <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-xs">
                                    <span class="font-mono text-[11px] text-slate-400">
                                        Dipilih: <strong class="text-emerald-400" x-text="selectedEmpIds.length"></strong> dari <strong class="text-slate-300" x-text="allEmpIds.length"></strong>
                                    </span>
                                    <button type="button" @click="toggleSelectAll()" class="px-2 py-0.5 rounded bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 font-mono text-[11px] font-semibold transition-colors">
                                        <span x-text="selectedEmpIds.length === allEmpIds.length ? 'Batalkan Semua' : 'Pilih Semua'"></span>
                                    </button>
                                </div>

                                <!-- Filters inside dropdown: Search & Department -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="relative">
                                        <span class="material-symbols-outlined text-xs absolute left-2 top-2 text-slate-500">search</span>
                                        <input type="text" x-model="searchEmp" placeholder="Cari nama / NIP..." class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-7 pr-2 py-1 text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-emerald-500"/>
                                    </div>
                                    <select x-model="filterDept" class="bg-slate-900 border border-slate-800 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:border-emerald-500">
                                        <option value="">Semua Divisi</option>
                                        <option value="HR">HR</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Bisnis Development">Bisnis Dev</option>
                                        <option value="GA">GA</option>
                                        <option value="Resepsionis">Resepsionis</option>
                                        <option value="Trainer">Trainer</option>
                                    </select>
                                </div>

                                <!-- Checkbox List Items -->
                                <div class="max-h-48 overflow-y-auto space-y-1 pr-1 divide-y divide-slate-800/60 font-sans">
                                    @foreach($employees as $emp)
                                        <label x-show="('{{ strtolower($emp->name) }} {{ strtolower($emp->nip) }}'.includes(searchEmp.toLowerCase())) && (filterDept === '' || '{{ $emp->department }}' === filterDept)"
                                               class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800/60 cursor-pointer transition-colors text-xs select-none">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input type="checkbox" 
                                                       name="employee_ids[]" 
                                                       value="{{ $emp->id }}" 
                                                       x-model="selectedEmpIds"
                                                       :disabled="!isBatchMode"
                                                       class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0 focus:ring-offset-0"/>
                                                <div class="truncate">
                                                    <span class="text-white font-medium">{{ $emp->name }}</span>
                                                    <span class="text-slate-400 font-mono text-[10px]">({{ $emp->nip }})</span>
                                                </div>
                                            </div>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-slate-800 text-slate-300 shrink-0">
                                                {{ $emp->department }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                <!-- Done button to close dropdown -->
                                <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-500 font-mono">*Bisa memilih beberapa orang</span>
                                    <button type="button" @click="openDropdown = false" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-semibold">
                                        Selesai
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Periode Bulan Gaji</label>
                            <input type="text" name="period_month" value="Oktober 2026" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Tanggal Transfer / Penggajian</label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                        </div>
                    </div>

                    <!-- Earnings -->
                    <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 space-y-2.5">
                        <span class="font-mono text-[10px] text-emerald-400 font-bold uppercase tracking-wider block">Komponen Pendapatan (Rp)</span>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Gaji Pokok</label>
                                <input type="number" name="basic_salary" value="8000000" min="0" required class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Tunjangan Jabatan</label>
                                <input type="number" name="allowance_position" value="1500000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Tunjangan Transport</label>
                                <input type="number" name="allowance_transport" value="1000000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Tunjangan Makan</label>
                                <input type="number" name="allowance_meal" value="800000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Bonus / Insentif</label>
                                <input type="number" name="bonus" value="500000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">Upah Lembur</label>
                                <input type="number" name="overtime_pay" value="0" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white"/>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 space-y-2.5">
                        <span class="font-mono text-[10px] text-rose-400 font-bold uppercase tracking-wider block">Komponen Potongan (Rp)</span>
                        <div class="grid grid-cols-3 gap-2.5">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">BPJS TK (3%)</label>
                                <input type="number" name="deduction_bpjs_tk" value="240000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">BPJS Kes (1%)</label>
                                <input type="number" name="deduction_bpjs_kes" value="80000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white"/>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-0.5">PPh 21</label>
                                <input type="number" name="deduction_tax_pph21" value="150000" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-2 py-1.5 text-xs text-white"/>
                            </div>
                        </div>
                    </div>

                    <!-- Bank details -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Metode & Bank</label>
                            <input type="text" name="payment_method" value="Bank Transfer BCA" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white"/>
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-slate-400 mb-1">Nomor Rekening</label>
                            <input type="text" name="bank_account_number" value="8820-1928-11" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white"/>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button type="button" @click="openSlipModal = false" class="px-4 py-2 bg-slate-800 text-xs text-slate-300 rounded-xl">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-md flex items-center gap-1.5 transition-all active:scale-95">
                            <span class="material-symbols-outlined text-sm">send</span>
                            <span x-text="isBatchMode ? 'Terbitkan ' + selectedEmpIds.length + ' Slip Gaji Masal' : 'Terbitkan & Simpan Slip'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Form: Pengajuan Reimbursement -->
    <div x-cloak x-show="openClaimModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-800 w-full max-w-md p-6 shadow-2xl relative" @click.away="openClaimModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <h3 class="font-headline font-bold text-base text-white">Form Pengajuan Reimbursement</h3>
                <button @click="openClaimModal = false" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('finance.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-slate-400 mb-1">Karyawan Pemohon</label>
                    <select name="employee_id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ auth()->user()->employee_id == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} ({{ $emp->nip }}) - {{ $emp->department }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-400 mb-1">Judul / Keperluan Pengeluaran</label>
                    <input type="text" name="title" required placeholder="contoh: Biaya Transportasi Client Visit" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-400 mb-1">Kategori</label>
                        <select name="category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="Transportasi & Bensin">Transportasi & Bensin</option>
                            <option value="Client Meeting / Makan">Client Meeting / Makan</option>
                            <option value="Operasional Kantor">Operasional Kantor</option>
                            <option value="Kesehatan / Medis">Kesehatan / Medis</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-400 mb-1">Nominal (Rp)</label>
                        <input type="number" name="amount" min="1000" step="1000" required placeholder="150000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-400 mb-1">Tanggal Kuitansi / Pembayaran</label>
                    <input type="date" name="expense_date" value="{{ now()->toDateString() }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-400 mb-1">Deskripsi Tambahan</label>
                    <textarea name="description" rows="2" placeholder="Nomor invoice/bukti transfer..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openClaimModal = false" class="px-4 py-2 bg-slate-800 text-xs text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-md">Simpan Reimbursement</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
