@extends('layouts.app')

@section('title', 'Sekretariat Direksi - Pemantauan Kontrak & MoU Klien')

@section('content')
<div class="flex flex-col gap-6" x-data="contractManagerComponent()">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Executive Office • Sekretariat Direksi</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Monitoring Kontrak & MoU Kerjasama Klien</h1>
            <p class="font-mono text-xs text-tertiary">Pusat registrasi perjanjian hukum, penyimpanan draf MoU, tracking masa berlaku, dan peringatan dini habisnya kontrak kemitraan</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Tombol Tambah Kontrak / MoU Baru -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">handshake</span>
                <span>Daftarkan Kontrak/MoU Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Banner Peringatan Dini Masa Berlaku Habis -->
    @if($totalCritical > 0)
        <div class="p-4 bg-rose-950/60 border border-rose-500/40 rounded-xl flex items-start sm:items-center justify-between gap-3 shadow-lg">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-rose-900/80 rounded-lg text-rose-300">
                    <span class="material-symbols-outlined text-2xl animate-pulse">warning</span>
                </div>
                <div>
                    <h3 class="text-rose-200 font-bold text-sm">Peringatan Kritis: {{ $totalCritical }} Kontrak/MoU Berakhir dalam &le; 14 Hari!</h3>
                    <p class="text-rose-300/80 text-xs mt-0.5">Harap segera koordinasikan dengan Direktur Utama dan hubungi PIC Klien untuk konfirmasi perpanjangan atau penerbitan addendum.</p>
                </div>
            </div>
            <a href="{{ route('secretary.contracts.index', ['urgency' => 'critical']) }}" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold whitespace-nowrap transition-colors shadow">
                Lihat Kontrak Kritis
            </a>
        </div>
    @elseif($totalExpiringSoon > 0)
        <div class="p-4 bg-amber-950/50 border border-amber-500/40 rounded-xl flex items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-900/70 rounded-lg text-amber-300">
                    <span class="material-symbols-outlined text-2xl">alarm</span>
                </div>
                <div>
                    <h3 class="text-amber-200 font-bold text-sm">Perhatian: {{ $totalExpiringSoon }} Kontrak/MoU Berakhir dalam &le; 30 Hari</h3>
                    <p class="text-amber-300/80 text-xs mt-0.5">Segera lakukan peninjauan draf perpanjangan atau evaluasi klausul kerjasama bersama Direktur Utama.</p>
                </div>
            </div>
            <a href="{{ route('secretary.contracts.index', ['status' => 'expiring_soon']) }}" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-semibold whitespace-nowrap transition-colors shadow">
                Lihat Daftar Segera Habis
            </a>
        </div>
    @endif

    <!-- Metric Cards Monitoring Kontrak -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- Total Kontrak -->
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Total Kontrak / MoU</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalContracts }}</div>
                <span class="text-[10px] text-slate-400 font-mono">Tercatat di sistem</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-amber-400">
                <span class="material-symbols-outlined text-xl">folder_shared</span>
            </div>
        </div>

        <!-- Aktif Berjalan -->
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Aktif Berjalan</span>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $totalActive }}</div>
                <span class="text-[10px] text-slate-400 font-mono">Masa berlaku sah</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-emerald-400">
                <span class="material-symbols-outlined text-xl">verified</span>
            </div>
        </div>

        <!-- Segera Berakhir -->
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Segera Berakhir (&le; 30 Hari)</span>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $totalExpiringSoon }}</div>
                <span class="text-[10px] text-amber-300/80 font-mono">Perlu tindak lanjut</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-amber-400">
                <span class="material-symbols-outlined text-xl">timer</span>
            </div>
        </div>

        <!-- Kedaluwarsa / Expired -->
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Kedaluwarsa / Habis</span>
                <div class="font-headline text-2xl font-bold text-rose-400 mt-1">{{ $totalExpired }}</div>
                <span class="text-[10px] text-rose-300/80 font-mono">Lewat masa berlaku</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-rose-400">
                <span class="material-symbols-outlined text-xl">event_busy</span>
            </div>
        </div>

        <!-- Draft & Review Direktur -->
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Draft & Review Direktur</span>
                <div class="font-headline text-2xl font-bold text-sky-400 mt-1">{{ $totalDraft }}</div>
                <span class="text-[10px] text-slate-400 font-mono">Tahap penyusunan</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-sky-400">
                <span class="material-symbols-outlined text-xl">edit_document</span>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Bar -->
    <form action="{{ route('secretary.contracts.index') }}" method="GET" class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
            <!-- Search Text -->
            <div class="relative min-w-[220px] flex-1 max-w-sm">
                <span class="material-symbols-outlined absolute left-2.5 top-2 text-tertiary text-base">search</span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari No. MoU, Klien, Judul, atau PIC..." 
                       class="w-full pl-8 pr-3 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface placeholder:text-tertiary focus:outline-none focus:border-amber-500 text-xs">
            </div>

            <!-- Status Filter -->
            <select name="status" class="px-3 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                <option value="">-- Semua Status --</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif Berjalan</option>
                <option value="expiring_soon" {{ $status === 'expiring_soon' ? 'selected' : '' }}>Segera Berakhir (<= 30 Hari)</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Kedaluwarsa (Expired)</option>
                <option value="review_direktur" {{ $status === 'review_direktur' ? 'selected' : '' }}>Review Direktur</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft Penyusunan</option>
                <option value="renewed" {{ $status === 'renewed' ? 'selected' : '' }}>Telah Diperpanjang</option>
                <option value="terminated" {{ $status === 'terminated' ? 'selected' : '' }}>Dihentikan / Batal</option>
            </select>

            <!-- Kategori Klien Filter -->
            <select name="category" class="px-3 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                <option value="">-- Semua Kategori Klien --</option>
                @foreach($categories as $key => $lbl)
                    <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>

            <!-- Jenis Dokumen Filter -->
            <select name="type" class="px-3 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                <option value="">-- Semua Jenis Perjanjian --</option>
                @foreach($types as $key => $lbl)
                    <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg font-semibold transition-colors flex items-center gap-1 shadow-sm">
                <span class="material-symbols-outlined text-base">filter_alt</span>
                <span>Terapkan</span>
            </button>
            <a href="{{ route('secretary.contracts.index') }}" class="px-3 py-1.5 bg-surface-container border border-[#333539] hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg transition-colors">
                Reset
            </a>
        </div>
    </form>

    <!-- Tabel Data Kontrak & MoU Klien -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/60 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e] bg-surface-container/60 text-tertiary font-mono uppercase text-[10px]">
                        <th class="py-3 px-4">No. Kontrak & Jenis</th>
                        <th class="py-3 px-4">Klien / Mitra Kerjasama</th>
                        <th class="py-3 px-4">Judul & Nilai Kerjasama</th>
                        <th class="py-3 px-4">Masa Berlaku</th>
                        <th class="py-3 px-4">Sisa Waktu & Status</th>
                        <th class="py-3 px-4">Dokumen Fisik</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/40">
                    @forelse($contracts as $contract)
                        @php
                            $computed = $contract->computed_status;
                            $urgency = $contract->urgency_level;
                            $days = $contract->days_remaining;
                        @endphp
                        <tr class="hover:bg-surface-container/40 transition-colors {{ $urgency === 'critical' ? 'bg-rose-950/10' : ($urgency === 'warning' ? 'bg-amber-950/10' : '') }}">
                            <!-- No Kontrak & Jenis -->
                            <td class="py-3 px-4 align-top">
                                <div class="font-mono font-bold text-on-surface text-xs flex items-center gap-1.5">
                                    <span>{{ $contract->contract_number }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="px-2 py-0.5 rounded bg-surface-container text-amber-300 border border-amber-500/20 font-mono text-[9px] uppercase font-bold">
                                        {{ $contract->type_label }}
                                    </span>
                                    @if($contract->auto_renewal)
                                        <span class="px-1.5 py-0.5 rounded bg-sky-950/70 text-sky-300 border border-sky-500/30 text-[9px] font-mono" title="Perjanjian ini memiliki klausul perpanjangan otomatis">
                                            Auto-Renew
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] text-tertiary block mt-1">
                                    Dibuat: {{ $contract->created_at->format('d/m/Y') }}
                                </span>
                            </td>

                            <!-- Klien & Kontak -->
                            <td class="py-3 px-4 align-top">
                                <div class="font-semibold text-on-surface text-xs">
                                    {{ $contract->client_name }}
                                </div>
                                <span class="inline-block mt-0.5 text-[10px] font-mono text-tertiary">
                                    {{ $contract->category_label }}
                                </span>
                                @if($contract->client_pic_name)
                                    <div class="mt-1 text-[11px] text-slate-300 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px] text-amber-400">person</span>
                                        <span>{{ $contract->client_pic_name }}</span>
                                    </div>
                                    @if($contract->client_pic_phone)
                                        <div class="text-[10px] text-tertiary font-mono">
                                            {{ $contract->client_pic_phone }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Judul & Nilai Kerjasama -->
                            <td class="py-3 px-4 align-top max-w-xs">
                                <p class="text-xs text-on-surface font-medium line-clamp-2" title="{{ $contract->title }}">
                                    {{ $contract->title }}
                                </p>
                                @if($contract->contract_value)
                                    <div class="mt-1 text-[11px] font-mono font-bold text-emerald-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">payments</span>
                                        <span>{{ $contract->formatted_contract_value }}</span>
                                    </div>
                                @endif
                                @if($contract->notes)
                                    <span class="text-[10px] text-tertiary line-clamp-1 mt-0.5 italic" title="{{ $contract->notes }}">
                                        Catatan: {{ $contract->notes }}
                                    </span>
                                @endif
                            </td>

                            <!-- Masa Berlaku -->
                            <td class="py-3 px-4 align-top font-mono text-[11px]">
                                <div class="text-tertiary">Mulai: <span class="text-on-surface">{{ $contract->start_date->format('d M Y') }}</span></div>
                                <div class="mt-0.5 text-tertiary">Berakhir: <span class="font-bold {{ $urgency === 'expired' ? 'text-rose-400' : ($urgency === 'critical' ? 'text-rose-300' : 'text-on-surface') }}">{{ $contract->end_date->format('d M Y') }}</span></div>
                                @if($contract->signed_at)
                                    <div class="text-[10px] text-slate-400 mt-1">Ditandatangani: {{ $contract->signed_at->format('d/m/Y') }}</div>
                                @endif
                            </td>

                            <!-- Sisa Waktu & Status -->
                            <td class="py-3 px-4 align-top">
                                <!-- Badge Status -->
                                @if($computed === 'active')
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Aktif Berjalan
                                    </span>
                                @elseif($computed === 'expiring_soon')
                                    <span class="px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        Segera Habis
                                    </span>
                                @elseif($computed === 'expired')
                                    <span class="px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        Kedaluwarsa
                                    </span>
                                @elseif($computed === 'review_direktur')
                                    <span class="px-2.5 py-1 rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                                        Review Direktur
                                    </span>
                                @elseif($computed === 'draft')
                                    <span class="px-2.5 py-1 rounded-full bg-slate-500/10 text-slate-300 border border-slate-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Draft
                                    </span>
                                @elseif($computed === 'renewed')
                                    <span class="px-2.5 py-1 rounded-full bg-sky-500/10 text-sky-300 border border-sky-500/30 text-[10px] font-mono font-semibold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-400"></span>
                                        Diperpanjang
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full bg-zinc-500/10 text-zinc-400 border border-zinc-500/30 text-[10px] font-mono font-semibold">
                                        {{ ucfirst($contract->status) }}
                                    </span>
                                @endif

                                <!-- Countdown Days -->
                                <div class="mt-1.5">
                                    @if($computed === 'expired')
                                        <span class="text-[10px] font-mono font-bold text-rose-400 flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[13px]">history</span>
                                            <span>Lewat {{ abs($days) }} hari lalu</span>
                                        </span>
                                    @elseif($urgency === 'critical')
                                        <span class="text-[10px] font-mono font-bold text-rose-300 bg-rose-950/80 px-1.5 py-0.5 rounded border border-rose-500/30 flex items-center gap-1 w-max">
                                            <span class="material-symbols-outlined text-[12px] animate-pulse">priority_high</span>
                                            <span>Sisa {{ $days }} Hari Lagi!</span>
                                        </span>
                                    @elseif($urgency === 'warning')
                                        <span class="text-[10px] font-mono font-semibold text-amber-300 bg-amber-950/60 px-1.5 py-0.5 rounded border border-amber-500/30 flex items-center gap-1 w-max">
                                            <span class="material-symbols-outlined text-[12px]">timer</span>
                                            <span>Sisa {{ $days }} Hari</span>
                                        </span>
                                    @elseif($computed === 'active')
                                        <span class="text-[10px] font-mono text-emerald-400">
                                            Sisa {{ $days }} hari
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Dokumen Fisik -->
                            <td class="py-3 px-4 align-top">
                                <div class="flex flex-col gap-1.5">
                                    @if($contract->signed_file_path)
                                        <a href="{{ route('secretary.contracts.download', ['id' => $contract->id, 'type' => 'signed']) }}" 
                                           target="_blank" 
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono text-[10px] font-semibold transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-xs">verified</span>
                                            <span>Signed MoU (PDF)</span>
                                        </a>
                                    @endif

                                    @if($contract->draft_file_path)
                                        <a href="{{ route('secretary.contracts.download', ['id' => $contract->id, 'type' => 'draft']) }}" 
                                           target="_blank" 
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 font-mono text-[10px] font-semibold transition-all shadow-sm">
                                            <span class="material-symbols-outlined text-xs">draft</span>
                                            <span>Draft Berkas</span>
                                        </a>
                                    @endif

                                    @if(!$contract->signed_file_path && !$contract->draft_file_path)
                                        <span class="text-[10px] text-tertiary font-mono italic">Belum ada berkas</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3 px-4 align-top text-right">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <!-- Tombol Detail -->
                                    <button type="button" 
                                            @click="openDetailModal({{ json_encode($contract) }})" 
                                            class="p-1.5 bg-surface-container hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg transition-colors border border-[#333539]" 
                                            title="Lihat Detail Kontrak">
                                        <span class="material-symbols-outlined text-base">visibility</span>
                                    </button>

                                    <!-- Tombol Perpanjang (Renew) -->
                                    <button type="button" 
                                            @click="openRenewModal({{ json_encode($contract) }})" 
                                            class="p-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 rounded-lg transition-colors border border-amber-500/30" 
                                            title="Perpanjang Masa Berlaku (Addendum)">
                                        <span class="material-symbols-outlined text-base">update</span>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button" 
                                            @click="openEditModal({{ json_encode($contract) }})" 
                                            class="p-1.5 bg-surface-container hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg transition-colors border border-[#333539]" 
                                            title="Edit Kontrak">
                                        <span class="material-symbols-outlined text-base">edit</span>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <form action="{{ route('secretary.contracts.destroy', $contract->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kontrak/MoU {{ $contract->contract_number }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-surface-container hover:bg-rose-500/20 text-tertiary hover:text-rose-400 rounded-lg transition-colors border border-[#333539]" title="Hapus Kontrak">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-tertiary">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-600">handshake</span>
                                    <p class="font-headline font-semibold text-sm text-on-surface">Tidak Ada Data Kontrak / MoU Ditemukan</p>
                                    <p class="text-xs max-w-sm">Gunakan tombol "Daftarkan Kontrak/MoU Baru" di atas untuk menambahkan perjanjian kerjasama klien ke dalam sistem monitoring.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($contracts->hasPages())
            <div class="p-4 border-t border-[#282a2e]/60 bg-surface-container/30">
                {{ $contracts->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: DAFTARKAN KONTRAK / MOU BARU (Strictly No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-amber-500/30 w-full max-w-3xl p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showCreateModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">handshake</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Daftarkan Kontrak / MoU Kerjasama Baru</h3>
                        <p class="text-xs text-tertiary font-mono">Registrasi dokumen hukum, data klien, masa berlaku, dan unggah draf/berkas</p>
                    </div>
                </div>
                <button @click="showCreateModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('secretary.contracts.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <!-- Nomor Kontrak & Jenis Perjanjian -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-tertiary">Nomor Kontrak / MoU Resmi <span class="text-rose-400">*</span></label>
                        <input type="text" 
                               name="contract_number" 
                               required 
                               placeholder="Contoh: 012/MOU-GYK/XI/2026" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Jenis Perjanjian <span class="text-rose-400">*</span></label>
                        <select name="contract_type" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            @foreach($types as $key => $lbl)
                                <option value="{{ $key }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Judul Kerjasama -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Judul / Perihal Kerjasama <span class="text-rose-400">*</span></label>
                    <input type="text" 
                           name="title" 
                           required 
                           placeholder="Contoh: Kerjasama Penyaluran Tenaga Kerja Magang Jepang Batch 2026" 
                           class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                </div>

                <!-- Nama Klien & Kategori Klien -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-tertiary">Nama Perusahaan Klien / Lembaga Mitra <span class="text-rose-400">*</span></label>
                        <input type="text" 
                               name="client_name" 
                               required 
                               placeholder="Contoh: PT Asahi Seimitsu Indonesia" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-semibold">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Kategori Klien <span class="text-rose-400">*</span></label>
                        <select name="client_category" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            @foreach($categories as $key => $lbl)
                                <option value="{{ $key }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Kontak PIC Klien -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">Nama PIC / Kontak Klien</label>
                        <input type="text" 
                               name="client_pic_name" 
                               placeholder="Nama Penanggung Jawab" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">No. Telepon / WhatsApp PIC</label>
                        <input type="text" 
                               name="client_pic_phone" 
                               placeholder="Contoh: 0812-xxxx-xxxx" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">Alamat Email PIC</label>
                        <input type="email" 
                               name="client_pic_email" 
                               placeholder="pic@klien.com" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                    <div class="md:col-span-3 space-y-1 pt-1">
                        <label class="block text-[11px] font-medium text-tertiary">Alamat Kantor / Domisili Klien</label>
                        <input type="text" 
                               name="client_address" 
                               placeholder="Alamat lengkap instansi/perusahaan klien" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                </div>

                <!-- Masa Berlaku Kontrak & Status Awal -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3.5">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tanggal Mulai Berlaku <span class="text-rose-400">*</span></label>
                        <input type="date" 
                               name="start_date" 
                               value="{{ date('Y-m-d') }}" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tanggal Berakhir <span class="text-rose-400">*</span></label>
                        <input type="date" 
                               name="end_date" 
                               value="{{ date('Y-m-d', strtotime('+1 year')) }}" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Status Dokumen <span class="text-rose-400">*</span></label>
                        <select name="status" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            <option value="active">Aktif Berjalan</option>
                            <option value="review_direktur">Review Direktur</option>
                            <option value="draft">Draft Penyusunan</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tgl Ditandatangani</label>
                        <input type="date" 
                               name="signed_at" 
                               value="{{ date('Y-m-d') }}" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>
                </div>

                <!-- Nilai Kontrak & Auto-Renewal -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Mata Uang</label>
                        <select name="currency" class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="JPY">JPY (Yen Jepang)</option>
                            <option value="USD">USD (Dolar)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Nilai Kontrak (Opsional)</label>
                        <input type="number" 
                               name="contract_value" 
                               step="any" 
                               placeholder="Contoh: 150000000" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="flex items-center gap-2.5 pt-6">
                        <input type="checkbox" id="auto_renewal" name="auto_renewal" value="1" class="rounded bg-surface-container-low border-[#333539] text-amber-600 focus:ring-0">
                        <label for="auto_renewal" class="text-xs text-on-surface font-medium cursor-pointer">
                            Klausul Perpanjangan Otomatis (Auto-Renewal)
                        </label>
                    </div>
                </div>

                <!-- Unggah Berkas: Draft & Signed MoU -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-sky-400 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">draft</span>
                            <span>Unggah Draft Berkas MoU (.docx / .doc / .pdf)</span>
                        </label>
                        <p class="text-[11px] text-tertiary">Untuk penyimpanan draft kerja sebelum penandatanganan final.</p>
                        <input type="file" 
                               name="draft_file" 
                               accept=".pdf,.doc,.docx" 
                               class="w-full text-xs text-tertiary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-surface-container-high file:text-on-surface hover:file:bg-surface-bright border border-[#333539] rounded-lg p-1 bg-surface-container">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-emerald-400 flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-base">verified</span>
                            <span>Unggah Berkas Resmi Bertanda Tangan (.pdf)</span>
                        </label>
                        <p class="text-[11px] text-tertiary">Scan dokumen MoU/Kontrak yang telah bertanda tangan & bermeterai.</p>
                        <input type="file" 
                               name="signed_file" 
                               accept=".pdf" 
                               class="w-full text-xs text-tertiary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-surface-container-high file:text-on-surface hover:file:bg-surface-bright border border-[#333539] rounded-lg p-1 bg-surface-container">
                    </div>
                </div>

                <!-- Catatan / Klausul Penting -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Klausul Khusus / Catatan Tambahan</label>
                    <textarea name="notes" 
                              rows="2" 
                              placeholder="Klausul penalti, batas permohonan terminasi, jadwal evaluasi berkala, dsb..." 
                              class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#282a2e]">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 text-white font-semibold text-xs rounded-lg transition-all shadow-md">
                        Simpan Kontrak / MoU
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: PERPANJANG MASA BERLAKU KONTRAK / ADDENDUM (Strictly No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showRenewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-amber-500/40 w-full max-w-lg p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showRenewModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">update</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Perpanjangan Kontrak / Addendum</h3>
                        <p class="text-xs text-tertiary font-mono" x-text="activeContract.contract_number"></p>
                    </div>
                </div>
                <button @click="showRenewModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/secretary/contracts') }}/' + activeContract.id + '/renew'" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <div class="p-3 bg-surface-container-low rounded-xl border border-[#333539] space-y-1">
                    <div class="text-xs text-tertiary">Nama Klien / Mitra:</div>
                    <div class="font-semibold text-sm text-on-surface" x-text="activeContract.client_name"></div>
                    <div class="text-[11px] text-tertiary font-mono mt-1">
                        Tanggal Berakhir Sebelumnya: <strong class="text-rose-400" x-text="formatDate(activeContract.end_date)"></strong>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Tanggal Berakhir Baru (Masa Berlaku Diperpanjang) <span class="text-rose-400">*</span></label>
                    <input type="date" 
                           name="new_end_date" 
                           required 
                           class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono font-bold text-amber-400">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Catatan Perpanjangan / Nomor Addendum</label>
                    <textarea name="renewal_notes" 
                              rows="2" 
                              placeholder="Contoh: Perpanjangan 1 tahun berdasarkan Surat Persetujuan Addendum No. 004/ADD/2027..." 
                              class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs"></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-medium text-emerald-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">upload_file</span>
                        <span>Unggah Berkas Scan Addendum / Perpanjangan (.pdf)</span>
                    </label>
                    <input type="file" 
                           name="signed_file" 
                           accept=".pdf" 
                           class="w-full text-xs text-tertiary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-surface-container-high file:text-on-surface hover:file:bg-surface-bright border border-[#333539] rounded-lg p-1 bg-surface-container">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#282a2e]">
                    <button type="button" @click="showRenewModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 text-white font-semibold text-xs rounded-lg transition-all shadow-md">
                        Simpan Perpanjangan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: EDIT DATA KONTRAK / MOU (Strictly No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-amber-500/30 w-full max-w-3xl p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showEditModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">edit_note</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Edit Data Kontrak / MoU Kerjasama</h3>
                        <p class="text-xs text-tertiary font-mono" x-text="editForm.contract_number"></p>
                    </div>
                </div>
                <button @click="showEditModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/secretary/contracts') }}/' + editForm.id" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <!-- Nomor Kontrak & Jenis Perjanjian -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-tertiary">Nomor Kontrak / MoU <span class="text-rose-400">*</span></label>
                        <input type="text" 
                               name="contract_number" 
                               x-model="editForm.contract_number" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Jenis Perjanjian <span class="text-rose-400">*</span></label>
                        <select name="contract_type" x-model="editForm.contract_type" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            @foreach($types as $key => $lbl)
                                <option value="{{ $key }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Judul Kerjasama -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Judul / Perihal Kerjasama <span class="text-rose-400">*</span></label>
                    <input type="text" 
                           name="title" 
                           x-model="editForm.title" 
                           required 
                           class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                </div>

                <!-- Nama Klien & Kategori Klien -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-medium text-tertiary">Nama Klien / Lembaga Mitra <span class="text-rose-400">*</span></label>
                        <input type="text" 
                               name="client_name" 
                               x-model="editForm.client_name" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-semibold">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Kategori Klien <span class="text-rose-400">*</span></label>
                        <select name="client_category" x-model="editForm.client_category" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            @foreach($categories as $key => $lbl)
                                <option value="{{ $key }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Kontak PIC Klien -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50">
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">Nama PIC Klien</label>
                        <input type="text" 
                               name="client_pic_name" 
                               x-model="editForm.client_pic_name" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">No. Telp / WA PIC</label>
                        <input type="text" 
                               name="client_pic_phone" 
                               x-model="editForm.client_pic_phone" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[11px] font-medium text-tertiary">Email PIC</label>
                        <input type="email" 
                               name="client_pic_email" 
                               x-model="editForm.client_pic_email" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                    <div class="md:col-span-3 space-y-1 pt-1">
                        <label class="block text-[11px] font-medium text-tertiary">Alamat Klien</label>
                        <input type="text" 
                               name="client_address" 
                               x-model="editForm.client_address" 
                               class="w-full px-2.5 py-1.5 bg-surface-container rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                    </div>
                </div>

                <!-- Masa Berlaku Kontrak & Status -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3.5">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tanggal Mulai <span class="text-rose-400">*</span></label>
                        <input type="date" 
                               name="start_date" 
                               x-model="editForm.start_date" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tanggal Berakhir <span class="text-rose-400">*</span></label>
                        <input type="date" 
                               name="end_date" 
                               x-model="editForm.end_date" 
                               required 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Status <span class="text-rose-400">*</span></label>
                        <select name="status" x-model="editForm.status" required class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs">
                            <option value="active">Aktif Berjalan</option>
                            <option value="review_direktur">Review Direktur</option>
                            <option value="draft">Draft Penyusunan</option>
                            <option value="renewed">Telah Diperpanjang</option>
                            <option value="terminated">Dihentikan / Batal</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Tgl Ditandatangani</label>
                        <input type="date" 
                               name="signed_at" 
                               x-model="editForm.signed_at" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>
                </div>

                <!-- Nilai Kontrak & Auto-Renewal -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Mata Uang</label>
                        <select name="currency" x-model="editForm.currency" class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                            <option value="IDR">IDR (Rupiah)</option>
                            <option value="JPY">JPY (Yen Jepang)</option>
                            <option value="USD">USD (Dolar)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-tertiary">Nilai Kontrak</label>
                        <input type="number" 
                               name="contract_value" 
                               step="any" 
                               x-model="editForm.contract_value" 
                               class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs font-mono">
                    </div>

                    <div class="flex items-center gap-2.5 pt-6">
                        <input type="checkbox" id="edit_auto_renewal" name="auto_renewal" value="1" x-model="editForm.auto_renewal" class="rounded bg-surface-container-low border-[#333539] text-amber-600 focus:ring-0">
                        <label for="edit_auto_renewal" class="text-xs text-on-surface font-medium cursor-pointer">
                            Perpanjangan Otomatis (Auto-Renewal)
                        </label>
                    </div>
                </div>

                <!-- Ganti Berkas -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-sky-400">Ganti Draft Berkas (.docx / .pdf)</label>
                        <input type="file" 
                               name="draft_file" 
                               accept=".pdf,.doc,.docx" 
                               class="w-full text-xs text-tertiary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-surface-container-high file:text-on-surface hover:file:bg-surface-bright border border-[#333539] rounded-lg p-1 bg-surface-container">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-emerald-400">Ganti Berkas Signed Resmi (.pdf)</label>
                        <input type="file" 
                               name="signed_file" 
                               accept=".pdf" 
                               class="w-full text-xs text-tertiary file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-surface-container-high file:text-on-surface hover:file:bg-surface-bright border border-[#333539] rounded-lg p-1 bg-surface-container">
                    </div>
                </div>

                <!-- Catatan -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-tertiary">Klausul Khusus / Catatan Tambahan</label>
                    <textarea name="notes" 
                              rows="2" 
                              x-model="editForm.notes" 
                              class="w-full px-3 py-2 bg-surface-container-low rounded-lg border border-[#333539] text-on-surface focus:outline-none focus:border-amber-500 text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-[#282a2e]">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 text-white font-semibold text-xs rounded-lg transition-all shadow-md">
                        Perbarui Data Kontrak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: DETAIL LENGKAP KONTRAK / MOU (Strictly No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-amber-500/30 w-full max-w-2xl p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showDetailModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">description</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface" x-text="activeContract.title"></h3>
                        <p class="text-xs text-tertiary font-mono" x-text="activeContract.contract_number"></p>
                    </div>
                </div>
                <button @click="showDetailModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="mt-4 space-y-4 text-xs">
                <!-- Info Klien -->
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/60 space-y-2">
                    <span class="font-mono text-[10px] text-amber-400 uppercase font-semibold block">Informasi Klien / Mitra</span>
                    <div class="grid grid-cols-2 gap-2 text-on-surface">
                        <div>
                            <span class="text-tertiary block text-[11px]">Nama Perusahaan/Lembaga:</span>
                            <span class="font-bold text-sm" x-text="activeContract.client_name"></span>
                        </div>
                        <div>
                            <span class="text-tertiary block text-[11px]">Kategori:</span>
                            <span class="font-mono text-xs" x-text="activeContract.client_category"></span>
                        </div>
                        <div x-show="activeContract.client_pic_name">
                            <span class="text-tertiary block text-[11px]">PIC Penanggung Jawab:</span>
                            <span x-text="activeContract.client_pic_name"></span>
                            <span x-show="activeContract.client_pic_phone" class="text-tertiary font-mono text-[10px] block" x-text="activeContract.client_pic_phone"></span>
                        </div>
                        <div x-show="activeContract.client_pic_email">
                            <span class="text-tertiary block text-[11px]">Email PIC:</span>
                            <span class="font-mono text-[11px]" x-text="activeContract.client_pic_email"></span>
                        </div>
                        <div class="col-span-2" x-show="activeContract.client_address">
                            <span class="text-tertiary block text-[11px]">Alamat:</span>
                            <span x-text="activeContract.client_address"></span>
                        </div>
                    </div>
                </div>

                <!-- Info Masa Berlaku & Nilai -->
                <div class="grid grid-cols-2 gap-3 p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/60">
                    <div>
                        <span class="text-tertiary block text-[11px]">Mulai Berlaku:</span>
                        <span class="font-mono font-bold text-on-surface" x-text="formatDate(activeContract.start_date)"></span>
                    </div>
                    <div>
                        <span class="text-tertiary block text-[11px]">Berakhir Pada:</span>
                        <span class="font-mono font-bold text-amber-400" x-text="formatDate(activeContract.end_date)"></span>
                    </div>
                    <div x-show="activeContract.contract_value">
                        <span class="text-tertiary block text-[11px]">Nilai Kontrak:</span>
                        <span class="font-mono font-bold text-emerald-400" x-text="(activeContract.currency || 'IDR') + ' ' + (activeContract.contract_value || '-')"></span>
                    </div>
                    <div>
                        <span class="text-tertiary block text-[11px]">Klausul Auto-Renewal:</span>
                        <span class="font-mono text-xs" x-text="activeContract.auto_renewal ? 'Aktif (Otomatis Perpanjang)' : 'Tidak Ada'"></span>
                    </div>
                </div>

                <!-- Klausul & Catatan -->
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/60 space-y-1.5" x-show="activeContract.notes">
                    <span class="font-mono text-[10px] text-amber-400 uppercase font-semibold block">Catatan & Riwayat Perpanjangan</span>
                    <p class="text-slate-300 whitespace-pre-line text-xs font-mono leading-relaxed" x-text="activeContract.notes"></p>
                </div>

                <!-- Unduh Berkas -->
                <div class="flex items-center gap-3 pt-2">
                    <template x-if="activeContract.signed_file_path">
                        <a :href="'{{ url('/secretary/contracts') }}/' + activeContract.id + '/download/signed'" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base">verified</span>
                            <span>Buka Berkas Signed (PDF)</span>
                        </a>
                    </template>

                    <template x-if="activeContract.draft_file_path">
                        <a :href="'{{ url('/secretary/contracts') }}/' + activeContract.id + '/download/draft'" target="_blank" class="px-4 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
                            <span class="material-symbols-outlined text-base">draft</span>
                            <span>Unduh Draft Berkas</span>
                        </a>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-[#282a2e] mt-4">
                <button type="button" @click="showDetailModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function contractManagerComponent() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showRenewModal: false,
        showDetailModal: false,
        activeContract: {},
        editForm: {},

        openCreateModal() {
            this.showCreateModal = true;
        },

        openEditModal(contract) {
            this.editForm = {
                id: contract.id,
                contract_number: contract.contract_number,
                title: contract.title,
                client_name: contract.client_name,
                client_category: contract.client_category,
                contract_type: contract.contract_type,
                client_pic_name: contract.client_pic_name || '',
                client_pic_phone: contract.client_pic_phone || '',
                client_pic_email: contract.client_pic_email || '',
                client_address: contract.client_address || '',
                start_date: contract.start_date ? contract.start_date.substring(0, 10) : '',
                end_date: contract.end_date ? contract.end_date.substring(0, 10) : '',
                status: contract.status,
                auto_renewal: Boolean(contract.auto_renewal),
                contract_value: contract.contract_value || '',
                currency: contract.currency || 'IDR',
                notes: contract.notes || '',
                signed_at: contract.signed_at ? contract.signed_at.substring(0, 10) : '',
            };
            this.showEditModal = true;
        },

        openRenewModal(contract) {
            this.activeContract = contract;
            this.showRenewModal = true;
        },

        openDetailModal(contract) {
            this.activeContract = contract;
            this.showDetailModal = true;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        }
    };
}
</script>
@endsection

