@extends('layouts.app')

@section('title', 'Slip Gaji - ' . $slip->slip_number)

@section('content')
<div class="flex flex-col gap-6 max-w-4xl mx-auto">

    <!-- Action Bar (Kembali & Print) -->
    <div class="flex items-center justify-between p-4 bg-[#151922] rounded-xl border border-slate-800 print:hidden">
        @if($authUser->isFinance() || $authUser->isDirektur() || $authUser->isManager())
            <a href="{{ route('finance.index', ['tab' => 'slips']) }}" class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                <span>Kembali ke Laporan Finance</span>
            </a>
        @else
            <button onclick="window.history.back()" class="flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                <span>Kembali</span>
            </button>
        @endif

        <div class="flex items-center gap-2">
            @if($authUser->canDeleteSalarySlips())
                <form action="{{ route('finance.salarySlips.destroy', $slip->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus slip gaji {{ $slip->slip_number }} ini? Data yang terhapus tidak dapat dikembalikan.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center gap-1.5 px-3.5 py-2 bg-rose-500/15 hover:bg-rose-600 text-rose-300 hover:text-white rounded-xl text-xs font-bold border border-rose-500/30 transition-all shadow-sm active:scale-95">
                        <span class="material-symbols-outlined text-base">delete</span>
                        <span>Hapus Slip Gaji</span>
                    </button>
                </form>
            @endif
            <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Salary Slip Document Paper -->
    <div class="bg-[#121620] text-slate-200 p-8 sm:p-10 rounded-2xl border border-slate-800 shadow-2xl relative overflow-hidden print:bg-white print:text-black print:border-none print:shadow-none print:p-0">
        
        <!-- Header Perusahaan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-800 print:border-gray-300 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-white/5 border border-slate-700/80 p-1.5 flex items-center justify-center shadow-lg print:border-none">
                    <img src="{{ asset('images/gyokai_logo.png') }}" alt="Gyokai Logo" class="w-full h-full object-contain"/>
                </div>
                <div>
                    <h1 class="font-headline font-extrabold text-xl text-white print:text-black tracking-tight">PT. GYOKAI INDONESIA</h1>
                    <p class="font-mono text-xs text-slate-400 print:text-gray-600">Financial & Human Capital Division • Jakarta HQ</p>
                </div>
            </div>

            <div class="text-left sm:text-right font-mono text-xs">
                <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 uppercase font-bold text-[10px]">
                    STATUS: {{ strtoupper($slip->status) }}
                </span>
                <div class="font-bold text-sm text-slate-300 print:text-black mt-1">{{ $slip->slip_number }}</div>
                <div class="text-slate-400 print:text-gray-600">Periode: {{ $slip->period_month }}</div>
            </div>
        </div>

        <!-- Detail Karyawan & Pembayaran -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-800 print:border-gray-300 text-xs">
            <div class="space-y-1.5 font-mono">
                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Informasi Karyawan / Penerima</div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Nama Karyawan:</span>
                    <strong class="text-white print:text-black">{{ $slip->employee->name }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">NIP / ID:</span>
                    <strong class="text-white print:text-black">{{ $slip->employee->nip }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Jabatan:</span>
                    <strong class="text-rose-400 print:text-black">{{ $slip->employee->role_title }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Departemen:</span>
                    <strong class="text-white print:text-black">{{ $slip->employee->department }}</strong>
                </div>
            </div>

            <div class="space-y-1.5 font-mono">
                <div class="text-[10px] uppercase tracking-wider text-slate-500 font-bold mb-1">Detail Pembayaran & Tanggal</div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Tanggal Penggajian:</span>
                    <strong class="text-white print:text-black">{{ $slip->payment_date ? $slip->payment_date->translatedFormat('d F Y') : '-' }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Metode Bayar:</span>
                    <strong class="text-white print:text-black">{{ $slip->payment_method }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400 print:text-gray-600">Rekening Bank:</span>
                    <strong class="text-white print:text-black">{{ $slip->bank_name ?? 'BCA' }} - {{ $slip->bank_account_number ?? '8820-XXXX-XX' }}</strong>
                </div>
            </div>
        </div>

        <!-- 2 Kolom: Pendapatan (Earnings) vs Potongan (Deductions) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 py-6">
            <!-- Kolom Pendapatan -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-emerald-500/30">
                    <span class="font-headline font-bold text-sm text-emerald-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">add_circle</span>
                        PENDAPATAN (EARNINGS)
                    </span>
                    <span class="font-mono text-xs text-slate-400">Nominal (Rp)</span>
                </div>

                <div class="space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">Gaji Pokok (Basic Salary)</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->basic_salary, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">Tunjangan Jabatan</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->allowance_position, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">Tunjangan Transportasi</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->allowance_transport, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">Tunjangan Makan</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->allowance_meal, 0, ',', '.') }}</span>
                    </div>
                    @if($slip->overtime_pay > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-300 print:text-gray-700">Upah Lembur</span>
                            <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->overtime_pay, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($slip->bonus > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-300 print:text-gray-700">Bonus & Insentif Kerja</span>
                            <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->bonus, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-800 print:border-gray-300 flex items-center justify-between font-mono text-xs font-bold text-emerald-400 print:text-black">
                    <span>TOTAL PENDAPATAN (A):</span>
                    <span>Rp {{ number_format($slip->total_earnings, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Kolom Potongan -->
            <div class="space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-rose-500/30">
                    <span class="font-headline font-bold text-sm text-rose-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">remove_circle</span>
                        POTONGAN (DEDUCTIONS)
                    </span>
                    <span class="font-mono text-xs text-slate-400">Nominal (Rp)</span>
                </div>

                <div class="space-y-2 text-xs font-mono">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">BPJS Ketenagakerjaan (3%)</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->deduction_bpjs_tk, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">BPJS Kesehatan (1%)</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->deduction_bpjs_kes, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 print:text-gray-700">Pajak Penghasilan (PPh 21)</span>
                        <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->deduction_tax_pph21, 0, ',', '.') }}</span>
                    </div>
                    @if($slip->deduction_absence > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-300 print:text-gray-700">Potongan Terlambat / Absensi</span>
                            <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->deduction_absence, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    @if($slip->deduction_other > 0)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-300 print:text-gray-700">Potongan Lainnya</span>
                            <span class="font-bold text-white print:text-black">Rp {{ number_format($slip->deduction_other, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-slate-800 print:border-gray-300 flex items-center justify-between font-mono text-xs font-bold text-rose-400 print:text-black">
                    <span>TOTAL POTONGAN (B):</span>
                    <span>Rp {{ number_format($slip->total_deductions, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Net Take Home Pay Highlight -->
        <div class="mt-4 p-5 rounded-xl bg-gradient-to-r from-emerald-950/40 via-slate-900 to-emerald-950/40 border border-emerald-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3 print:bg-gray-100 print:border-gray-400">
            <div>
                <span class="font-mono text-xs uppercase tracking-wider text-slate-400 print:text-gray-700">GAJI BERSIH DITERIMA (TAKE HOME PAY = A - B)</span>
                <p class="font-mono text-[11px] text-slate-500 print:text-gray-600">Telah ditransfer ke rekening penerima tanpa potongan tambahan</p>
            </div>
            <div class="font-headline font-extrabold text-2xl lg:text-3xl text-emerald-400 print:text-black">
                Rp {{ number_format($slip->net_salary, 0, ',', '.') }}
            </div>
        </div>

        @if($slip->notes)
            <div class="mt-4 p-3 bg-slate-900/60 rounded-xl border border-slate-800 text-xs font-mono text-slate-400">
                <span class="font-bold text-slate-300">Catatan Finance:</span> {{ $slip->notes }}
            </div>
        @endif

        <!-- Tanda Tangan Digital & Approval Slip -->
        <div class="grid grid-cols-2 gap-8 pt-10 mt-8 border-t border-slate-800 print:border-gray-300 text-center font-mono text-xs">
            <div class="flex flex-col items-center">
                <span class="text-slate-400 print:text-gray-600 mb-16">Penerima Gaji (Karyawan/Manager)</span>
                <strong class="text-white print:text-black border-b border-slate-700 pb-1 w-48">{{ $slip->employee->name }}</strong>
                <span class="text-[10px] text-slate-500 print:text-gray-600 mt-1">NIP: {{ $slip->employee->nip }}</span>
            </div>

            <div class="flex flex-col items-center">
                <span class="text-slate-400 print:text-gray-600 mb-16">Departemen Finance & Payroll</span>
                <strong class="text-emerald-400 print:text-black border-b border-slate-700 pb-1 w-48">Bambang S. (Finance Mgr)</strong>
                <span class="text-[10px] text-slate-500 print:text-gray-600 mt-1">GYOKAI FINANCIAL SYSTEM VERIFIED</span>
            </div>
        </div>

    </div>

</div>

@push('styles')
<style>
@media print {
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    aside, header, nav, .print\:hidden {
        display: none !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }
    .print\:bg-white {
        background-color: #ffffff !important;
    }
    .print\:text-black {
        color: #000000 !important;
    }
}
</style>
@endpush
@endsection
