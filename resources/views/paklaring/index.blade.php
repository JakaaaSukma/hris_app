@extends('layouts.app')

@section('title', 'Penerbitan Paklaring & Surat Keterangan')

@push('styles')
<!-- Google Fonts untuk script cursive name pada live preview -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
<style>
    @font-face {
        font-family: 'Apple Chancery';
        src: url('{{ asset('fonts/AppleChancery.ttf') }}') format('truetype');
        font-weight: normal;
        font-style: normal;
    }
    .font-script-preview {
        font-family: 'Apple Chancery', 'Alex Brush', cursive;
    }
    .preview-recipient-name {
        position: absolute;
        top: 40.2%;
        left: 5%;
        width: 90%;
        text-align: center;
        font-family: 'Apple Chancery', 'Alex Brush', cursive;
        font-weight: 600;
        color: #000000;
        line-height: 1.1;
        font-size: clamp(16px, 3.2vw, 40px);
        letter-spacing: 0.5px;
    }
    .preview-body-program {
        position: absolute;
        top: 49.8%;
        left: 8%;
        width: 84%;
        text-align: center;
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #000000;
        line-height: 1.4;
        font-size: clamp(9px, 1.35vw, 15px);
        letter-spacing: 0.2px;
    }
    .preview-body-period {
        position: absolute;
        top: 53.6%;
        left: 8%;
        width: 84%;
        text-align: center;
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #000000;
        line-height: 1.4;
        font-size: clamp(9px, 1.35vw, 15px);
        letter-spacing: 0.2px;
    }
    .preview-body-company {
        position: absolute;
        top: 57.3%;
        left: 8%;
        width: 84%;
        text-align: center;
        font-family: Arial, sans-serif;
        font-weight: bold;
        color: #000000;
        line-height: 1.4;
        font-size: clamp(9px, 1.35vw, 15px);
        letter-spacing: 0.2px;
    }
    .preview-signature-header {
        position: absolute;
        top: 66.0%;
        left: 66.8%;
        width: 30%;
        text-align: left;
        display: flex;
        flex-direction: column;
        gap: 2px;
        color: #000000;
        line-height: 1.3;
    }
    .preview-city-date {
        font-family: Arial, sans-serif;
        font-size: clamp(8px, 1.15vw, 13.5px);
        font-weight: normal;
        color: #000000;
    }
    .preview-instansi {
        font-family: Arial, sans-serif;
        font-size: clamp(8px, 1.15vw, 13.5px);
        font-weight: bold;
        color: #000000;
    }
</style>
@endpush

