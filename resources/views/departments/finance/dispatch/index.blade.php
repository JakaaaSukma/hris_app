@extends('layouts.app')

@section('title', 'Kirim Slip Gaji (Email Dispatch) - Finance')

@section('content')
<div class="flex flex-col gap-6" x-data="{ currentSubTab: 'dispatch' }">

    <!-- Header Banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Finance & Payroll System • Live Sync Gyokai02</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-400 text-2xl">mark_email_read</span>
                <span>Kirim Slip Gaji (Email Dispatch)</span>
            </h1>
            <p class="font-mono text-xs text-slate-400 mt-0.5">
                Pemrosesan PDF otomatis, rotasi multi-SMTP Gmail pool, dan sinkronisasi real-time 2 arah dengan Finance Gyokai02.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('finance.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700 shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base text-slate-400">arrow_back</span>
                <span>Keuangan & Payroll</span>
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

    @if(session('error'))
        <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 rounded-xl flex items-center gap-3 text-sm">
            <span class="material-symbols-outlined text-xl">error</span>
            <div class="flex-1 font-medium">{{ session('error') }}</div>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl flex items-center gap-3 text-sm">
            <span class="material-symbols-outlined text-xl">warning</span>
            <div class="flex-1 font-medium">{{ session('warning') }}</div>
        </div>
    @endif

    <!-- Main Grid: Info Sistem & Form Upload -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Informasi Sistem & Status Multi-SMTP Pool -->
        <div class="lg:col-span-5 flex flex-col gap-5">
            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col gap-4 shadow-sm">
                <div class="flex items-center gap-2 text-cyan-400">
                    <span class="material-symbols-outlined text-xl">info</span>
                    <h3 class="font-headline font-bold text-sm text-white">Informasi & Mekanisme Pengiriman</h3>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Sistem membaca teks PDF slip gaji secara presisi, mengekstrak alamat email karyawan dan periode slip, lalu mengirimkan via pool rotasi multi-SMTP Gmail.
                </p>
                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <div class="flex items-start gap-2 text-xs text-slate-400">
                        <span class="material-symbols-outlined text-sm text-emerald-400 mt-0.5">check</span>
                        <span>File PDF wajib mencantumkan email valid karyawan pada dokumen.</span>
                    </div>
                    <div class="flex items-start gap-2 text-xs text-slate-400">
                        <span class="material-symbols-outlined text-sm text-emerald-400 mt-0.5">check</span>
                        <span>Multi-SMTP pool (9 akun aktif) secara otomatis melakukan failover & cooldown bila kuota limit Gmail tercapai.</span>
                    </div>
                    <div class="flex items-start gap-2 text-xs text-slate-400">
                        <span class="material-symbols-outlined text-sm text-emerald-400 mt-0.5">sync_alt</span>
                        <span>Sinkronisasi database bersama <code class="text-emerald-400 bg-slate-900 px-1 py-0.5 rounded">gyokai02</code>: Seluruh riwayat dan log dapat dilihat secara bersamaan.</span>
                    </div>
                </div>
            </div>

            <!-- Pool SMTP Mailer Status Mini-Card -->
            <div class="bg-[#151922] p-5 rounded-2xl border border-slate-800 flex flex-col gap-3 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-emerald-400">
                        <span class="material-symbols-outlined text-xl">dns</span>
                        <h3 class="font-headline font-bold text-sm text-white">Pool Akun SMTP Gyokai</h3>
                    </div>
                    <span class="font-mono text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">
                        {{ count($smtpMailerOptions ?? []) }} Mailer
                    </span>
                </div>
                <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                    @forelse($smtpMailerOptions ?? [] as $m)
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-900/60 border border-slate-800/80 text-xs">
                            <div class="flex items-center gap-2 truncate">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                <span class="text-white font-medium truncate">{{ $m->mailer_name }}</span>
                                <span class="text-[10px] text-slate-400 truncate">({{ $m->from_address }})</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-500 italic">Tidak ada mailer terdaftar</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column: Form Upload File Slip Gaji -->
        <div class="lg:col-span-7">
            <div class="bg-[#151922] p-6 rounded-2xl border border-slate-800 shadow-sm flex flex-col justify-between h-full">
                <div>
                    <div class="flex items-center gap-2.5 text-emerald-400 mb-2">
                        <span class="material-symbols-outlined text-2xl">cloud_upload</span>
                        <h2 class="font-headline font-extrabold text-base text-white">Upload File Slip Gaji</h2>
                    </div>
                    <p class="font-mono text-xs text-slate-400 mb-5 leading-relaxed">
                        Unggah file dokumen slip gaji untuk diproses. Anda dapat memeriksa hasil deteksi email dan periode sebelum melakukan pengiriman batch.
                    </p>

                    <form action="{{ route('finance.dispatch.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label for="payslip_file" class="block font-headline text-xs font-semibold text-slate-300 mb-2">
                                Pilih File PDF atau Arsip ZIP
                            </label>
                            <input class="w-full text-xs text-slate-300 bg-slate-900 border border-slate-700/80 rounded-xl file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 cursor-pointer focus:outline-none focus:border-emerald-500 transition-all p-2" 
                                   type="file" 
                                   id="payslip_file" 
                                   name="payslip_file" 
                                   accept=".pdf,.zip" 
                                   required>
                        </div>

                        <div class="p-3.5 bg-slate-900/60 rounded-xl border border-slate-800 text-xs text-slate-400 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-cyan-400">picture_as_pdf</span>
                                <span>Format <strong>.pdf</strong>: Untuk satu file slip gaji individual.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-amber-400">folder_zip</span>
                                <span>Format <strong>.zip</strong>: Kumpulan banyak file PDF untuk pengiriman batch masal.</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-slate-400">straighten</span>
                                <span>Batas maksimal ukuran file: <strong>100 MB</strong>.</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-4 flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                            <span class="material-symbols-outlined text-base">checklist</span>
                            <span>Proses & Lanjut ke Preview Daftar Penerima</span>
                        </button>
                    </form>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[11px] font-mono text-slate-500">
                    <span>Engine: Smalot PDF Parser</span>
                    <span>Keamanan: Validation Trait Protection</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section: Laporan Sesi Pengiriman (Sessions) -->
    @if(isset($sendSessions))
        <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm mt-2">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-400 text-xl">folder_managed</span>
                    <h3 class="font-headline font-bold text-sm text-white">Laporan Sesi Pengiriman Slip Gaji</h3>
                </div>
                <span class="font-mono text-[10px] text-slate-400">Total: {{ $sendSessions->total() }} Sesi</span>
            </div>

            @if($sendSessions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-[#11151f] text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                                <th class="p-3.5">Kode Sesi</th>
                                <th class="p-3.5">Waktu Mulai</th>
                                <th class="p-3.5 text-center">Total</th>
                                <th class="p-3.5 text-center">Berhasil</th>
                                <th class="p-3.5 text-center">Gagal</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @foreach($sendSessions as $sendSession)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="p-3.5 font-bold text-white">
                                        <div class="font-sans font-semibold text-slate-200">{{ $sendSession->session_code }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">
                                            Selesai: {{ $sendSession->finished_at ? $sendSession->finished_at->format('d M Y H:i') : '-' }}
                                        </div>
                                    </td>
                                    <td class="p-3.5 text-slate-400">
                                        {{ $sendSession->started_at ? $sendSession->started_at->format('d M Y H:i') : $sendSession->created_at->format('d M Y H:i') }}
                                    </td>
                                    <td class="p-3.5 text-center font-bold text-white">
                                        {{ $sendSession->total_files }}
                                    </td>
                                    <td class="p-3.5 text-center font-bold text-emerald-400">
                                        {{ $sendSession->success_count }}
                                    </td>
                                    <td class="p-3.5 text-center font-bold text-rose-400">
                                        {{ $sendSession->failed_count }}
                                    </td>
                                    <td class="p-3.5">
                                        @if($sendSession->status === 'completed')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Selesai</span>
                                        @elseif($sendSession->status === 'completed_with_errors')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">Selesai Berbintik</span>
                                        @elseif($sendSession->status === 'cancelled')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-700 text-slate-300 border border-slate-600">Dibatalkan</span>
                                        @elseif($sendSession->status === 'failed')
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">Gagal</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">Sedang Proses</span>
                                        @endif
                                    </td>
                                    <td class="p-3.5 text-right font-sans">
                                        <div class="flex items-center justify-end gap-2">
                                            @if($sendSession->status === 'processing')
                                                <a href="{{ route('finance.dispatch.sending', ['send_session_id' => $sendSession->id]) }}" 
                                                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-xs font-semibold transition-all">
                                                    <span class="material-symbols-outlined text-sm">send</span>
                                                    <span>Lanjutkan</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('finance.dispatch.report', $sendSession) }}" 
                                               class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-semibold transition-all">
                                                <span class="material-symbols-outlined text-sm">visibility</span>
                                                <span>Laporan</span>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-slate-800 bg-[#11151f]">
                    {{ $sendSessions->links() }}
                </div>
            @else
                <div class="p-8 text-center text-slate-500 text-xs">
                    Belum ada riwayat sesi pengiriman slip gaji tersimpan di database.
                </div>
            @endif
        </div>
    @endif

    <!-- Section: Log Riwayat Pengiriman Per File -->
    @if(isset($logs))
        <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm mt-2">
            <div class="p-4 border-b border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-900/40">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-slate-400 text-xl">history</span>
                    <h3 class="font-headline font-bold text-sm text-white">Detail Riwayat Log Pengiriman Slip Gaji</h3>
                </div>

                <!-- Filter Controls Form -->
                <form action="{{ route('finance.dispatch.index') }}" method="GET" class="flex flex-wrap items-center gap-2.5">
                    <input type="date" name="filter_date" value="{{ request('filter_date') }}" class="px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                    
                    <select name="filter_status" class="px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                        <option value="all" {{ request('filter_status') == 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="success" {{ request('filter_status') == 'success' ? 'selected' : '' }}>Berhasil</option>
                        <option value="failed" {{ request('filter_status') == 'failed' ? 'selected' : '' }}>Gagal</option>
                    </select>

                    <select name="filter_mailer" class="px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                        <option value="all">Semua Mailer</option>
                        @foreach(($smtpMailerOptions ?? collect()) as $mailerOption)
                            <option value="{{ $mailerOption->id }}" {{ (string) request('filter_mailer') === (string) $mailerOption->id ? 'selected' : '' }}>
                                {{ $mailerOption->mailer_name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="sort_by" class="px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                        <option value="time_desc" {{ request('sort_by', 'time_desc') == 'time_desc' ? 'selected' : '' }}>Terbaru</option>
                        <option value="time_asc" {{ request('sort_by') == 'time_asc' ? 'selected' : '' }}>Terlama</option>
                    </select>

                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition-all">
                        Filter
                    </button>
                    <a href="{{ route('finance.dispatch.index') }}" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition-all">
                        Reset
                    </a>
                </form>
            </div>

            @if($logs->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 bg-[#11151f] text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                                <th class="p-3.5">Waktu</th>
                                <th class="p-3.5">Kode Sesi</th>
                                <th class="p-3.5">Mailer SMTP</th>
                                <th class="p-3.5">Nama File</th>
                                <th class="p-3.5">Periode</th>
                                <th class="p-3.5">Penerima</th>
                                <th class="p-3.5 text-center">Status</th>
                                <th class="p-3.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @foreach($logs as $log)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="p-3.5 text-slate-400 whitespace-nowrap">
                                        {{ $log->created_at->format('d/m/y H:i') }}
                                    </td>
                                    <td class="p-3.5 text-slate-300 font-semibold whitespace-nowrap">
                                        @if($log->sendSession)
                                            <a href="{{ route('finance.dispatch.report', $log->sendSession) }}" class="text-cyan-400 hover:underline">
                                                {{ $log->sendSession->session_code }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="p-3.5 font-sans">
                                        <div class="font-semibold text-slate-200">{{ $log->smtp_mailer_name ?? '-' }}</div>
                                        <div class="font-mono text-[10px] text-slate-500">{{ $log->smtp_mailer_address ?? '-' }}</div>
                                    </td>
                                    <td class="p-3.5 font-sans text-white max-w-xs truncate" title="{{ $log->filename }}">
                                        {{ $log->filename }}
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
                                    <td class="p-3.5 font-sans text-[11px] text-rose-400 max-w-xs truncate" title="{{ $log->error_message }}">
                                        {{ Str::limit($log->error_message, 45) ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-slate-800 bg-[#11151f]">
                    {{ $logs->links() }}
                </div>
            @else
                <div class="p-8 text-center text-slate-500 text-xs">
                    Data log tidak ditemukan dengan kriteria filter tersebut.
                </div>
            @endif
        </div>
    @endif

</div>
@endsection
