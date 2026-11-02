@extends('layouts.app')

@section('title', 'General Affairs - Inventaris & Pengajuan Fasilitas Kantor')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openAssetModal: false, 
    openRequestModal: false, 
    detailRequestModal: false,
    selectedRequest: null,
    activeTab: '{{ $activeTab ?? 'inventory' }}',
    requestType: 'penambahan',
    selectedInventoryItem: '',
    itemsList: {{ json_encode($items->items()) }},
    onItemChange(itemId) {
        const found = this.itemsList.find(i => i.id == itemId);
        if (found) {
            document.getElementById('item_name_input').value = found.name;
            document.getElementById('item_category_input').value = found.category;
        }
    }
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Department Feature • General Affairs (GA) & Finance</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Manajemen Aset & Pengajuan Inventaris</h1>
            <p class="font-mono text-xs text-tertiary">Pencatatan aset kantor, pengajuan penambahan/perbaikan inventaris, dan persetujuan (approval) Tim Finance/Direktur</p>
        </div>

        <div class="flex items-center gap-2.5">
            <!-- Tombol Pengajuan Penambahan/Perbaikan (Untuk GA & Staf) -->
            <button @click="openRequestModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">post_add</span>
                <span>Ajukan Inventaris / Perbaikan</span>
            </button>

            <!-- Tombol Tambah Langsung ke Katalog (Khusus GA, Manager HR, & Direktur) -->
            @if(auth()->user()->canControlGA())
                <button @click="openAssetModal = true" class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-high hover:bg-surface-bright text-on-surface border border-[#333539] rounded-lg text-xs font-semibold transition-all shadow-sm">
                    <span class="material-symbols-outlined text-base text-amber-400">add_box</span>
                    <span>Katalog Aset</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Metric Cards -->
    @php
        $damagedRatio = $totalItems > 0 ? round(($maintenanceCount / $totalItems) * 100, 1) : 0;
        $healthyRatio = 100 - $damagedRatio;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Total Unit Aset</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        {{ $healthyRatio }}% Layak
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalItems }} Unit</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">inventory_2</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Dalam Perbaikan / Rusak</span>
                    <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/30 text-red-400 font-mono text-[10px] font-bold">
                        {{ $damagedRatio }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-red-400 mt-1">{{ $maintenanceCount }} Item</div>
            </div>
            <div class="p-2.5 rounded-lg bg-red-950/40 text-red-400 border border-red-500/20">
                <span class="material-symbols-outlined text-xl">build</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Menunggu Approval Finance</span>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $pendingRequestsCount }} Pengajuan</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">pending_actions</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Est. Biaya Pending</span>
                <div class="font-headline text-xl font-bold text-emerald-400 mt-1">Rp {{ number_format($totalEstimatedCost, 0, ',', '.') }}</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">payments</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-[#282a2e]/60 pb-1">
        <button @click="activeTab = 'requests'" 
                :class="activeTab === 'requests' ? 'text-amber-400 border-amber-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">assignment_turned_in</span>
            <span>Pengajuan Penambahan & Perbaikan Inventaris</span>
            @if($pendingRequestsCount > 0)
                <span class="px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold border border-amber-500/30">
                    {{ $pendingRequestsCount }}
                </span>
            @endif
        </button>

        <button @click="activeTab = 'inventory'" 
                :class="activeTab === 'inventory' ? 'text-amber-400 border-amber-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">inventory_2</span>
            <span>Katalog Inventaris & Aset Kantor ({{ $items->total() }})</span>
        </button>
    </div>

    <!-- TAB 1: PENGAJUAN PENAMBAHAN & PERBAIKAN INVENTARIS (APPROVAL FINANCE / DIREKTUR) -->
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
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($requests as $req)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4">
                                    @if($req->request_type === 'penambahan')
                                        <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/20 font-mono text-[10px] font-semibold flex items-center gap-1 w-max">
                                            <span class="material-symbols-outlined text-xs">add_shopping_cart</span>
                                            <span>Penambahan Baru</span>
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-purple-950/60 text-purple-400 border border-purple-500/20 font-mono text-[10px] font-semibold flex items-center gap-1 w-max">
                                            <span class="material-symbols-outlined text-xs">build</span>
                                            <span>Perbaikan Aset</span>
                                        </span>
                                    @endif
                                    @if($req->urgency === 'urgent')
                                        <span class="mt-1 px-1.5 py-0.2 rounded bg-red-950/60 text-red-400 border border-red-500/30 font-mono text-[9px] font-bold block w-max uppercase">URGENT</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-sm text-on-surface">{{ $req->item_name }}</div>
                                    <div class="text-xs text-tertiary font-mono">
                                        Kategori: {{ $req->category }}
                                        @if($req->inventoryItem)
                                            • Kode: {{ $req->inventoryItem->item_code }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs">
                                    <div class="font-semibold text-emerald-400">Rp {{ number_format($req->estimated_cost, 0, ',', '.') }}</div>
                                    <div class="text-[11px] text-tertiary">{{ $req->quantity }} Unit</div>
                                </td>
                                <td class="px-5 py-4 text-xs text-on-surface max-w-xs">
                                    <p class="line-clamp-2">{{ $req->reason }}</p>
                                    @if($req->approval_notes)
                                        <div class="text-[11px] text-tertiary italic mt-1 bg-surface-container p-1 rounded">
                                            Catatan: "{{ $req->approval_notes }}"
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                    <span class="text-on-surface font-medium block">{{ $req->requester?->name ?? 'Staf GA' }}</span>
                                    <span class="text-[10px]">{{ $req->created_at->format('d M Y, H:i') }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono text-[10px]">
                                    @if($req->status === 'approved')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase w-max">APPROVED</span>
                                            <span class="text-[9px] text-tertiary">Oleh: {{ $req->approver?->name ?? 'Finance' }}</span>
                                        </div>
                                    @elseif($req->status === 'rejected')
                                        <div class="flex flex-col gap-0.5">
                                            <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/20 uppercase font-bold w-max">REJECTED</span>
                                            <span class="text-[9px] text-tertiary">Oleh: {{ $req->approver?->name ?? 'Finance' }}</span>
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 uppercase font-semibold">PENDING REVIEW</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @php
                                        $myEmpId = auth()->user()->employee_id ?? (auth()->user()->employee?->id);
                                        $isOwnRequest = $myEmpId && ((int)$req->requested_by === (int)$myEmpId);
                                    @endphp

                                    @if($isOwnRequest && $req->status === 'pending')
                                        <span class="font-mono text-[10px] text-amber-400 bg-amber-950/60 px-2 py-1 rounded border border-amber-500/30 inline-flex items-center gap-1 justify-end ml-auto" title="Anda tidak dapat meng-approve pengajuan inventaris milik sendiri">
                                            <span class="material-symbols-outlined text-xs">lock</span>
                                            <span>Pengajuan Sendiri</span>
                                        </span>
                                    @elseif($req->status === 'pending' && auth()->user()->canApproveInventoryRequests())
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Approve Form -->
                                            <form action="{{ route('ga.updateRequestStatus', $req->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit" onclick="return confirm('Setujui pengajuan inventaris ini?')" class="p-1.5 rounded-lg bg-emerald-950/60 hover:bg-emerald-900 text-emerald-400 border border-emerald-500/30 transition-colors shadow-sm" title="Approve Pengajuan">
                                                    <span class="material-symbols-outlined text-base">check</span>
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form action="{{ route('ga.updateRequestStatus', $req->id) }}" method="POST" class="inline" onsubmit="
                                                let reason = prompt('Masukkan alasan penolakan pengajuan inventaris (wajib diisi):');
                                                if (!reason || reason.trim() === '') {
                                                    alert('Alasan penolakan wajib diisi!');
                                                    return false;
                                                }
                                                this.querySelector('input[name=approval_notes]').value = reason;
                                                return true;
                                            ">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <input type="hidden" name="approval_notes" value="">
                                                <button type="submit" class="p-1.5 rounded-lg bg-red-950/60 hover:bg-red-900 text-red-400 border border-red-500/30 transition-colors shadow-sm" title="Tolak Pengajuan (Wajib Alasan)">
                                                    <span class="material-symbols-outlined text-base">close</span>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-[11px] font-mono text-tertiary">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">
                                    Belum ada daftar pengajuan penambahan atau perbaikan inventaris. Klik "Ajukan Inventaris / Perbaikan" untuk membuat pengajuan baru.
                                </td>
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

    <!-- TAB 2: KATALOG INVENTARIS ASET -->
    <div x-show="activeTab === 'inventory'" class="space-y-4">
        <!-- Filter Bar -->
        <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('ga.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <input type="hidden" name="tab" value="inventory">
                <select name="category" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Kategori Aset</option>
                    <option value="Perangkat IT / Laptop" {{ $category === 'Perangkat IT / Laptop' ? 'selected' : '' }}>Perangkat IT / Laptop</option>
                    <option value="Furniture & Meja" {{ $category === 'Furniture & Meja' ? 'selected' : '' }}>Furniture & Meja</option>
                    <option value="Fasilitas Gedung" {{ $category === 'Fasilitas Gedung' ? 'selected' : '' }}>Fasilitas Gedung</option>
                    <option value="ATK & Stationary" {{ $category === 'ATK & Stationary' ? 'selected' : '' }}>ATK & Stationary</option>
                </select>

                <select name="condition" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Kondisi</option>
                    <option value="good" {{ $condition === 'good' ? 'selected' : '' }}>Baik (Good)</option>
                    <option value="maintenance" {{ $condition === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="damaged" {{ $condition === 'damaged' ? 'selected' : '' }}>Rusak (Damaged)</option>
                </select>

                @if($category || $condition)
                    <a href="{{ route('ga.index', ['tab' => 'inventory']) }}" class="text-xs text-tertiary hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">Kode & Nama Barang</th>
                            <th class="px-5 py-3.5">Kategori</th>
                            <th class="px-5 py-3.5">Lokasi Penempatan</th>
                            <th class="px-5 py-3.5">Kondisi Saat Ini</th>
                            <th class="px-5 py-3.5">Peminjam (PIC)</th>
                            <th class="px-5 py-3.5 text-right">Ubah Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($items as $item)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-tertiary text-[10px] block">{{ $item->item_code }}</span>
                                    <div class="font-semibold text-sm text-on-surface">{{ $item->name }}</div>
                                    @if($item->notes)
                                        <div class="text-xs text-tertiary italic max-w-xs truncate">{{ $item->notes }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-tertiary">{{ $item->category }}</td>
                                <td class="px-5 py-4 text-xs font-mono text-on-surface">{{ $item->location }}</td>
                                <td class="px-5 py-4 font-mono text-[10px]">
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

    <!-- Modal Form Pengajuan Penambahan / Perbaikan Inventaris -->
    <div x-cloak x-show="openRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openRequestModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400 text-xl">post_add</span>
                    <h3 class="font-headline font-semibold text-base text-on-surface">Form Pengajuan Inventaris Kantor</h3>
                </div>
                <button @click="openRequestModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('ga.storeRequest') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Jenis Pengajuan</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-xs"
                               :class="requestType === 'penambahan' ? 'bg-amber-950/40 border-amber-500/40 text-amber-300 font-semibold' : 'bg-surface-container-low border-[#333539] text-tertiary'">
                            <input type="radio" name="request_type" value="penambahan" x-model="requestType" class="hidden">
                            <span class="material-symbols-outlined text-base">add_circle</span>
                            <span>Penambahan Baru</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border cursor-pointer text-xs"
                               :class="requestType === 'perbaikan' ? 'bg-purple-950/40 border-purple-500/40 text-purple-300 font-semibold' : 'bg-surface-container-low border-[#333539] text-tertiary'">
                            <input type="radio" name="request_type" value="perbaikan" x-model="requestType" class="hidden">
                            <span class="material-symbols-outlined text-base">build</span>
                            <span>Perbaikan Aset Rusak</span>
                        </label>
                    </div>
                </div>

                <!-- Jika Perbaikan: Pilih Item dari Inventaris yang Ada -->
                <div x-show="requestType === 'perbaikan'">
                    <label class="block text-xs font-mono text-tertiary mb-1">Pilih Barang yang Rusak / Perlu Servis</label>
                    <select name="inventory_item_id" @change="onItemChange($event.target.value)" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                        <option value="">-- Pilih dari Daftar Inventaris --</option>
                        @foreach($items as $i)
                            <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->item_code }}) - Kondisi: {{ $i->condition }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Nama Barang / Deskripsi Servis</label>
                    <input type="text" name="item_name" id="item_name_input" required placeholder="contoh: Pengadaan Monitor 27-inch / Ganti Keyboard Laptop HR" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kategori</label>
                        <select name="category" id="item_category_input" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="Perangkat IT / Laptop">Perangkat IT / Laptop</option>
                            <option value="Furniture & Meja">Furniture & Meja</option>
                            <option value="Fasilitas Gedung">Fasilitas Gedung</option>
                            <option value="ATK & Stationary">ATK & Stationary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Urgensi</label>
                        <select name="urgency" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="normal">Normal (Standar)</option>
                            <option value="urgent">Urgent (Mendesak)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Jumlah Unit (Qty)</label>
                        <input type="number" name="quantity" min="1" value="1" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Estimasi Biaya (Rp)</label>
                        <input type="number" name="estimated_cost" min="0" step="50000" placeholder="contoh: 2500000" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Alasan Kebutuhan & Spesifikasi</label>
                    <textarea name="reason" rows="3" required placeholder="Jelaskan kendala kerusakan atau urgensi penambahan aset baru..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="p-3 bg-surface-container/40 rounded-xl border border-[#282a2e]/60 flex items-center gap-2 text-xs text-tertiary font-mono">
                    <span class="material-symbols-outlined text-amber-400 text-sm">info</span>
                    <span>Pengajuan ini akan diteruskan ke Tim Finance / Direktur untuk persetujuan anggaran.</span>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openRequestModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-md">Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Tambah Inventaris Langsung -->
    <div x-cloak x-show="openAssetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openAssetModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400 text-xl">add_box</span>
                    <h3 class="font-headline font-semibold text-base text-on-surface">Tambah Aset Inventaris Baru</h3>
                </div>
                <button @click="openAssetModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('ga.store') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kode Aset / Item Code</label>
                        <input type="text" name="item_code" required placeholder="contoh: AST-IT-009" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kategori</label>
                        <select name="category" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="Perangkat IT / Laptop">Perangkat IT / Laptop</option>
                            <option value="Furniture & Meja">Furniture & Meja</option>
                            <option value="Fasilitas Gedung">Fasilitas Gedung</option>
                            <option value="ATK & Stationary">ATK & Stationary</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Nama Barang / Merk</label>
                    <input type="text" name="name" required placeholder="contoh: MacBook Pro M2 / Kursi Ergonomis" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Jumlah Unit (Qty)</label>
                        <input type="number" name="stock_qty" min="1" value="1" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kondisi Awal</label>
                        <select name="condition" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="good">Baik</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="damaged">Rusak</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Lokasi Penempatan</label>
                    <input type="text" name="location" value="Headquarters (Jakarta) - Lt. 5" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Tugaskan ke Karyawan (Opsional)</label>
                    <select name="assigned_to" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                        <option value="">-- Tetap di Gudang / Shared --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->nip }}) - {{ $emp->department }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Catatan Nomor Seri / Kondisi</label>
                    <textarea name="notes" rows="2" placeholder="Serial number, spesifikasi RAM/SSD..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openAssetModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-md">Simpan Inventaris</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