@section('content')
<div class="space-y-6" x-data="{
    recipient_name: '{{ $defaults['recipient_name'] }}',
    program_or_position: '{{ $defaults['program_or_position'] }}',
    period_text: '{{ $defaults['period_text'] }}',
    company_unit: '{{ $defaults['company_unit'] }}',
    city: '{{ $defaults['city'] }}',
    issue_date: '{{ $defaults['issue_date'] }}',
    formatted_date: '{{ $defaults['formatted_issue_date'] }}',
    selected_employee_id: '',
    
    // Auto-fill dari data karyawan internal jika dipilih
    onSelectEmployee(e) {
        const empId = e.target.value;
        this.selected_employee_id = empId;
        if (!empId) return;

        const empData = {
            @foreach($employees as $emp)
            '{{ $emp->id }}': {
                name: '{{ addslashes($emp->name) }}',
                position: '{{ addslashes($emp->position ?? $emp->department) }}',
                join_date: '{{ $emp->join_date ? \Carbon\Carbon::parse($emp->join_date)->locale('id')->translatedFormat('d F Y') : '' }}',
            },
            @endforeach
        };

        if (empData[empId]) {
            this.recipient_name = empData[empId].name;
            this.program_or_position = 'Karyawan Posisi ' + empData[empId].position;
            const join = empData[empId].join_date || '01 Januari 2022';
            this.period_text = 'Periode Tanggal ' + join + ' s/d ' + this.formatted_date;
            this.company_unit = 'PT. Gyokai Indonesia Global';
        }
    },

    updateDateText(val) {
        if (!val) return;
        const d = new Date(val);
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        this.formatted_date = d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    },

    setPreset(program, company) {
        this.program_or_position = program;
        if (company) this.company_unit = company;
    }
}">

    <!-- Top Header Banner -->
    <div class="p-6 rounded-2xl bg-[#151922] border border-slate-800 shadow-lg flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-rose-600 to-amber-600 flex items-center justify-center text-white shadow-lg shadow-rose-600/30">
                <span class="material-symbols-outlined text-2xl">workspace_premium</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="font-headline font-extrabold text-xl lg:text-2xl text-white tracking-tight">Penerbitan Surat Keterangan / Paklaring</h1>
                    <span class="px-2 py-0.5 rounded-md bg-rose-500/10 text-rose-400 border border-rose-500/20 text-[10px] font-mono font-bold">HR Dept</span>
                </div>
                <p class="text-xs text-slate-400 mt-1 font-mono">
                    Pembuatan dokumen resmi pemagangan / surat pengalaman kerja LPK Gyokai Indonesia dengan template A4 landscape
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a :href="'{{ route('hr.paklaring.directPrint') }}?recipient_name=' + encodeURIComponent(recipient_name) + '&program_or_position=' + encodeURIComponent(program_or_position) + '&period_text=' + encodeURIComponent(period_text) + '&company_unit=' + encodeURIComponent(company_unit) + '&city=' + encodeURIComponent(city) + '&issue_date=' + encodeURIComponent(issue_date)"
               target="_blank"
               class="px-4 py-2.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-rose-600/30 flex items-center gap-2 active:scale-95">
                <span class="material-symbols-outlined text-base">print</span>
                <span>Cetak / PDF Preview</span>
            </a>
        </div>
    </div>

    <!-- Alert Success Notification -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-3 font-mono">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Main Grid: Input Form (Left) & Real-time Live Preview (Right) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Form Column (5 Cols) -->
        <div class="lg:col-span-5 space-y-5">
            <form action="{{ route('hr.paklaring.store') }}" method="POST" class="p-5 rounded-2xl bg-[#151922] border border-slate-800 shadow-md space-y-4">
                @csrf
                <input type="hidden" name="employee_id" :value="selected_employee_id">

                <div class="border-b border-slate-800 pb-3 flex items-center justify-between">
                    <span class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-rose-400 text-lg">edit_document</span>
                        <span>Form Data Paklaring</span>
                    </span>
                    <span class="text-[10px] font-mono text-slate-400">Template Resmi</span>
                </div>

                <!-- 1. Quick Selector dari Pegawai (Opsional) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Pilih Karyawan Terdaftar <span class="text-[10px] text-slate-500 font-normal">(Auto-Fill, atau abaikan jika peserta luar/magang)</span>
                    </label>
                    <select @change="onSelectEmployee($event)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                        <option value="">-- Ketik Bebas / Peserta Magang Luar --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->position ?? $emp->department }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- 2. Data Penerima -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Nama Lengkap Penerima <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="recipient_name" 
                           x-model="recipient_name"
                           required
                           placeholder="Contoh: Rida Rapika Saragi" 
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white font-semibold focus:outline-none focus:border-rose-500">
                </div>

                <!-- 3. Badan Keterangan: Program / Jabatan -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-semibold text-slate-300">
                            Program Kejuruan / Jabatan <span class="text-rose-500">*</span>
                        </label>
                        <!-- Quick Presets -->
                        <div class="flex items-center gap-1">
                            <button type="button" @click="setPreset('Program Pemagangan Kejuruan QC', 'PT. Sungshin Best Indonesia')" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-rose-300 hover:bg-slate-700">Preset QC</button>
                            <button type="button" @click="setPreset('Program Pemagangan Kejuruan Produksi', '')" class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 hover:bg-slate-700">Produksi</button>
                        </div>
                    </div>
                    <input type="text" 
                           name="program_or_position" 
                           x-model="program_or_position"
                           required
                           placeholder="Contoh: Program Pemagangan Kejuruan QC" 
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                    <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Sistem otomatis menyusun: Telah Mengikuti [Isian ini]</span>
                </div>

                <!-- 4. Badan Keterangan: Periode Masa Kerja / Magang -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Periode Tanggal Masa Berlaku <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="period_text" 
                           x-model="period_text"
                           required
                           placeholder="Contoh: Periode Tanggal 20 Juni 2021 s/d 31 Desember 2025" 
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                </div>

                <!-- 5. Badan Keterangan: Unit Penempatan / Perusahaan -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Unit Perusahaan Penempatan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" 
                           name="company_unit" 
                           x-model="company_unit"
                           required
                           placeholder="Contoh: PT. Sungshin Best Indonesia" 
                           class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                    <span class="text-[10px] text-slate-500 font-mono mt-0.5 block">Sistem otomatis menyusun: di Unit Perusahaan [Isian ini]</span>
                </div>

                <!-- 6. Tempat & Tanggal Penerbitan -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Kota Penerbitan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="city" 
                               x-model="city"
                               required
                               placeholder="Tangerang" 
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Tanggal Terbit <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="issue_date" 
                               x-model="issue_date"
                               @change="updateDateText($event.target.value)"
                               required
                               class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-rose-500">
                    </div>
                </div>

                <!-- Info Penandatangan Resmi (Sesuai Template) -->
                <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-[11px] font-mono text-slate-400 space-y-1">
                    <div class="flex items-center justify-between text-slate-300 font-bold">
                        <span>Penandatangan Resmi:</span>
                        <span class="text-emerald-400">Stempel & TTD Aktif</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Nama Direktur:</span>
                        <span class="text-white">ANITA SANDRA DEWI</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Lembaga:</span>
                        <span class="text-white">LPK Gyokai Indonesia</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row gap-2">
                    <button type="submit" 
                            name="action_save" 
                            value="1" 
                            class="flex-1 py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition-all border border-slate-700 shadow-sm active:scale-95 flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">save</span>
                        <span>Simpan ke Database</span>
                    </button>

                    <button type="submit" 
                            name="action_print" 
                            value="1" 
                            class="flex-1 py-2.5 px-4 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-700 text-white rounded-xl text-xs font-bold transition-all shadow-lg shadow-rose-600/25 active:scale-95 flex items-center justify-center gap-1.5">
                        <span class="material-symbols-outlined text-base">print</span>
                        <span>Simpan & Buka Cetak</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Preview Column (7 Cols) -->
        <div class="lg:col-span-7 flex flex-col gap-3">
            <div class="flex items-center justify-between px-1">
                <span class="text-xs font-mono text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live Visual Preview (Sama Persis Template)
                </span>
                <span class="text-[11px] font-mono text-slate-500">Rasio 297mm x 210mm</span>
            </div>

            <!-- Scaled Certificate Preview Card -->
            <div class="w-full bg-slate-950/60 p-4 rounded-2xl border border-slate-800 shadow-2xl flex items-center justify-center overflow-hidden">
                @php
                    $previewTemplateUrl = $templateUrl ?? (method_exists(\App\Http\Controllers\PaklaringController::class, 'getTemplateUrl') 
                        ? \App\Http\Controllers\PaklaringController::getTemplateUrl() 
                        : (file_exists(public_path('images/paklaring/exact_base_template.png'))
                            ? asset('images/paklaring/exact_base_template.png') . '?v=' . filemtime(public_path('images/paklaring/exact_base_template.png'))
                            : route('hr.paklaring.templateImage')));
                @endphp
                <div class="relative w-full rounded-sm shadow-2xl overflow-hidden select-none border border-slate-300 bg-white"
                     style="position: relative; width: 100%; aspect-ratio: 297/210; min-height: 240px; background-color: #ffffff;">
                    
                    <!-- Base Template Image (Guarantees intrinsic aspect ratio & 100% visible height) -->
                    <img src="{!! $previewTemplateUrl !!}" 
                         alt="Certificate Template" 
                         class="w-full h-auto block select-none pointer-events-none"
                         style="width: 100%; height: auto; display: block;">

                    <!-- Dynamic Overlays Layer -->
                    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; pointer-events: none; user-select: none;">
                        <!-- 1. Nama Penerima (Script Font) -->
                        <div class="preview-recipient-name font-script-preview"
                             style="position: absolute; top: 40.2%; left: 5%; width: 90%; text-align: center; font-family: 'Apple Chancery', 'Alex Brush', cursive; font-size: clamp(16px, 3.2vw, 40px); font-weight: 600; color: #000000; line-height: 1.1; letter-spacing: 0.5px;"
                             x-text="recipient_name || 'Nama Lengkap Penerima'">
                        </div>

                        <!-- 2. Program / Jabatan -->
                        <div class="preview-body-program"
                             style="position: absolute; top: 49.8%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: clamp(9px, 1.35vw, 15px); font-weight: bold; color: #000000; line-height: 1.4; letter-spacing: 0.2px;"
                             x-text="(program_or_position && program_or_position.startsWith('Telah')) ? program_or_position : 'Telah Mengikuti ' + (program_or_position || 'Program Pemagangan Kejuruan QC')">
                        </div>

                        <!-- 3. Periode Tanggal -->
                        <div class="preview-body-period"
                             style="position: absolute; top: 53.6%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: clamp(9px, 1.35vw, 15px); font-weight: bold; color: #000000; line-height: 1.4; letter-spacing: 0.2px;"
                             x-text="(period_text && period_text.startsWith('Periode')) ? period_text : 'Periode Tanggal ' + (period_text || '20 Juni 2021 s/d 31 Desember 2025')">
                        </div>

                        <!-- 4. Unit Perusahaan -->
                        <div class="preview-body-company"
                             style="position: absolute; top: 57.3%; left: 8%; width: 84%; text-align: center; font-family: Arial, sans-serif; font-size: clamp(9px, 1.35vw, 15px); font-weight: bold; color: #000000; line-height: 1.4; letter-spacing: 0.2px;"
                             x-text="(company_unit && company_unit.startsWith('di Unit')) ? company_unit : 'di Unit Perusahaan ' + (company_unit || 'PT. Sungshin Best Indonesia')">
                        </div>

                        <!-- 5. Tempat, Tanggal & Instansi -->
                        <div class="preview-signature-header"
                             style="position: absolute; top: 66.0%; left: 66.8%; width: 30%; text-align: left; display: flex; flex-direction: column; gap: 2px; color: #000000; line-height: 1.3;">
                            <span class="preview-city-date" style="font-family: Arial, sans-serif; font-size: clamp(8px, 1.15vw, 13.5px); font-weight: normal; color: #000000;" x-text="(city || 'Tangerang') + ', ' + formatted_date"></span>
                            <span class="preview-instansi" style="font-family: Arial, sans-serif; font-size: clamp(8px, 1.15vw, 13.5px); font-weight: bold; color: #000000;">LPK Gyokai Indonesia</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-[#151922] rounded-xl border border-slate-800 text-[11px] text-slate-400 font-mono flex items-center justify-between">
                <span>💡 Teks di atas berubah secara real-time saat Anda mengetik di formulir.</span>
                <span class="text-rose-400 font-bold">100% Pixel Perfect</span>
            </div>
        </div>

    </div>

    <!-- History / Archive Table -->
    <div class="p-6 rounded-2xl bg-[#151922] border border-slate-800 shadow-md space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
            <div>
                <h2 class="font-headline font-bold text-base text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-400">history_edu</span>
                    <span>Riwayat Paklaring yang Telah Diterbitkan</span>
                </h2>
                <p class="text-xs text-slate-400 font-mono mt-0.5">Daftar arsip seluruh surat keterangan pemagangan & paklaring karyawan</p>
            </div>
            <span class="text-xs font-mono px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 border border-slate-700">
                Total: {{ method_exists($paklarings, 'total') ? $paklarings->total() : $paklarings->count() }} Dokumen
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-[11px] font-mono text-slate-400 uppercase tracking-wider">
                        <th class="py-3 px-3">No. Surat</th>
                        <th class="py-3 px-3">Nama Penerima</th>
                        <th class="py-3 px-3">Program / Jabatan</th>
                        <th class="py-3 px-3">Unit Penempatan</th>
                        <th class="py-3 px-3">Tanggal Terbit</th>
                        <th class="py-3 px-3">Diterbitkan Oleh</th>
                        <th class="py-3 px-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-xs">
                    @forelse($paklarings as $item)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-3 font-mono text-rose-400 font-bold">
                                {{ $item->certificate_number ?? '-' }}
                            </td>
                            <td class="py-3 px-3 font-semibold text-white">
                                {{ $item->recipient_name }}
                                @if($item->employee)
                                    <span class="block text-[10px] text-slate-400 font-mono">{{ $item->employee->nip }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                {{ $item->program_or_position }}
                                <span class="block text-[10px] text-slate-500 font-mono">{{ $item->period_text }}</span>
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                {{ $item->company_unit }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-400">
                                {{ \Carbon\Carbon::parse($item->issue_date)->locale('id')->translatedFormat('d M Y') }}
                            </td>
                            <td class="py-3 px-3">
                                @if($item->creator)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-rose-500/20 text-rose-400 font-bold text-[10px] flex items-center justify-center border border-rose-500/30 flex-shrink-0">
                                            {{ strtoupper(substr($item->creator->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="font-medium text-slate-200 block text-xs leading-snug">{{ $item->creator->name }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono capitalize">
                                                {{ $item->creator->role }} {{ $item->creator->department ? '• ' . $item->creator->department : '' }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-500 font-mono text-xs">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('hr.paklaring.print', $item->id) }}" 
                                       target="_blank"
                                       class="px-2.5 py-1.5 rounded-lg bg-rose-600/20 text-rose-300 hover:bg-rose-600/40 border border-rose-500/30 transition-all font-mono text-[11px] font-bold flex items-center gap-1"
                                       title="Buka & Cetak PDF">
                                        <span class="material-symbols-outlined text-sm">print</span>
                                        <span>Cetak</span>
                                    </a>

                                    <form action="{{ route('hr.paklaring.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus arsip paklaring ini?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-slate-800 hover:bg-red-950/60 text-slate-400 hover:text-red-400 border border-slate-700 hover:border-red-500/30 transition-colors" title="Hapus Arsip">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 font-mono text-xs">
                                <span class="material-symbols-outlined text-3xl text-slate-600 block mb-1">description</span>
                                Belum ada arsip paklaring yang disimpan. Silakan gunakan form di atas untuk menerbitkan dokumen baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($paklarings, 'hasPages') && $paklarings->hasPages())
            <div class="pt-3 border-t border-slate-800">
                {{ $paklarings->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
