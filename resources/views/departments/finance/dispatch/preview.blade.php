@extends('layouts.app')

@section('title', 'Preview Pengiriman Slip Gaji - Finance')

@section('content')
<div class="flex flex-col gap-6" x-data="{
    checkAll: true,
    fileRows: @js(array_keys($files)),
    selectedRows: @js(array_keys(array_filter($files, fn($f) => $f['status'] === 'valid'))),
    get isAllChecked() {
        return this.fileRows.length > 0 && this.selectedRows.length === this.fileRows.length;
    },
    toggleAll(checked) {
        if (checked) {
            this.selectedRows = [...this.fileRows];
        } else {
            this.selectedRows = [];
        }
    }
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                <span class="font-mono text-[10px] text-slate-400 uppercase tracking-wider font-semibold">Konfirmasi Dispatch • Verification Step</span>
            </div>
            <h1 class="font-headline font-extrabold text-2xl text-white tracking-tight mt-1 flex items-center gap-2.5">
                <span class="material-symbols-outlined text-cyan-400 text-2xl">checklist</span>
                <span>Preview Berkas & Daftar Penerima Email</span>
            </h1>
            <p class="font-mono text-xs text-slate-400 mt-0.5">
                Periksa daftar email karyawan dan periode yang terdeteksi secara otomatis dari isi dokumen PDF sebelum dispatch.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('finance.dispatch.index') }}" class="flex items-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-semibold transition-all border border-slate-700 shadow-sm active:scale-95">
                <span class="material-symbols-outlined text-base text-slate-400">arrow_back</span>
                <span>Batal & Kembali</span>
            </a>
        </div>
    </div>

    <!-- Instructions Banner -->
    <div class="p-4 bg-cyan-500/10 border border-cyan-500/20 text-cyan-300 rounded-xl flex items-center gap-3 text-xs leading-relaxed">
        <span class="material-symbols-outlined text-xl text-cyan-400">info</span>
        <div>
            <strong>Instruksi Pengiriman:</strong> Anda dapat mengedit alamat email tujuan secara manual jika terdapat salah deteksi atau belum tercantum di PDF. Centang berkas yang siap dikirim dan klik tombol <span class="text-white font-semibold">Mulai Pengiriman Email</span> di bawah.
        </div>
    </div>

    <!-- Dispatch Form -->
    <form action="{{ route('finance.dispatch.send.start') }}" method="POST" id="sendForm" class="space-y-6">
        @csrf

        <!-- Global CC & BCC Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-5 bg-[#151922] rounded-2xl border border-slate-800 shadow-sm">
            <div>
                <label class="block font-headline text-xs font-semibold text-slate-300 mb-1.5">
                    Email CC Masal (Opsional)
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-base">forward_to_inbox</span>
                    <input type="email" 
                           name="global_cc" 
                           placeholder="contoh: manager.finance@gyokai.com"
                           class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 transition-all">
                </div>
                <span class="font-mono text-[10px] text-slate-500 mt-1 block">Alamat ini akan disematkan sebagai CC pada setiap email slip gaji yang dikirim.</span>
            </div>

            <div>
                <label class="block font-headline text-xs font-semibold text-slate-300 mb-1.5">
                    Email BCC Masal (Opsional)
                </label>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-2.5 text-slate-500 text-base">visibility_off</span>
                    <input type="email" 
                           name="global_bcc" 
                           placeholder="contoh: arsip.payroll@gyokai.com"
                           class="w-full pl-9 pr-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-cyan-500 transition-all">
                </div>
                <span class="font-mono text-[10px] text-slate-500 mt-1 block">Alamat ini akan menerima salinan tersembunyi (BCC) tanpa terlihat oleh penerima utama.</span>
            </div>
        </div>

        <!-- Table of Parsed PDF Files -->
        <div class="bg-[#151922] rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between bg-slate-900/40">
                <div class="flex items-center gap-3">
                    <span class="font-headline font-bold text-sm text-white">Daftar Berkas Slip Gaji Terbaca</span>
                    <span class="font-mono text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 border border-slate-700">
                        Total: {{ count($files) }} Dokumen
                    </span>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs text-slate-400">
                    <span class="text-emerald-400 font-bold" x-text="selectedRows.length"></span> dari <span>{{ count($files) }}</span> dipilih
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 bg-[#11151f] text-slate-400 font-mono uppercase text-[10px] tracking-wider">
                            <th class="p-3.5 text-center w-12">
                                <input type="checkbox" 
                                       class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0 cursor-pointer"
                                       :checked="isAllChecked"
                                       @change="toggleAll($event.target.checked)">
                            </th>
                            <th class="p-3.5 w-1/4">Nama File Dokumen</th>
                            <th class="p-3.5 w-1/6">Periode Terdeteksi</th>
                            <th class="p-3.5 w-1/3">Alamat Email Karyawan</th>
                            <th class="p-3.5 w-24 text-center">Status</th>
                            <th class="p-3.5">Catatan Parser</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @forelse($files as $index => $file)
                            <tr class="hover:bg-slate-800/30 transition-colors {{ $file['status'] !== 'valid' ? 'bg-amber-500/5' : '' }}">
                                <td class="p-3.5 text-center">
                                    <input type="checkbox" 
                                           class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0 cursor-pointer file-cb"
                                           name="selected_files[{{ $index }}][filename]" 
                                           value="{{ $file['filename'] }}"
                                           value="{{ $index }}"
                                           :value="{{ $index }}"
                                           x-model.number="selectedRows">
                                    <input type="hidden" name="selected_files[{{ $index }}][period]" value="{{ $file['period'] }}">
                                </td>
                                <td class="p-3.5 font-sans">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-rose-400 text-lg">picture_as_pdf</span>
                                        <span class="text-white font-medium break-all">{{ $file['filename'] }}</span>
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    @if(!empty($file['period']))
                                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-cyan-500/10 text-cyan-300 border border-cyan-500/20">
                                            {{ $file['period'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-500 italic text-[11px]">Tidak terdeteksi</span>
                                    @endif
                                </td>
                                <td class="p-3.5 font-sans">
                                    <input type="email" 
                                           class="w-full px-2.5 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                                           name="selected_files[{{ $index }}][email]" 
                                           value="{{ $file['email'] }}" 
                                           placeholder="Masukkan email karyawan..." 
                                           required>
                                </td>
                                <td class="p-3.5 text-center">
                                    @if($file['status'] == 'valid')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Siap</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20">Periksa</span>
                                    @endif
                                </td>
                                <td class="p-3.5 font-sans text-xs text-slate-400">
                                    {{ $file['message'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-500">
                                    Tidak ada file slip gaji ditemukan dalam arsip.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sticky Bottom Actions -->
        <div class="flex items-center justify-between p-4 bg-[#151922] rounded-2xl border border-slate-800 shadow-md">
            <div class="font-mono text-xs text-slate-400">
                Pastikan kuota SMTP harian mencukupi. Pengiriman akan berjalan secara otomatis per batch 10 email.
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('finance.dispatch.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition-all">
                    Batalkan
                </a>
                <button type="submit" 
                        class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl text-xs font-bold transition-all shadow-md active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        :disabled="selectedRows.length === 0">
                    <span class="material-symbols-outlined text-base">send</span>
                    <span>Mulai Kirim (<span x-text="selectedRows.length"></span> Slip Gaji)</span>
                </button>
            </div>
        </div>

    </form>
</div>
@endsection
