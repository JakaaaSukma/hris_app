@extends('layouts.app')

@section('title', 'Finance - Manajemen Invoice & Jatuh Tempo')

@section('content')
<div class="flex flex-col gap-5" x-data="invoiceManager()">

    <!-- Header Page -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-5 editorial-surface rounded-2xl" data-reveal>
        <div>
            <div class="flex items-center gap-2">
                <x-skiper-link href="{{ route('finance.index') }}" variant="000" class="text-[11px] font-mono text-emerald-500 font-semibold">
                    Kembali ke Finance Hub
                </x-skiper-link>
                <span class="text-[var(--color-text-muted)] text-[10px]">/</span>
                <span class="font-mono text-[10px] text-[var(--color-text-muted)] uppercase tracking-wider font-semibold">Invoice, Piutang &amp; Jatuh Tempo</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1">Manajemen Invoice & Pembayaran</h1>
            <p class="font-mono text-xs text-slate-400">Penerbitan tagihan klien/vendor, pantauan jatuh tempo, cicilan & pelunasan terintegrasi</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" 
                    @click="openCreate()"
                    class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                <span class="material-symbols-outlined text-base">add_card</span>
                <span>Terbitkan Invoice Baru</span>
            </button>
        </div>
    </div>

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-base">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium flex items-center gap-2">
            <span class="material-symbols-outlined text-base">error</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <span class="material-symbols-outlined text-base">warning</span>
                <span>Periksa kembali isian formulir invoice:</span>
            </div>
            <ul class="list-disc pl-5 font-mono text-[11px]">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Alert Banner: Jatuh Tempo / Overdue -->
    @if($totalOverdueCount > 0 || $totalDueSoonCount > 0)
        <div class="p-4 rounded-2xl border {{ $totalOverdueCount > 0 ? 'bg-rose-950/40 border-rose-500/40' : 'bg-amber-950/40 border-amber-500/40' }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3">
                <span class="p-2 rounded-xl {{ $totalOverdueCount > 0 ? 'bg-rose-500/20 text-rose-400' : 'bg-amber-500/20 text-amber-400' }}">
                    <span class="material-symbols-outlined text-xl">notifications_active</span>
                </span>
                <div class="text-xs">
                    <div class="font-bold {{ $totalOverdueCount > 0 ? 'text-rose-300' : 'text-amber-300' }}">
                        @if($totalOverdueCount > 0)
                            Perhatian! Terdapat {{ $totalOverdueCount }} Invoice Melewati Jatuh Tempo (Overdue)
                            @if($totalDueSoonCount > 0) & {{ $totalDueSoonCount }} Segera Jatuh Tempo (&le; 7 Hari) @endif
                        @else
                            Pengingat Pembayaran: {{ $totalDueSoonCount }} Invoice Akan Jatuh Tempo dalam 7 Hari ke Depan
                        @endif
                    </div>
                    <p class="text-slate-400 font-mono text-[11px] mt-0.5">
                        Segera tindak lanjuti penagihan atau catat pelunasan agar arus kas perusahaan tetap sehat.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($totalOverdueCount > 0)
                    <a href="{{ route('finance.invoices.index', ['status' => 'overdue']) }}" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-white text-[11px] font-bold transition-colors">
                        Lihat Overdue ({{ $totalOverdueCount }})
                    </a>
                @endif
                @if($totalDueSoonCount > 0)
                    <a href="{{ route('finance.invoices.index', ['status' => 'due_soon']) }}" class="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold transition-colors">
                        Segera Tempo ({{ $totalDueSoonCount }})
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- 5 Summary Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3.5">
        <div class="bg-[#151922] p-4 rounded-2xl border border-slate-800 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-slate-400 uppercase font-semibold block truncate">Total Invoice Aktif</span>
                <div class="font-headline text-xl font-extrabold text-white mt-1 tabular-nums">{{ $totalIssuedCount }} <span class="text-xs font-normal text-slate-400">Inv</span></div>
                <span class="font-mono text-[10px] text-slate-500 block truncate">{{ $totalDraftCount }} berstatus draft</span>
            </div>
            <div class="p-2.5 rounded-xl bg-slate-800 text-slate-300 border border-slate-700 shrink-0">
                <span class="material-symbols-outlined text-xl">receipt_long</span>
            </div>
        </div>

        <div class="bg-[#151922] p-4 rounded-2xl border border-slate-800 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-slate-400 uppercase font-semibold block truncate">Sisa Tagihan (IDR)</span>
                <div class="font-headline text-[15px] xl:text-base font-extrabold text-sky-400 mt-1 tabular-nums whitespace-nowrap">Rp {{ number_format($totalOutstandingIdr, 0, ',', '.') }}</div>
                <span class="font-mono text-[10px] text-slate-500 block truncate">Outstanding menunggu bayar</span>
            </div>
            <div class="p-2.5 rounded-xl bg-sky-500/10 text-sky-400 border border-sky-500/20 shrink-0">
                <span class="material-symbols-outlined text-xl">hourglass_top</span>
            </div>
        </div>

        <div class="bg-[#151922] p-4 rounded-2xl border border-rose-500/30 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-rose-400 uppercase font-semibold block truncate">Jatuh Tempo (Overdue)</span>
                <div class="font-headline text-lg font-extrabold text-rose-400 mt-1 tabular-nums">{{ $totalOverdueCount }} <span class="text-xs font-normal">Inv</span></div>
                <span class="font-mono text-[10px] text-slate-400 block truncate tabular-nums">Rp {{ number_format($totalOverdueIdr, 0, ',', '.') }}</span>
            </div>
            <div class="p-2.5 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 shrink-0">
                <span class="material-symbols-outlined text-xl">event_busy</span>
            </div>
        </div>

        <div class="bg-[#151922] p-4 rounded-2xl border border-amber-500/30 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-amber-400 uppercase font-semibold block truncate">Segera Tempo (&le; 7 Hr)</span>
                <div class="font-headline text-xl font-extrabold text-amber-400 mt-1 tabular-nums">{{ $totalDueSoonCount }} <span class="text-xs font-normal">Inv</span></div>
                <span class="font-mono text-[10px] text-slate-500 block truncate">Perlu follow-up pembayaran</span>
            </div>
            <div class="p-2.5 rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 shrink-0">
                <span class="material-symbols-outlined text-xl">schedule</span>
            </div>
        </div>

        <div class="bg-[#151922] p-4 rounded-2xl border border-emerald-500/30 flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <span class="font-mono text-[10px] text-emerald-400 uppercase font-semibold block truncate">Total Terbayar (IDR)</span>
                <div class="font-headline text-[15px] xl:text-base font-extrabold text-emerald-400 mt-1 tabular-nums whitespace-nowrap">Rp {{ number_format($totalPaidIdr, 0, ',', '.') }}</div>
                <span class="font-mono text-[10px] text-slate-500 block truncate">Cicilan & pelunasan masuk</span>
            </div>
            <div class="p-2.5 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shrink-0">
                <span class="material-symbols-outlined text-xl">paid</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <form action="{{ route('finance.invoices.index') }}" method="GET" class="p-4 bg-[#151922] rounded-2xl border border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5 items-end">
        <div class="lg:col-span-2 space-y-1">
            <label class="block text-[10px] font-mono uppercase text-slate-400">Cari Nomor / Klien / Perihal</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Contoh: INV/FIN-GYK atau PT Asahi..." class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
        </div>

        <div class="space-y-1">
            <label class="block text-[10px] font-mono uppercase text-slate-400">Jenis Tagihan</label>
            <select name="type" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                <option value="">Semua Jenis</option>
                @foreach($types as $key => $lbl)
                    <option value="{{ $key }}" {{ $type === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-1">
            <label class="block text-[10px] font-mono uppercase text-slate-400">Status Pembayaran</label>
            <select name="status" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500">
                <option value="">Semua Status</option>
                @foreach($statuses as $key => $lbl)
                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-1">
            <label class="block text-[10px] font-mono uppercase text-slate-400">Bulan Terbit</label>
            <input type="month" name="month" value="{{ $month }}" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500 font-mono">
        </div>

        <div class="flex items-center gap-2">
            <button type="submit" class="flex-1 px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-colors flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-sm">filter_alt</span>
                <span>Filter</span>
            </button>
            @if($search || $status || $type || $currency || $month)
                <a href="{{ route('finance.invoices.index') }}" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold">
                    Reset
                </a>
            @endif
        </div>
    </form>

    <!-- Table Daftar Invoice -->
    <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 font-mono text-[11px] uppercase bg-slate-900/60">
                        <th class="px-4 py-3.5">Nomor & Perihal Invoice</th>
                        <th class="px-4 py-3.5">Klien / Vendor</th>
                        <th class="px-4 py-3.5">Terbit & Jatuh Tempo</th>
                        <th class="px-4 py-3.5">Nominal & Progres Bayar</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    @forelse($invoices as $inv)
                        @php
                            $compStatus = $inv->computed_status;
                            $daysDue = $inv->days_until_due;
                            $badgeClass = match($compStatus) {
                                'paid' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30',
                                'overdue' => 'bg-rose-500/15 text-rose-300 border-rose-500/30',
                                'partial' => 'bg-amber-500/15 text-amber-300 border-amber-500/30',
                                'issued' => 'bg-sky-500/15 text-sky-300 border-sky-500/30',
                                'draft' => 'bg-slate-700/60 text-slate-300 border-slate-600',
                                'cancelled' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
                                default => 'bg-slate-800 text-slate-300 border-slate-700',
                            };
                            $statusText = match($compStatus) {
                                'paid' => 'LUNAS (PAID)',
                                'overdue' => 'JATUH TEMPO',
                                'partial' => 'CICILAN',
                                'issued' => 'TERBIT',
                                'draft' => 'DRAFT',
                                'cancelled' => 'DIBATALKAN',
                                default => strtoupper($compStatus),
                            };
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('finance.invoices.show', $inv->id) }}" class="font-mono font-bold text-emerald-400 hover:underline">
                                        {{ $inv->invoice_number }}
                                    </a>
                                    <span class="text-[9px] font-mono px-1.5 py-0.5 rounded {{ $inv->invoice_type === 'payable' ? 'bg-purple-500/15 text-purple-300 border border-purple-500/25' : 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/25' }}">
                                        {{ $inv->invoice_type === 'payable' ? 'AP / Vendor' : 'AR / Klien' }}
                                    </span>
                                </div>
                                <div class="text-white font-semibold mt-0.5">{{ $inv->title }}</div>
                                @if($inv->contract)
                                    <div class="text-[10px] font-mono text-amber-400 mt-0.5">MoU: {{ $inv->contract->contract_number }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="font-semibold text-slate-200">{{ $inv->counterparty_name }}</div>
                                @if($inv->counterparty_phone || $inv->counterparty_email)
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">
                                        {{ $inv->counterparty_phone ?: $inv->counterparty_email }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 font-mono min-w-[168px] whitespace-nowrap">
                                <div class="text-[11px] text-slate-400">Terbit: <span class="text-slate-200">{{ $inv->issue_date->format('d M Y') }}</span></div>
                                <div class="text-[11px] mt-0.5">
                                    Tempo: <strong class="{{ $compStatus === 'overdue' ? 'text-rose-400' : 'text-white' }}">{{ $inv->due_date->format('d M Y') }}</strong>
                                </div>
                                @if(in_array($compStatus, ['issued', 'partial', 'overdue']))
                                    <div class="mt-1">
                                        @if($daysDue < 0)
                                            <span class="inline-flex items-center whitespace-nowrap text-[10px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold">
                                                Telat {{ abs($daysDue) }} hari
                                            </span>
                                        @elseif($daysDue === 0)
                                            <span class="inline-flex items-center whitespace-nowrap text-[10px] px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold">
                                                Jatuh tempo HARI INI
                                            </span>
                                        @elseif($daysDue <= 7)
                                            <span class="inline-flex items-center whitespace-nowrap text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold">
                                                Sisa {{ $daysDue }} hari lagi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center whitespace-nowrap text-[10px] text-slate-400">Sisa {{ $daysDue }} hari</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 font-mono min-w-[190px]">
                                <div class="font-bold text-white">{{ $inv->formatCurrency((float) $inv->total_amount) }}</div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                    <span>Terbayar: <span class="text-emerald-400">{{ $inv->formatCurrency((float) $inv->paid_amount) }}</span></span>
                                    <span>{{ $inv->payment_progress }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden mt-1">
                                    <div class="h-full {{ $compStatus === 'paid' ? 'bg-emerald-500' : ($compStatus === 'overdue' ? 'bg-rose-500' : 'bg-sky-500') }}" style="width: {{ $inv->payment_progress }}%"></div>
                                </div>
                                @if($inv->remaining_amount > 0 && $compStatus !== 'cancelled')
                                    <div class="text-[10px] text-amber-400 mt-0.5">Sisa: {{ $inv->formatCurrency($inv->remaining_amount) }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 rounded-full text-[10px] font-mono font-bold border {{ $badgeClass }}">
                                    {{ $statusText }}
                                </span>
                                @if($inv->paid_at)
                                    <div class="text-[10px] font-mono text-emerald-400 mt-1">Lunas {{ $inv->paid_at->format('d/m/y') }}</div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <a href="{{ route('finance.invoices.show', $inv->id) }}" title="Detail & Cetak Invoice" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition-colors">
                                        <span class="material-symbols-outlined text-base">print</span>
                                    </a>

                                    @if($inv->status === 'draft')
                                        <form action="{{ route('finance.invoices.issue', $inv->id) }}" method="POST" onsubmit="return confirm('Terbitkan resmi invoice {{ $inv->invoice_number }} sekarang?')">
                                            @csrf
                                            <button type="submit" title="Terbitkan Invoice Resmi" class="p-1.5 rounded-lg bg-sky-600/20 hover:bg-sky-600 text-sky-300 hover:text-white border border-sky-500/30 transition-colors">
                                                <span class="material-symbols-outlined text-base">send</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if(!in_array($inv->status, ['paid', 'cancelled']))
                                        <button type="button"
                                                @click="openPayment({{ $inv->id }}, '{{ addslashes($inv->invoice_number) }}', '{{ addslashes($inv->counterparty_name) }}', {{ $inv->remaining_amount }}, '{{ $inv->currency }}')"
                                                title="Catat Pembayaran / Pelunasan"
                                                class="px-2.5 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-[11px] font-bold flex items-center gap-1 transition-colors">
                                            <span class="material-symbols-outlined text-sm">payments</span>
                                            <span>Bayar</span>
                                        </button>

                                        <button type="button"
                                                @click='openEdit(@json($inv))'
                                                title="Edit Invoice"
                                                class="p-1.5 rounded-lg bg-slate-800 hover:bg-amber-600/30 text-amber-300 border border-slate-700 transition-colors">
                                            <span class="material-symbols-outlined text-base">edit</span>
                                        </button>

                                        <button type="button"
                                                @click="openCancel({{ $inv->id }}, '{{ addslashes($inv->invoice_number) }}')"
                                                title="Batalkan Invoice"
                                                class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-600/30 text-rose-300 border border-slate-700 transition-colors">
                                            <span class="material-symbols-outlined text-base">block</span>
                                        </button>
                                    @endif

                                    @if($authUser->canDeleteInvoices())
                                        <form action="{{ route('finance.invoices.destroy', $inv->id) }}" method="POST" onsubmit="return confirm('Hapus permanen invoice {{ $inv->invoice_number }} beserta riwayat pembayarannya?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus Invoice" class="p-1.5 rounded-lg bg-rose-950/50 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 transition-colors">
                                                <span class="material-symbols-outlined text-base">delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400 font-mono text-xs">
                                <span class="material-symbols-outlined text-4xl text-slate-600 block mb-2">receipt_long</span>
                                Belum ada data invoice yang sesuai filter. Klik <strong>Terbitkan Invoice Baru</strong> untuk membuat invoice pertama.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="p-4 border-t border-slate-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: TERBITKAN / BUAT INVOICE BARU (Compact 2-Kolom, No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-emerald-500/30 w-full max-w-5xl p-5 sm:p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showCreateModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-500/30">
                        <span class="material-symbols-outlined text-xl">receipt_long</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Terbitkan Invoice Pembayaran Baru</h3>
                        <p class="text-xs text-slate-400 font-mono">Isi data tagihan, jatuh tempo pembayaran, rincian item, dan pajak PPN</p>
                    </div>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('finance.invoices.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                <input type="hidden" name="action_mode" x-model="createForm.action_mode">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <!-- KOLOM KIRI: IDENTITAS INVOICE, KLIEN & JATUH TEMPO -->
                    <div class="space-y-3">
                        <div class="text-[11px] font-mono font-semibold uppercase text-emerald-400 tracking-wider flex items-center gap-1.5 pb-1 border-b border-slate-800">
                            <span class="material-symbols-outlined text-sm">badge</span>
                            <span>1. Identitas Tagihan & Jatuh Tempo</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                            <div class="sm:col-span-3 space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Nomor Invoice <span class="text-rose-400">*</span></label>
                                <input type="text" name="invoice_number" x-model="createForm.invoice_number" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white focus:outline-none focus:border-emerald-500 text-xs font-mono">
                            </div>
                            <div class="sm:col-span-2 space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Jenis Invoice <span class="text-rose-400">*</span></label>
                                <select name="invoice_type" x-model="createForm.invoice_type" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white focus:outline-none focus:border-emerald-500 text-xs">
                                    <option value="receivable">Tagihan Klien (AR)</option>
                                    <option value="payable">Tagihan Vendor (AP)</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Perihal / Judul Tagihan <span class="text-rose-400">*</span></label>
                            <input type="text" name="title" x-model="createForm.title" required placeholder="Contoh: Termin I Biaya Pemberangkatan Siswa Magang Batch Nov 2026" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white focus:outline-none focus:border-emerald-500 text-xs">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Nama Klien / Vendor <span class="text-rose-400">*</span></label>
                                <input type="text" name="counterparty_name" x-model="createForm.counterparty_name" required placeholder="PT / Kumiai / Vendor" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white focus:outline-none focus:border-emerald-500 text-xs font-semibold">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Terkait Kontrak / MoU</label>
                                <select name="client_contract_id" x-model="createForm.client_contract_id" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white focus:outline-none focus:border-emerald-500 text-xs">
                                    <option value="">-- Tidak Tertaut Kontrak --</option>
                                    @foreach($contracts as $c)
                                        <option value="{{ $c->id }}">{{ $c->contract_number }} - {{ $c->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">No. HP / WhatsApp</label>
                                <input type="text" name="counterparty_phone" x-model="createForm.counterparty_phone" placeholder="0812-xxxx-xxxx" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Email Tagihan</label>
                                <input type="email" name="counterparty_email" x-model="createForm.counterparty_email" placeholder="finance@klien.com" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Alamat Penagihan</label>
                            <input type="text" name="counterparty_address" x-model="createForm.counterparty_address" placeholder="Alamat lengkap perusahaan / klien" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                        </div>

                        <!-- Tanggal Terbit & Jatuh Tempo -->
                        <div class="p-3 bg-slate-900/70 rounded-xl border border-slate-800 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-mono uppercase text-amber-400 font-semibold">Jadwal Terbit & Jatuh Tempo</span>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="setDuePreset(createForm, 7)" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-[10px] font-mono text-slate-300">+7 Hr</button>
                                    <button type="button" @click="setDuePreset(createForm, 14)" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-[10px] font-mono text-slate-300">+14 Hr</button>
                                    <button type="button" @click="setDuePreset(createForm, 30)" class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-[10px] font-mono text-slate-300">+30 Hr</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                <div class="space-y-1">
                                    <label class="block text-[10px] text-slate-400">Tanggal Terbit <span class="text-rose-400">*</span></label>
                                    <input type="date" name="issue_date" x-model="createForm.issue_date" required class="w-full px-2.5 py-1.5 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                </div>
                                <div class="space-y-1">
                                    <label class="block text-[10px] text-slate-400">Jatuh Tempo <span class="text-rose-400">*</span></label>
                                    <input type="date" name="due_date" x-model="createForm.due_date" required class="w-full px-2.5 py-1.5 bg-slate-950 rounded-lg border border-amber-500/50 text-white text-xs font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Rekening Pembayaran -->
                        <div class="grid grid-cols-3 gap-2">
                            <div class="space-y-1">
                                <label class="block text-[10px] text-slate-400">Bank Tujuan</label>
                                <input type="text" name="bank_name" x-model="createForm.bank_name" placeholder="BCA / Mandiri" class="w-full px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] text-slate-400">No. Rekening</label>
                                <input type="text" name="bank_account_number" x-model="createForm.bank_account_number" placeholder="1234567890" class="w-full px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[10px] text-slate-400">Atas Nama</label>
                                <input type="text" name="bank_account_holder" x-model="createForm.bank_account_holder" placeholder="LPK Gyokai" class="w-full px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: RINCIAN ITEM, PAJAK, TOTAL & LAMPIRAN -->
                    <div class="space-y-3">
                        <div class="text-[11px] font-mono font-semibold uppercase text-emerald-400 tracking-wider flex items-center justify-between pb-1 border-b border-slate-800">
                            <span class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-sm">list_alt</span>
                                <span>2. Rincian Item & Kalkulasi Tagihan</span>
                            </span>
                            <button type="button" @click="addItem(createForm)" class="px-2 py-0.5 rounded bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white text-[10px] font-bold transition-colors">
                                + Tambah Baris
                            </button>
                        </div>

                        <!-- Dynamic Line Items -->
                        <div class="space-y-2 max-h-[210px] overflow-y-auto pr-1">
                            <template x-for="(item, idx) in createForm.items" :key="idx">
                                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'items['+idx+'][description]'" x-model="item.description" required placeholder="Deskripsi item / jasa / termin..." class="flex-1 px-2.5 py-1.5 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs">
                                        <button type="button" x-show="createForm.items.length > 1" @click="removeItem(createForm, idx)" class="p-1 text-rose-400 hover:text-rose-300" title="Hapus baris">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <div class="col-span-3">
                                            <input type="number" step="0.01" min="0.01" :name="'items['+idx+'][quantity]'" x-model="item.quantity" required placeholder="Qty" class="w-full px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                        </div>
                                        <div class="col-span-3">
                                            <input type="text" :name="'items['+idx+'][unit]'" x-model="item.unit" placeholder="paket/org" class="w-full px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs">
                                        </div>
                                        <div class="col-span-6">
                                            <input type="number" step="0.01" min="0" :name="'items['+idx+'][unit_price]'" x-model="item.unit_price" required placeholder="Harga Satuan" class="w-full px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Mata Uang, Diskon & Pajak -->
                        <div class="grid grid-cols-3 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Mata Uang</label>
                                <select name="currency" x-model="createForm.currency" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                    <option value="IDR">IDR (Rp)</option>
                                    <option value="JPY">JPY (¥)</option>
                                    <option value="USD">USD ($)</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Diskon Nominal</label>
                                <input type="number" step="0.01" min="0" name="discount_amount" x-model="createForm.discount_amount" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Pajak PPN (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="tax_percentage" x-model="createForm.tax_percentage" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                        </div>

                        <!-- Live Summary Box -->
                        <div class="p-3 bg-emerald-950/20 rounded-xl border border-emerald-500/30 font-mono text-xs space-y-1">
                            <div class="flex justify-between text-slate-400">
                                <span>Subtotal Item:</span>
                                <span x-text="formatMoney(calcSubtotal(createForm), createForm.currency)"></span>
                            </div>
                            <div class="flex justify-between text-slate-400" x-show="Number(createForm.tax_percentage) > 0">
                                <span x-text="'Pajak PPN (' + createForm.tax_percentage + '%):'"></span>
                                <span x-text="formatMoney(calcTax(createForm), createForm.currency)"></span>
                            </div>
                            <div class="flex justify-between text-white font-extrabold text-sm pt-1 border-t border-emerald-500/20">
                                <span>TOTAL TAGIHAN:</span>
                                <span class="text-emerald-400" x-text="formatMoney(calcTotal(createForm), createForm.currency)"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Lampiran PDF/Foto (Opsional)</label>
                                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-slate-800 file:text-white border border-slate-700 rounded-lg p-0.5 bg-slate-900">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Catatan Pembayaran</label>
                                <input type="text" name="notes" x-model="createForm.notes" placeholder="Mohon transfer sebelum jatuh tempo..." class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-end gap-2.5 pt-3.5 border-t border-slate-800 mt-4">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 rounded-lg">Batal</button>
                    <button type="submit" @click="createForm.action_mode = 'draft'" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-semibold text-xs rounded-lg">
                        Simpan Draft
                    </button>
                    <button type="submit" @click="createForm.action_mode = 'issue'" class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 text-white font-bold text-xs rounded-lg shadow-md flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Terbitkan Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: EDIT INVOICE (Compact 2-Kolom, No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-amber-500/30 w-full max-w-5xl p-5 sm:p-6 shadow-2xl relative max-h-[92vh] overflow-y-auto" @click.away="showEditModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-500/30">
                        <span class="material-symbols-outlined text-xl">edit_document</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Edit Data Invoice</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="editForm.invoice_number"></p>
                    </div>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/finance/invoices') }}/' + editForm.id" method="POST" enctype="multipart/form-data" class="mt-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                            <div class="sm:col-span-3 space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Nomor Invoice <span class="text-rose-400">*</span></label>
                                <input type="text" name="invoice_number" x-model="editForm.invoice_number" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="sm:col-span-2 space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Jenis Invoice <span class="text-rose-400">*</span></label>
                                <select name="invoice_type" x-model="editForm.invoice_type" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                                    <option value="receivable">Tagihan Klien (AR)</option>
                                    <option value="payable">Tagihan Vendor (AP)</option>
                                </select>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Perihal Tagihan <span class="text-rose-400">*</span></label>
                            <input type="text" name="title" x-model="editForm.title" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Nama Klien / Vendor <span class="text-rose-400">*</span></label>
                                <input type="text" name="counterparty_name" x-model="editForm.counterparty_name" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-semibold">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Terkait Kontrak / MoU</label>
                                <select name="client_contract_id" x-model="editForm.client_contract_id" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                                    <option value="">-- Tidak Tertaut Kontrak --</option>
                                    @foreach($contracts as $c)
                                        <option value="{{ $c->id }}">{{ $c->contract_number }} - {{ $c->client_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">No. HP / WA</label>
                                <input type="text" name="counterparty_phone" x-model="editForm.counterparty_phone" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Email</label>
                                <input type="email" name="counterparty_email" x-model="editForm.counterparty_email" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Alamat Penagihan</label>
                            <input type="text" name="counterparty_address" x-model="editForm.counterparty_address" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                        </div>

                        <div class="grid grid-cols-2 gap-2.5">
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Tanggal Terbit <span class="text-rose-400">*</span></label>
                                <input type="date" name="issue_date" x-model="editForm.issue_date" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            </div>
                            <div class="space-y-1">
                                <label class="block text-[11px] font-medium text-slate-300">Jatuh Tempo <span class="text-rose-400">*</span></label>
                                <input type="date" name="due_date" x-model="editForm.due_date" required class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-amber-500/50 text-white text-xs font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" name="bank_name" x-model="editForm.bank_name" placeholder="Bank" class="px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            <input type="text" name="bank_account_number" x-model="editForm.bank_account_number" placeholder="No. Rek" class="px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            <input type="text" name="bank_account_holder" x-model="editForm.bank_account_holder" placeholder="Atas Nama" class="px-2 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center justify-between pb-1 border-b border-slate-800">
                            <span class="text-[11px] font-mono font-semibold uppercase text-amber-400">Rincian Item Tagihan</span>
                            <button type="button" @click="addItem(editForm)" class="px-2 py-0.5 rounded bg-amber-600/20 hover:bg-amber-600 text-amber-300 hover:text-white text-[10px] font-bold">
                                + Tambah Baris
                            </button>
                        </div>

                        <div class="space-y-2 max-h-[210px] overflow-y-auto pr-1">
                            <template x-for="(item, idx) in editForm.items" :key="idx">
                                <div class="p-2.5 bg-slate-900/80 rounded-xl border border-slate-800 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'items['+idx+'][description]'" x-model="item.description" required class="flex-1 px-2.5 py-1.5 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs">
                                        <button type="button" x-show="editForm.items.length > 1" @click="removeItem(editForm, idx)" class="p-1 text-rose-400 hover:text-rose-300">
                                            <span class="material-symbols-outlined text-base">delete</span>
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-12 gap-2">
                                        <input type="number" step="0.01" min="0.01" :name="'items['+idx+'][quantity]'" x-model="item.quantity" required class="col-span-3 px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                        <input type="text" :name="'items['+idx+'][unit]'" x-model="item.unit" class="col-span-3 px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs">
                                        <input type="number" step="0.01" min="0" :name="'items['+idx+'][unit_price]'" x-model="item.unit_price" required class="col-span-6 px-2 py-1 bg-slate-950 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="grid grid-cols-3 gap-2.5">
                            <select name="currency" x-model="editForm.currency" class="px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                                <option value="IDR">IDR (Rp)</option>
                                <option value="JPY">JPY (¥)</option>
                                <option value="USD">USD ($)</option>
                            </select>
                            <input type="number" step="0.01" min="0" name="discount_amount" x-model="editForm.discount_amount" placeholder="Diskon" class="px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                            <input type="number" step="0.01" min="0" max="100" name="tax_percentage" x-model="editForm.tax_percentage" placeholder="PPN %" class="px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                        </div>

                        <div class="p-3 bg-amber-950/20 rounded-xl border border-amber-500/30 font-mono text-xs">
                            <div class="flex justify-between text-white font-bold">
                                <span>TOTAL TAGIHAN BARU:</span>
                                <span class="text-amber-400" x-text="formatMoney(calcTotal(editForm), editForm.currency)"></span>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Ganti Lampiran (Opsional)</label>
                            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-[11px] text-slate-400 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:bg-slate-800 file:text-white border border-slate-700 rounded-lg p-0.5 bg-slate-900">
                        </div>

                        <div class="space-y-1">
                            <label class="block text-[11px] font-medium text-slate-300">Catatan Invoice</label>
                            <textarea name="notes" rows="2" x-model="editForm.notes" class="w-full px-2.5 py-1.5 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3.5 border-t border-slate-800 mt-4">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold text-xs rounded-lg shadow-md">Perbarui Invoice</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: CATAT PEMBAYARAN / PELUNASAN INVOICE (No backdrop-blur) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-emerald-500/30 w-full max-w-lg p-6 shadow-2xl relative" @click.away="showPaymentModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-500/30">
                        <span class="material-symbols-outlined text-xl">payments</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Catat Pembayaran Invoice</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="paymentForm.invoice_number + ' • ' + paymentForm.counterparty_name"></p>
                    </div>
                </div>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/finance/invoices') }}/' + paymentForm.id + '/payments'" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3.5">
                @csrf

                <div class="p-3 rounded-xl bg-emerald-950/30 border border-emerald-500/30 flex items-center justify-between text-xs font-mono">
                    <div>
                        <span class="text-slate-400 block text-[10px]">Sisa Tagihan Belum Dibayar:</span>
                        <strong class="text-emerald-400 text-sm" x-text="formatMoney(paymentForm.remaining, paymentForm.currency)"></strong>
                    </div>
                    <button type="button" @click="paymentForm.amount = paymentForm.remaining" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold">
                        Isi Lunas 100%
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-300">Tanggal Bayar <span class="text-rose-400">*</span></label>
                        <input type="date" name="payment_date" x-model="paymentForm.payment_date" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-300">Nominal Dibayar <span class="text-rose-400">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model="paymentForm.amount" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-300">Metode Pembayaran <span class="text-rose-400">*</span></label>
                        <select name="payment_method" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                            @foreach($paymentMethods as $key => $lbl)
                                <option value="{{ $key }}">{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs font-medium text-slate-300">No. Referensi / Mutasi</label>
                        <input type="text" name="reference_number" placeholder="TRF-20261126..." class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-slate-300">Bukti Transfer / Kuitansi (PDF/JPG/PNG)</label>
                    <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-slate-800 file:text-white border border-slate-700 rounded-lg p-1 bg-slate-900">
                </div>

                <div class="space-y-1">
                    <label class="block text-xs font-medium text-slate-300">Catatan Pembayaran</label>
                    <textarea name="notes" rows="2" placeholder="Pembayaran termin / pelunasan penuh..." class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
                    <button type="button" @click="showPaymentModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs text-slate-200 rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow-md">Simpan Pembayaran</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 4: BATALKAN INVOICE (Mandatory Cancellation Reason) -->
    <!-- ========================================================================= -->
    <div x-cloak x-show="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-rose-500/30 w-full max-w-md p-6 shadow-2xl relative" @click.away="showCancelModal = false">
            <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-rose-950/60 text-rose-400 border border-rose-500/30">
                        <span class="material-symbols-outlined text-xl">block</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Batalkan Invoice</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="cancelForm.invoice_number"></p>
                    </div>
                </div>
                <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-white p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="'{{ url('/finance/invoices') }}/' + cancelForm.id + '/cancel'" method="POST" class="mt-4 space-y-3">
                @csrf
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-slate-300">Alasan Pembatalan Resmi <span class="text-rose-400">*</span></label>
                    <textarea name="cancel_reason" rows="3" required minlength="5" placeholder="Jelaskan alasan pembatalan invoice (misal: revisi kontrak atau salah nominal)..." class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs focus:outline-none focus:border-rose-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showCancelModal = false" class="px-4 py-2 bg-slate-800 text-xs text-slate-200 rounded-lg">Tutup</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-lg">Konfirmasi Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function invoiceManager() {
    const todayStr = '{{ now()->toDateString() }}';
    const plus14Str = '{{ now()->addDays(14)->toDateString() }}';

    return {
        showCreateModal: false,
        showEditModal: false,
        showPaymentModal: false,
        showCancelModal: false,

        createForm: {
            invoice_number: '{{ $nextInvoiceNumber }}',
            invoice_type: 'receivable',
            title: '',
            counterparty_name: '',
            counterparty_email: '',
            counterparty_phone: '',
            counterparty_address: '',
            client_contract_id: '',
            issue_date: todayStr,
            due_date: plus14Str,
            currency: 'IDR',
            discount_amount: 0,
            tax_percentage: 0,
            bank_name: '',
            bank_account_number: '',
            bank_account_holder: 'LPK Gyokai Indonesia',
            notes: '',
            action_mode: 'issue',
            items: [{ description: '', quantity: 1, unit: 'paket', unit_price: 0 }]
        },

        editForm: {
            id: null,
            invoice_number: '',
            invoice_type: 'receivable',
            title: '',
            counterparty_name: '',
            counterparty_email: '',
            counterparty_phone: '',
            counterparty_address: '',
            client_contract_id: '',
            issue_date: '',
            due_date: '',
            currency: 'IDR',
            discount_amount: 0,
            tax_percentage: 0,
            bank_name: '',
            bank_account_number: '',
            bank_account_holder: '',
            notes: '',
            items: [{ description: '', quantity: 1, unit: 'paket', unit_price: 0 }]
        },

        paymentForm: {
            id: null,
            invoice_number: '',
            counterparty_name: '',
            remaining: 0,
            amount: 0,
            currency: 'IDR',
            payment_date: todayStr
        },

        cancelForm: {
            id: null,
            invoice_number: ''
        },

        openCreate() {
            this.showCreateModal = true;
        },

        openEdit(inv) {
            this.editForm = {
                id: inv.id,
                invoice_number: inv.invoice_number,
                invoice_type: inv.invoice_type,
                title: inv.title,
                counterparty_name: inv.counterparty_name,
                counterparty_email: inv.counterparty_email || '',
                counterparty_phone: inv.counterparty_phone || '',
                counterparty_address: inv.counterparty_address || '',
                client_contract_id: inv.client_contract_id || '',
                issue_date: (inv.issue_date || '').substring(0, 10),
                due_date: (inv.due_date || '').substring(0, 10),
                currency: inv.currency || 'IDR',
                discount_amount: Number(inv.discount_amount || 0),
                tax_percentage: Number(inv.tax_percentage || 0),
                bank_name: inv.bank_name || '',
                bank_account_number: inv.bank_account_number || '',
                bank_account_holder: inv.bank_account_holder || '',
                notes: inv.notes || '',
                items: (inv.items && inv.items.length)
                    ? inv.items.map(i => ({
                        description: i.description,
                        quantity: Number(i.quantity),
                        unit: i.unit || 'unit',
                        unit_price: Number(i.unit_price)
                    }))
                    : [{ description: inv.title, quantity: 1, unit: 'paket', unit_price: Number(inv.subtotal || 0) }]
            };
            this.showEditModal = true;
        },

        openPayment(id, number, counterparty, remaining, currency) {
            this.paymentForm = {
                id,
                invoice_number: number,
                counterparty_name: counterparty,
                remaining: Number(remaining || 0),
                amount: Number(remaining || 0),
                currency: currency || 'IDR',
                payment_date: todayStr
            };
            this.showPaymentModal = true;
        },

        openCancel(id, number) {
            this.cancelForm = { id, invoice_number: number };
            this.showCancelModal = true;
        },

        addItem(form) {
            form.items.push({ description: '', quantity: 1, unit: 'unit', unit_price: 0 });
        },

        removeItem(form, idx) {
            if (form.items.length > 1) {
                form.items.splice(idx, 1);
            }
        },

        setDuePreset(form, days) {
            const base = form.issue_date ? new Date(form.issue_date) : new Date();
            base.setDate(base.getDate() + days);
            form.due_date = base.toISOString().substring(0, 10);
        },

        calcSubtotal(form) {
            return form.items.reduce((sum, i) => sum + (Number(i.quantity || 0) * Number(i.unit_price || 0)), 0);
        },

        calcTax(form) {
            const base = Math.max(0, this.calcSubtotal(form) - Number(form.discount_amount || 0));
            return base * (Number(form.tax_percentage || 0) / 100);
        },

        calcTotal(form) {
            const base = Math.max(0, this.calcSubtotal(form) - Number(form.discount_amount || 0));
            return base + this.calcTax(form);
        },

        formatMoney(val, currency) {
            const num = Number(val || 0);
            if (currency === 'JPY') return '¥ ' + Math.round(num).toLocaleString('id-ID');
            if (currency === 'USD') return '$ ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return 'Rp ' + Math.round(num).toLocaleString('id-ID');
        }
    };
}
</script>
@endsection
