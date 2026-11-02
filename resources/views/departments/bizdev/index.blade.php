@extends('layouts.app')

@section('title', 'Bisnis Development - Pipeline & Proyek Deals')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openDealModal: false,
    openLostModal: false,
    lostDealId: null,
    lostClientName: '',
    lostProjectTitle: '',
    lostDealValue: '',
    lostReason: '',
    lostActionUrl: '',
    currentSelectElement: null,
    previousStageValue: '',
    
    promptLost(event, dealId, clientName, projectTitle, dealValue, actionUrl, currentStage, existingReason = '') {
        const selectEl = event.target;
        if (selectEl.value === 'lost') {
            this.currentSelectElement = selectEl;
            this.previousStageValue = currentStage;
            this.lostDealId = dealId;
            this.lostClientName = clientName;
            this.lostProjectTitle = projectTitle;
            this.lostDealValue = dealValue;
            this.lostActionUrl = actionUrl;
            this.lostReason = existingReason || '';
            this.openLostModal = true;
        } else {
            selectEl.form.submit();
        }
    },
    
    openEditLost(dealId, clientName, projectTitle, dealValue, actionUrl, existingReason = '') {
        this.currentSelectElement = null;
        this.previousStageValue = 'lost';
        this.lostDealId = dealId;
        this.lostClientName = clientName;
        this.lostProjectTitle = projectTitle;
        this.lostDealValue = dealValue;
        this.lostActionUrl = actionUrl;
        this.lostReason = existingReason || '';
        this.openLostModal = true;
    },
    
    cancelLost() {
        if (this.currentSelectElement) {
            this.currentSelectElement.value = this.previousStageValue;
        }
        this.openLostModal = false;
    }
}">

    @if($errors->any())
        <div class="p-4 rounded-xl bg-red-950/60 border border-red-500/40 text-red-200 text-xs flex items-center gap-3">
            <span class="material-symbols-outlined text-red-400 text-xl">error</span>
            <div>
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Department Feature • Bisnis Development</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Pipeline Deals & Peluang Bisnis</h1>
            <p class="font-mono text-xs text-tertiary">Monitoring proyek prospek klien, tahapan negosiasi, dan pencapaian deals</p>
        </div>

        <button @click="openDealModal = true" class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
            <span class="material-symbols-outlined text-base">add_business</span>
            <span>Tambah Prospek Deal</span>
        </button>
    </div>

    <!-- Metric Cards -->
    @php
        $totalDealsPotential = $totalPipeline + $totalWon;
        $winRatePct = $totalDealsPotential > 0 ? round(($totalWon / $totalDealsPotential) * 100, 1) : 0;
        $pipelinePct = $totalDealsPotential > 0 ? round(($totalPipeline / $totalDealsPotential) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Active Deals Pipeline</span>
                    <span class="px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/30 text-blue-400 font-mono text-[10px] font-bold">
                        {{ $pipelinePct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-blue-400 mt-1">Rp {{ number_format($totalPipeline, 0, ',', '.') }}</div>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20">
                <span class="material-symbols-outlined text-xl">analytics</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Deals Berhasil (Won)</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        Win Rate: {{ $winRatePct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">Rp {{ number_format($totalWon, 0, ',', '.') }}</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">verified</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Deals Lost</span>
                    <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/30 text-red-400 font-mono text-[10px] font-bold">
                        {{ $lostDealsCount }} Deals
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-red-400 mt-1">Rp {{ number_format($totalLost, 0, ',', '.') }}</div>
            </div>
            <div class="p-2.5 rounded-lg bg-red-950/40 text-red-400 border border-red-500/20">
                <span class="material-symbols-outlined text-xl">trending_down</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Jumlah Proyek Aktif</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $activeDealsCount }} Klien</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">handshake</span>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#282a2e]/50 flex items-center justify-between bg-surface-container/30">
            <div class="flex items-center gap-1 font-mono text-xs overflow-x-auto">
                <a href="{{ route('bizdev.index') }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ empty($stage) ? 'bg-surface-container-highest text-on-surface font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Semua Tahapan
                </a>
                <a href="{{ route('bizdev.index', ['stage' => 'prospect']) }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $stage === 'prospect' ? 'bg-surface-container-highest text-blue-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Prospect
                </a>
                <a href="{{ route('bizdev.index', ['stage' => 'proposal']) }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $stage === 'proposal' ? 'bg-surface-container-highest text-amber-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Proposal
                </a>
                <a href="{{ route('bizdev.index', ['stage' => 'negotiation']) }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $stage === 'negotiation' ? 'bg-surface-container-highest text-purple-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Negotiation
                </a>
                <a href="{{ route('bizdev.index', ['stage' => 'won']) }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $stage === 'won' ? 'bg-surface-container-highest text-emerald-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Won Deals
                </a>
                <a href="{{ route('bizdev.index', ['stage' => 'lost']) }}" class="px-3 py-1.5 rounded-lg transition-colors whitespace-nowrap {{ $stage === 'lost' ? 'bg-surface-container-highest text-red-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Lost Deals ({{ $lostDealsCount }})
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                        <th class="px-5 py-3.5">Nama Klien / Perusahaan</th>
                        <th class="px-5 py-3.5">Nama Proyek</th>
                        <th class="px-5 py-3.5">Nilai Deal</th>
                        <th class="px-5 py-3.5">PIC BizDev</th>
                        <th class="px-5 py-3.5">Target Close</th>
                        <th class="px-5 py-3.5">Tahapan / Stage</th>
                        <th class="px-5 py-3.5 text-right">Update Stage</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/30 font-body">
                    @forelse($projects as $proj)
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-5 py-4 font-semibold text-on-surface text-sm">
                                {{ $proj->client_name }}
                            </td>
                            <td class="px-5 py-4 text-on-surface">
                                <div>{{ $proj->project_title }}</div>
                                @if($proj->notes)
                                    <span class="text-xs text-tertiary italic">{{ $proj->notes }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs font-bold text-on-surface">
                                Rp {{ number_format($proj->deal_value, 0, ',', '.') }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                {{ $proj->creator->name ?? 'BizDev Team' }}
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                {{ $proj->target_close_date ? \Carbon\Carbon::parse($proj->target_close_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                @if($proj->stage === 'won')
                                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] font-semibold">WON</span>
                                @elseif($proj->stage === 'negotiation')
                                    <span class="px-2 py-0.5 rounded bg-purple-950/60 border border-purple-500/20 text-purple-400 font-mono text-[10px] font-semibold">NEGOTIATION</span>
                                @elseif($proj->stage === 'proposal')
                                    <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/20 text-amber-400 font-mono text-[10px] font-semibold">PROPOSAL</span>
                                @elseif($proj->stage === 'lost')
                                    <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/20 text-red-400 font-mono text-[10px] font-semibold">LOST</span>
                                    @if($proj->lost_reason)
                                        <div class="mt-1.5 p-2 rounded-lg bg-red-950/30 border border-red-900/40 text-[11px] text-red-300 max-w-xs space-y-0.5">
                                            <div class="font-mono text-[9px] uppercase tracking-wider text-red-400 font-bold flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[12px]">info</span>
                                                <span>Alasan Lost:</span>
                                            </div>
                                            <p class="leading-snug break-words">{{ $proj->lost_reason }}</p>
                                        </div>
                                    @endif
                                @else
                                    <span class="px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/20 text-blue-400 font-mono text-[10px] font-semibold">PROSPECT</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <form action="{{ route('bizdev.updateStage', $proj->id) }}" method="POST" class="inline-flex flex-col items-end gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <select name="stage" 
                                            @change="promptLost($event, {{ $proj->id }}, '{{ addslashes($proj->client_name) }}', '{{ addslashes($proj->project_title) }}', 'Rp {{ number_format($proj->deal_value, 0, ',', '.') }}', '{{ route('bizdev.updateStage', $proj->id) }}', '{{ $proj->stage }}', '{{ addslashes($proj->lost_reason ?? '') }}')" 
                                            class="bg-surface-container-high border border-[#333539] rounded px-2 py-1 text-[11px] text-on-surface focus:outline-none">
                                        <option value="prospect" {{ $proj->stage === 'prospect' ? 'selected' : '' }}>Prospect</option>
                                        <option value="proposal" {{ $proj->stage === 'proposal' ? 'selected' : '' }}>Proposal</option>
                                        <option value="negotiation" {{ $proj->stage === 'negotiation' ? 'selected' : '' }}>Negotiation</option>
                                        <option value="won" {{ $proj->stage === 'won' ? 'selected' : '' }}>Won (Closing)</option>
                                        <option value="lost" {{ $proj->stage === 'lost' ? 'selected' : '' }}>Lost</option>
                                    </select>
                                    @if($proj->stage === 'lost')
                                        <button type="button" 
                                                @click="openEditLost({{ $proj->id }}, '{{ addslashes($proj->client_name) }}', '{{ addslashes($proj->project_title) }}', 'Rp {{ number_format($proj->deal_value, 0, ',', '.') }}', '{{ route('bizdev.updateStage', $proj->id) }}', '{{ addslashes($proj->lost_reason ?? '') }}')"
                                                class="text-[10px] text-red-400 hover:text-red-300 font-mono flex items-center gap-0.5 mt-0.5 transition-colors">
                                            <span class="material-symbols-outlined text-xs">edit_note</span>
                                            <span>Edit Alasan</span>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">Belum ada deal bisnis di pipeline.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($projects->hasPages())
            <div class="p-4 border-t border-[#282a2e]/40">{{ $projects->links() }}</div>
        @endif
    </div>

    <!-- Modal Form Tambah Deal -->
    <div x-cloak x-show="openDealModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-md p-6 shadow-2xl relative" @click.away="openDealModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <h3 class="font-headline font-semibold text-base text-on-surface">Tambah Prospek Deal Bisnis</h3>
                <button @click="openDealModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('bizdev.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Nama Perusahaan / Klien</label>
                    <input type="text" name="client_name" required placeholder="contoh: PT Bank Nusantara Tbk" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Judul Solusi / Proyek</label>
                    <input type="text" name="project_title" required placeholder="contoh: Implementasi Core Banking HR Module" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Perkiraan Nilai Deal (Rp)</label>
                        <input type="number" name="deal_value" min="0" step="500000" required placeholder="250000000" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Tahap Awal</label>
                        <select name="stage" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="prospect">Prospect</option>
                            <option value="proposal">Proposal</option>
                            <option value="negotiation">Negotiation</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Target Close Date</label>
                    <input type="date" name="target_close_date" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Catatan Tambahan (Kebutuhan Klien)</label>
                    <textarea name="notes" rows="2" placeholder="Catatan kualifikasi anggaran dan timeline klien..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openDealModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-md">Simpan Deal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Keterangan Lost Deal (Wajib Keterangan Alasan Lost) -->
    <div x-cloak x-show="openLostModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75">
        <div class="bg-surface-container rounded-2xl border border-red-500/30 w-full max-w-lg p-6 shadow-2xl relative" @click.away="cancelLost()">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-red-950/60 border border-red-500/30 flex items-center justify-center text-red-400">
                        <span class="material-symbols-outlined text-lg">trending_down</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Keterangan Deal Lost (Gagal)</h3>
                        <p class="font-mono text-[11px] text-tertiary">Sertakan keterangan penyebab kegagalan deal untuk evaluasi</p>
                    </div>
                </div>
                <button type="button" @click="cancelLost()" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="lostActionUrl" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')
                <input type="hidden" name="stage" value="lost">

                <!-- Ringkasan Info Deal -->
                <div class="p-3.5 rounded-xl bg-surface-container-low border border-[#282a2e]/60 space-y-1.5 font-mono text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-tertiary">Nama Klien / Perusahaan:</span>
                        <span class="font-bold text-on-surface text-right" x-text="lostClientName"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-tertiary">Judul Proyek:</span>
                        <span class="text-on-surface text-right" x-text="lostProjectTitle"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-tertiary">Nilai Deal Bisnis:</span>
                        <span class="font-bold text-red-400 text-right" x-text="lostDealValue"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-on-surface font-semibold mb-1.5">
                        Keterangan / Alasan Terjadinya Lost <span class="text-red-400">*</span>
                    </label>
                    <textarea 
                        name="lost_reason" 
                        x-model="lostReason" 
                        required 
                        rows="4" 
                        placeholder="Contoh: Klien mengalihkan alokasi anggaran ke proyek internal / Klien memilih vendor lokal kompetitor karena selisih penawaran harga / Kebutuhan implementasi dibatalkan oleh direksi klien..." 
                        class="w-full bg-surface-container-low border border-[#333539] focus:border-red-500 rounded-lg p-3 text-xs text-on-surface focus:outline-none placeholder:text-tertiary/60 leading-relaxed"></textarea>
                    <p class="text-[10px] text-tertiary font-mono mt-1">
                        * Keterangan wajib diisi sebelum status deal dialihkan ke Lost.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="cancelLost()" class="px-4 py-2 bg-surface-container-high hover:bg-surface-container-highest text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="flex items-center gap-1.5 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg shadow-md transition-colors">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        <span>Simpan & Tandai Lost</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
