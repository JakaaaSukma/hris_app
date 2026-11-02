@extends('layouts.app')

@section('title', 'Executive Task Monitoring - Direktur Utama')

@section('content')
<div class="flex flex-col gap-6" x-data="{
    openExportModal: false,
    exportPeriod: '1_week',
    exportDepartment: 'all'
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">
                    Executive Dashboard • Pelaporan Seluruh Departemen
                </span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Monitoring Progres Tugas Perusahaan</h1>
            <p class="font-mono text-xs text-tertiary">Pantau eksekusi program kerja tiap divisi, kinerja manajer, dan pencapaian tugas karyawan</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button @click="openExportModal = true" class="flex items-center gap-2 px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">download</span>
                <span>Tarik Data Task & KPI</span>
            </button>
            <span class="px-3 py-1.5 rounded-lg bg-surface-container-high border border-[#333539] text-xs font-mono text-amber-400 flex items-center gap-1.5">
                <span class="material-symbols-outlined text-sm">shield_person</span>
                <span>Direksi View</span>
            </span>
        </div>
    </div>

    <!-- 4 Department Progress Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($departmentProgress as $deptName => $data)
            @php
                $completionPct = $data['total'] > 0 ? round(($data['completed'] / $data['total']) * 100, 1) : 0;
            @endphp
            <div class="p-5 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex flex-col justify-between shadow-sm hover:border-primary-container/40 transition-all">
                <div class="flex items-center justify-between">
                    <span class="font-mono text-[11px] font-bold uppercase tracking-wider text-on-surface">Divisi {{ $deptName }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $data['avg_progress'] >= 75 ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20' : ($data['avg_progress'] >= 40 ? 'bg-blue-950/60 text-blue-400 border border-blue-500/20' : 'bg-amber-950/60 text-amber-400 border border-amber-500/20') }}">
                        Rata Progres: {{ $data['avg_progress'] }}%
                    </span>
                </div>

                <div class="my-3">
                    <div class="flex items-baseline justify-between mb-1">
                        <span class="font-headline text-2xl font-bold text-on-surface">{{ $data['total'] }} <span class="text-xs text-tertiary font-mono">Tugas</span></span>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs font-mono text-emerald-400 font-semibold">{{ $data['completed'] }} Selesai</span>
                            <span class="px-1.5 py-0.2 rounded bg-emerald-950/60 text-emerald-300 font-mono text-[9px] font-bold">({{ $completionPct }}%)</span>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div class="w-full bg-surface-container-highest rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-500 {{ $data['avg_progress'] >= 75 ? 'bg-emerald-400' : ($data['avg_progress'] >= 40 ? 'bg-blue-400' : 'bg-amber-400') }}"
                             style="width: {{ $data['avg_progress'] }}%"></div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2 border-t border-[#282a2e]/40 font-mono text-[10px] text-tertiary">
                    <span>Sedang Dikerjakan: <strong class="text-on-surface">{{ $data['in_progress'] }}</strong></span>
                    <a href="{{ route('tasks.index', ['department' => $deptName]) }}" class="text-primary hover:underline">Filter &rarr;</a>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Filter & Monitoring Table for Director -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <!-- Filter Bar -->
        <div class="p-4 border-b border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 bg-surface-container/30">
            <form action="{{ route('tasks.index') }}" method="GET" class="flex flex-wrap items-center gap-3">
                <!-- Departemen Filter -->
                <select name="department" onchange="this.form.submit()" class="bg-surface-container-high border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Departemen</option>
                    @foreach($departments as $d)
                        <option value="{{ $d }}" {{ $departmentFilter === $d ? 'selected' : '' }}>Departemen {{ $d }}</option>
                    @endforeach
                </select>

                <!-- Karyawan / Manager Filter -->
                <select name="employee_id" onchange="this.form.submit()" class="bg-surface-container-high border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Personel (PIC)</option>
                    @foreach($allEmployees as $emp)
                        <option value="{{ $emp->id }}" {{ $employeeFilter == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->department }})
                        </option>
                    @endforeach
                </select>

                <!-- Skala Filter -->
                <select name="scope" onchange="this.form.submit()" class="bg-surface-container-high border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Skala</option>
                    <option value="department" {{ $scope === 'department' ? 'selected' : '' }}>Skala Tim Departemen</option>
                    <option value="individual" {{ $scope === 'individual' ? 'selected' : '' }}>Skala Individu</option>
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="bg-surface-container-high border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Status</option>
                    <option value="todo" {{ $status === 'todo' ? 'selected' : '' }}>To Do</option>
                    <option value="in_progress" {{ $status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="review" {{ $status === 'review' ? 'selected' : '' }}>Review</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>

                @if($departmentFilter || $employeeFilter || $scope || $status)
                    <a href="{{ route('tasks.index') }}" class="text-xs text-tertiary hover:underline">Reset</a>
                @endif
            </form>

            <span class="font-mono text-xs text-tertiary">
                Total: <strong class="text-on-surface">{{ $tasks->total() }}</strong> Tugas Terpantau
            </span>
        </div>

        <!-- Master Task Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                        <th class="px-5 py-3.5">Departemen</th>
                        <th class="px-5 py-3.5">Tugas & Objective</th>
                        <th class="px-5 py-3.5">Skala</th>
                        <th class="px-5 py-3.5">PIC / Pelaksana</th>
                        <th class="px-5 py-3.5">Target (Due)</th>
                        <th class="px-5 py-3.5">Progres Saat Ini</th>
                        <th class="px-5 py-3.5 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/30 font-body">
                    @forelse($tasks as $t)
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-5 py-4">
                                <span class="px-2 py-0.5 rounded font-mono text-[10px] font-bold {{ $t->department === 'HR' ? 'bg-red-950/60 text-red-400' : ($t->department === 'Finance' ? 'bg-emerald-950/60 text-emerald-400' : ($t->department === 'Bisnis Development' ? 'bg-blue-950/60 text-blue-400' : 'bg-amber-950/60 text-amber-400')) }}">
                                    {{ $t->department }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-sm text-on-surface">{{ $t->title }}</div>
                                @if($t->description)
                                    <div class="text-xs text-tertiary italic max-w-sm line-clamp-1">{{ $t->description }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($t->scope === 'department')
                                    <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/20 font-mono text-[10px]">
                                        Tim Dept
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 font-mono text-[10px]">
                                        Individu
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                @if($t->assignedEmployee)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $t->assignedEmployee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-6 h-6 rounded-full object-cover"/>
                                        <div>
                                            <span class="text-on-surface font-semibold block">{{ $t->assignedEmployee->name }}</span>
                                            <span class="text-[10px] text-tertiary">{{ $t->assignedEmployee->role_title }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-secondary font-medium">Tim Divisi (Bersama)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                {{ $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="w-40">
                                    <div class="flex items-center justify-between text-[11px] font-mono mb-1">
                                        <span class="font-bold text-on-surface">{{ $t->progress_percentage }}%</span>
                                        <span class="text-tertiary text-[10px]">{{ $t->status === 'completed' ? 'Selesai' : 'Sedang jalan' }}</span>
                                    </div>
                                    <div class="w-full bg-surface-container-highest rounded-full h-2 overflow-hidden">
                                        <div class="h-2 rounded-full transition-all duration-300 {{ $t->progress_percentage == 100 ? 'bg-emerald-400' : ($t->progress_percentage >= 50 ? 'bg-blue-400' : 'bg-amber-400') }}"
                                             style="width: {{ $t->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($t->status === 'completed')
                                    <span class="px-2.5 py-1 rounded bg-emerald-950/60 border border-emerald-500/20 text-emerald-400 font-mono text-[10px] font-bold">
                                        COMPLETED
                                    </span>
                                @elseif($t->status === 'in_progress')
                                    <span class="px-2.5 py-1 rounded bg-blue-950/60 border border-blue-500/20 text-blue-400 font-mono text-[10px] font-semibold">
                                        IN PROGRESS
                                    </span>
                                @elseif($t->status === 'review')
                                    <span class="px-2.5 py-1 rounded bg-purple-950/60 border border-purple-500/20 text-purple-400 font-mono text-[10px] font-semibold">
                                        REVIEW
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded bg-surface-container text-tertiary font-mono text-[10px]">
                                        TO DO
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-tertiary font-mono">
                                Belum ada laporan tugas yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tasks->hasPages())
            <div class="p-4 border-t border-[#282a2e]/40">{{ $tasks->links() }}</div>
        @endif
    </div>

    <!-- Modal Tarik Data Task & KPI (Direktur Utama) -->
    <div x-cloak x-show="openExportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75">
        <div class="bg-surface-container rounded-2xl border border-emerald-500/30 w-full max-w-md p-6 shadow-2xl relative" @click.away="openExportModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-950/60 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <span class="material-symbols-outlined text-xl">assessment</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Tarik Laporan Task & KPI</h3>
                        <p class="font-mono text-[11px] text-tertiary">Rekap tugas, progres kerja, dan evaluasi KPI perusahaan</p>
                    </div>
                </div>
                <button @click="openExportModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Pilihan Departemen untuk Direktur -->
                <div>
                    <label class="block text-xs font-mono text-on-surface font-semibold mb-1.5">
                        Pilih Departemen:
                    </label>
                    <select x-model="exportDepartment" class="w-full bg-surface-container-low border border-[#333539] focus:border-emerald-500 rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none">
                        <option value="all">Semua Departemen (Rekap Perusahaan)</option>
                        <option value="HR">Human Resources (HR)</option>
                        <option value="Finance">Finance</option>
                        <option value="Bisnis Development">Bisnis Development</option>
                        <option value="GA">General Affairs (GA)</option>
                    </select>
                </div>

                <!-- Pilihan Opsi Periode (1 Minggu vs 1 Bulan) -->
                <div>
                    <label class="block text-xs font-mono text-on-surface font-semibold mb-2">
                        Pilih Periode Waktu Laporan:
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label @click="exportPeriod = '1_week'" class="cursor-pointer p-3 rounded-xl border transition-all flex flex-col gap-1"
                               :class="exportPeriod === '1_week' ? 'bg-emerald-950/30 border-emerald-500 text-on-surface ring-1 ring-emerald-500/50' : 'bg-surface-container-low border-[#333539] text-tertiary hover:text-on-surface'">
                            <div class="flex items-center justify-between">
                                <span class="material-symbols-outlined text-lg" :class="exportPeriod === '1_week' ? 'text-emerald-400' : 'text-tertiary'">calendar_view_week</span>
                                <input type="radio" name="export_period_dir" value="1_week" x-model="exportPeriod" class="accent-emerald-500"/>
                            </div>
                            <span class="font-bold text-xs mt-1">1 Minggu Terakhir</span>
                            <span class="text-[10px] font-mono opacity-70">7 hari ke belakang</span>
                        </label>

                        <label @click="exportPeriod = '1_month'" class="cursor-pointer p-3 rounded-xl border transition-all flex flex-col gap-1"
                               :class="exportPeriod === '1_month' ? 'bg-emerald-950/30 border-emerald-500 text-on-surface ring-1 ring-emerald-500/50' : 'bg-surface-container-low border-[#333539] text-tertiary hover:text-on-surface'">
                            <div class="flex items-center justify-between">
                                <span class="material-symbols-outlined text-lg" :class="exportPeriod === '1_month' ? 'text-emerald-400' : 'text-tertiary'">calendar_month</span>
                                <input type="radio" name="export_period_dir" value="1_month" x-model="exportPeriod" class="accent-emerald-500"/>
                            </div>
                            <span class="font-bold text-xs mt-1">1 Bulan Terakhir</span>
                            <span class="text-[10px] font-mono opacity-70">30 hari ke belakang</span>
                        </label>
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-surface-container-highest/30 border border-[#282a2e] text-[11px] text-tertiary font-mono space-y-1">
                    <div class="flex items-center gap-1.5 text-on-surface font-semibold">
                        <span class="material-symbols-outlined text-sm text-emerald-400">check_circle</span>
                        <span>Isi Laporan Mencakup:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 pl-1 text-[10.5px]">
                        <li>Indeks Skor KPI Departemen & Beban Tugas Divisi</li>
                        <li>Rekap Performa & Rata-rata Progres Per PIC</li>
                        <li>Daftar Rinci Task, Due Date, Status & Ketepatan Waktu</li>
                    </ul>
                </div>

                <!-- Tombol Aksi Download -->
                <div class="pt-3 border-t border-[#282a2e] space-y-2.5">
                    <a :href="'{{ route('tasks.export.excel') }}?period=' + exportPeriod + '&department=' + encodeURIComponent(exportDepartment)"
                       class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-md transition-colors">
                        <span class="material-symbols-outlined text-base">table_view</span>
                        <span>Download Microsoft Excel (.xlsx)</span>
                    </a>

                    <a :href="'{{ route('tasks.export.pdf') }}?period=' + exportPeriod + '&department=' + encodeURIComponent(exportDepartment)"
                       target="_blank"
                       class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-surface-container-high hover:bg-surface-bright text-on-surface text-xs font-semibold border border-[#333539] transition-colors">
                        <span class="material-symbols-outlined text-base text-rose-400">picture_as_pdf</span>
                        <span>Buka / Cetak Laporan PDF</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
