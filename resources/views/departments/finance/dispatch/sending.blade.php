@extends('layouts.app')

@section('title', 'Proses Pengiriman Slip Gaji - Finance')

@section('content')
<div class="flex flex-col gap-6" x-data="payslipSender({
    autoProcess: {{ !empty($autoProcessEnabled) ? 'true' : 'false' }},
    batchUrl: '{{ route('finance.dispatch.send.batch') }}',
    reportUrl: '{{ $reportUrl ?? route('finance.dispatch.index') }}',
    sessionId: {{ $activeSendSessionId ?? 'null' }},
    csrfToken: '{{ csrf_token() }}',
    initialTotal: {{ $total ?? 0 }},
    initialProcessed: {{ $processed ?? 0 }},
    initialSuccess: {{ $success ?? 0 }},
    initialFailed: {{ $failed ?? 0 }},
    initialRemaining: {{ $remaining ?? 0 }},
    initialPercent: {{ $progressPercent ?? 0 }}
})">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Live Dispatch Worker • Rotating Multi-SMTP</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-emerald-400 text-2xl" :class="isSending ? 'animate-spin' : ''">sync</span>
                <span>Proses Pengiriman Slip Gaji Karyawan</span>
            </h1>
            <p class="font-mono text-xs text-slate-400 mt-0.5">
                Sistem mengirim email batch secara terisolasi untuk menghindari timeout dan rotasi akun Gmail saat limit kuota tercapai.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a :href="reportUrl" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700 shadow-sm">
                <span class="material-symbols-outlined text-base text-cyan-400">summarize</span>
                <span>Laporan Sesi</span>
            </a>
            <a href="{{ route('finance.dispatch.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl text-xs transition-all border border-slate-800">
                <span class="material-symbols-outlined text-base">close</span>
                <span>Tutup</span>
            </a>
        </div>
    </div>

    <!-- Alert / Notice Box -->
    @if(!empty($sendingUnavailableReason))
        <div class="p-4 bg-amber-500/10 border border-amber-500/20 text-amber-300 rounded-xl flex items-center gap-3 text-xs leading-relaxed">
            <span class="material-symbols-outlined text-xl text-amber-400">warning</span>
            <div>{{ $sendingUnavailableReason }}</div>
        </div>
    @endif

    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 rounded-xl flex items-center gap-3 text-xs leading-relaxed">
        <span class="material-symbols-outlined text-2xl text-emerald-400 flex-shrink-0" :class="isSending ? 'animate-spin' : ''">forward_to_inbox</span>
        <div class="space-y-1">
            <div class="font-bold text-white flex items-center gap-2">
                <span>Pengiriman Otomatis Batch (Maks. 10 Email/Batch)</span>
                @if(!empty($sessionCode))
                    <span class="font-mono text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">Kode: {{ $sessionCode }}</span>
                @endif
            </div>
            <div class="text-slate-300">
                Mohon untuk <strong>tidak menutup atau me-refresh browser</strong> ini sampai proses seluruh berkas slip gaji selesai.
            </div>
        </div>
    </div>

    <!-- Main Progress Box -->
    <div class="bg-[#151922] p-6 rounded-2xl border border-slate-800 shadow-sm space-y-6">
        
        <!-- Progress Bar -->
        <div class="space-y-2">
            <div class="flex items-center justify-between text-xs font-mono">
                <span class="text-slate-400">Progres Pengiriman</span>
                <span class="font-bold text-emerald-400 text-sm" x-text="progressPercent + '%'"></span>
            </div>
            <div class="w-full h-4 bg-slate-900 rounded-full overflow-hidden border border-slate-800/80 p-0.5">
                <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full transition-all duration-300"
                     :style="'width: ' + progressPercent + '%'"></div>
            </div>
        </div>

        <!-- Metric Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80 flex flex-col">
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider">Total Antrean</span>
                <span class="font-headline font-bold text-xl text-white mt-1" x-text="total"></span>
            </div>

            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80 flex flex-col">
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider">Diproses</span>
                <span class="font-headline font-bold text-xl text-cyan-400 mt-1" x-text="processed + ' / ' + total"></span>
            </div>

            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80 flex flex-col">
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider">Berhasil</span>
                <span class="font-headline font-bold text-xl text-emerald-400 mt-1" x-text="success"></span>
            </div>

            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80 flex flex-col">
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider">Gagal</span>
                <span class="font-headline font-bold text-xl text-rose-400 mt-1" x-text="failed"></span>
            </div>

            <div class="bg-slate-900/60 p-4 rounded-xl border border-slate-800/80 flex flex-col col-span-2 sm:col-span-1">
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider">Sisa Antrian</span>
                <span class="font-headline font-bold text-xl text-amber-400 mt-1" x-text="remaining"></span>
            </div>
        </div>

        <!-- Status Message Banner -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 font-mono text-xs flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-base" :class="isSending ? 'text-cyan-400 animate-spin' : (isFinished ? 'text-emerald-400' : 'text-slate-400')">
                    <span x-text="isSending ? 'sync' : (isFinished ? 'task_alt' : 'schedule')"></span>
                </span>
                <span class="text-slate-300" x-text="statusMessage"></span>
            </div>
            <div class="text-[11px] text-slate-500" x-show="isSending">
                Batch: maks 10 berkas...
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between pt-2">
            <div>
                @if(!empty($canCancelSending))
                    <form action="{{ route('finance.dispatch.send.cancel') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan sisa pengiriman slip gaji?');">
                        @csrf
                        <input type="hidden" name="send_session_id" value="{{ $activeSendSessionId ?? session('current_payslip_send_session_id') }}">
                        <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 rounded-xl text-xs font-semibold transition-all">
                            <span class="material-symbols-outlined text-base">cancel</span>
                            <span>Batalkan Pengiriman Sisa</span>
                        </button>
                    </form>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <button type="button" 
                        @click="processNextBatch()" 
                        class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="isSending || isFinished || remaining <= 0">
                    <span class="material-symbols-outlined text-base" :class="isSending ? 'animate-spin' : ''">send</span>
                    <span x-text="isSending ? 'Memproses Batch...' : 'Proses Batch Berikutnya'"></span>
                </button>

                <a :href="reportUrl" 
                   x-show="isFinished" 
                   class="flex items-center gap-2 px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95">
                    <span class="material-symbols-outlined text-base">assessment</span>
                    <span>Buka Laporan Selesai</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function payslipSender(config) {
    return {
        autoProcess: config.autoProcess,
        batchUrl: config.batchUrl,
        reportUrl: config.reportUrl,
        sessionId: config.sessionId,
        csrfToken: config.csrfToken,
        
        total: config.initialTotal,
        processed: config.initialProcessed,
        success: config.initialSuccess,
        failed: config.initialFailed,
        remaining: config.initialRemaining,
        progressPercent: config.initialPercent,
        
        isSending: false,
        isFinished: false,
        statusMessage: 'Menyiapkan proses antrean email slip gaji...',

        init() {
            if (this.remaining <= 0 && this.total > 0) {
                this.isFinished = true;
                this.statusMessage = 'Pengiriman slip gaji telah selesai seluruhnya.';
                return;
            }

            if (this.autoProcess && this.remaining > 0) {
                this.statusMessage = 'Memulai proses batch otomatis...';
                setTimeout(() => {
                    this.processNextBatch();
                }, 1000);
            } else if (!this.autoProcess) {
                this.statusMessage = 'Silakan klik Proses Batch Berikutnya atau batalkan.';
            }
        },

        async processNextBatch() {
            if (this.isSending || this.isFinished) return;

            this.isSending = true;
            this.statusMessage = 'Sedang mengirim batch email (rotasi SMTP)...';

            try {
                const response = await fetch(this.batchUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        send_session_id: this.sessionId
                    })
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Terjadi kesalahan saat memproses batch.');
                }

                // Update counters
                if (data.total !== undefined) this.total = data.total;
                if (data.processed !== undefined) this.processed = data.processed;
                if (data.success !== undefined) this.success = data.success;
                if (data.failed !== undefined) this.failed = data.failed;
                if (data.remaining !== undefined) this.remaining = data.remaining;
                if (data.progressPercent !== undefined) this.progressPercent = data.progressPercent;

                this.statusMessage = data.message || 'Batch selesai diproses.';

                if (data.completed || this.remaining <= 0) {
                    this.isFinished = true;
                    this.isSending = false;
                    this.statusMessage = data.message || 'Semua slip gaji berhasil dikirim ke antrean!';
                    if (data.redirectUrl) {
                        this.reportUrl = data.redirectUrl;
                        setTimeout(() => {
                            window.location.href = data.redirectUrl;
                        }, 2000);
                    }
                    return;
                }

                // Next batch recursive if still remaining
                this.isSending = false;
                if (this.autoProcess && this.remaining > 0) {
                    setTimeout(() => {
                        this.processNextBatch();
                    }, 1200);
                }
            } catch (err) {
                this.isSending = false;
                this.statusMessage = 'Kendala: ' + err.message;
                console.error(err);
            }
        }
    };
}
</script>
@endsection
