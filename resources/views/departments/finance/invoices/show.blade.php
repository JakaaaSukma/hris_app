@extends('layouts.app')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('content')
@php
    $compStatus = $invoice->computed_status;
    $statusBadge = match($compStatus) {
        'paid' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30 print:text-emerald-700 print:border-emerald-700',
        'overdue' => 'bg-rose-500/15 text-rose-400 border-rose-500/30 print:text-rose-700 print:border-rose-700',
        'partial' => 'bg-amber-500/15 text-amber-400 border-amber-500/30 print:text-amber-700 print:border-amber-700',
        'issued' => 'bg-sky-500/15 text-sky-400 border-sky-500/30 print:text-sky-700 print:border-sky-700',
        'draft' => 'bg-slate-700 text-slate-300 border-slate-600',
        'cancelled' => 'bg-zinc-800 text-zinc-400 border-zinc-700',
        default => 'bg-slate-800 text-slate-300 border-slate-700',
    };
    $statusLabel = match($compStatus) {
        'paid' => 'LUNAS / PAID',
        'overdue' => 'JATUH TEMPO / OVERDUE',
        'partial' => 'DIBAYAR SEBAGIAN',
        'issued' => 'MENUNGGU PEMBAYARAN',
        'draft' => 'DRAFT INVOICE',
        'cancelled' => 'DIBATALKAN',
        default => strtoupper($compStatus),
    };
@endphp

