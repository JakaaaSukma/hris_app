@extends('layouts.app')

@section('title', 'General Affairs - Inventaris & Manajemen ATK Kantor')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openAssetModal: false, 
    openRequestModal: false, 
    detailRequestModal: false,
    openSupplyModal: false,
    openUsageModal: false,
    openRestockModal: false,
    editSupplyModal: false,
    selectedRequest: null,
    selectedSupplyForUsage: '',
    selectedSupplyForRestock: null,
    editingSupply: {},
    activeTab: '{{ $activeTab ?? 'supplies' }}',
    atkSubTab: 'catalog',
    requestType: 'penambahan',
    selectedInventoryItem: '',
    itemsList: {{ json_encode($items->items()) }},
    onItemChange(itemId) {
        const found = this.itemsList.find(i => i.id == itemId);
        if (found) {
            document.getElementById('item_name_input').value = found.name;
            document.getElementById('item_category_input').value = found.category;
        }
    },
    openRecordUsage(supplyId = '') {
        this.selectedSupplyForUsage = supplyId ? String(supplyId) : '';
        this.openUsageModal = true;
    },
    openRestock(supply) {
        this.selectedSupplyForRestock = supply;
        this.openRestockModal = true;
    },
    openEditSupply(supply) {
        this.editingSupply = Object.assign({}, supply);
        this.editSupplyModal = true;
    }
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Department Feature • General Affairs (GA) & Finance</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Manajemen Aset & Pemakaian ATK Kantor</h1>
            <p class="font-mono text-xs text-tertiary">Pencatatan persediaan ATK, distribusi pemakaian barang habis pakai, inventaris aset, dan perbaikan fasilitas kantor</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            @if(auth()->user()->canControlGA())
                <!-- Tombol Catat Pemakaian ATK -->
                <button @click="openRecordUsage()" class="flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                    <span class="material-symbols-outlined text-base">outgoing_mail</span>
                    <span>Catat Pemakaian ATK</span>
                </button>

                <!-- Tombol Tambah Master ATK -->
                <button @click="openSupplyModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high hover:bg-surface-bright text-on-surface border border-[#333539] rounded-lg text-xs font-semibold transition-all shadow-sm">
                    <span class="material-symbols-outlined text-base text-emerald-400">add_box</span>
                    <span>Tambah ATK</span>
                </button>
            @endif

            <!-- Tombol Pengajuan Penambahan/Perbaikan Aset (Untuk GA & Staf) -->
            <button @click="openRequestModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">post_add</span>
                <span>Ajukan Inventaris</span>
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-[#282a2e]/60 pb-1 overflow-x-auto">
        <!-- TAB 1: MANAJEMEN ATK -->
        <button @click="activeTab = 'supplies'" 
                :class="activeTab === 'supplies' ? 'text-emerald-400 border-emerald-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all whitespace-nowrap">
            <span class="material-symbols-outlined text-base">edit_note</span>
            <span>Stok & Pemakaian ATK ({{ $totalAtkItems }})</span>
            @if($lowStockAtkCount + $outOfStockAtkCount > 0)
                <span class="px-1.5 py-0.2 rounded-full bg-rose-500/20 text-rose-300 text-[10px] font-bold border border-rose-500/30">
                    {{ $lowStockAtkCount + $outOfStockAtkCount }} Restock
                </span>
            @endif
        </button>

        <!-- TAB 2: KATALOG INVENTARIS ASET -->
        <button @click="activeTab = 'inventory'" 
                :class="activeTab === 'inventory' ? 'text-amber-400 border-amber-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all whitespace-nowrap">
            <span class="material-symbols-outlined text-base">inventory_2</span>
            <span>Katalog Aset & Inventaris ({{ $items->total() }})</span>
        </button>

        <!-- TAB 3: PENGAJUAN PENAMBAHAN / PERBAIKAN -->
        <button @click="activeTab = 'requests'" 
                :class="activeTab === 'requests' ? 'text-amber-400 border-amber-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all whitespace-nowrap">
            <span class="material-symbols-outlined text-base">assignment_turned_in</span>
            <span>Pengajuan Penambahan & Perbaikan</span>
            @if($pendingRequestsCount > 0)
                <span class="px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold border border-amber-500/30">
                    {{ $pendingRequestsCount }}
                </span>
            @endif
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 1: MANAJEMEN STOK & PEMAKAIAN ATK KANTOR                   -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'supplies'" class="space-y-5">
        
        <!-- Metric Cards Khusus ATK -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-tertiary uppercase">Total Macam ATK</span>
                    <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalAtkItems }} Barang</div>
                    <span class="text-[10px] text-slate-400 font-mono">Tersedia di Gudang GA</span>
                </div>
                <div class="p-2.5 rounded-lg bg-surface-container text-emerald-400">
                    <span class="material-symbols-outlined text-xl">category</span>
                </div>
            </div>

            <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-tertiary uppercase">Total Stok Fisik</span>
                    <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ number_format($totalAtkStock) }} Unit</div>
                    <span class="text-[10px] text-slate-400 font-mono">Gabungan Rim, Box, Pcs</span>
                </div>
                <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                    <span class="material-symbols-outlined text-xl">inventory</span>
                </div>
            </div>

            <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-mono text-[10px] text-tertiary uppercase">Stok Menipis / Kritis</span>
                        @if($lowStockAtkCount + $outOfStockAtkCount > 0)
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                        @endif
                    </div>
                    <div class="font-headline text-2xl font-bold {{ ($lowStockAtkCount + $outOfStockAtkCount) > 0 ? 'text-rose-400' : 'text-on-surface' }} mt-1">
                        {{ $lowStockAtkCount + $outOfStockAtkCount }} Item
                    </div>
                    <span class="text-[10px] text-slate-400 font-mono">{{ $outOfStockAtkCount }} habis, {{ $lowStockAtkCount }} mendekati limit</span>
                </div>
                <div class="p-2.5 rounded-lg {{ ($lowStockAtkCount + $outOfStockAtkCount) > 0 ? 'bg-rose-950/40 text-rose-400 border border-rose-500/30' : 'bg-surface-container text-tertiary' }}">
                    <span class="material-symbols-outlined text-xl">warning</span>
                </div>
            </div>

            <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
                <div>
                    <span class="font-mono text-[10px] text-tertiary uppercase">Pemakaian Bulan Ini</span>
                    <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ number_format($totalAtkUsageThisMonth) }} Item</div>
                    <span class="text-[10px] text-slate-400 font-mono">Bulan {{ now()->translatedFormat('F Y') }}</span>
                </div>
                <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                    <span class="material-symbols-outlined text-xl">trending_down</span>
                </div>
            </div>
        </div>

        <!-- Sub-tab Selector ATK (Katalog Stok vs Log Pemakaian) -->
        <div class="flex items-center justify-between gap-4 p-2 bg-surface-container-low rounded-xl border border-[#282a2e]/50">
            <div class="flex items-center gap-1.5 p-1 bg-surface-container rounded-lg border border-[#333539] text-xs font-mono">
                <button type="button" 
                        @click="atkSubTab = 'catalog'"
                        :class="atkSubTab === 'catalog' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-tertiary hover:text-on-surface'"
                        class="px-3.5 py-1.5 rounded-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">inventory_2</span>
                    <span>Katalog Stok ATK Tersedia ({{ $supplies->total() }})</span>
                </button>
                <button type="button" 
                        @click="atkSubTab = 'usages'"
                        :class="atkSubTab === 'usages' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-tertiary hover:text-on-surface'"
                        class="px-3.5 py-1.5 rounded-md transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">history</span>
                    <span>Riwayat Pemakaian & Distribusi ({{ $usages->total() }})</span>
                </button>
            </div>

            @if(auth()->user()->canControlGA())
                <div class="flex items-center gap-2">
                    <button type="button" @click="openRecordUsage()" class="px-3 py-1.5 rounded-lg bg-emerald-600/20 border border-emerald-500/30 text-emerald-400 hover:bg-emerald-600/30 text-xs font-mono font-semibold flex items-center gap-1 transition-all">
                        <span class="material-symbols-outlined text-sm">outgoing_mail</span>
                        <span>Catat Pengambilan ATK</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- SUB-TAB 1: KATALOG STOK ATK TERSEDIA -->
        <div x-show="atkSubTab === 'catalog'" class="space-y-4">
            
            <!-- Filter & Search ATK -->
            <form action="{{ route('ga.index') }}" method="GET" class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 text-xs">
                <input type="hidden" name="tab" value="supplies">
                <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-sm">search</span>
                        <input type="text" name="atk_search" value="{{ $atkSearch }}" placeholder="Cari nama barang, kode ATK, atau lokasi rak..." class="w-full bg-surface-container border border-[#333539] rounded-lg pl-9 pr-3 py-2 text-xs text-on-surface focus:outline-none"/>
                    </div>

                    <select name="atk_category" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                        <option value="">-- Semua Kategori ATK --</option>
                        <option value="Kertas & Buku" {{ $atkCategory === 'Kertas & Buku' ? 'selected' : '' }}>Kertas & Buku</option>
                        <option value="Alat Tulis & Koreksi" {{ $atkCategory === 'Alat Tulis & Koreksi' ? 'selected' : '' }}>Alat Tulis & Koreksi</option>
                        <option value="Filing & Dokumen" {{ $atkCategory === 'Filing & Dokumen' ? 'selected' : '' }}>Filing & Dokumen</option>
                        <option value="Tinta & Printer" {{ $atkCategory === 'Tinta & Printer' ? 'selected' : '' }}>Tinta & Printer</option>
                        <option value="Perekat & Pengikat" {{ $atkCategory === 'Perekat & Pengikat' ? 'selected' : '' }}>Perekat & Pengikat</option>
                        <option value="Lainnya" {{ $atkCategory === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>

                    <select name="atk_status" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                        <option value="">-- Semua Status Stok --</option>
                        <option value="available" {{ $atkStatus === 'available' ? 'selected' : '' }}>Stok Aman</option>
                        <option value="low_stock" {{ $atkStatus === 'low_stock' ? 'selected' : '' }}>Stok Menipis (Perlu Restock)</option>
                        <option value="out_of_stock" {{ $atkStatus === 'out_of_stock' ? 'selected' : '' }}>Stok Habis (Kosong)</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold flex items-center gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Filter</span>
                    </button>
                    @if($atkSearch || $atkCategory || $atkStatus)
                        <a href="{{ route('ga.index', ['tab' => 'supplies']) }}" class="px-3 py-2 bg-surface-container border border-[#333539] hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg">Reset</a>
                    @endif
                </div>
            </form>

            <!-- Tabel Stok ATK -->
            <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                                <th class="px-5 py-3.5">Kode & Nama ATK</th>
                                <th class="px-5 py-3.5">Kategori</th>
                                <th class="px-5 py-3.5 text-center">Stok Tersedia</th>
                                <th class="px-5 py-3.5">Batas Min. Stok</th>
                                <th class="px-5 py-3.5">Lokasi Penyimpanan</th>
                                <th class="px-5 py-3.5 text-right">Aksi Manajemen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/40">
                            @forelse($supplies as $sup)
                                <tr class="hover:bg-surface-container/20 transition-colors">
                                    <td class="px-5 py-4">
                                        <div class="flex items-start gap-2.5">
                                            <div class="p-2 rounded-lg bg-surface-container text-slate-300 shrink-0 mt-0.5">
                                                <span class="material-symbols-outlined text-base">
                                                    @if(str_contains(strtolower($sup->category), 'kertas'))
                                                        description
                                                    @elseif(str_contains(strtolower($sup->category), 'tulis'))
                                                        edit
                                                    @elseif(str_contains(strtolower($sup->category), 'filing'))
                                                        folder
                                                    @elseif(str_contains(strtolower($sup->category), 'tinta') || str_contains(strtolower($sup->category), 'printer'))
                                                        print
                                                    @else
                                                        inventory_2
                                                    @endif
                                                </span>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-on-surface text-sm">{{ $sup->name }}</div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="font-mono text-[11px] text-emerald-400 font-bold">{{ $sup->item_code }}</span>
                                                    @if($sup->notes)
                                                        <span class="text-slate-400 text-[11px] truncate max-w-xs" title="{{ $sup->notes }}">• {{ $sup->notes }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2 py-0.5 rounded bg-surface-container text-slate-300 font-mono text-[11px] border border-[#333539]">
                                            {{ $sup->category }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="inline-flex flex-col items-center">
                                            <span class="px-2.5 py-1 rounded-full font-mono text-xs font-bold inline-flex items-center gap-1.5
                                                @if($sup->isOutOfStock())
                                                    bg-rose-500/20 text-rose-400 border border-rose-500/30
                                                @elseif($sup->isLowStock())
                                                    bg-amber-500/20 text-amber-300 border border-amber-500/30
                                                @else
                                                    bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                                                @endif">
                                                <span class="w-1.5 h-1.5 rounded-full 
                                                    @if($sup->isOutOfStock()) bg-rose-400
                                                    @elseif($sup->isLowStock()) bg-amber-400
                                                    @else bg-emerald-400 @endif">
                                                </span>
                                                <span>{{ $sup->stock_qty }} {{ $sup->unit }}</span>
                                            </span>
                                            <span class="text-[9px] font-mono mt-0.5 
                                                @if($sup->isOutOfStock()) text-rose-400 font-bold
                                                @elseif($sup->isLowStock()) text-amber-400 font-semibold
                                                @else text-slate-400 @endif">
                                                {{ $sup->status_label }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 font-mono text-slate-400">
                                        {{ $sup->min_stock }} {{ $sup->unit }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-1.5 text-slate-300">
                                            <span class="material-symbols-outlined text-xs text-tertiary">shelves</span>
                                            <span>{{ $sup->location }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            @if(auth()->user()->canControlGA())
                                                <!-- Tombol Cepat Catat Pakai -->
                                                <button type="button" 
                                                        @click="openRecordUsage('{{ $sup->id }}')" 
                                                        :disabled="{{ $sup->isOutOfStock() ? 'true' : 'false' }}"
                                                        class="px-2 py-1 rounded bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 font-mono text-[10px] flex items-center gap-1 transition-all disabled:opacity-40 disabled:cursor-not-allowed"
                                                        title="Catat pengambilan ATK ini">
                                                    <span class="material-symbols-outlined text-xs">outgoing_mail</span>
                                                    <span>Pakai</span>
                                                </button>

                                                <!-- Tombol Restock -->
                                                <button type="button" 
                                                        @click="openRestock({{ json_encode($sup) }})" 
                                                        class="px-2 py-1 rounded bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/30 font-mono text-[10px] flex items-center gap-1 transition-all"
                                                        title="Tambah kuantitas stok masuk">
                                                    <span class="material-symbols-outlined text-xs">add_shopping_cart</span>
                                                    <span>+ Stok</span>
                                                </button>

                                                <!-- Tombol Edit -->
                                                <button type="button" 
                                                        @click="openEditSupply({{ json_encode($sup) }})" 
                                                        class="p-1 rounded bg-surface-container hover:bg-surface-bright text-tertiary hover:text-white border border-[#333539]"
                                                        title="Edit Data ATK">
                                                    <span class="material-symbols-outlined text-xs">edit</span>
                                                </button>

                                                <!-- Tombol Hapus -->
                                                <form action="{{ route('ga.supplies.destroy', $sup->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus barang ATK ini dari master data?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1 rounded bg-surface-container hover:bg-rose-950/60 text-tertiary hover:text-rose-400 border border-[#333539]" title="Hapus Barang">
                                                        <span class="material-symbols-outlined text-xs">delete</span>
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-tertiary font-mono text-[10px]">Tersedia</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-tertiary font-mono">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <span class="material-symbols-outlined text-4xl text-slate-600">inventory_2</span>
                                            <p class="text-sm">Tidak ada barang ATK yang sesuai kriteria pencarian.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($supplies->hasPages())
                    <div class="p-4 border-t border-[#282a2e]/40">{{ $supplies->links() }}</div>
                @endif
            </div>
        </div>

        <!-- SUB-TAB 2: RIWAYAT PEMAKAIAN / DISTRIBUSI ATK KANTOR -->
        <div x-show="atkSubTab === 'usages'" class="space-y-4">
            
            <!-- Filter & Search Riwayat Pemakaian -->
            <form action="{{ route('ga.index') }}" method="GET" class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 text-xs">
                <input type="hidden" name="tab" value="supplies">
                <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
                    <div class="relative flex-1 min-w-[200px]">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-sm">search</span>
                        <input type="text" name="usage_search" value="{{ $usageSearch }}" placeholder="Cari nama karyawan, nama barang, atau keperluan..." class="w-full bg-surface-container border border-[#333539] rounded-lg pl-9 pr-3 py-2 text-xs text-on-surface focus:outline-none"/>
                    </div>

                    <select name="usage_dept" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                        <option value="">-- Semua Departemen --</option>
                        <option value="HR" {{ $usageDept === 'HR' ? 'selected' : '' }}>HR</option>
                        <option value="Finance" {{ $usageDept === 'Finance' ? 'selected' : '' }}>Finance</option>
                        <option value="Bisnis Development" {{ $usageDept === 'Bisnis Development' ? 'selected' : '' }}>Bisnis Development</option>
                        <option value="Executive" {{ $usageDept === 'Executive' ? 'selected' : '' }}>Executive / Direksi</option>
                        <option value="Resepsionis" {{ $usageDept === 'Resepsionis' ? 'selected' : '' }}>Resepsionis</option>
                        <option value="Trainer" {{ $usageDept === 'Trainer' ? 'selected' : '' }}>Trainer</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold flex items-center gap-1.5 shadow-sm">
                        <span class="material-symbols-outlined text-sm">filter_alt</span>
                        <span>Filter Log</span>
                    </button>
                    @if($usageSearch || $usageDept)
                        <a href="{{ route('ga.index', ['tab' => 'supplies']) }}" class="px-3 py-2 bg-surface-container border border-[#333539] hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg">Reset</a>
                    @endif
                </div>
            </form>

            <!-- Tabel Log Pemakaian -->
            <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                                <th class="px-5 py-3.5">Tanggal</th>
                                <th class="px-5 py-3.5">Karyawan & Departemen</th>
                                <th class="px-5 py-3.5">Barang ATK Terpakai</th>
                                <th class="px-5 py-3.5">Jumlah (Qty)</th>
                                <th class="px-5 py-3.5">Tujuan / Keperluan</th>
                                <th class="px-5 py-3.5">Dicatat Oleh (GA)</th>
                                @if(auth()->user()->canControlGA())
                                    <th class="px-5 py-3.5 text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/40">
                            @forelse($usages as $u)
                                <tr class="hover:bg-surface-container/20 transition-colors">
                                    <td class="px-5 py-4 font-mono text-slate-300 whitespace-nowrap">
                                        <div class="font-bold text-white">{{ $u->usage_date ? $u->usage_date->translatedFormat('d M Y') : '-' }}</div>
                                        <div class="text-[10px] text-slate-500">{{ $u->created_at->format('H:i') }} WIB</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $u->employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-7 h-7 rounded-full object-cover border border-slate-700"/>
                                            <div>
                                                <span class="text-on-surface font-semibold block text-sm">{{ $u->employee->name ?? 'Karyawan' }}</span>
                                                <div class="flex items-center gap-1.5 mt-0.5">
                                                    <span class="font-mono text-[10px] text-slate-400">{{ $u->employee->nip ?? '' }}</span>
                                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-mono font-semibold
                                                        @if($u->department === 'HR') bg-blue-500/20 text-blue-300 border border-blue-500/30
                                                        @elseif($u->department === 'Finance') bg-emerald-500/20 text-emerald-300 border border-emerald-500/30
                                                        @elseif($u->department === 'Bisnis Development') bg-amber-500/20 text-amber-300 border border-amber-500/30
                                                        @elseif($u->department === 'Trainer') bg-purple-500/20 text-purple-300 border border-purple-500/30
                                                        @elseif($u->department === 'Resepsionis') bg-teal-500/20 text-teal-300 border border-teal-500/30
                                                        @else bg-slate-500/20 text-slate-300 border border-slate-500/30 @endif">
                                                        {{ $u->department }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-semibold text-white">{{ $u->officeSupply->name ?? 'Barang Dihapus' }}</div>
                                        <div class="font-mono text-[10px] text-emerald-400 font-bold">{{ $u->officeSupply->item_code ?? '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="px-2.5 py-1 rounded-md bg-slate-900 border border-slate-700 font-mono font-bold text-white text-xs">
                                            {{ $u->quantity }} {{ $u->officeSupply->unit ?? 'Unit' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="text-slate-200 text-xs">{{ $u->purpose }}</div>
                                        @if($u->notes)
                                            <div class="text-[10px] text-slate-500 italic mt-0.5">• {{ $u->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 font-mono text-xs text-slate-400">
                                        <div class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-xs text-emerald-400">shield_person</span>
                                            <span>{{ $u->recorder->name ?? 'Admin GA' }}</span>
                                        </div>
                                    </td>
                                    @if(auth()->user()->canControlGA())
                                        <td class="px-5 py-4 text-right">
                                            <form action="{{ route('ga.supplies.destroyUsage', $u->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan catatan pemakaian ini? Stok sebanyak {{ $u->quantity }} {{ $u->officeSupply->unit ?? 'unit' }} akan otomatis dikembalikan ke gudang.');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1.5 rounded bg-surface-container hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 border border-[#333539] transition-colors" title="Batalkan & Kembalikan Stok">
                                                    <span class="material-symbols-outlined text-sm">undo</span>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center text-tertiary font-mono">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <span class="material-symbols-outlined text-4xl text-slate-600">history_toggle_off</span>
                                            <p class="text-sm">Belum ada riwayat pemakaian ATK yang tercatat.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($usages->hasPages())
                    <div class="p-4 border-t border-[#282a2e]/40">{{ $usages->links() }}</div>
                @endif
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 2: PENGAJUAN PENAMBAHAN & PERBAIKAN ASET (APPROVAL FINANCE)-->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'requests'" class="space-y-4">
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-[#282a2e]/50 flex items-center justify-between bg-surface-container/30">
                <div>
                    <h3 class="font-headline font-semibold text-sm text-on-surface">Daftar Pengajuan Penambahan / Perbaikan Inventaris</h3>
                    <p class="text-xs text-tertiary font-mono">Setiap pengajuan diajukan oleh GA dan memerlukan persetujuan otorisasi anggaran dari Tim Finance atau Direktur</p>
                </div>

                @if(auth()->user()->canApproveInventoryRequests())
                    <span class="px-2.5 py-1 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-xs flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">verified_user</span>
                        <span>Anda Memiliki Hak Approval</span>
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">Jenis Pengajuan</th>
                            <th class="px-5 py-3.5">Barang / Deskripsi</th>
                            <th class="px-5 py-3.5">Estimasi Biaya & Qty</th>
                            <th class="px-5 py-3.5">Alasan / Kebutuhan</th>
                            <th class="px-5 py-3.5">Pengaju (GA)</th>
                            <th class="px-5 py-3.5">Status Approval</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/40">
                        @forelse($requests as $req)
                            <tr class="hover:bg-surface-container/20 transition-colors">
                                <td class="px-5 py-4">
                                    @if($req->request_type === 'penambahan')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase text-[10px]">PENAMBAHAN</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-purple-950/60 text-purple-400 border border-purple-500/20 font-bold uppercase text-[10px]">PERBAIKAN</span>
                                    @endif
                                    <div class="mt-1 font-mono text-[10px] text-tertiary">{{ $req->created_at->format('d M Y') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-semibold text-on-surface block">{{ $req->item_name }}</span>
                                    <span class="text-tertiary text-[10px] font-mono">{{ $req->category }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono">
                                    <div class="font-bold text-emerald-400">Rp {{ number_format($req->estimated_cost, 0, ',', '.') }}</div>
                                    <div class="text-[10px] text-tertiary">{{ $req->quantity }} Unit</div>
                                </td>
                                <td class="px-5 py-4 max-w-xs">
                                    <p class="truncate text-on-surface" title="{{ $req->reason }}">{{ $req->reason }}</p>
                                    @if($req->urgency === 'urgent')
                                        <span class="inline-block mt-1 px-1.5 py-0.2 bg-red-950/80 text-red-400 border border-red-500/30 text-[9px] font-bold rounded">URGENT</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        <img src="{{ $req->requester->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-5 h-5 rounded-full object-cover"/>
                                        <span class="text-on-surface text-xs">{{ $req->requester->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono">
                                    @if($req->status === 'approved')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase text-[10px]">DISETUJUI</span>
                                        @if($req->approver)
                                            <div class="text-[9px] text-tertiary mt-1">oleh {{ $req->approver->name }}</div>
                                        @endif
                                    @elseif($req->status === 'rejected')
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/20 font-bold uppercase text-[10px]">DITOLAK</span>
                                        @if($req->approval_notes)
                                            <div class="text-[9px] text-red-300 mt-1 max-w-[150px] truncate" title="{{ $req->approval_notes }}">{{ $req->approval_notes }}</div>
                                        @endif
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 font-bold uppercase text-[10px]">PENDING</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if($req->status === 'pending' && auth()->user()->canApproveInventoryRequests())
                                        @php
                                            $currEmpId = auth()->user()->employee_id ?? auth()->user()->employee?->id;
                                            $isSelf = $currEmpId && (int)$req->requested_by === (int)$currEmpId;
                                        @endphp
                                        @if(!$isSelf)
                                            <div class="inline-flex items-center gap-1">
                                                <form action="{{ route('ga.updateRequestStatus', $req->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-mono text-[10px] font-bold shadow-sm" title="Setujui Pengajuan">Setujui</button>
                                                </form>
                                                <button type="button" @click="selectedRequest = {{ json_encode($req) }}; detailRequestModal = true;" class="px-2 py-1 bg-red-600/80 hover:bg-red-700 text-white rounded font-mono text-[10px] font-bold shadow-sm" title="Tolak Pengajuan">Tolak</button>
                                            </div>
                                        @else
                                            <span class="text-[10px] font-mono text-tertiary italic">Pengajuan Anda</span>
                                        @endif
                                    @else
                                        <span class="font-mono text-[10px] text-tertiary">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">Belum ada riwayat pengajuan penambahan atau perbaikan inventaris.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($requests->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- TAB 3: KATALOG ASET & INVENTARIS KANTOR                        -->
    <!-- ============================================================== -->
    <div x-show="activeTab === 'inventory'" class="space-y-4">
        <!-- Filter Form Inventaris -->
        <form action="{{ route('ga.index') }}" method="GET" class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 text-xs">
            <input type="hidden" name="tab" value="inventory">
            <div class="flex flex-wrap items-center gap-3">
                <select name="category" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">-- Semua Kategori Aset --</option>
                    <option value="Perangkat IT / Laptop" {{ $category === 'Perangkat IT / Laptop' ? 'selected' : '' }}>Perangkat IT / Laptop</option>
                    <option value="Furniture & Meja" {{ $category === 'Furniture & Meja' ? 'selected' : '' }}>Furniture & Meja</option>
                    <option value="Fasilitas Gedung" {{ $category === 'Fasilitas Gedung' ? 'selected' : '' }}>Fasilitas Gedung</option>
                    <option value="ATK & Stationary" {{ $category === 'ATK & Stationary' ? 'selected' : '' }}>ATK & Stationary</option>
                </select>

                <select name="condition" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="good" {{ $condition === 'good' ? 'selected' : '' }}>Baik / Berfungsi</option>
                    <option value="maintenance" {{ $condition === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="damaged" {{ $condition === 'damaged' ? 'selected' : '' }}>Rusak</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold flex items-center gap-1 shadow-sm">
                    <span class="material-symbols-outlined text-sm">filter_alt</span>
                    <span>Filter</span>
                </button>
                @if($category || $condition)
                    <a href="{{ route('ga.index', ['tab' => 'inventory']) }}" class="px-3 py-1.5 bg-surface-container border border-[#333539] hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg">Reset</a>
                @endif
            </div>
        </form>

        <!-- Tabel Inventaris Aset -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">Kode & Nama Aset</th>
                            <th class="px-5 py-3.5">Kategori</th>
                            <th class="px-5 py-3.5">Lokasi</th>
                            <th class="px-5 py-3.5">Kondisi</th>
                            <th class="px-5 py-3.5">Penanggung Jawab</th>
                            <th class="px-5 py-3.5 text-right">Update Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/40">
                        @forelse($items as $item)
                            <tr class="hover:bg-surface-container/20 transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-[11px] text-amber-400 font-bold block">{{ $item->item_code }}</span>
                                    <span class="font-semibold text-on-surface text-sm">{{ $item->name }}</span>
                                    <span class="text-[10px] text-tertiary font-mono block">Qty: {{ $item->stock_qty }} Unit</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary font-mono text-[10px]">
                                        {{ $item->category }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-tertiary">{{ $item->location }}</td>
                                <td class="px-5 py-4 font-mono">
                                    @if($item->condition === 'good')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase">BAIK</span>
                                    @elseif($item->condition === 'maintenance')
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 font-bold uppercase">MAINTENANCE</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/20 font-bold uppercase">RUSAK</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-mono text-xs">
                                    @if($item->assignedEmployee)
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $item->assignedEmployee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-6 h-6 rounded-full object-cover"/>
                                            <div>
                                                <span class="text-on-surface font-semibold block">{{ $item->assignedEmployee->name }}</span>
                                                <span class="text-[10px] text-tertiary">{{ $item->assignedEmployee->department }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-tertiary italic">Gudang / Shared Asset</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form action="{{ route('ga.update', $item->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="condition" class="bg-surface-container-high border border-[#333539] text-[10px] text-on-surface rounded px-2 py-1 focus:outline-none">
                                            <option value="good" {{ $item->condition === 'good' ? 'selected' : '' }}>Baik</option>
                                            <option value="maintenance" {{ $item->condition === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                            <option value="damaged" {{ $item->condition === 'damaged' ? 'selected' : '' }}>Rusak</option>
                                        </select>
                                        <button type="submit" class="p-1 rounded bg-surface-container-high hover:bg-surface-bright text-tertiary hover:text-on-surface transition-colors" title="Simpan Status">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-tertiary font-mono">Belum ada inventaris yang terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">{{ $items->links() }}</div>
            @endif
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: CATAT PEMAKAIAN ATK KANTOR                            -->
    <!-- ============================================================== -->
    <div x-cloak x-show="openUsageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative" @click.away="openUsageModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">outgoing_mail</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Catat Pemakaian ATK Kantor</h3>
                        <p class="font-mono text-[11px] text-slate-400">Pengambilan barang habis pakai untuk karyawan / operasional</p>
                    </div>
                </div>
                <button type="button" @click="openUsageModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('ga.supplies.recordUsage') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                
                <!-- Pilihan Barang ATK -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Pilih Barang ATK <span class="text-rose-400">*</span></label>
                    <select name="office_supply_id" x-model="selectedSupplyForUsage" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="">-- Pilih Barang ATK dari Gudang --</option>
                        @foreach($allSupplies as $sItem)
                            <option value="{{ $sItem->id }}">
                                {{ $sItem->item_code }} - {{ $sItem->name }} (Sisa Stok: {{ $sItem->stock_qty }} {{ $sItem->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Pilihan Karyawan Penerima -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Karyawan Penerima ATK <span class="text-rose-400">*</span></label>
                    <select name="employee_id" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                        <option value="">-- Pilih Karyawan --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">
                                {{ $emp->name }} ({{ $emp->nip }}) - Dept: {{ $emp->department }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Jumlah & Tanggal -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Jumlah Diambil (Qty) <span class="text-rose-400">*</span></label>
                        <input type="number" name="quantity" min="1" value="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono font-bold"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Tanggal Pemakaian <span class="text-rose-400">*</span></label>
                        <input type="date" name="usage_date" value="{{ date('Y-m-d') }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono"/>
                    </div>
                </div>

                <!-- Keperluan Pemakaian -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Keperluan / Tujuan Pemakaian <span class="text-rose-400">*</span></label>
                    <input type="text" name="purpose" required placeholder="contoh: Cetak sertifikat paklaring, operasional meja tamu, dll." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" placeholder="contoh: Diserahkan langsung di loket GA Lt. 1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 text-[11px] font-mono text-slate-400 flex items-start gap-2">
                    <span class="material-symbols-outlined text-emerald-400 text-sm mt-0.5">info</span>
                    <span>Stok barang akan otomatis berkurang sesuai jumlah yang dicatat. Riwayat tercatat atas nama akun Anda.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openUsageModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-semibold rounded-xl shadow-md">Simpan Pemakaian</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: TAMBAH MASTER BARANG ATK BARU                         -->
    <!-- ============================================================== -->
    <div x-cloak x-show="openSupplyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative" @click.away="openSupplyModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">add_box</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Tambah Barang Master ATK Baru</h3>
                        <p class="font-mono text-[11px] text-slate-400">Pendaftaran item persediaan barang habis pakai kantor</p>
                    </div>
                </div>
                <button type="button" @click="openSupplyModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('ga.supplies.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Kode ATK <span class="text-rose-400">*</span></label>
                        <input type="text" name="item_code" required placeholder="contoh: ATK-013" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono font-bold uppercase"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Kategori <span class="text-rose-400">*</span></label>
                        <select name="category" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                            <option value="Kertas & Buku">Kertas & Buku</option>
                            <option value="Alat Tulis & Koreksi">Alat Tulis & Koreksi</option>
                            <option value="Filing & Dokumen">Filing & Dokumen</option>
                            <option value="Tinta & Printer">Tinta & Printer</option>
                            <option value="Perekat & Pengikat">Perekat & Pengikat</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Nama Barang ATK <span class="text-rose-400">*</span></label>
                    <input type="text" name="name" required placeholder="contoh: Kertas HVS A4 80gr PaperOne / Pulpen Gel 0.5mm" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Satuan <span class="text-rose-400">*</span></label>
                        <select name="unit" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
                            <option value="Rim">Rim</option>
                            <option value="Pcs">Pcs</option>
                            <option value="Box">Box</option>
                            <option value="Pack">Pack</option>
                            <option value="Lusin">Lusin</option>
                            <option value="Rol">Rol</option>
                            <option value="Set">Set</option>
                            <option value="Botol">Botol</option>
                            <option value="Pad">Pad</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Stok Awal <span class="text-rose-400">*</span></label>
                        <input type="number" name="stock_qty" min="0" value="10" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono font-bold"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Min. Limit Stok <span class="text-rose-400">*</span></label>
                        <input type="number" name="min_stock" min="0" value="5" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500 font-mono" title="Alert muncul jika stok di bawah angka ini"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Lokasi Lemari / Rak <span class="text-rose-400">*</span></label>
                    <input type="text" name="location" value="Lemari ATK Lt. 1 - Rak A1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1">Catatan / Deskripsi (Opsional)</label>
                    <textarea name="notes" rows="2" placeholder="Merk, spesifikasi ukuran, atau petunjuk penggunaan..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openSupplyModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-md">Simpan ATK</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: RESTOCK / TAMBAH STOK MASUK ATK                       -->
    <!-- ============================================================== -->
    <div x-cloak x-show="openRestockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-md p-6 shadow-2xl relative" @click.away="openRestockModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Restock / Tambah Stok ATK</h3>
                        <p class="font-mono text-[11px] text-slate-400">Penerimaan pasokan barang baru dari pengadaan</p>
                    </div>
                </div>
                <button type="button" @click="openRestockModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/ga/supplies') }}/' + (selectedSupplyForRestock ? selectedSupplyForRestock.id : 0) + '/restock'" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800 space-y-1 font-mono text-xs">
                    <div class="text-slate-400 text-[10px] uppercase tracking-wider">Barang yang Direstock:</div>
                    <div class="font-bold text-white text-sm" x-text="selectedSupplyForRestock?.name"></div>
                    <div class="flex justify-between items-center text-slate-300 pt-1 border-t border-slate-800 text-[11px]">
                        <span>Kode ATK: <strong class="text-emerald-400" x-text="selectedSupplyForRestock?.item_code"></strong></span>
                        <span>Stok Saat Ini: <strong class="text-amber-400" x-text="(selectedSupplyForRestock?.stock_qty || 0) + ' ' + (selectedSupplyForRestock?.unit || '')"></strong></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Jumlah Stok Masuk (Qty) <span class="text-rose-400">*</span></label>
                    <input type="number" name="add_qty" min="1" value="10" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 font-mono font-bold text-base"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1">Keterangan / Sumber Pengadaan</label>
                    <input type="text" name="notes" placeholder="contoh: Pembelian Toko ATK Surya / Pengadaan Triwulan 4" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openRestockModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white text-xs font-semibold rounded-xl shadow-md">Simpan Restock</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 4: EDIT MASTER ATK                                       -->
    <!-- ============================================================== -->
    <div x-cloak x-show="editSupplyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative" @click.away="editSupplyModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">edit</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Edit Master Data ATK</h3>
                        <p class="font-mono text-[11px] text-slate-400" x-text="'Kode: ' + (editingSupply.item_code || '')"></p>
                    </div>
                </div>
                <button type="button" @click="editSupplyModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/ga/supplies') }}/' + editingSupply.id" method="POST" class="mt-4 space-y-3.5">
                @csrf
                @method('PATCH')
                
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Nama Barang ATK</label>
                    <input type="text" name="name" x-model="editingSupply.name" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Kategori</label>
                        <select name="category" x-model="editingSupply.category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="Kertas & Buku">Kertas & Buku</option>
                            <option value="Alat Tulis & Koreksi">Alat Tulis & Koreksi</option>
                            <option value="Filing & Dokumen">Filing & Dokumen</option>
                            <option value="Tinta & Printer">Tinta & Printer</option>
                            <option value="Perekat & Pengikat">Perekat & Pengikat</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Satuan</label>
                        <select name="unit" x-model="editingSupply.unit" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                            <option value="Rim">Rim</option>
                            <option value="Pcs">Pcs</option>
                            <option value="Box">Box</option>
                            <option value="Pack">Pack</option>
                            <option value="Lusin">Lusin</option>
                            <option value="Rol">Rol</option>
                            <option value="Set">Set</option>
                            <option value="Botol">Botol</option>
                            <option value="Pad">Pad</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Batas Min. Limit Stok</label>
                        <input type="number" name="min_stock" min="0" x-model="editingSupply.min_stock" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Lokasi Lemari / Rak</label>
                        <input type="text" name="location" x-model="editingSupply.location" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1">Catatan Deskripsi</label>
                    <textarea name="notes" rows="2" x-model="editingSupply.notes" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="editSupplyModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 5: DETAIL PENGAJUAN & FORM PENOLAKAN ASET                -->
    <!-- ============================================================== -->
    <div x-cloak x-show="detailRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-rose-500/30 w-full max-w-md p-6 shadow-2xl relative" @click.away="detailRequestModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-400 text-xl">cancel</span>
                    <h3 class="font-headline font-bold text-base text-white">Tolak Pengajuan Inventaris</h3>
                </div>
                <button type="button" @click="detailRequestModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/ga/request') }}/' + (selectedRequest ? selectedRequest.id : 0) + '/status'" method="POST" class="mt-4 space-y-3.5">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">

                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 font-mono text-xs space-y-1">
                    <div class="text-slate-400">Barang: <strong class="text-white" x-text="selectedRequest?.item_name"></strong></div>
                    <div class="text-slate-400">Estimasi Biaya: <strong class="text-emerald-400" x-text="'Rp ' + Number(selectedRequest?.estimated_cost || 0).toLocaleString('id-ID')"></strong></div>
                    <div class="text-slate-400">Alasan: <span class="text-slate-300" x-text="selectedRequest?.reason"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Alasan Penolakan <span class="text-rose-400">*</span></label>
                    <textarea name="approval_notes" rows="3" required placeholder="Tuliskan alasan mengapa pengajuan anggaran inventaris ini ditolak..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="detailRequestModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl shadow-md">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 6: FORM PENGAJUAN INVENTARIS ASET BARU                   -->
    <!-- ============================================================== -->
    <div x-cloak x-show="openRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative" @click.away="openRequestModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">post_add</span>
                    </div>
                    <h3 class="font-headline font-bold text-base text-white">Form Pengajuan Aset / Perbaikan</h3>
                </div>
                <button type="button" @click="openRequestModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('ga.storeRequest') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Jenis Pengajuan</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs"
                               :class="requestType === 'penambahan' ? 'bg-amber-950/40 border-amber-500/50 text-amber-300 font-semibold' : 'bg-slate-900 border-slate-700 text-slate-400'">
                            <input type="radio" name="request_type" value="penambahan" x-model="requestType" class="hidden">
                            <span class="material-symbols-outlined text-base">add_circle</span>
                            <span>Penambahan Baru</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-xl border cursor-pointer text-xs"
                               :class="requestType === 'perbaikan' ? 'bg-purple-950/40 border-purple-500/50 text-purple-300 font-semibold' : 'bg-slate-900 border-slate-700 text-slate-400'">
                            <input type="radio" name="request_type" value="perbaikan" x-model="requestType" class="hidden">
                            <span class="material-symbols-outlined text-base">build</span>
                            <span>Perbaikan Aset Rusak</span>
                        </label>
                    </div>
                </div>

                <div x-show="requestType === 'perbaikan'" class="space-y-1">
                    <label class="block text-xs font-mono text-slate-300">Pilih Aset yang Rusak</label>
                    <select name="inventory_item_id" x-model="selectedInventoryItem" @change="onItemChange($event.target.value)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                        <option value="">-- Pilih dari Daftar Aset --</option>
                        @foreach($items as $i)
                            <option value="{{ $i->id }}">{{ $i->item_code }} - {{ $i->name }} ({{ $i->location }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Nama Barang / Deskripsi</label>
                    <input type="text" id="item_name_input" name="item_name" required placeholder="contoh: Kursi Kerja Ergonomis / Laptop Developer" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Kategori</label>
                        <select id="item_category_input" name="category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                            <option value="Perangkat IT / Laptop">Perangkat IT / Laptop</option>
                            <option value="Furniture & Meja">Furniture & Meja</option>
                            <option value="Fasilitas Gedung">Fasilitas Gedung</option>
                            <option value="ATK & Stationary">ATK & Stationary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Tingkat Urgensi</label>
                        <select name="urgency" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                            <option value="normal">Normal (Standar)</option>
                            <option value="urgent">Urgent (Mendesak)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Jumlah Unit (Qty)</label>
                        <input type="number" name="quantity" min="1" value="1" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 font-mono font-bold"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Estimasi Biaya (Rp)</label>
                        <input type="number" name="estimated_cost" min="0" step="50000" placeholder="contoh: 2500000" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 font-mono font-bold"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Alasan Kebutuhan & Spesifikasi</label>
                    <textarea name="reason" rows="3" required placeholder="Jelaskan kendala kerusakan atau urgensi penambahan aset baru..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div class="p-3 bg-slate-900/80 rounded-xl border border-slate-800 flex items-center gap-2 text-xs text-slate-400 font-mono">
                    <span class="material-symbols-outlined text-amber-400 text-sm">info</span>
                    <span>Pengajuan ini akan diteruskan ke Tim Finance / Direktur untuk persetujuan anggaran.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openRequestModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-xl shadow-md">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
