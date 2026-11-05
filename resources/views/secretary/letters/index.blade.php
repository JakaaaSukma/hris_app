@extends('layouts.app')

@section('title', 'Sekretariat Direksi - Agenda Surat Keluar & Penomoran Resmi')

@section('content')
<div class="flex flex-col gap-6" x-data="letterManagerComponent()">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Executive Office • Sekretariat Direksi</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Buku Agenda Surat Keluar & Penomoran Resmi</h1>
            <p class="font-mono text-xs text-tertiary">Sistem penomoran otomatis surat resmi kantor, registrasi agenda, verifikasi penandatanganan Direktur, dan pengarsipan berkas keluar</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Tombol Terbitkan Nomor Surat Baru -->
            <button type="button" 
                    @click="openCreateModal()" 
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">outgoing_mail</span>
                <span>Terbitkan Nomor Surat</span>
            </button>

            <!-- Tombol Reservasi Nomor Cepat -->
            <button type="button" 
                    @click="openReservationModal()" 
                    class="flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high hover:bg-surface-bright text-on-surface border border-[#333539] rounded-lg text-xs font-semibold transition-all shadow-sm">
                <span class="material-symbols-outlined text-base text-amber-400">bookmark_add</span>
                <span>Reservasi Nomor</span>
            </button>
        </div>
    </div>

    <!-- Metric Cards Persuratan -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Surat Keluar {{ $year === 'all' ? 'Semua Tahun' : $year }}</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalLettersYear }} Surat</div>
                <span class="text-[10px] text-slate-400 font-mono">Total tercatat di buku agenda</span>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-amber-400">
                <span class="material-symbols-outlined text-xl">mark_email_read</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Terkirim / Selesai</span>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $totalSent }} Surat</div>
                <span class="text-[10px] text-slate-400 font-mono">Telah dikirim & diarsipkan</span>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">send</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Draf & Reservasi Nomor</span>
                <div class="font-headline text-2xl font-bold {{ $totalPending > 0 ? 'text-amber-400' : 'text-on-surface' }} mt-1">
                    {{ $totalPending }} Nomor
                </div>
                <span class="text-[10px] text-slate-400 font-mono">Booking / menunggu tanda tangan</span>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">history_edu</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Bulan Ini ({{ date('F') }})</span>
                <div class="font-headline text-2xl font-bold text-blue-400 mt-1">{{ $totalThisMonth }} Surat</div>
                <span class="text-[10px] text-slate-400 font-mono">Penerbitan bulan berjalan</span>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20">
                <span class="material-symbols-outlined text-xl">calendar_month</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form action="{{ route('secretary.letters.index') }}" method="GET" class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 text-xs">
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
            <div class="relative flex-1 min-w-[220px]">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-sm">search</span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor surat, penerima, atau perihal..." class="w-full bg-surface-container border border-[#333539] rounded-lg pl-9 pr-3 py-2 text-xs text-on-surface focus:outline-none"/>
            </div>

            <!-- Filter Kategori -->
            <select name="category" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                <option value="">-- Semua Kategori Surat --</option>
                @foreach($categories as $code => $lbl)
                    <option value="{{ $code }}" {{ $category === $code ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>

            <!-- Filter Status -->
            <select name="status" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                <option value="">-- Semua Status --</option>
                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Konsep (Draft)</option>
                <option value="reserved" {{ $status === 'reserved' ? 'selected' : '' }}>Nomor Dipesan</option>
                <option value="signed" {{ $status === 'signed' ? 'selected' : '' }}>Ditandatangani</option>
                <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Terkirim</option>
                <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
            </select>

            <!-- Filter Urgensi -->
            <select name="urgency" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                <option value="">-- Semua Urgensi --</option>
                <option value="biasa" {{ $urgency === 'biasa' ? 'selected' : '' }}>Biasa</option>
                <option value="penting" {{ $urgency === 'penting' ? 'selected' : '' }}>Penting</option>
                <option value="segera" {{ $urgency === 'segera' ? 'selected' : '' }}>Segera</option>
                <option value="rahasia" {{ $urgency === 'rahasia' ? 'selected' : '' }}>Rahasia</option>
            </select>

            <!-- Filter Tahun -->
            <select name="year" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none font-mono">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                @endforeach
                <option value="all" {{ $year === 'all' ? 'selected' : '' }}>Semua Tahun</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-semibold flex items-center gap-1.5 shadow-sm">
                <span class="material-symbols-outlined text-sm">filter_alt</span>
                <span>Filter</span>
            </button>
            @if($search || $category || $status || $urgency || ($year && $year != date('Y')))
                <a href="{{ route('secretary.letters.index') }}" class="px-3 py-2 bg-surface-container border border-[#333539] hover:bg-surface-bright text-tertiary hover:text-on-surface rounded-lg">Reset</a>
            @endif
        </div>
    </form>

    <!-- Tabel Agenda Surat Keluar -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                        <th class="px-5 py-3.5">Nomor & Tanggal Surat</th>
                        <th class="px-5 py-3.5">Penerima / Instansi Tujuan</th>
                        <th class="px-5 py-3.5">Perihal & Ringkasan</th>
                        <th class="px-5 py-3.5 text-center">Urgensi</th>
                        <th class="px-5 py-3.5 text-center">Status & Penandatangan</th>
                        <th class="px-5 py-3.5 text-center">Berkas Digital</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/40">
                    @forelse($letters as $letter)
                        <tr class="hover:bg-surface-container/20 transition-colors">
                            <!-- Nomor & Tanggal Surat -->
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] uppercase border
                                        @if($letter->letter_category === 'SK') bg-rose-500/15 text-rose-300 border-rose-500/30
                                        @elseif($letter->letter_category === 'SPK') bg-blue-500/15 text-blue-300 border-blue-500/30
                                        @elseif($letter->letter_category === 'ST') bg-emerald-500/15 text-emerald-300 border-emerald-500/30
                                        @elseif($letter->letter_category === 'UND') bg-purple-500/15 text-purple-300 border-purple-500/30
                                        @elseif($letter->letter_category === 'MOU') bg-amber-500/15 text-amber-300 border-amber-500/30
                                        @else bg-slate-500/15 text-slate-300 border-slate-500/30 @endif">
                                        {{ $letter->letter_category }}
                                    </span>
                                    <span class="font-mono font-bold text-sm text-white">{{ $letter->letter_number }}</span>
                                </div>
                                <div class="mt-1 flex items-center gap-1.5 font-mono text-[11px] text-slate-400">
                                    <span class="material-symbols-outlined text-[13px] text-amber-400">calendar_today</span>
                                    <span>{{ $letter->letter_date ? $letter->letter_date->translatedFormat('d M Y') : '-' }}</span>
                                    <span class="text-slate-600">•</span>
                                    <span>No. Urut: <strong>#{{ $letter->sequence_number }}</strong></span>
                                </div>
                            </td>

                            <!-- Penerima & Alamat Tujuan -->
                            <td class="px-5 py-4 max-w-xs">
                                <div class="font-semibold text-on-surface text-sm">{{ $letter->recipient }}</div>
                                @if($letter->recipient_address)
                                    <div class="text-[11px] text-slate-400 truncate mt-0.5" title="{{ $letter->recipient_address }}">
                                        {{ $letter->recipient_address }}
                                    </div>
                                @endif
                            </td>

                            <!-- Perihal & Ringkasan -->
                            <td class="px-5 py-4 max-w-sm">
                                <div class="font-medium text-slate-200 line-clamp-2" title="{{ $letter->subject }}">
                                    {{ $letter->subject }}
                                </div>
                                @if($letter->regarding_summary)
                                    <div class="text-[11px] text-slate-400 italic line-clamp-1 mt-0.5" title="{{ $letter->regarding_summary }}">
                                        • {{ $letter->regarding_summary }}
                                    </div>
                                @endif
                                @if($letter->notes)
                                    <div class="text-[10px] text-amber-400/90 font-mono mt-1">
                                        Ket: {{ $letter->notes }}
                                    </div>
                                @endif
                            </td>

                            <!-- Urgensi -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-mono text-[10px] font-semibold border {{ $letter->urgency_badge }}">
                                    {{ strtoupper($letter->urgency) }}
                                </span>
                            </td>

                            <!-- Status & Penandatangan -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <div class="inline-flex flex-col items-center">
                                    <span class="px-2.5 py-0.5 rounded-full font-mono text-[10px] font-bold border {{ $letter->status_badge }}">
                                        {{ $letter->status_label }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono mt-1">
                                        {{ $letter->signer_name }} ({{ $letter->signer_title }})
                                    </span>
                                </div>
                            </td>

                            <!-- Berkas Digital / Scan PDF -->
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                @if($letter->file_path)
                                    <a href="{{ route('secretary.letters.download', $letter->id) }}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-mono text-[11px] font-semibold transition-all shadow-sm">
                                        <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
                                        <span>Lihat PDF</span>
                                    </a>
                                @else
                                    <span class="font-mono text-[11px] text-slate-500 italic">Belum ada scan</span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Tombol Lembar Tanda Terima / Ekspedisi -->
                                    <button type="button" 
                                            @click="openReceiptModal({{ json_encode($letter) }})" 
                                            class="p-1.5 rounded bg-surface-container hover:bg-surface-bright text-slate-300 hover:text-white border border-[#333539] transition-colors" 
                                            title="Cetak Lembar Tanda Terima / Ekspedisi">
                                        <span class="material-symbols-outlined text-sm text-amber-400">receipt_long</span>
                                    </button>

                                    <!-- Tombol Edit / Update Status -->
                                    <button type="button" 
                                            @click="openEditModal({{ json_encode($letter) }})" 
                                            class="p-1.5 rounded bg-surface-container hover:bg-surface-bright text-slate-300 hover:text-white border border-[#333539] transition-colors" 
                                            title="Edit Surat / Update Status & File">
                                        <span class="material-symbols-outlined text-sm text-blue-400">edit</span>
                                    </button>

                                    <!-- Tombol Hapus -->
                                    <form action="{{ route('secretary.letters.destroy', $letter->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip surat resmi ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded bg-surface-container hover:bg-rose-950/60 text-slate-400 hover:text-rose-400 border border-[#333539] transition-colors" title="Hapus Surat">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-tertiary font-mono">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <span class="material-symbols-outlined text-4xl text-slate-600">drafts</span>
                                    <p class="text-sm">Belum ada surat keluar yang terdaftar pada kriteria ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($letters->hasPages())
            <div class="p-4 border-t border-[#282a2e]/40">{{ $letters->links() }}</div>
        @endif
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: REGISTRASI / PENERBITAN NOMOR SURAT RESMI             -->
    <!-- ============================================================== -->
    <div x-cloak x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-2xl p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showCreateModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">outgoing_mail</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Registrasi & Penomoran Surat Keluar</h3>
                        <p class="font-mono text-[11px] text-slate-400">Penerbitan nomor surat resmi kantor LPK Gyokai Indonesia</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('secretary.letters.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <!-- CARD LIVE PREVIEW NOMOR SURAT OTOMATIS -->
                <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-mono uppercase tracking-wider text-slate-400 font-semibold">Nomor Surat Resmi Terbentuk:</span>
                        <template x-if="numberExists">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold">
                                Duplikat! Nomor sudah ada
                            </span>
                        </template>
                        <template x-if="!numberExists">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">check_circle</span>
                                <span>Nomor Tersedia</span>
                            </span>
                        </template>
                    </div>

                    <!-- Input Nomor Surat (Readonly/Editable) -->
                    <div class="relative">
                        <input type="text" 
                               name="letter_number" 
                               x-model="form.letter_number" 
                               required 
                               class="w-full bg-[#151922] border rounded-xl px-4 py-2.5 text-base font-mono font-bold text-amber-400 tracking-wide focus:outline-none"
                               :class="numberExists ? 'border-rose-500/80 text-rose-300' : 'border-amber-500/50'"/>
                    </div>
                    <p class="text-[11px] font-mono text-slate-500">Format: [No. Urut]/[Kategori]/[Kode Organisasi]/[Bulan Romawi]/[Tahun]. Dihitung otomatis sesuai tanggal & urutan.</p>
                </div>

                <!-- Parameter Pembentuk Nomor Surat -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3.5 bg-slate-900/50 rounded-xl border border-slate-800/80">
                    <div>
                        <label class="block text-[11px] font-mono text-slate-300 mb-1 font-semibold">No. Urut <span class="text-rose-400">*</span></label>
                        <input type="number" 
                               name="sequence_number" 
                               x-model="form.sequence_number" 
                               @input="calculateLetterNumber()"
                               min="1" 
                               required 
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono font-bold"/>
                    </div>
                    <div>
                        <label class="block text-[11px] font-mono text-slate-300 mb-1 font-semibold">Kategori <span class="text-rose-400">*</span></label>
                        <select name="letter_category" 
                                x-model="form.letter_category" 
                                @change="calculateLetterNumber()"
                                required 
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono">
                            @foreach($categories as $code => $lbl)
                                <option value="{{ $code }}">{{ $code }} ({{ Str::limit($lbl, 16) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-mono text-slate-300 mb-1 font-semibold">Pengirim / Kode <span class="text-rose-400">*</span></label>
                        <select name="organization_code" 
                                x-model="form.organization_code" 
                                @change="calculateLetterNumber()"
                                required 
                                class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono">
                            @foreach($organizations as $oCode => $oLbl)
                                <option value="{{ $oCode }}">{{ $oCode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-mono text-slate-300 mb-1 font-semibold">Tanggal Surat <span class="text-rose-400">*</span></label>
                        <input type="date" 
                               name="letter_date" 
                               x-model="form.letter_date" 
                               @change="calculateLetterNumber()"
                               required 
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white focus:outline-none focus:border-amber-500 font-mono"/>
                    </div>
                </div>

                <!-- Penerima & Alamat Tujuan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Ditujukan Kepada Yth. <span class="text-rose-400">*</span></label>
                        <input type="text" name="recipient" required placeholder="contoh: Direktur PT Daikin / Kepala Disnaker" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Alamat / Domisili Tujuan</label>
                        <input type="text" name="recipient_address" placeholder="contoh: Jl. M.H. Thamrin Kav. 51, Jakarta Pusat" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"/>
                    </div>
                </div>

                <!-- Perihal / Hal Surat -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Perihal / Hal Surat <span class="text-rose-400">*</span></label>
                    <input type="text" name="subject" required placeholder="contoh: Undangan Upacara Pelepasan Siswa Magang Batch XII" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 font-medium"/>
                </div>

                <!-- Ringkasan Isi -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Ringkasan / Keterangan Isi Surat</label>
                    <textarea name="regarding_summary" rows="2" placeholder="Catatan pokok perihal isi surat atau instruksi yang tercantum..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"></textarea>
                </div>

                <!-- Urgensi, Status & Penandatangan -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Tingkat Urgensi <span class="text-rose-400">*</span></label>
                        <select name="urgency" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                            <option value="biasa">Biasa (Standar)</option>
                            <option value="penting">Penting</option>
                            <option value="segera">Segera / Mendesak</option>
                            <option value="rahasia">Rahasia / Konfidensial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Status Surat <span class="text-rose-400">*</span></label>
                        <select name="status" x-model="form.status" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
                            <option value="signed">Ditandatangani (Siap Kirim)</option>
                            <option value="sent">Terkirim ke Penerima</option>
                            <option value="reserved">Nomor Dipesan (Booking)</option>
                            <option value="draft">Konsep (Draft)</option>
                            <option value="archived">Diarsipkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Pejabat Penandatangan <span class="text-rose-400">*</span></label>
                        <input type="text" name="signer_name" value="{{ $directorName }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500 font-semibold"/>
                        <input type="hidden" name="signer_title" value="Direktur Utama"/>
                    </div>
                </div>

                <!-- Upload Berkas Scan PDF / Gambar Surat -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Upload Berkas Scan Surat (PDF / JPG / PNG, Max 10MB)</label>
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-600 file:text-white hover:file:bg-amber-700"/>
                </div>

                <!-- Catatan Ekspedisi / Nomor Resi / Arsip -->
                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Catatan Ekspedisi / Resi Kirim (Opsional)</label>
                    <input type="text" name="notes" placeholder="contoh: Dikirim via JNE (Resi: 8812398) / Diserahkan langsung" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" :disabled="numberExists" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white text-xs font-semibold rounded-xl shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        Terbitkan & Registrasi Surat
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: EDIT SURAT & UPDATE STATUS PENGIRIMAN                 -->
    <!-- ============================================================== -->
    <div x-cloak x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showEditModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">edit_document</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Update Status & Berkas Surat</h3>
                        <p class="font-mono text-[11px] text-amber-400" x-text="editForm.letter_number"></p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/secretary/letters') }}/' + editForm.id" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3.5">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Nomor Surat Resmi</label>
                    <input type="text" name="letter_number" x-model="editForm.letter_number" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs font-mono font-bold text-amber-400 focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Tanggal Surat</label>
                        <input type="date" name="letter_date" x-model="editForm.letter_date" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Status Surat</label>
                        <select name="status" x-model="editForm.status" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="draft">Konsep (Draft)</option>
                            <option value="reserved">Nomor Dipesan (Booking)</option>
                            <option value="signed">Ditandatangani (Siap Kirim)</option>
                            <option value="sent">Terkirim ke Tujuan</option>
                            <option value="archived">Selesai & Diarsipkan</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Penerima / Instansi Tujuan</label>
                    <input type="text" name="recipient" x-model="editForm.recipient" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Perihal Surat</label>
                    <input type="text" name="subject" x-model="editForm.subject" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none font-medium"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Tingkat Urgensi</label>
                        <select name="urgency" x-model="editForm.urgency" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="biasa">Biasa</option>
                            <option value="penting">Penting</option>
                            <option value="segera">Segera</option>
                            <option value="rahasia">Rahasia</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Penandatangan</label>
                        <input type="text" name="signer_name" x-model="editForm.signer_name" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                        <input type="hidden" name="signer_title" x-model="editForm.signer_title"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Ganti / Upload Berkas Scan Surat (PDF / JPG)</label>
                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-300 focus:outline-none file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700"/>
                    <template x-if="editForm.file_path">
                        <p class="text-[11px] font-mono text-emerald-400 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">check_circle</span>
                            <span>Berkas digital saat ini sudah terunggah. Upload ulang hanya jika ingin mengganti.</span>
                        </p>
                    </template>
                </div>

                <div>
                    <label class="block text-xs font-mono text-slate-300 mb-1 font-semibold">Catatan Ekspedisi / Status Resi</label>
                    <input type="text" name="notes" x-model="editForm.notes" placeholder="Nomor resi, ekspedisi, atau nama kurir..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: CETAK LEMBAR EKSPEDISI / TANDA TERIMA SURAT           -->
    <!-- ============================================================== -->
    <div x-cloak x-show="showReceiptModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative" @click.away="showReceiptModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">receipt_long</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Lembar Tanda Terima / Ekspedisi</h3>
                        <p class="font-mono text-[11px] text-slate-400">Bukti penyerahan & pengiriman surat resmi</p>
                    </div>
                </div>
                <button type="button" @click="showReceiptModal = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Area Printable Receipt -->
            <div id="printable-receipt" class="mt-4 p-5 bg-white text-slate-900 rounded-xl space-y-4 font-sans text-xs border border-slate-300">
                <div class="text-center pb-3 border-b-2 border-slate-900">
                    <h4 class="font-bold text-sm tracking-wide uppercase text-slate-950">LEMBAR EKSPEDISI SURAT KELUAR</h4>
                    <p class="text-[11px] text-slate-700 font-semibold">LPK GYOKAI INDONESIA • SEKRETARIAT DIREKSI</p>
                    <p class="text-[10px] text-slate-500">Jakarta Central Office • Dokumen Resmi Eksekutif</p>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Nomor Surat:</span>
                        <span class="col-span-2 font-mono font-bold text-slate-950" x-text="receiptData?.letter_number"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Tanggal Surat:</span>
                        <span class="col-span-2 font-bold text-slate-900" x-text="receiptData?.letter_date ? new Date(receiptData.letter_date).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'}) : '-'"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Kepada Yth:</span>
                        <span class="col-span-2 font-bold text-slate-900" x-text="receiptData?.recipient"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Alamat Tujuan:</span>
                        <span class="col-span-2 text-slate-700" x-text="receiptData?.recipient_address || '-'"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Perihal:</span>
                        <span class="col-span-2 font-semibold text-slate-900" x-text="receiptData?.subject"></span>
                    </div>
                    <div class="grid grid-cols-3 gap-1">
                        <span class="text-slate-600 font-medium">Pengirim:</span>
                        <span class="col-span-2 text-slate-800" x-text="(receiptData?.signer_name || '{{ $directorName }}') + ' (' + (receiptData?.signer_title || 'Direktur Utama') + ')'"></span>
                    </div>
                </div>

                <div class="pt-4 border-t border-dashed border-slate-400 grid grid-cols-2 gap-4 text-center text-[11px]">
                    <div class="space-y-12">
                        <p class="text-slate-600">Diserahkan Oleh (Sekretariat):</p>
                        <p class="font-bold text-slate-900 border-t border-slate-400 pt-1">Sekretaris Direktur</p>
                    </div>
                    <div class="space-y-12">
                        <p class="text-slate-600">Diterima Oleh (Penerima/Kurir):</p>
                        <p class="font-bold text-slate-900 border-t border-slate-400 pt-1">(Nama, Tanda Tangan & Cap)</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800 mt-4">
                <button type="button" @click="showReceiptModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-mono text-slate-300 rounded-xl">Tutup</button>
                <button type="button" @click="printReceipt()" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white text-xs font-semibold rounded-xl shadow-md flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">print</span>
                    <span>Cetak Tanda Terima</span>
                </button>
            </div>
        </div>
    </div>

</div>

<!-- Alpine Javascript Logic for Realtime Auto-Numbering -->
<script>
function letterManagerComponent() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showReceiptModal: false,
        numberExists: false,
        receiptData: null,
        
        form: {
            sequence_number: {{ $nextSequence }},
            letter_category: 'SKet',
            organization_code: 'LPK-GYOKAI',
            letter_date: '{{ date('Y-m-d') }}',
            letter_number: '{{ $defaultLetterNumber }}',
            status: 'signed',
        },

        editForm: {},

        openCreateModal(statusPreset = 'signed') {
            this.form.status = statusPreset;
            this.calculateLetterNumber();
            this.showCreateModal = true;
        },

        openReservationModal() {
            this.openCreateModal('reserved');
        },

        openEditModal(letter) {
            this.editForm = Object.assign({}, letter);
            if (letter.letter_date) {
                this.editForm.letter_date = letter.letter_date.substring(0, 10);
            }
            this.showEditModal = true;
        },

        openReceiptModal(letter) {
            this.receiptData = letter;
            this.showReceiptModal = true;
        },

        async calculateLetterNumber() {
            const params = new URLSearchParams({
                category: this.form.letter_category,
                organization_code: this.form.organization_code,
                letter_date: this.form.letter_date,
                sequence_number: this.form.sequence_number
            });

            try {
                const response = await fetch(`{{ route('secretary.letters.nextNumber') }}?${params.toString()}`);
                const data = await response.json();
                if (data.success) {
                    this.form.letter_number = data.letter_number;
                    this.numberExists = data.exists;
                }
            } catch (e) {
                console.error('Error fetching letter number:', e);
            }
        },

        printReceipt() {
            const receiptEl = document.getElementById('printable-receipt');
            if (!receiptEl) return;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Lembar Ekspedisi - ${this.receiptData?.letter_number || 'Surat Keluar'}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 25px; margin: 0; color: #111; }
                        .text-center { text-align: center; }
                        .pb-3 { padding-bottom: 12px; }
                        .border-b-2 { border-bottom: 2px solid #111; }
                        .border-t { border-top: 1px solid #999; }
                        .border-dashed { border-style: dashed; }
                        .font-bold { font-weight: bold; }
                        .font-mono { font-family: monospace; }
                        .uppercase { text-transform: uppercase; }
                        .grid { display: grid; }
                        .grid-cols-3 { grid-template-columns: 140px 1fr; }
                        .grid-cols-2 { display: flex; justify-content: space-between; }
                        .gap-4 { gap: 16px; }
                        .space-y-2 > div { margin-bottom: 8px; }
                        .space-y-12 { margin-top: 20px; }
                        .space-y-12 p:first-child { margin-bottom: 60px; }
                    </style>
                </head>
                <body>
                    ${receiptEl.innerHTML}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 300);
        }
    };
}
</script>
@endsection

