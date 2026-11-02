@extends('layouts.app')

@section('title', 'Laporan Sesi Pengiriman Slip Gaji - Finance')

@section('content')
<div class="flex flex-col gap-6">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Audit Log Dispatch • Kode Sesi: {{ $sendSession->session_code }}</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-cyan-400 text-2xl">receipt_long</span>
                <span>Laporan Detail Sesi Pengiriman Slip Gaji</span>
            </h1>
            <p class="font-mono text-xs text-slate-400 mt-0.5">
                Rincian pengiriman per file slip gaji, akun SMTP yang digunakan, status pengiriman, dan kendala/alasan jika terjadi kegagalan.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($sendSession->status === 'processing')
                <a href="{{ route('finance.dispatch.sending', ['send_session_id' => $sendSession->id]) }}" class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-base">send</span>
                    <span>Lanjutkan Pengiriman</span>
                </a>
            @endif

            <a href="{{ route('finance.dispatch.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700 shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base text-slate-400">arrow_back</span>
                <span>Kembali ke Dispatch</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl flex items-center gap-3 text-sm">
            <span class="material-symbols-outlined text-xl">check_circle</span>
            <div class="flex-1 font-medium">{{ session('success') }}</div>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl flex items-center gap-3 text-sm">
            <span class="material-symbols-outlined text-xl">warning</span>
            <div class="flex-1 font-medium">{{ session('warning') }}</div>
        </div>
    @endif

    <!-- Metric Summary Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
        <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Waktu Mulai</span>
            <div class="font-mono text-sm font-bold text-slate-200 mt-2">
                {{ $sendSession->started_at ? $sendSession->started_at->format('d M Y H:i') : '-' }}
            </div>
            <span class="font-mono text-[10px] text-slate-500 mt-1">
                Selesai: {{ $sendSession->finished_at ? $sendSession->finished_at->format('H:i') : '-' }}
            </span>
        </div>

        <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Total Berkas</span>
            <div class="font-headline text-2xl font-extrabold text-white mt-1">
                {{ $sendSession->total_files }} <span class="text-xs text-slate-400 font-normal">File</span>
            </div>
            <span class="font-mono text-[10px] text-slate-500 mt-1">100% diproses</span>
        </div>

        <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Terkirim Berhasil</span>
            <div class="font-headline text-2xl font-extrabold text-emerald-400 mt-1">
                {{ $sendSession->success_count }}
            </div>
            <span class="font-mono text-[10px] text-slate-500 mt-1">Email sukses diterima</span>
        </div>

        <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
            <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Gagal Dikirim</span>
            <div class="font-headline text-2xl font-extrabold text-rose-400 mt-1">
                {{ $sendSession->failed_count }}
            </div>
            <span class="font-mono text-[10px] text-slate-500 mt-1">Periksa alasan gagal</span>
        </div>

        <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col justify-between col-span-2 sm:col-span-1">
            <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Status Akhir</span>
            <div class="mt-2">
                @if($sendSession->status === 'completed')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Selesai Penuh</span>
                @elseif($sendSession->status === 'completed_with_errors')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">Sebagian Gagal</span>
                @elseif($sendSession->status === 'cancelled')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-700 text-slate-300 border border-slate-600">Dibatalkan</span>
                @elseif($sendSession->status === 'failed')
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Gagal Total</span>
                @else
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">Sedang Proses</span>
                @endif
            </div>
            <span class="font-mono text-[10px] text-slate-500 mt-1">Audit status</span>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-slate-400 text-xl">list_alt</span>
                <h3 class="font-headline font-bold text-sm text-white">Rincian Dokumen Slip Gaji yang Diproses</h3>
            </div>
            <span class="font-mono text-[10px] text-slate-400">Total: {{ $logs->total() }} Log</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 bg-[#11151f] text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                        <th class="p-3.5">Waktu Kirim</th>
                        <th class="p-3.5">Mailer SMTP</th>
                        <th class="p-3.5">Nama File Lampiran</th>
                        <th class="p-3.5">Periode</th>
                        <th class="p-3.5">Penerima Email</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5">Keterangan / Error Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="p-3.5 text-slate-400 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="p-3.5 font-sans">
                                <div class="font-semibold text-slate-200">{{ $log->smtp_mailer_name ?? '-' }}</div>
                                <div class="font-mono text-[10px] text-slate-500">{{ $log->smtp_mailer_address ?? '-' }}</div>
                            </td>
                            <td class="p-3.5 font-sans text-white max-w-xs truncate" title="{{ $log->filename }}">
                                <div class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-rose-400 text-base">picture_as_pdf</span>
                                    <span>{{ $log->filename }}</span>
                                </div>
                            </td>
                            <td class="p-3.5 text-slate-300 whitespace-nowrap">
                                {{ $log->period ?? '-' }}
                            </td>
                            <td class="p-3.5 font-sans">
                                <div class="font-medium text-slate-200">{{ $log->recipient_name ?? '-' }}</div>
                                <div class="font-mono text-[10px] text-slate-400">{{ $log->recipient_email }}</div>
                            </td>
                            <td class="p-3.5 text-center">
                                @if($log->status == 'success')
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Berhasil</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Gagal</span>
                                @endif
                            </td>
                            <td class="p-3.5 font-sans text-[11px] text-slate-400 max-w-xs">
                                @if($log->status == 'success')
                                    <span class="text-emerald-400 flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                        Email terkirim
                                    </span>
                                @else
                                    <span class="text-rose-400 font-mono text-[10px] break-all">
                                        {{ $log->error_message ?: 'Gagal diproses' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                Tidak ada log pengiriman pada sesi ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-3 border-t border-slate-800 bg-[#11151f]">
            {{ $logs->links() }}
        </div>
    </div>

</div>
@endsection
