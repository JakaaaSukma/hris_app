@extends('layouts.app')

@section('title', 'Layanan & Laporan Kendala Fasilitas GA')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openReportModal: false, 
    detailTicketModal: false,
    selectedTicket: null 
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Helpdesk Fasilitas & General Affairs</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Pelaporan Kerusakan & Kendala Fasilitas</h1>
            <p class="font-mono text-xs text-tertiary">
                {{ $isGA ? 'Pusat tiket kendala fasilitas kantor yang dilaporkan oleh karyawan & monitoring penanganan oleh Tim GA' : 'Laporkan kerusakan AC, listrik, internet, laptop, atau meja kantor Anda ke Tim General Affairs' }}
            </p>
        </div>

        <button @click="openReportModal = true" class="flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
            <span class="material-symbols-outlined text-base">report_problem</span>
            <span>Laporkan Kerusakan Baru</span>
        </button>
    </div>

    <!-- Metric Cards -->
    @php
        $openPct = $totalTickets > 0 ? round(($openTickets / $totalTickets) * 100, 1) : 0;
        $inProgressPct = $totalTickets > 0 ? round(($inProgressTickets / $totalTickets) * 100, 1) : 0;
        $resolvedPct = $totalTickets > 0 ? round(($resolvedTickets / $totalTickets) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Total Laporan</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalTickets }} Tiket</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">confirmation_number</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Menunggu Ditangani</span>
                    <span class="px-2 py-0.5 rounded bg-red-950/60 border border-red-500/30 text-red-400 font-mono text-[10px] font-bold">
                        {{ $openPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-red-400 mt-1">{{ $openTickets }} Baru</div>
            </div>
            <div class="p-2.5 rounded-lg bg-red-950/40 text-red-400 border border-red-500/20">
                <span class="material-symbols-outlined text-xl">mark_email_unread</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Dalam Pengerjaan</span>
                    <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold">
                        {{ $inProgressPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $inProgressTickets }} Proses</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">engineering</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Selesai Diperbaiki</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        {{ $resolvedPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $resolvedTickets }} Selesai</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">task_alt</span>
            </div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="space-y-4">
        <!-- Filter Bar -->
        <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('facilities.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <select name="status" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Status Laporan</option>
                    <option value="open" {{ $statusFilter === 'open' ? 'selected' : '' }}>Open (Belum Ditangani)</option>
                    <option value="in_progress" {{ $statusFilter === 'in_progress' ? 'selected' : '' }}>In Progress (Sedang Dikerjakan)</option>
                    <option value="resolved" {{ $statusFilter === 'resolved' ? 'selected' : '' }}>Resolved (Selesai Diperbaiki)</option>
                    <option value="closed" {{ $statusFilter === 'closed' ? 'selected' : '' }}>Closed (Selesai)</option>
                </select>

                <select name="priority" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Prioritas</option>
                    <option value="urgent" {{ $priorityFilter === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="high" {{ $priorityFilter === 'high' ? 'selected' : '' }}>High</option>
                    <option value="medium" {{ $priorityFilter === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="low" {{ $priorityFilter === 'low' ? 'selected' : '' }}>Low</option>
                </select>

                @if($statusFilter || $priorityFilter)
                    <a href="{{ route('facilities.index') }}" class="text-xs text-tertiary hover:underline">Reset</a>
                @endif
            </form>

            @if($isGA)
                <span class="text-xs font-mono text-amber-400 bg-amber-950/40 border border-amber-500/20 px-2.5 py-1 rounded-lg">
                    Mode Pengelola: Tim GA
                </span>
            @endif
        </div>

        <!-- Tabel Tiket Kendala -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">No. Tiket & Kategori</th>
                            <th class="px-5 py-3.5">Kendala / Kerusakan</th>
                            <th class="px-5 py-3.5">Lokasi Ruangan</th>
                            <th class="px-5 py-3.5">Pelapor</th>
                            <th class="px-5 py-3.5">Prioritas</th>
                            <th class="px-5 py-3.5">Progres Pengerjaan GA</th>
                            <th class="px-5 py-3.5 text-right">Detail & Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($tickets as $t)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4">
                                    <span class="font-mono text-tertiary text-[11px] font-semibold block">{{ $t->ticket_number }}</span>
                                    <span class="text-xs text-on-surface-variant font-mono">{{ $t->category }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2.5">
                                        @if($t->photo)
                                            <img src="{{ $t->photo }}" alt="Foto Bukti" class="w-10 h-10 rounded-lg object-cover border border-[#333539] flex-shrink-0 cursor-pointer hover:border-amber-400 transition-colors"
                                                 title="Klik untuk melihat bukti foto"/>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="font-semibold text-sm text-on-surface truncate">{{ $t->title }}</div>
                                            <div class="text-xs text-tertiary max-w-xs truncate mt-0.5">{{ $t->description }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-on-surface">
                                    <span class="flex items-center gap-1 text-tertiary">
                                        <span class="material-symbols-outlined text-xs text-amber-400">place</span>
                                        <span>{{ $t->location }}</span>
                                    </span>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs">
                                    <span class="text-on-surface font-semibold block">{{ $t->reporter?->name ?? 'Pegawai' }}</span>
                                    <span class="text-[10px] text-tertiary">{{ $t->reporter?->department ?? 'Staff' }} • {{ $t->created_at->format('d M H:i') }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono text-[10px]">
                                    @if($t->priority === 'urgent')
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/30 uppercase font-bold">URGENT</span>
                                    @elseif($t->priority === 'high')
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/30 uppercase font-semibold">HIGH</span>
                                    @elseif($t->priority === 'medium')
                                        <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/30 uppercase">MEDIUM</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary uppercase">LOW</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="w-36 space-y-1">
                                        <div class="flex items-center justify-between text-[11px] font-mono">
                                            <span class="text-[9px] uppercase font-bold {{ $t->status === 'resolved' || $t->status === 'closed' ? 'text-emerald-400' : ($t->status === 'in_progress' ? 'text-amber-400' : 'text-tertiary') }}">
                                                {{ strtoupper(str_replace('_', ' ', $t->status)) }}
                                            </span>
                                            <span class="font-bold text-on-surface">{{ $t->progress_percentage }}%</span>
                                        </div>
                                        <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full transition-all duration-300 {{ $t->progress_percentage == 100 ? 'bg-emerald-400' : ($t->progress_percentage > 30 ? 'bg-amber-400' : 'bg-red-400') }}"
                                                 style="width: {{ $t->progress_percentage }}%"></div>
                                        </div>
                                        @if($t->handler)
                                            <span class="text-[9px] font-mono text-tertiary block truncate">Petugas: {{ $t->handler->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <button type="button" 
                                            @click="selectedTicket = {{ json_encode([
                                                'id' => $t->id,
                                                'ticket_number' => $t->ticket_number,
                                                'title' => $t->title,
                                                'category' => $t->category,
                                                'location' => $t->location,
                                                'priority' => $t->priority,
                                                'description' => $t->description,
                                                'photo' => $t->photo,
                                                'status' => $t->status,
                                                'progress_percentage' => $t->progress_percentage,
                                                'reporter_name' => $t->reporter?->name ?? 'Pegawai',
                                                'reporter_dept' => $t->reporter?->department ?? 'Staff',
                                                'handled_by' => $t->handled_by,
                                                'handler_name' => $t->handler?->name ?? 'Belum Ditugaskan',
                                                'resolution_notes' => $t->resolution_notes ?? '',
                                                'created_at' => $t->created_at->format('d M Y, H:i'),
                                                'update_url' => route('facilities.updateProgress', $t->id),
                                            ]) }}; detailTicketModal = true"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-bright text-xs font-medium text-tertiary hover:text-on-surface border border-[#333539] transition-colors">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span>{{ $isGA ? 'Progres & Update' : 'Lihat Detail' }}</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">
                                    Belum ada kendala fasilitas yang dilaporkan. Jika ada kerusakan AC, listrik, internet, atau perlengkapan kerja, silakan klik "Laporkan Kerusakan Baru".
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tickets->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">{{ $tickets->links() }}</div>
            @endif
        </div>
    </div>

    <!-- Modal Form Laporkan Kerusakan Baru (Seluruh Karyawan & Manager) -->
    <div x-cloak x-show="openReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 "
         x-data="{ photoPreview: null }">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="openReportModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-amber-400 text-xl">report_problem</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Buat Laporan Kendala Fasilitas</h3>
                        <p class="text-xs text-tertiary">Laporan akan langsung diteruskan ke Tim General Affairs</p>
                    </div>
                </div>
                <button @click="openReportModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('facilities.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Judul Kendala / Kerusakan</label>
                    <input type="text" name="title" required placeholder="contoh: AC Ruang Finance Bocor / Port LAN Meja 12 Mati" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-amber-500"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kategori Kendala</label>
                        <select name="category" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="AC & Pendingin Ruangan">AC & Pendingin Ruangan</option>
                            <option value="Perangkat IT / Laptop">Perangkat IT / Laptop</option>
                            <option value="Listrik & Pencahayaan">Listrik & Pencahayaan</option>
                            <option value="Jaringan Internet & Wifi">Jaringan Internet & Wifi</option>
                            <option value="Furniture & Meja/Kursi">Furniture & Meja/Kursi</option>
                            <option value="Plumbing & Toilet">Plumbing & Toilet</option>
                            <option value="Fasilitas Umum Kantor">Fasilitas Umum Kantor</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Tingkat Urgensi</label>
                        <select name="priority" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="low">Low (Santai)</option>
                            <option value="medium" selected>Medium (Standar)</option>
                            <option value="high">High (Mendesak)</option>
                            <option value="urgent">Urgent (Kritis / Bahaya)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Lokasi Kejadian / Ruangan</label>
                    <input type="text" name="location" required placeholder="contoh: Lt. 4, Ruang Kerja Divisi HR, Meja Dekat Jendela" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-amber-500"/>
                </div>

                <!-- Foto Bukti Kerusakan -->
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Foto Bukti Kerusakan (Opsional / Disarankan)</label>
                    <div class="border-2 border-dashed border-[#333539] hover:border-amber-500/50 rounded-xl p-3 bg-surface-container-low transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-lg bg-surface-container text-amber-400 border border-amber-500/20">
                                <span class="material-symbols-outlined text-xl">add_a_photo</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <input type="file" name="photo" accept="image/*" class="text-xs text-on-surface file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-surface-container-high file:text-amber-400 hover:file:bg-amber-500/20 file:cursor-pointer"
                                       @change="
                                           const file = $event.target.files[0];
                                           if (file) {
                                               const reader = new FileReader();
                                               reader.onload = (e) => { photoPreview = e.target.result; };
                                               reader.readAsDataURL(file);
                                           } else {
                                               photoPreview = null;
                                           }
                                       "/>
                                <p class="text-[10px] text-tertiary mt-0.5">Format: JPG, PNG, WEBP (Maksimal 5MB)</p>
                            </div>
                        </div>
                        <!-- Image Preview -->
                        <div x-show="photoPreview" class="mt-2.5 relative inline-block">
                            <img :src="photoPreview" alt="Preview Foto Bukti" class="h-24 w-auto rounded-lg object-cover border border-[#333539] shadow-sm"/>
                            <button type="button" @click="photoPreview = null; $el.closest('form').querySelector('input[type=file]').value = ''" 
                                    class="absolute -top-1.5 -right-1.5 bg-red-600 text-white rounded-full p-0.5 hover:bg-red-700 shadow-md">
                                <span class="material-symbols-outlined text-xs">close</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Penjelasan Rinci Kendala</label>
                    <textarea name="description" rows="3.5" required placeholder="Uraikan detail kerusakan atau kondisi saat ini..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openReportModal = false; photoPreview = null" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg shadow-md flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm">send</span>
                        <span>Kirim Laporan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail & Update Progres Tiket (Oleh Tim GA) -->
    <div x-cloak x-show="detailTicketModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-xl p-6 shadow-2xl relative" @click.away="detailTicketModal = false" x-show="selectedTicket">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-surface-container-highest text-tertiary" x-text="selectedTicket?.ticket_number"></span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-amber-950/60 text-amber-300 border border-amber-500/20 uppercase" x-text="selectedTicket?.category"></span>
                    </div>
                    <h3 class="font-headline font-bold text-lg text-on-surface" x-text="selectedTicket?.title"></h3>
                    <p class="text-xs text-tertiary font-mono" x-text="'Lokasi: ' + selectedTicket?.location"></p>
                </div>
                <button @click="detailTicketModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Ticket Info -->
            <div class="mt-4 space-y-4">
                <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/60 space-y-2">
                    <span class="block text-[10px] font-mono uppercase text-tertiary">Uraian Kendala dari Pelapor:</span>
                    <p class="text-xs text-on-surface leading-relaxed" style="white-space: pre-wrap; word-break: break-word;" x-text="selectedTicket?.description"></p>
                    
                    <!-- Foto Bukti Kerusakan jika disertakan -->
                    <template x-if="selectedTicket?.photo">
                        <div class="pt-2 border-t border-[#282a2e]/50">
                            <span class="block text-[10px] font-mono uppercase text-amber-400 mb-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">image</span>
                                <span>Foto Bukti Kerusakan:</span>
                            </span>
                            <a :href="selectedTicket?.photo" target="_blank" class="block group relative w-max rounded-xl overflow-hidden border border-[#333539] hover:border-amber-400 transition-colors">
                                <img :src="selectedTicket?.photo" alt="Foto Bukti Kerusakan" class="max-h-56 max-w-full rounded-xl object-contain bg-surface-container-lowest"/>
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1.5 text-white text-xs font-mono">
                                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                                    <span>Buka Gambar Penuh</span>
                                </div>
                            </a>
                        </div>
                    </template>

                    <div class="pt-2 text-[10px] font-mono text-tertiary flex items-center justify-between">
                        <span x-text="'Dilaporkan oleh: ' + selectedTicket?.reporter_name + ' (' + selectedTicket?.reporter_dept + ')'"></span>
                        <span x-text="selectedTicket?.created_at"></span>
                    </div>
                </div>

                <!-- Form Update Progres (Khusus Tim GA atau Direktur) -->
                @if($isGA)
                    <form :action="selectedTicket?.update_url" method="POST" class="p-4 bg-surface-container-low rounded-xl border border-amber-500/30 space-y-3.5">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-center gap-1.5 pb-2 border-b border-[#282a2e]/60">
                            <span class="material-symbols-outlined text-amber-400 text-sm">engineering</span>
                            <span class="text-xs font-mono uppercase font-bold text-amber-300">Update Penanganan Tim GA</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-mono text-tertiary mb-1">Status Penanganan</label>
                                <select name="status" x-model="selectedTicket.status" class="w-full bg-surface-container border border-[#333539] rounded-lg px-2.5 py-1.5 text-xs text-on-surface focus:outline-none">
                                    <option value="open">Open (Belum Ditangani)</option>
                                    <option value="in_progress">In Progress (Sedang Dikerjakan)</option>
                                    <option value="resolved">Resolved (Selesai Diperbaiki)</option>
                                    <option value="closed">Closed (Ditutup)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[11px] font-mono text-tertiary mb-1">Progres (%)</label>
                                <div class="flex items-center gap-2">
                                    <input type="range" name="progress_percentage" min="0" max="100" step="10" x-model="selectedTicket.progress_percentage" class="flex-1 accent-amber-500"/>
                                    <span class="font-mono text-xs font-bold text-on-surface w-10 text-right" x-text="selectedTicket?.progress_percentage + '%'"></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Tugaskan Petugas GA</label>
                            <select name="handled_by" x-model="selectedTicket.handled_by" class="w-full bg-surface-container border border-[#333539] rounded-lg px-2.5 py-1.5 text-xs text-on-surface focus:outline-none">
                                <option value="">-- Pilih Staf GA --</option>
                                @foreach($gaStaff as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->role_title }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Catatan Tindakan / Solusi Perbaikan</label>
                            <textarea name="resolution_notes" x-model="selectedTicket.resolution_notes" rows="2" placeholder="contoh: Teknisi AC luar sudah dipanggil, filter dibersihkan dan freon diisi kembali..." class="w-full bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="submit" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-all">
                                Simpan Update Progres
                            </button>
                        </div>
                    </form>
                @else
                    <!-- View Only untuk Karyawan Pelapor -->
                    <div class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono uppercase text-tertiary">Status Saat Ini:</span>
                            <span class="px-2 py-0.5 rounded font-mono text-xs font-bold uppercase"
                                  :class="selectedTicket?.status === 'resolved' || selectedTicket?.status === 'closed' ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20' : 'bg-amber-950/60 text-amber-400 border border-amber-500/20'"
                                  x-text="selectedTicket?.status?.replace('_', ' ')">
                            </span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs font-mono">
                                <span class="text-tertiary">Progres Pengerjaan</span>
                                <span class="font-bold text-on-surface" x-text="selectedTicket?.progress_percentage + '%'"></span>
                            </div>
                            <div class="w-full bg-surface-container-highest rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-amber-400 transition-all duration-300"
                                     :style="'width: ' + selectedTicket?.progress_percentage + '%'"></div>
                            </div>
                        </div>
                        <div class="pt-2 text-xs text-on-surface space-y-1">
                            <span class="block text-[10px] font-mono uppercase text-tertiary">Catatan dari Tim GA:</span>
                            <p class="italic text-tertiary" x-text="selectedTicket?.resolution_notes || 'Sedang dalam antrean penanganan oleh Tim General Affairs.'"></p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end pt-4 border-t border-[#282a2e] mt-4">
                <button type="button" @click="detailTicketModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection
