@extends('layouts.app')

@section('title', 'HR - Manajemen Offboarding & Resign Karyawan')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openOffboardingModal: false, 
    detailOffboardingModal: false, 
    selectedOffboarding: null 
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Department Feature • Human Resources (HR)</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Manajemen Offboarding Karyawan</h1>
            <p class="font-mono text-xs text-tertiary">Kelola proses pengunduran diri (resign), habis kontrak, pengembalian aset, clearance finance, dan exit interview</p>
        </div>

        <button @click="openOffboardingModal = true" class="flex items-center gap-1.5 px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
            <span class="material-symbols-outlined text-base">person_remove</span>
            <span>Ajukan Offboarding</span>
        </button>
    </div>

    <!-- Metric Cards -->
    <!-- Metric Cards -->
    @php
        $processingPct = $totalOffboardings > 0 ? round(($processingCount / $totalOffboardings) * 100, 1) : 0;
        $pendingOffboardPct = $totalOffboardings > 0 ? round(($pendingCount / $totalOffboardings) * 100, 1) : 0;
        $completedOffboardPct = $totalOffboardings > 0 ? round(($completedCount / $totalOffboardings) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Total Kasus Offboarding</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalOffboardings }} Kasus</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">folder_delete</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Sedang Proses Clearance</span>
                    <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold">
                        {{ $processingPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $processingCount }} Pegawai</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">pending_actions</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Menunggu Review HR</span>
                    <span class="px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/30 text-blue-400 font-mono text-[10px] font-bold">
                        {{ $pendingOffboardPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-blue-400 mt-1">{{ $pendingCount }} Pengajuan</div>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20">
                <span class="material-symbols-outlined text-xl">hourglass_empty</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Selesai (Resmi Keluar)</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        {{ $completedOffboardPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $completedCount }} Selesai</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">check_circle</span>
            </div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="space-y-4">
        <!-- Filter Bar -->
        <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('hr.offboarding.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <select name="status" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Status Offboarding</option>
                    <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending (Menunggu Persetujuan)</option>
                    <option value="processing" {{ $statusFilter === 'processing' ? 'selected' : '' }}>Processing (Sedang Clearance)</option>
                    <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed (Selesai Offboard)</option>
                    <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Dibatalkan / Rejected</option>
                </select>

                @if($statusFilter)
                    <a href="{{ route('hr.offboarding.index') }}" class="text-xs text-tertiary hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <!-- Tabel Offboarding -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">Nama Pegawai</th>
                            <th class="px-5 py-3.5">Departemen</th>
                            <th class="px-5 py-3.5">Jenis & Alasan</th>
                            <th class="px-5 py-3.5">Hari Terakhir Kerja</th>
                            <th class="px-5 py-3.5">Checklist Clearance</th>
                            <th class="px-5 py-3.5">Status Akhir</th>
                            <th class="px-5 py-3.5 text-right">Aksi & Checklist</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($offboardings as $ob)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $ob->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-8 h-8 rounded-full object-cover border border-[#333539]"/>
                                        <div>
                                            <div class="font-semibold text-sm text-on-surface">{{ $ob->employee->name }}</div>
                                            <div class="text-xs text-tertiary font-mono">{{ $ob->employee->nip }} • {{ $ob->employee->role_title }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                    <span class="px-2 py-0.5 rounded bg-surface-container-highest text-on-surface font-medium">{{ $ob->employee->department }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold uppercase {{ $ob->type === 'Resign' ? 'bg-amber-950/60 text-amber-400 border border-amber-500/20' : 'bg-red-950/60 text-red-400 border border-red-500/20' }}">
                                        {{ $ob->type }}
                                    </span>
                                    <div class="text-xs text-tertiary mt-1 italic max-w-xs truncate" title="{{ $ob->reason }}">{{ $ob->reason }}</div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs">
                                    <span class="text-on-surface font-semibold block">{{ \Carbon\Carbon::parse($ob->effective_date)->format('d M Y') }}</span>
                                    <span class="text-[10px] text-tertiary">Diajukan: {{ \Carbon\Carbon::parse($ob->request_date)->format('d M Y') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1 text-[11px] font-mono">
                                        <span class="flex items-center gap-1.5 {{ $ob->asset_returned ? 'text-emerald-400' : 'text-tertiary' }}">
                                            <span class="material-symbols-outlined text-xs">{{ $ob->asset_returned ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                            <span>Kembalikan Aset (GA)</span>
                                        </span>
                                        <span class="flex items-center gap-1.5 {{ $ob->finance_cleared ? 'text-emerald-400' : 'text-tertiary' }}">
                                            <span class="material-symbols-outlined text-xs">{{ $ob->finance_cleared ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                            <span>Clearance Kas/Klaim (Finance)</span>
                                        </span>
                                        <span class="flex items-center gap-1.5 {{ $ob->handover_completed ? 'text-emerald-400' : 'text-tertiary' }}">
                                            <span class="material-symbols-outlined text-xs">{{ $ob->handover_completed ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                            <span>Handover Tugas Tim</span>
                                        </span>
                                        <span class="flex items-center gap-1.5 {{ $ob->exit_interview_done ? 'text-emerald-400' : 'text-tertiary' }}">
                                            <span class="material-symbols-outlined text-xs">{{ $ob->exit_interview_done ? 'check_circle' : 'radio_button_unchecked' }}</span>
                                            <span>Exit Interview (HR)</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-[10px]">
                                    @if($ob->status === 'completed')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase">COMPLETED (NONAKTIF)</span>
                                    @elseif($ob->status === 'processing')
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 font-semibold uppercase">CLEARANCE ONGOING</span>
                                    @elseif($ob->status === 'rejected')
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/20 uppercase">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/20 uppercase">PENDING</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button type="button" 
                                            @click="selectedOffboarding = {{ json_encode([
                                                'id' => $ob->id,
                                                'name' => $ob->employee->name,
                                                'nip' => $ob->employee->nip,
                                                'role_title' => $ob->employee->role_title,
                                                'department' => $ob->employee->department,
                                                'type' => $ob->type,
                                                'reason' => $ob->reason,
                                                'effective_date' => $ob->effective_date,
                                                'asset_returned' => (bool)$ob->asset_returned,
                                                'finance_cleared' => (bool)$ob->finance_cleared,
                                                'handover_completed' => (bool)$ob->handover_completed,
                                                'exit_interview_done' => (bool)$ob->exit_interview_done,
                                                'status' => $ob->status,
                                                'hr_notes' => $ob->hr_notes ?? '',
                                                'update_url' => route('hr.offboarding.updateChecklist', $ob->id),
                                            ]) }}; detailOffboardingModal = true"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-bright text-xs font-medium text-tertiary hover:text-on-surface border border-[#333539] transition-colors">
                                        <span class="material-symbols-outlined text-sm">checklist</span>
                                        <span>Update Checklist</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">
                                    Belum ada data offboarding karyawan pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($offboardings->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">{{ $offboardings->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal Ajukan Offboarding Baru -->
    <div x-cloak x-show="openOffboardingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openOffboardingModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-purple-400 text-xl">person_remove</span>
                    <h3 class="font-headline font-semibold text-base text-on-surface">Pendaftaran Offboarding Pegawai</h3>
                </div>
                <button @click="openOffboardingModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('hr.offboarding.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Pilih Pegawai</label>
                    <select name="employee_id" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->nip }} - {{ $e->department }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Jenis Offboarding</label>
                        <select name="type" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="Resign">Resign (Pengunduran Diri)</option>
                            <option value="PHK">PHK (Pemutusan Hubungan Kerja)</option>
                            <option value="Habis Kontrak">Habis Masa Kontrak</option>
                            <option value="Pensiun">Pensiun</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Hari Terakhir Kerja (Effective Date)</label>
                        <input type="date" name="effective_date" value="{{ now()->addDays(30)->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Tanggal Pengajuan Surat</label>
                    <input type="date" name="request_date" value="{{ now()->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Alasan Pengunduran Diri / Pemutusan</label>
                    <textarea name="reason" rows="2.5" required placeholder="Jelaskan alasan pengunduran diri atau keterangan pemutusan..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Catatan HR (Internal)</label>
                    <input type="text" name="hr_notes" placeholder="Rencana handover atau instruksi khusus..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openOffboardingModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold rounded-lg shadow-md">Daftarkan Offboarding</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Update Checklist Offboarding -->
    <div x-cloak x-show="detailOffboardingModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="detailOffboardingModal = false" x-show="selectedOffboarding">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-950/60 text-purple-400 border border-purple-500/20 font-bold uppercase">
                        CLEARANCE & EXIT INTERVIEW
                    </span>
                    <h3 class="font-headline font-semibold text-base text-on-surface mt-1" x-text="selectedOffboarding?.name"></h3>
                    <p class="text-xs text-tertiary font-mono" x-text="selectedOffboarding?.nip + ' • ' + selectedOffboarding?.department"></p>
                </div>
                <button @click="detailOffboardingModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="selectedOffboarding?.update_url" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/60 space-y-3">
                    <span class="block text-xs font-mono uppercase text-tertiary font-semibold tracking-wider">Tahapan Clearance Pegawai:</span>
                    
                    <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                        <input type="checkbox" name="asset_returned" value="1" :checked="selectedOffboarding?.asset_returned" class="rounded border-gray-600 text-purple-500 focus:ring-0">
                        <span>1. Pengembalian Aset (Laptop, ID Card, Akses Kantor GA)</span>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                        <input type="checkbox" name="finance_cleared" value="1" :checked="selectedOffboarding?.finance_cleared" class="rounded border-gray-600 text-purple-500 focus:ring-0">
                        <span>2. Clearance Finance (Penyelesaian Kasbon / Klaim Reimburse)</span>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                        <input type="checkbox" name="handover_completed" value="1" :checked="selectedOffboarding?.handover_completed" class="rounded border-gray-600 text-purple-500 focus:ring-0">
                        <span>3. Handover Pekerjaan & Akses Akun Kerja ke Tim</span>
                    </label>

                    <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                        <input type="checkbox" name="exit_interview_done" value="1" :checked="selectedOffboarding?.exit_interview_done" class="rounded border-gray-600 text-purple-500 focus:ring-0">
                        <span>4. Sesi Exit Interview Selesai (Feedback HR)</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Status Offboarding</label>
                        <select name="status" x-model="selectedOffboarding.status" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing (Clearance)</option>
                            <option value="completed">Completed (Resmi Keluar)</option>
                            <option value="rejected">Dibatalkan</option>
                        </select>
                        <p class="text-[10px] text-tertiary mt-1">*Jika "Completed", status pegawai otomatis nonaktif.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Hari Terakhir Kerja</label>
                        <input type="date" disabled :value="selectedOffboarding?.effective_date" class="w-full bg-surface-container-low/50 border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-tertiary"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Catatan HR / Hasil Exit Interview</label>
                    <textarea name="hr_notes" x-model="selectedOffboarding.hr_notes" rows="2.5" placeholder="Feedback dari pegawai atau instruksi penerbitan paklaring..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="detailOffboardingModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-purple-700 hover:bg-purple-800 text-white text-xs font-semibold rounded-lg shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