<div class="flex flex-col gap-5 max-w-4xl mx-auto" x-data="{ showPayModal: false, payAmount: {{ $invoice->remaining_amount }} }">

    <!-- Action Bar (Print Hidden) -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 editorial-surface rounded-xl print:hidden" data-reveal>
        <x-skiper-link href="{{ route('finance.invoices.index') }}" variant="000" class="text-xs font-semibold text-[var(--color-text-muted)] hover:text-[var(--color-text-primary)]">
            Kembali ke Daftar Invoice
        </x-skiper-link>

        <div class="flex items-center gap-2 flex-wrap">
            @if($invoice->attachment_path)
                <a href="{{ route('finance.invoices.downloadAttachment', $invoice->id) }}" target="_blank" class="flex items-center gap-1.5 px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold border border-slate-700">
                    <span class="material-symbols-outlined text-base text-sky-400">attach_file</span>
                    <span>Lampiran Asli</span>
                </a>
            @endif

            @if($invoice->status === 'draft')
                <form action="{{ route('finance.invoices.issue', $invoice->id) }}" method="POST" onsubmit="return confirm('Terbitkan resmi invoice {{ $invoice->invoice_number }}?')">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 px-3.5 py-2 bg-sky-600 hover:bg-sky-500 text-white rounded-xl text-xs font-bold shadow-md">
                        <span class="material-symbols-outlined text-base">send</span>
                        <span>Terbitkan Invoice</span>
                    </button>
                </form>
            @endif

            @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <button type="button" @click="showPayModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold shadow-md">
                    <span class="material-symbols-outlined text-base">payments</span>
                    <span>Catat Pembayaran</span>
                </button>
            @endif

            <button type="button" onclick="window.print()" class="flex items-center gap-1.5 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak / PDF</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-medium print:hidden">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-medium print:hidden">
            {{ session('error') }}
        </div>
    @endif

    <!-- Official A4 Invoice Document -->
    <div class="bg-[#121620] text-slate-200 p-7 sm:p-10 rounded-2xl border border-slate-800 shadow-2xl print:bg-white print:text-black print:border-none print:shadow-none print:p-0">

        <!-- Header Perusahaan & Nomor Invoice -->
        <div class="flex flex-col sm:flex-row sm:items-start justify-between pb-6 border-b border-slate-800 print:border-gray-300 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/5 border border-slate-700/80 p-1.5 flex items-center justify-center print:border-gray-300">
                    <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
                </div>
                <div>
                    <h1 class="font-headline font-extrabold text-xl text-white print:text-black tracking-tight">LPK GYOKAI INDONESIA</h1>
                    <p class="font-mono text-[11px] text-slate-400 print:text-gray-600">Finance & Accounting Division</p>
                    <p class="font-mono text-[10px] text-slate-500 print:text-gray-500">Jl. Pesona Wibawa Praja, Cisoka, Kab. Tangerang</p>
                </div>
            </div>

            <div class="text-left sm:text-right font-mono">
                <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold border uppercase {{ $statusBadge }}">
                    {{ $statusLabel }}
                </span>
                <div class="text-lg font-extrabold text-white print:text-black mt-2">{{ $invoice->invoice_number }}</div>
                <div class="text-[11px] text-slate-400 print:text-gray-600">{{ $invoice->type_label }}</div>
            </div>
        </div>

        <!-- Bill To & Metadata Tanggal -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-800 print:border-gray-300 text-xs">
            <div class="space-y-1">
                <div class="text-[10px] font-mono uppercase tracking-wider text-slate-500 print:text-gray-500 font-bold">
                    {{ $invoice->invoice_type === 'payable' ? 'Tagihan Dari Vendor / Mitra:' : 'Ditagihkan Kepada (Bill To):' }}
                </div>
                <div class="font-bold text-sm text-white print:text-black">{{ $invoice->counterparty_name }}</div>
                @if($invoice->counterparty_address)
                    <div class="text-slate-300 print:text-gray-700">{{ $invoice->counterparty_address }}</div>
                @endif
                @if($invoice->counterparty_phone)
                    <div class="font-mono text-slate-400 print:text-gray-600">Telp/WA: {{ $invoice->counterparty_phone }}</div>
                @endif
                @if($invoice->counterparty_email)
                    <div class="font-mono text-slate-400 print:text-gray-600">Email: {{ $invoice->counterparty_email }}</div>
                @endif
                @if($invoice->contract)
                    <div class="font-mono text-amber-400 print:text-gray-700 pt-1">Ref. Kontrak/MoU: {{ $invoice->contract->contract_number }}</div>
                @endif
            </div>

            <div class="space-y-1.5 font-mono sm:text-right">
                <div class="text-[10px] uppercase tracking-wider text-slate-500 print:text-gray-500 font-bold">Informasi Tanggal & Perihal</div>
                <div><span class="text-slate-400 print:text-gray-600">Perihal:</span> <strong class="text-white print:text-black">{{ $invoice->title }}</strong></div>
                <div><span class="text-slate-400 print:text-gray-600">Tanggal Terbit:</span> <strong class="text-white print:text-black">{{ $invoice->issue_date->translatedFormat('d F Y') }}</strong></div>
                <div>
                    <span class="text-slate-400 print:text-gray-600">Jatuh Tempo:</span>
                    <strong class="{{ $compStatus === 'overdue' ? 'text-rose-400 print:text-rose-700' : 'text-amber-400 print:text-black' }}">
                        {{ $invoice->due_date->translatedFormat('d F Y') }}
                    </strong>
                </div>
                @if($invoice->paid_at)
                    <div><span class="text-slate-400 print:text-gray-600">Tanggal Lunas:</span> <strong class="text-emerald-400 print:text-emerald-700">{{ $invoice->paid_at->translatedFormat('d F Y') }}</strong></div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="py-6 border-b border-slate-800 print:border-gray-300">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-800 print:border-gray-300 text-slate-400 print:text-gray-600 font-mono text-[10px] uppercase">
                        <th class="py-2.5 w-10">No</th>
                        <th class="py-2.5">Deskripsi Item / Jasa</th>
                        <th class="py-2.5 text-right">Qty</th>
                        <th class="py-2.5 text-right">Harga Satuan</th>
                        <th class="py-2.5 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 print:divide-gray-200">
                    @foreach($invoice->items as $idx => $item)
                        <tr>
                            <td class="py-3 font-mono text-slate-400 print:text-gray-600">{{ $idx + 1 }}</td>
                            <td class="py-3 font-medium text-white print:text-black">{{ $item->description }}</td>
                            <td class="py-3 text-right font-mono text-slate-300 print:text-gray-700">
                                {{ rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.') }} {{ $item->unit }}
                            </td>
                            <td class="py-3 text-right font-mono text-slate-300 print:text-gray-700">{{ $invoice->formatCurrency((float) $item->unit_price) }}</td>
                            <td class="py-3 text-right font-mono font-bold text-white print:text-black">{{ $invoice->formatCurrency((float) $item->total_price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary & Bank Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 text-xs">
            <div class="space-y-3">
                @if($invoice->bank_name || $invoice->bank_account_number)
                    <div class="p-3.5 rounded-xl bg-slate-900/70 border border-slate-800 print:bg-gray-50 print:border-gray-300 space-y-1 font-mono">
                        <div class="text-[10px] uppercase text-emerald-400 print:text-gray-700 font-bold">Instruksi Rekening Pembayaran</div>
                        <div class="text-white print:text-black font-bold">{{ $invoice->bank_name }} • {{ $invoice->bank_account_number }}</div>
                        @if($invoice->bank_account_holder)
                            <div class="text-slate-400 print:text-gray-600">a.n. {{ $invoice->bank_account_holder }}</div>
                        @endif
                    </div>
                @endif

                @if($invoice->notes)
                    <div class="text-xs text-slate-300 print:text-gray-700">
                        <span class="font-mono text-[10px] uppercase text-slate-500 block font-bold mb-1">Catatan & Syarat Pembayaran:</span>
                        <p class="whitespace-pre-line">{{ $invoice->notes }}</p>
                    </div>
                @endif

                @if($invoice->status === 'cancelled' && $invoice->cancel_reason)
                    <div class="p-3 rounded-xl bg-rose-950/30 border border-rose-500/30 text-rose-300 print:text-rose-700">
                        <strong class="block text-[10px] font-mono uppercase">Alasan Pembatalan:</strong>
                        <span>{{ $invoice->cancel_reason }}</span>
                    </div>
                @endif
            </div>

            <div class="space-y-2 font-mono">
                <div class="flex justify-between text-slate-400 print:text-gray-600">
                    <span>Subtotal:</span>
                    <span class="text-white print:text-black">{{ $invoice->formatCurrency((float) $invoice->subtotal) }}</span>
                </div>
                @if((float) $invoice->discount_amount > 0)
                    <div class="flex justify-between text-slate-400 print:text-gray-600">
                        <span>Diskon:</span>
                        <span class="text-rose-400 print:text-rose-700">- {{ $invoice->formatCurrency((float) $invoice->discount_amount) }}</span>
                    </div>
                @endif
                @if((float) $invoice->tax_amount > 0)
                    <div class="flex justify-between text-slate-400 print:text-gray-600">
                        <span>Pajak PPN ({{ rtrim(rtrim((string) $invoice->tax_percentage, '0'), '.') }}%):</span>
                        <span class="text-white print:text-black">{{ $invoice->formatCurrency((float) $invoice->tax_amount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-sm font-extrabold pt-2 border-t border-slate-800 print:border-gray-300 text-white print:text-black">
                    <span>TOTAL INVOICE:</span>
                    <span>{{ $invoice->formatCurrency((float) $invoice->total_amount) }}</span>
                </div>
                <div class="flex justify-between text-emerald-400 print:text-emerald-700">
                    <span>Sudah Dibayar:</span>
                    <span>{{ $invoice->formatCurrency((float) $invoice->paid_amount) }}</span>
                </div>
                <div class="flex justify-between text-sm font-extrabold pt-2 border-t border-slate-800 print:border-gray-300 {{ $invoice->remaining_amount > 0 ? 'text-amber-400 print:text-black' : 'text-emerald-400 print:text-emerald-700' }}">
                    <span>SISA TAGIHAN:</span>
                    <span>{{ $invoice->formatCurrency($invoice->remaining_amount) }}</span>
                </div>
            </div>
        </div>

        <!-- Riwayat Pembayaran / Cicilan -->
        @if($invoice->payments->isNotEmpty())
            <div class="pt-5 border-t border-slate-800 print:border-gray-300">
                <h4 class="font-mono text-[11px] uppercase text-emerald-400 print:text-gray-700 font-bold mb-3">Riwayat Pembayaran Tercatat</h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead>
                            <tr class="border-b border-slate-800 print:border-gray-300 text-slate-400 print:text-gray-600 text-[10px] uppercase">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Metode</th>
                                <th class="py-2">No. Referensi</th>
                                <th class="py-2">Dicatat Oleh</th>
                                <th class="py-2 text-right">Nominal</th>
                                <th class="py-2 text-right print:hidden">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 print:divide-gray-200">
                            @foreach($invoice->payments as $pay)
                                <tr>
                                    <td class="py-2.5 text-white print:text-black">{{ $pay->payment_date->format('d M Y') }}</td>
                                    <td class="py-2.5 text-slate-300 print:text-gray-700">{{ $pay->payment_method }}</td>
                                    <td class="py-2.5 text-slate-400 print:text-gray-600">{{ $pay->reference_number ?: '-' }}</td>
                                    <td class="py-2.5 text-slate-400 print:text-gray-600">{{ $pay->recorder->name ?? 'Finance' }}</td>
                                    <td class="py-2.5 text-right font-bold text-emerald-400 print:text-emerald-700">{{ $invoice->formatCurrency((float) $pay->amount) }}</td>
                                    <td class="py-2.5 text-right print:hidden">
                                        @if($pay->proof_file_path)
                                            <a href="{{ route('finance.invoices.downloadPaymentProof', $pay->id) }}" target="_blank" class="text-sky-400 hover:underline text-[11px]">Lihat Bukti</a>
                                        @else
                                            <span class="text-slate-600">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Footer Tanda Tangan -->
        <div class="mt-8 pt-6 border-t border-slate-800 print:border-gray-300 flex items-end justify-between text-xs font-mono">
            <div class="text-slate-500 print:text-gray-600">
                <div>Dibuat oleh: {{ $invoice->creator->name ?? 'Tim Finance' }}</div>
                @if($invoice->issuer)
                    <div>Diterbitkan resmi oleh: {{ $invoice->issuer->name }} ({{ optional($invoice->issued_at)->format('d M Y H:i') }})</div>
                @endif
            </div>
            <div class="text-center min-w-[180px]">
                <div class="text-slate-400 print:text-gray-600 mb-12">Hormat Kami,</div>
                <div class="font-bold text-white print:text-black border-t border-slate-700 print:border-gray-400 pt-1">Finance Division • LPK Gyokai Indonesia</div>
            </div>
        </div>
    </div>

    <!-- Modal Catat Pembayaran (Print Hidden, No backdrop-blur) -->
    @if(!in_array($invoice->status, ['paid', 'cancelled']))
        <div x-cloak x-show="showPayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 print:hidden">
            <div class="bg-[#151922] rounded-2xl border border-emerald-500/30 w-full max-w-lg p-6 shadow-2xl relative" @click.away="showPayModal = false">
                <div class="flex items-start justify-between pb-3 border-b border-slate-800">
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Catat Pembayaran {{ $invoice->invoice_number }}</h3>
                        <p class="text-xs text-slate-400 font-mono">Sisa tagihan: {{ $invoice->formatCurrency($invoice->remaining_amount) }}</p>
                    </div>
                    <button type="button" @click="showPayModal = false" class="text-slate-400 hover:text-white p-1">
                        <span class="material-symbols-outlined text-xl">close</span>
                    </button>
                </div>

                <form action="{{ route('finance.invoices.recordPayment', $invoice->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs text-slate-300">Tanggal Bayar *</label>
                            <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs text-slate-300">Nominal Dibayar *</label>
                            <input type="number" step="0.01" min="0.01" name="amount" x-model="payAmount" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-xs text-slate-300">Metode *</label>
                            <select name="payment_method" required class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs">
                                @foreach($paymentMethods as $key => $lbl)
                                    <option value="{{ $key }}">{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="block text-xs text-slate-300">No. Referensi</label>
                            <input type="text" name="reference_number" class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs font-mono">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs text-slate-300">Bukti Transfer (PDF/JPG/PNG)</label>
                        <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:bg-slate-800 file:text-white border border-slate-700 rounded-lg p-1 bg-slate-900">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-xs text-slate-300">Catatan</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 bg-slate-900 rounded-lg border border-slate-700 text-white text-xs"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showPayModal = false" class="px-4 py-2 bg-slate-800 text-xs text-slate-200 rounded-lg">Batal</button>
                        <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg">Simpan Pembayaran</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
