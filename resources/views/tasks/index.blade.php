@extends('layouts.app')

@section('title', 'Manajemen Tugas & Proyek Departemen')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openTaskModal: false, 
    detailTaskModal: false, 
    detailTask: null,
    isDailyForm: false,
    openExportModal: false,
    exportPeriod: '1_week',
    exportDepartment: '{{ auth()->user()->department }}'
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">
                    Task Tracking • Departemen {{ auth()->user()->department }}
                </span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Manajemen Tugas & Progres Kerja</h1>
            <p class="font-mono text-xs text-tertiary">Kelola target tim, penugasan individu, dan daily task harian secara terpadu</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            @if($authUser->isManager() || $authUser->isDirektur())
                <button @click="openExportModal = true" class="flex items-center gap-2 px-3.5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                    <span class="material-symbols-outlined text-base">download</span>
                    <span>Tarik Data Task & KPI</span>
                </button>
            @endif
            <button @click="isDailyForm = true; openTaskModal = true" class="flex items-center gap-2 px-3.5 py-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-lg text-xs font-semibold transition-all shadow-sm">
                <span class="material-symbols-outlined text-base">today</span>
                <span>+ Catat Daily Task</span>
            </button>
            <button @click="isDailyForm = false; openTaskModal = true" class="flex items-center gap-2 px-4 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">add_task</span>
                <span>Buat Tugas Baru</span>
            </button>
        </div>
    </div>

    <!-- Metric Cards: Tim vs Individu vs Daily Task -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        <a href="{{ route('tasks.index') }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-primary-container/40 transition-all flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Total Tugas Divisi</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $deptTotalTasks }} Task</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">assignment</span>
            </div>
        </a>

        <a href="{{ route('tasks.index', ['scope' => 'department']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-blue-500/40 transition-all flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Tugas Skala Tim</span>
                <div class="font-headline text-2xl font-bold text-blue-400 mt-1">{{ $deptTeamTasks }} Task</div>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20">
                <span class="material-symbols-outlined text-xl">diversity_3</span>
            </div>
        </a>

        <a href="{{ route('tasks.index', ['scope' => 'individual']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-amber-500/40 transition-all flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Tugas Individu Anda</span>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $myIndividualTasks }} Task</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">person</span>
            </div>
        </a>

        <a href="{{ route('tasks.index', ['is_daily' => 1, 'task_date' => now()->toDateString()]) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-purple-500/40 transition-all flex items-center justify-between {{ $isDaily ? 'ring-1 ring-purple-500/50 bg-purple-950/10' : '' }}">
            <div>
                <span class="font-mono text-[10px] text-purple-400 uppercase font-semibold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    Daily Task Hari Ini
                </span>
                <div class="font-headline text-2xl font-bold text-purple-400 mt-1">{{ $myDailyTasksToday }} Task</div>
            </div>
            <div class="p-2.5 rounded-lg bg-purple-950/40 text-purple-400 border border-purple-500/20">
                <span class="material-symbols-outlined text-xl">calendar_today</span>
            </div>
        </a>

        <a href="{{ route('tasks.index', ['status' => 'completed']) }}" 
           class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/50 hover:border-emerald-500/40 transition-all flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Tugas Selesai (100%)</span>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $completedTasksCount }} Task</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">task_alt</span>
            </div>
        </a>
    </div>

    <!-- Filter Tab Bar -->
    <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3 bg-surface-container/30">
            <!-- Skala Task Tabs -->
            <div class="flex items-center gap-1 font-mono text-xs flex-wrap">
                <a href="{{ route('tasks.index') }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ empty($scope) && empty($isDaily) ? 'bg-surface-container-highest text-on-surface font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    Semua Skala
                </a>
                <a href="{{ route('tasks.index', ['scope' => 'department']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $scope === 'department' && empty($isDaily) ? 'bg-surface-container-highest text-blue-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    👥 Skala Tim Departemen
                </a>
                <a href="{{ route('tasks.index', ['scope' => 'individual']) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors {{ $scope === 'individual' && empty($isDaily) ? 'bg-surface-container-highest text-amber-400 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    👤 Skala Individu (Pribadi)
                </a>
                <a href="{{ route('tasks.index', ['is_daily' => 1, 'task_date' => $taskDate ?? now()->toDateString()]) }}" 
                   class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5 {{ !empty($isDaily) ? 'bg-purple-950/60 text-purple-300 border border-purple-500/30 font-semibold' : 'text-tertiary hover:text-on-surface' }}">
                    <span class="material-symbols-outlined text-xs">calendar_today</span>
                    <span>📅 Daily Task Harian</span>
                </a>
            </div>

            <!-- Status Tabs & Date Filter jika Daily Task -->
            <div class="flex items-center gap-3">
                @if(!empty($isDaily))
                    <form action="{{ route('tasks.index') }}" method="GET" class="flex items-center gap-1.5">
                        <input type="hidden" name="is_daily" value="1">
                        <span class="text-[10px] font-mono text-tertiary uppercase">Tgl:</span>
                        <input type="date" name="task_date" value="{{ $taskDate ?? now()->toDateString() }}" onchange="this.form.submit()" 
                               class="bg-surface-container border border-[#333539] text-xs font-mono text-purple-300 rounded px-2 py-1 focus:outline-none"/>
                    </form>
                @endif

                <div class="flex items-center gap-1 font-mono text-xs">
                    <a href="{{ route('tasks.index', array_merge(request()->query(), ['status' => ''])) }}" 
                       class="px-2.5 py-1 rounded {{ empty($status) ? 'text-on-surface font-bold underline' : 'text-tertiary' }}">
                        Semua
                    </a>
                    <a href="{{ route('tasks.index', array_merge(request()->query(), ['status' => 'in_progress'])) }}" 
                       class="px-2.5 py-1 rounded {{ $status === 'in_progress' ? 'text-amber-400 font-bold underline' : 'text-tertiary' }}">
                        In Progress
                    </a>
                    <a href="{{ route('tasks.index', array_merge(request()->query(), ['status' => 'completed'])) }}" 
                       class="px-2.5 py-1 rounded {{ $status === 'completed' ? 'text-emerald-400 font-bold underline' : 'text-tertiary' }}">
                        Completed
                    </a>
                </div>
            </div>
        </div>

        <!-- Task List Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[11px] uppercase bg-surface-container-lowest/30">
                        <th class="px-5 py-3.5">Judul Tugas</th>
                        <th class="px-5 py-3.5">Skala Target</th>
                        <th class="px-5 py-3.5">Penanggung Jawab (PIC)</th>
                        <th class="px-5 py-3.5">Prioritas</th>
                        <th class="px-5 py-3.5">Deadline</th>
                        <th class="px-5 py-3.5">Progres Pengerjaan</th>
                        <th class="px-5 py-3.5 text-right">Update Progres</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#282a2e]/30 font-body">
                    @forelse($tasks as $t)
                        <tr class="hover:bg-surface-container transition-colors">
                            <td class="px-5 py-4">
                                <button type="button" 
                                        @click="detailTask = {{ json_encode([
                                            'id' => $t->id,
                                            'title' => $t->title,
                                            'description' => $t->description ?? '',
                                            'department' => $t->department,
                                            'scope' => $t->scope,
                                            'is_daily' => (bool)$t->is_daily,
                                            'task_date' => $t->task_date ? \Carbon\Carbon::parse($t->task_date)->format('d M Y') : null,
                                            'priority' => $t->priority,
                                            'status' => $t->status,
                                            'progress_percentage' => $t->progress_percentage,
                                            'due_date' => $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M Y') : 'Tidak ada tenggat',
                                            'created_at' => $t->created_at ? $t->created_at->format('d M Y, H:i') : '—',
                                            'updated_at' => $t->updated_at ? $t->updated_at->format('d M Y, H:i') : '—',
                                            'creator_name' => $t->creator?->name ?? 'Sistem',
                                            'creator_role' => $t->creator?->role_title ?? 'User',
                                            'creator_avatar' => $t->creator?->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60',
                                            'pic_name' => $t->assignedEmployee?->name ?? ($t->scope === 'department' ? 'Seluruh Tim Departemen' : 'Belum Ditugaskan'),
                                            'pic_role' => $t->assignedEmployee?->role_title ?? ($t->scope === 'department' ? 'Divisi ' . $t->department : '—'),
                                            'pic_avatar' => $t->assignedEmployee?->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60',
                                            'update_url' => route('tasks.updateProgress', $t->id),
                                        ]) }}; detailTaskModal = true"
                                        class="text-left group flex flex-col focus:outline-none">
                                    <div class="font-semibold text-sm text-on-surface group-hover:text-primary-container transition-colors flex items-center gap-1.5 flex-wrap">
                                        @if($t->is_daily)
                                            <span class="px-1.5 py-0.5 rounded bg-purple-950/70 border border-purple-500/30 text-purple-300 font-mono text-[9px] font-semibold flex items-center gap-0.5">
                                                <span class="material-symbols-outlined text-[10px]">today</span>
                                                Daily ({{ \Carbon\Carbon::parse($t->task_date ?? $t->due_date)->format('d M') }})
                                            </span>
                                        @endif
                                        <span>{{ $t->title }}</span>
                                        <span class="material-symbols-outlined text-[14px] text-tertiary group-hover:text-primary-container opacity-0 group-hover:opacity-100 transition-opacity">open_in_new</span>
                                    </div>
                                    @if($t->description)
                                        <div class="text-xs text-tertiary italic max-w-sm line-clamp-1 mt-0.5">{{ $t->description }}</div>
                                    @endif
                                </button>
                            </td>
                            <td class="px-5 py-4">
                                @if($t->scope === 'department')
                                    <span class="px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/20 text-blue-400 font-mono text-[10px] font-semibold flex items-center gap-1 w-max">
                                        <span class="material-symbols-outlined text-xs">groups</span>
                                        <span>Tim Departemen</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/20 text-amber-400 font-mono text-[10px] font-semibold flex items-center gap-1 w-max">
                                        <span class="material-symbols-outlined text-xs">person</span>
                                        <span>Individu</span>
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs">
                                @if($t->scope === 'individual' && $t->assignedEmployee)
                                    <div class="flex items-center gap-2">
                                        <img src="{{ $t->assignedEmployee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60' }}" class="w-6 h-6 rounded-full object-cover"/>
                                        <span class="text-on-surface font-medium">{{ $t->assignedEmployee->name }}</span>
                                    </div>
                                @else
                                    <span class="text-secondary font-medium">Seluruh Anggota Tim</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-[10px]">
                                @if($t->priority === 'urgent')
                                    <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/30 uppercase font-bold">URGENT</span>
                                @elseif($t->priority === 'high')
                                    <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/30 uppercase font-semibold">HIGH</span>
                                @elseif($t->priority === 'medium')
                                    <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/30 uppercase">MEDIUM</span>
                                @else
                                    <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary uppercase">LOW</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                {{ $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-5 py-4">
                                <div class="w-36">
                                    <div class="flex items-center justify-between text-[11px] font-mono mb-1">
                                        <span class="text-tertiary uppercase text-[9px]">{{ strtoupper(str_replace('_', ' ', $t->status)) }}</span>
                                        <span class="font-bold text-on-surface">{{ $t->progress_percentage }}%</span>
                                    </div>
                                    <div class="w-full bg-surface-container-highest rounded-full h-1.5 overflow-hidden">
                                        <div class="h-1.5 rounded-full transition-all duration-300 {{ $t->progress_percentage == 100 ? 'bg-emerald-400' : ($t->progress_percentage > 40 ? 'bg-blue-400' : 'bg-amber-400') }}"
                                             style="width: {{ $t->progress_percentage }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" 
                                            @click="detailTask = {{ json_encode([
                                                'id' => $t->id,
                                                'title' => $t->title,
                                                'description' => $t->description ?? '',
                                                'department' => $t->department,
                                                'scope' => $t->scope,
                                                'is_daily' => (bool)$t->is_daily,
                                                'task_date' => $t->task_date ? \Carbon\Carbon::parse($t->task_date)->format('d M Y') : null,
                                                'priority' => $t->priority,
                                                'status' => $t->status,
                                                'progress_percentage' => $t->progress_percentage,
                                                'due_date' => $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M Y') : 'Tidak ada tenggat',
                                                'created_at' => $t->created_at ? $t->created_at->format('d M Y, H:i') : '—',
                                                'updated_at' => $t->updated_at ? $t->updated_at->format('d M Y, H:i') : '—',
                                                'creator_name' => $t->creator?->name ?? 'Sistem',
                                                'creator_role' => $t->creator?->role_title ?? 'User',
                                                'creator_avatar' => $t->creator?->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60',
                                                'pic_name' => $t->assignedEmployee?->name ?? ($t->scope === 'department' ? 'Seluruh Tim Departemen' : 'Belum Ditugaskan'),
                                                'pic_role' => $t->assignedEmployee?->role_title ?? ($t->scope === 'department' ? 'Divisi ' . $t->department : '—'),
                                                'pic_avatar' => $t->assignedEmployee?->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=60',
                                                'update_url' => route('tasks.updateProgress', $t->id),
                                            ]) }}; detailTaskModal = true"
                                            class="inline-flex items-center gap-1 px-2 py-1 rounded bg-surface-container-high hover:bg-surface-bright text-xs font-medium text-tertiary hover:text-on-surface border border-[#333539] transition-colors"
                                            title="Lihat Detail Task">
                                        <span class="material-symbols-outlined text-sm">visibility</span>
                                        <span class="text-[11px] font-mono hidden sm:inline">Detail</span>
                                    </button>

                                    <form action="{{ route('tasks.updateProgress', $t->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="bg-surface-container-high border border-[#333539] text-[10px] text-on-surface rounded px-2 py-1 focus:outline-none">
                                            <option value="todo" {{ $t->status === 'todo' ? 'selected' : '' }}>To Do</option>
                                            <option value="in_progress" {{ $t->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                            <option value="review" {{ $t->status === 'review' ? 'selected' : '' }}>Review</option>
                                            <option value="completed" {{ $t->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                        </select>
                                        <input type="number" name="progress_percentage" value="{{ $t->progress_percentage }}" min="0" max="100" step="5" class="w-12 bg-surface-container-high border border-[#333539] text-[11px] font-mono text-on-surface rounded px-1 py-1 text-center focus:outline-none"/>
                                        <button type="submit" class="p-1 rounded bg-surface-container-high hover:bg-surface-bright text-tertiary hover:text-on-surface transition-colors" title="Simpan Perubahan">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-tertiary font-mono">
                                Belum ada daftar tugas dalam kategori ini.
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

    <!-- Modal Form Tambah Task Baru / Daily Task -->
    <div x-cloak x-show="openTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openTaskModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container text-xl" x-text="isDailyForm ? 'today' : 'add_task'"></span>
                    <h3 class="font-headline font-semibold text-base text-on-surface" x-text="isDailyForm ? 'Catat Daily Task Harian' : 'Buat Tugas / Project Baru'"></h3>
                </div>
                <button @click="openTaskModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            @php
                $canCreateDeptTask = auth()->user()->isManager() || auth()->user()->isDirektur();
            @endphp

            <form action="{{ route('tasks.store') }}" method="POST" class="mt-4 space-y-3.5" x-data="{ currentScope: '{{ $canCreateDeptTask ? 'department' : 'individual' }}' }">
                @csrf
                
                <!-- Toggle Mode Daily Task vs Proyek Standar -->
                <div class="p-3 rounded-xl bg-surface-container-low border border-[#333539] flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-400 text-lg">calendar_month</span>
                        <div>
                            <span class="text-xs font-semibold text-on-surface block">Tandai sebagai Daily Task</span>
                            <span class="text-[10px] text-tertiary block">Tugas harian individu dengan tenggat hari ini/tanggal tertentu</span>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_daily" value="1" x-model="isDailyForm" @change="if(isDailyForm) { currentScope = 'individual'; }" class="sr-only peer">
                        <div class="w-9 h-5 bg-surface-container-highest peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Judul Tugas / Objective</label>
                    <input type="text" name="title" required :placeholder="isDailyForm ? 'contoh: Review 10 berkas lamaran & hubungi kandidat' : 'contoh: Rekapitulasi Laporan Audit Q3'" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Skala Penugasan</label>
                        @if($canCreateDeptTask)
                            <select name="scope" x-model="currentScope" :disabled="isDailyForm" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none disabled:opacity-60 disabled:cursor-not-allowed">
                                <option value="department">👥 Skala Tim Departemen</option>
                                <option value="individual">👤 Skala Individu (Per Karyawan)</option>
                            </select>
                            <input type="hidden" name="scope" value="individual" x-if="isDailyForm"/>
                        @else
                            <input type="hidden" name="scope" value="individual">
                            <div class="w-full bg-surface-container-low/60 border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-secondary flex items-center justify-between cursor-not-allowed">
                                <span>👤 Skala Individu</span>
                                <span class="text-[10px] bg-surface-container-highest px-1.5 py-0.5 rounded text-tertiary font-mono">Karyawan</span>
                            </div>
                            <p class="text-[10px] text-tertiary mt-1">*Tugas Skala Tim hanya dapat dibuat oleh Manager Departemen.</p>
                        @endif
                    </div>

                    <div x-show="currentScope === 'individual' || isDailyForm">
                        <label class="block text-xs font-mono text-tertiary mb-1">Tugaskan ke (PIC)</label>
                        @if($canCreateDeptTask)
                            <select name="assigned_to" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                                @foreach($deptEmployees as $e)
                                    <option value="{{ $e->id }}" {{ auth()->user()->employee_id == $e->id ? 'selected' : '' }}>
                                        {{ $e->name }} ({{ $e->role_title }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-tertiary mt-1">*Sebagai Manager, Anda dapat menugaskan ke staf atau diri sendiri.</p>
                        @else
                            <input type="hidden" name="assigned_to" value="{{ auth()->user()->employee_id }}">
                            <div class="w-full bg-surface-container-low/60 border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface flex items-center justify-between cursor-not-allowed">
                                <span>{{ auth()->user()->name }} (Diri Sendiri)</span>
                                <span class="text-[10px] bg-primary/20 text-primary-container px-1.5 py-0.5 rounded font-mono">PIC Otomatis</span>
                            </div>
                            <p class="text-[10px] text-tertiary mt-1">*Karyawan membuat task untuk diri sendiri.</p>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Tingkat Prioritas</label>
                        <select name="priority" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="low">Low (Santai)</option>
                            <option value="medium" selected>Medium (Standar)</option>
                            <option value="high">High (Tinggi)</option>
                            <option value="urgent">Urgent (Segera)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1" x-text="isDailyForm ? 'Tanggal Daily Task' : 'Target Selesai (Due Date)'"></label>
                        <template x-if="isDailyForm">
                            <input type="date" name="task_date" value="{{ now()->toDateString() }}" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                        </template>
                        <template x-if="!isDailyForm">
                            <input type="date" name="due_date" value="{{ now()->addDays(7)->toDateString() }}" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Deskripsi & Rincian Tugas</label>
                    <textarea name="description" rows="3" placeholder="Uraikan deliverables, instruksi kerja, atau catatan harian..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openTaskModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container text-xs font-semibold rounded-lg shadow-md" x-text="isDailyForm ? 'Simpan Daily Task' : 'Simpan Tugas'"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Detail Task -->
    <div x-cloak x-show="detailTaskModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-xl p-6 shadow-2xl relative" @click.away="detailTaskModal = false" x-show="detailTask">
            
            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-xl bg-surface-container-highest border border-[#333539] mt-0.5">
                        <span class="material-symbols-outlined text-primary-container text-xl" 
                              x-text="detailTask?.is_daily ? 'today' : (detailTask?.scope === 'department' ? 'groups' : 'person')"></span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <!-- Badge Daily Task -->
                            <template x-if="detailTask?.is_daily">
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-purple-950/70 text-purple-300 border border-purple-500/30 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-xs">today</span>
                                    <span>📅 Daily Task (<span x-text="detailTask?.task_date || detailTask?.due_date"></span>)</span>
                                </span>
                            </template>
                            <!-- Badge Scope -->
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase"
                                  :class="detailTask?.scope === 'department' ? 'bg-blue-950/60 text-blue-400 border border-blue-500/20' : 'bg-amber-950/60 text-amber-400 border border-amber-500/20'"
                                  x-text="detailTask?.scope === 'department' ? '👥 Skala Tim Departemen' : '👤 Skala Individu'">
                            </span>
                            <!-- Badge Priority -->
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase"
                                  :class="{
                                      'bg-red-950/60 text-red-400 border border-red-500/30': detailTask?.priority === 'urgent',
                                      'bg-amber-950/60 text-amber-400 border border-amber-500/30': detailTask?.priority === 'high',
                                      'bg-blue-950/60 text-blue-400 border border-blue-500/30': detailTask?.priority === 'medium',
                                      'bg-surface-container text-tertiary': detailTask?.priority === 'low'
                                  }"
                                  x-text="detailTask?.priority">
                            </span>
                            <!-- Dept Badge -->
                            <span class="px-2 py-0.5 rounded bg-surface-container-highest text-tertiary text-[10px] font-mono" x-text="'Divisi ' + detailTask?.department"></span>
                        </div>
                        <h3 class="font-headline font-bold text-lg text-on-surface leading-snug" x-text="detailTask?.title"></h3>
                    </div>
                </div>
                <button @click="detailTaskModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Modal Content Body -->
            <div class="mt-4 space-y-4">
                
                <!-- Deskripsi & Rincian Tugas (Menyesuaikan Kotak & Auto Wrap Baris Baru) -->
                <div class="bg-surface-container-low rounded-xl p-4 sm:p-5 border border-[#282a2e]/80 shadow-inner w-full min-w-0 overflow-hidden">
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <span class="material-symbols-outlined text-primary-container text-base">description</span>
                        <span class="text-xs font-mono uppercase tracking-wider text-tertiary font-semibold">Deskripsi & Rincian Tugas</span>
                    </div>
                    <div class="min-h-[100px] max-h-60 overflow-y-auto pr-2 w-full min-w-0" style="scrollbar-width: thin;">
                        <p class="text-sm text-on-surface leading-relaxed w-full min-w-0"
                           style="white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; word-wrap: break-word;"
                           x-text="detailTask?.description ? detailTask.description : 'Tidak ada deskripsi rincian yang disertakan untuk tugas ini.'">
                        </p>
                    </div>
                </div>

                <!-- Informasi PIC & Pembuat (Foto & Kartu Lebih Ringkas) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <div class="px-3 py-2 bg-surface-container-low rounded-lg border border-[#282a2e]/50 flex items-center gap-2.5">
                        <img :src="detailTask?.pic_avatar" class="w-7 h-7 rounded-full object-cover border border-[#333539] flex-shrink-0"/>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[9px] font-mono text-tertiary uppercase leading-tight">Penanggung Jawab (PIC)</span>
                            <span class="text-xs font-medium text-on-surface truncate block" x-text="detailTask?.pic_name"></span>
                            <span class="text-[10px] text-tertiary font-mono truncate block" x-text="detailTask?.pic_role"></span>
                        </div>
                    </div>

                    <div class="px-3 py-2 bg-surface-container-low rounded-lg border border-[#282a2e]/50 flex items-center gap-2.5">
                        <img :src="detailTask?.creator_avatar" class="w-7 h-7 rounded-full object-cover border border-[#333539] flex-shrink-0"/>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[9px] font-mono text-tertiary uppercase leading-tight">Dibuat Oleh</span>
                            <span class="text-xs font-medium text-on-surface truncate block" x-text="detailTask?.creator_name"></span>
                            <span class="text-[10px] text-tertiary font-mono truncate block" x-text="detailTask?.created_at"></span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar & Status -->
                <div class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono text-tertiary text-[10px] uppercase">Progres Pengerjaan</span>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold"
                                  :class="{
                                      'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20': detailTask?.status === 'completed',
                                      'bg-blue-950/60 text-blue-400 border border-blue-500/20': detailTask?.status === 'in_progress',
                                      'bg-amber-950/60 text-amber-400 border border-amber-500/20': detailTask?.status === 'review',
                                      'bg-surface-container text-tertiary': detailTask?.status === 'todo'
                                  }"
                                  x-text="detailTask?.status?.replace('_', ' ')">
                            </span>
                            <span class="font-bold text-on-surface font-mono" x-text="detailTask?.progress_percentage + '%'"></span>
                        </div>
                    </div>
                    <div class="w-full bg-surface-container-highest rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300"
                             :class="detailTask?.progress_percentage == 100 ? 'bg-emerald-400' : (detailTask?.progress_percentage > 40 ? 'bg-blue-400' : 'bg-amber-400')"
                             :style="'width: ' + detailTask?.progress_percentage + '%'"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-tertiary pt-1">
                        <span>Tenggat Waktu: <strong class="text-on-surface" x-text="detailTask?.due_date"></strong></span>
                        <span>Terakhir Diperbarui: <span x-text="detailTask?.updated_at"></span></span>
                    </div>
                </div>

                <!-- Update Status Form dari Modal Detail -->
                <form :action="detailTask?.update_url" method="POST" class="p-3 bg-surface-container-highest/40 rounded-xl border border-[#333539] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    @csrf
                    @method('PATCH')
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-tertiary text-sm">tune</span>
                        <span class="text-xs font-mono text-on-surface">Ubah Status & Progres:</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select name="status" x-model="detailTask.status" class="bg-surface-container-low border border-[#333539] text-xs text-on-surface rounded-lg px-2.5 py-1.5 focus:outline-none">
                            <option value="todo">To Do</option>
                            <option value="in_progress">In Progress</option>
                            <option value="review">Review</option>
                            <option value="completed">Completed</option>
                        </select>
                        <div class="relative flex items-center">
                            <input type="number" name="progress_percentage" x-model="detailTask.progress_percentage" min="0" max="100" step="5" class="w-16 bg-surface-container-low border border-[#333539] text-xs font-mono text-on-surface rounded-lg px-2 py-1.5 text-center focus:outline-none"/>
                            <span class="text-[10px] text-tertiary font-mono -ml-4 pointer-events-none">%</span>
                        </div>
                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-primary-container hover:bg-red-700 text-on-primary-container text-xs font-semibold flex items-center gap-1 shadow-sm transition-colors">
                            <span class="material-symbols-outlined text-sm">check</span>
                            <span>Simpan</span>
                        </button>
                    </div>
                </form>

            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end pt-4 border-t border-[#282a2e] mt-4">
                <button type="button" @click="detailTaskModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Tarik Data Task & KPI (Khusus Manager Departemen & Direktur) -->
    <div x-cloak x-show="openExportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75">
        <div class="bg-surface-container rounded-2xl border border-emerald-500/30 w-full max-w-md p-6 shadow-2xl relative" @click.away="openExportModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-950/60 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                        <span class="material-symbols-outlined text-xl">assessment</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Tarik Laporan Task & KPI</h3>
                        <p class="font-mono text-[11px] text-tertiary">Rekap tugas, progres tim, dan evaluasi PIC divisi</p>
                    </div>
                </div>
                <button @click="openExportModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Info Departemen -->
                <div class="p-3 rounded-xl bg-surface-container-low border border-[#282a2e]/60 flex items-center justify-between font-mono text-xs">
                    <span class="text-tertiary">Departemen:</span>
                    <span class="font-bold text-emerald-400">{{ auth()->user()->department }}</span>
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
                                <input type="radio" name="export_period" value="1_week" x-model="exportPeriod" class="accent-emerald-500"/>
                            </div>
                            <span class="font-bold text-xs mt-1">1 Minggu Terakhir</span>
                            <span class="text-[10px] font-mono opacity-70">7 hari ke belakang</span>
                        </label>

                        <label @click="exportPeriod = '1_month'" class="cursor-pointer p-3 rounded-xl border transition-all flex flex-col gap-1"
                               :class="exportPeriod === '1_month' ? 'bg-emerald-950/30 border-emerald-500 text-on-surface ring-1 ring-emerald-500/50' : 'bg-surface-container-low border-[#333539] text-tertiary hover:text-on-surface'">
                            <div class="flex items-center justify-between">
                                <span class="material-symbols-outlined text-lg" :class="exportPeriod === '1_month' ? 'text-emerald-400' : 'text-tertiary'">calendar_month</span>
                                <input type="radio" name="export_period" value="1_month" x-model="exportPeriod" class="accent-emerald-500"/>
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
                        <li>Indeks Skor KPI Departemen & Tingkat Selesai</li>
                        <li>Rekap Performa & Rata-rata Progres Per PIC</li>
                        <li>Daftar Rinci Task, Due Date, Status & Ketepatan Waktu</li>
                    </ul>
                </div>

                <!-- Tombol Aksi Download -->
                <div class="pt-3 border-t border-[#282a2e] space-y-2.5">
                    <a :href="'{{ route('tasks.export.excel') }}?period=' + exportPeriod + '&department={{ urlencode(auth()->user()->department) }}'"
                       class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-md transition-colors">
                        <span class="material-symbols-outlined text-base">table_view</span>
                        <span>Download Microsoft Excel (.xlsx)</span>
                    </a>

                    <a :href="'{{ route('tasks.export.pdf') }}?period=' + exportPeriod + '&department={{ urlencode(auth()->user()->department) }}'"
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
