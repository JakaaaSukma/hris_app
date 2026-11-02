@extends('layouts.app')

@section('title', 'Kalender & Jadwal Kerja')

@section('content')
<div class="flex flex-col gap-5 max-w-7xl mx-auto" x-data="{
    openScheduleModal: false,
    editMode: false,
    selectedSchedule: null,
    modalForm: {
        id: '',
        date: '{{ $selectedDate }}',
        title: '',
        category: 'jadwal',
        scope: '{{ auth()->user()->isKaryawan() ? 'individu' : 'departemen' }}',
        target_department: '{{ count($availableTargetDepartments ?? []) > 0 ? $availableTargetDepartments[0] : '' }}',
        target_departments: {{ json_encode(count($availableTargetDepartments ?? []) > 0 ? [$availableTargetDepartments[0]] : []) }},
        color: 'rose',
        time_start: '',
        time_end: '',
        description: '',
        is_company_wide: false
    },
    openCreateModal(dateVal = '{{ $selectedDate }}') {
        this.editMode = false;
        this.modalForm = {
            id: '',
            date: dateVal,
            title: '',
            category: 'jadwal',
            scope: '{{ auth()->user()->isKaryawan() ? 'individu' : 'departemen' }}',
            target_department: '{{ count($availableTargetDepartments ?? []) > 0 ? $availableTargetDepartments[0] : '' }}',
            target_departments: {{ json_encode(count($availableTargetDepartments ?? []) > 0 ? [$availableTargetDepartments[0]] : []) }},
            color: 'rose',
            time_start: '09:00',
            time_end: '11:00',
            description: '',
            is_company_wide: false
        };
        this.openScheduleModal = true;
    },
    openEditModal(schedule) {
        this.editMode = true;
        this.selectedSchedule = schedule;
        this.modalForm = {
            id: schedule.id,
            date: schedule.date_raw,
            title: schedule.title,
            category: schedule.category,
            scope: schedule.scope || 'individu',
            target_department: schedule.target_department || '',
            target_departments: Array.isArray(schedule.target_departments) && schedule.target_departments.length > 0 ? schedule.target_departments : (schedule.target_department ? schedule.target_department.split(',').map(s => s.trim()) : []),
            color: schedule.color,
            time_start: schedule.time_start_raw || '',
            time_end: schedule.time_end_raw || '',
            description: schedule.description || '',
            is_company_wide: !!schedule.is_company_wide
        };
        this.openScheduleModal = true;
    }
}">

    <!-- Top Navigation Bar: Month Selector on Left, Red Button '+ Tambah jadwal' on Right (Exact layout to screenshot) -->
    <div class="flex items-center justify-between gap-4 py-2">
        <!-- Month Navigator: [<] [Oktober 2026] [>] -->
        <div class="flex items-center gap-3">
            <a href="{{ route('calendar.index', array_merge(request()->query(), ['month' => $prevMonth->month, 'year' => $prevMonth->year, 'date' => $selectedDate])) }}" 
               class="w-10 h-10 rounded-xl bg-[#171719] hover:bg-[#232328] text-slate-300 hover:text-white border border-[#2b2b32] flex items-center justify-center transition-all shadow-sm active:scale-95"
               title="Bulan Sebelumnya">
                <span class="material-symbols-outlined text-lg">chevron_left</span>
            </a>

            <span class="font-headline font-bold text-base sm:text-lg text-white tracking-normal px-1">
                {{ $monthName }}
            </span>

            <a href="{{ route('calendar.index', array_merge(request()->query(), ['month' => $nextMonth->month, 'year' => $nextMonth->year, 'date' => $selectedDate])) }}" 
               class="w-10 h-10 rounded-xl bg-[#171719] hover:bg-[#232328] text-slate-300 hover:text-white border border-[#2b2b32] flex items-center justify-center transition-all shadow-sm active:scale-95"
               title="Bulan Berikutnya">
                <span class="material-symbols-outlined text-lg">chevron_right</span>
            </a>
        </div>

        <!-- Solid Vibrant Red Button: '+ Tambah jadwal' (Exact style to screenshot) -->
        <button type="button" 
                @click="openCreateModal('{{ $selectedDate }}')" 
                class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-[#e50914] hover:bg-[#c90812] text-white rounded-xl text-xs sm:text-sm font-semibold tracking-wide transition-all shadow-md active:scale-95">
            <span class="text-base font-bold leading-none">+</span>
            <span>Tambah jadwal</span>
        </button>
    </div>

    <!-- FILTER MONITORING KALENDER (Khusus Manager / Staf jika diperlukan) -->
    @if(!auth()->user()->isDirektur())
    <div class="p-3.5 sm:p-4 rounded-2xl bg-[#111113] border border-[#232329] shadow-lg flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 text-xs text-slate-300">
            <span class="w-8 h-8 rounded-xl bg-rose-500/10 text-rose-400 border border-rose-500/20 flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-lg">filter_alt</span>
            </span>
            <div>
                <span class="font-headline font-bold text-white block">Filter Jadwal</span>
                <span class="text-[11px] text-slate-400">Saring tampilan agenda departemen & pribadi</span>
            </div>
        </div>

        <form action="{{ route('calendar.index') }}" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">

            <!-- Filter Role / Cakupan -->
            <div class="flex items-center">
                <select name="filter_scope" onchange="this.form.submit()" 
                        class="bg-[#171719] border border-[#2b2b32] hover:border-slate-600 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 cursor-pointer">
                    <option value="all" {{ ($filterScope ?? 'all') === 'all' ? 'selected' : '' }}>🎯 Semua Cakupan</option>
                    <option value="departemen" {{ ($filterScope ?? '') === 'departemen' ? 'selected' : '' }}>👥 Jadwal Tim Departemen</option>
                    <option value="antar_departemen" {{ ($filterScope ?? '') === 'antar_departemen' ? 'selected' : '' }}>🌐 Jadwal Antar Departemen</option>
                    <option value="individu" {{ ($filterScope ?? '') === 'individu' ? 'selected' : '' }}>📌 Jadwal Individu</option>
                </select>
            </div>

            <!-- Tombol Reset Filter jika ada filter aktif -->
            @if(($filterScope ?? 'all') !== 'all')
                <a href="{{ route('calendar.index', ['month' => $month, 'year' => $year, 'date' => $selectedDate]) }}" 
                   class="px-2.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 text-xs font-semibold transition-all flex items-center gap-1"
                   title="Reset Filter">
                    <span class="material-symbols-outlined text-sm">refresh</span>
                    <span>Reset</span>
                </a>
            @endif
        </form>
    </div>
    @endif

    <!-- PENDING SCHEDULES REVIEW ALERT BANNER (Jika ada pengajuan jadwal yang butuh di-review oleh user ini) -->
    @if(isset($pendingReviewsList) && $pendingReviewsList->isNotEmpty())
        <div class="p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border border-amber-500/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 border border-amber-500/40">
                    <span class="material-symbols-outlined text-xl">assignment_late</span>
                </span>
                <div>
                    <h4 class="font-headline font-bold text-sm text-white flex items-center gap-2">
                        <span>Menunggu Review & Persetujuan Anda ({{ $pendingReviewsList->count() }} Jadwal)</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-amber-400/20 text-amber-300 border border-amber-400/30">Perlu Konfirmasi</span>
                    </h4>
                    <p class="text-xs text-slate-400">
                        Ada jadwal departemen atau antar departemen yang diajukan ke Anda dan belum disetujui. Silakan periksa detailnya di bawah.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="#pending-reviews-section" class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-bold transition-all shadow flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                    <span>Review Jadwal ({{ $pendingReviewsList->count() }})</span>
                </a>
            </div>
        </div>
    @endif

    <!-- MAIN TWO-PANEL LAYOUT (Exact layout to screenshot) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- KIRI: KALENDER BOX BESAR (lg:col-span-7) -->
        <div class="lg:col-span-7 bg-[#111113] rounded-2xl border border-[#232329] p-5 shadow-lg">
            
            <!-- Day of Week Row: Sen, Sel, Rab, Kam, Jum, Sab, Min -->
            <div class="grid grid-cols-7 text-center font-headline text-xs font-semibold text-slate-400 pb-4">
                <div>Sen</div>
                <div>Sel</div>
                <div>Rab</div>
                <div>Kam</div>
                <div>Jum</div>
                <div>Sab</div>
                <div>Min</div>
            </div>

            <!-- Month Days Grid with Outer Border and Clean Inner Grid Cells -->
            <div class="border border-[#26262e] rounded-xl overflow-hidden bg-[#111113]">
                <div class="grid grid-cols-7 divide-x divide-y divide-[#26262e]">
                    
                    <!-- Leading Blank Cells before day 1 -->
                    @for($i = 0; $i < $leadingBlanks; $i++)
                        <div class="h-20 sm:h-24 bg-[#0e0e10]/80"></div>
                    @endfor

                    <!-- Active Month Days 1 to 31 -->
                    @foreach($monthDays as $day)
                        @php
                            $isSelected = $day['isSelected'];
                            $hasSchedules = $day['scheduleCount'] > 0;
                            $firstSchedule = $day['schedules']->first();
                        @endphp
                        <a href="{{ route('calendar.index', array_merge(request()->query(), ['month' => $month, 'year' => $year, 'date' => $day['date']])) }}" 
                           class="h-20 sm:h-24 p-2 sm:p-2.5 flex flex-col justify-between transition-colors relative block text-left group {{ $isSelected ? 'bg-[#261217]' : 'hover:bg-[#1a1a1f]' }}">
                            
                            <!-- Day Number (Top Left, colored reddish/white if selected) -->
                            <div class="flex items-center justify-between">
                                <span class="font-headline text-xs sm:text-sm font-semibold {{ $isSelected ? 'text-[#ff4d6d] font-bold' : ($day['isToday'] ? 'text-rose-400 font-bold' : 'text-slate-300 group-hover:text-white') }}">
                                    {{ $day['day'] }}
                                </span>

                                @if($hasSchedules)
                                    <div class="flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $isSelected ? 'bg-[#ff4d6d]' : 'bg-rose-500' }}"></span>
                                    </div>
                                @endif
                            </div>

                            <!-- Small Label/Pill for schedules on this day -->
                            @if($hasSchedules)
                                <div class="truncate text-[10px] font-medium text-slate-300 leading-tight">
                                    <span class="truncate block opacity-90">{{ $firstSchedule->title }}</span>
                                    <div class="flex items-center justify-between gap-1 mt-0.5">
                                        <span class="text-[9px] font-mono text-slate-400 truncate">
                                            {{ $firstSchedule->creator->name ?? ($firstSchedule->department ?? 'General') }}
                                        </span>
                                        @if($day['scheduleCount'] > 1)
                                            <span class="text-[9px] text-[#ff4d6d] font-mono font-semibold shrink-0">+{{ $day['scheduleCount'] - 1 }}</span>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div></div>
                            @endif
                        </a>
                    @endforeach

                    <!-- Trailing Empty Cells to complete the last row -->
                    @php
                        $totalCells = $leadingBlanks + count($monthDays);
                        $remainingCells = (7 - ($totalCells % 7)) % 7;
                    @endphp
                    @for($j = 0; $j < $remainingCells; $j++)
                        <div class="h-20 sm:h-24 bg-[#0e0e10]/80"></div>
                    @endfor

                </div>
            </div>
        </div>

        <!-- KANAN: PANEL CATATAN / JADWAL (lg:col-span-5) (Exact title "Jadwal · 14") -->
        <div class="lg:col-span-5 bg-[#111113] rounded-2xl border border-[#232329] p-6 shadow-lg min-h-[460px] flex flex-col justify-start">
            
            <!-- Header: "Jadwal · 14" (Exact typography to screenshot) -->
            <div class="flex items-center justify-between pb-4 border-b border-[#232329]">
                <h2 class="font-headline font-bold text-base sm:text-lg text-white tracking-normal flex items-center gap-2">
                    <span>Jadwal · {{ $selectedDayNum }}</span>
                </h2>

                <button type="button" 
                        @click="openCreateModal('{{ $selectedDate }}')" 
                        class="text-xs text-rose-400 hover:text-rose-300 font-medium flex items-center gap-1 transition-colors">
                    <span class="material-symbols-outlined text-sm">add</span>
                    <span>Tambah</span>
                </button>
            </div>

            <!-- Content Area: Empty State "Tidak ada jadwal pada tanggal ini." OR List of cards -->
            <div class="mt-6 flex-1 flex flex-col">
                @if($selectedDateSchedules->isEmpty())
                    <!-- Center Empty State matching screenshot text -->
                    <div class="flex-1 flex items-center justify-center text-center py-16 px-4">
                        <p class="font-headline text-xs sm:text-sm text-slate-400">
                            Tidak ada jadwal pada tanggal ini.
                        </p>
                    </div>
                @else
                    <!-- List of items for selected date -->
                    <div class="flex flex-col gap-3 overflow-y-auto max-h-[520px] pr-1">
                        @foreach($selectedDateSchedules as $item)
                            @php
                                $badgeColor = match($item->color) {
                                    'rose' => 'bg-rose-500/10 border-rose-500/30 text-rose-300',
                                    'emerald' => 'bg-emerald-500/10 border-emerald-500/30 text-emerald-300',
                                    'amber' => 'bg-amber-500/10 border-amber-500/30 text-amber-300',
                                    'blue' => 'bg-blue-500/10 border-blue-500/30 text-blue-300',
                                    default => 'bg-slate-800 border-slate-700 text-slate-300'
                                };

                                // Check approval capability for current user
                                $canCurrentUserApprove = false;
                                if ($item->isPending() && $item->user_id !== auth()->id()) {
                                    if ($item->scope === 'departemen') {
                                        // Anggota departemen yang sama (atau Direktur) dan belum menyetujui
                                        $alreadyApproved = $item->approvals->where('user_id', auth()->id())->where('status', 'approved')->isNotEmpty();
                                        if (!$alreadyApproved && (auth()->user()->isDirektur() || strcasecmp(auth()->user()->department ?? '', $item->department ?? '') === 0)) {
                                            $canCurrentUserApprove = true;
                                        }
                                    } elseif ($item->scope === 'antar_departemen') {
                                        $alreadyApproved = $item->approvals->where('user_id', auth()->id())->where('status', 'approved')->isNotEmpty();
                                        // Jadwal Direksi: HANYA Sekretaris Direktur yang berhak menyetujui
                                        if ($item->hasTargetDepartment('Executive') || $item->hasTargetDepartment('Direksi')) {
                                            if (!$alreadyApproved && auth()->user()->isSekretaris()) {
                                                $canCurrentUserApprove = true;
                                            }
                                        } else {
                                            // Manager departemen target yang belum menyetujui
                                            if (!$alreadyApproved && (auth()->user()->isDirektur() || (auth()->user()->isManager() && $item->hasTargetDepartment(auth()->user()->department)))) {
                                                $canCurrentUserApprove = true;
                                            }
                                        }
                                    }
                                }
                            @endphp
                            <div class="p-4 rounded-xl bg-[#16161a] border {{ $item->isPending() ? 'border-amber-500/30' : 'border-[#26262e]' }} space-y-2.5 hover:border-[#383842] transition-colors relative group">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-semibold border {{ $badgeColor }}">
                                            {{ $item->category }}
                                        </span>

                                        <!-- Scope Badge -->
                                        @if($item->scope === 'individu')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-slate-800 text-slate-300 border border-slate-700">
                                                Individu
                                            </span>
                                        @elseif($item->scope === 'departemen')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-500/10 text-blue-300 border border-blue-500/30">
                                                Tim {{ $item->department }}
                                            </span>
                                        @elseif($item->scope === 'antar_departemen')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-purple-500/10 text-purple-300 border border-purple-500/30">
                                                {{ $item->department }} ➔ {{ implode(', ', $item->getTargetDepartmentsList()) }}
                                            </span>
                                        @endif

                                        <!-- Approval Status Badge -->
                                        @if($item->isPending())
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-amber-500/15 text-amber-300 border border-amber-500/30 flex items-center gap-1 font-semibold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                                Pending Persetujuan
                                            </span>
                                        @elseif($item->isApproved() && $item->scope !== 'individu')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[11px]">check_circle</span>
                                                Disetujui
                                            </span>
                                        @elseif($item->isRejected())
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-rose-500/15 text-rose-300 border border-rose-500/30 flex items-center gap-1">
                                                <span class="material-symbols-outlined text-[11px]">cancel</span>
                                                Ditolak
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Edit & Delete Action Buttons -->
                                    @if($item->user_id === auth()->id() || auth()->user()->isDirektur() || auth()->user()->isHR())
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button type="button" 
                                                    @click="openEditModal({
                                                        id: {{ $item->id }},
                                                        date_raw: '{{ Carbon\Carbon::parse($item->date)->toDateString() }}',
                                                        title: '{{ addslashes($item->title) }}',
                                                        category: '{{ $item->category }}',
                                                        scope: '{{ $item->scope }}',
                                                        target_department: '{{ $item->target_department }}',
                                                        target_departments: {{ json_encode($item->getTargetDepartmentsList()) }},
                                                        color: '{{ $item->color }}',
                                                        time_start_raw: '{{ $item->time_start ? substr($item->time_start, 0, 5) : '' }}',
                                                        time_end_raw: '{{ $item->time_end ? substr($item->time_end, 0, 5) : '' }}',
                                                        description: '{{ addslashes($item->description ?? '') }}',
                                                        is_company_wide: {{ $item->is_company_wide ? 'true' : 'false' }}
                                                    })" 
                                                    class="p-1 rounded text-slate-400 hover:text-white" title="Edit">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                            </button>
                                            
                                            <form action="{{ route('calendar.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jadwal ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-1 rounded text-slate-400 hover:text-rose-400" title="Hapus">
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                                <h3 class="font-headline font-bold text-sm text-white leading-snug">
                                    {{ $item->title }}
                                </h3>

                                @if($item->time_start || $item->time_end)
                                    <div class="flex items-center gap-1.5 font-mono text-xs text-slate-400">
                                        <span class="material-symbols-outlined text-sm text-rose-400">schedule</span>
                                        <span>
                                            {{ $item->time_start ? substr($item->time_start, 0, 5) : '' }} 
                                            {{ $item->time_end ? ' - ' . substr($item->time_end, 0, 5) : '' }} WIB
                                        </span>
                                    </div>
                                @endif

                                @if($item->description)
                                    <p class="text-xs text-slate-400 font-body leading-relaxed">
                                        {{ $item->description }}
                                    </p>
                                @endif

                                <!-- Information of Approver or Rejection Note -->
                                @if($item->isApproved() && $item->approver)
                                    <div class="text-[11px] text-emerald-400/90 font-mono bg-emerald-500/5 px-2.5 py-1.5 rounded-lg border border-emerald-500/20 flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-xs">verified</span>
                                        <span>Disetujui oleh: <strong>{{ $item->approver->name }}</strong> ({{ $item->approver->role }})</span>
                                    </div>
                                @elseif($item->isRejected() && $item->rejection_notes)
                                    <div class="text-[11px] text-rose-400/90 font-mono bg-rose-500/5 px-2.5 py-1.5 rounded-lg border border-rose-500/20">
                                        Alasan Penolakan: {{ $item->rejection_notes }}
                                    </div>
                                @endif

                                <!-- APPROVAL ACTION BUTTONS (For eligible members/managers) -->
                                @if($canCurrentUserApprove)
                                    <div class="p-2.5 rounded-lg bg-[#0e1017] border border-amber-500/30 flex items-center justify-between gap-2 mt-2">
                                        <div class="text-[11px] text-amber-300 font-mono">
                                            @if($item->hasTargetDepartment('Executive') || $item->hasTargetDepartment('Direksi'))
                                                Persetujuan Sekretaris Direktur:
                                            @elseif($item->scope === 'departemen')
                                                Konfirmasi jadwal departemen:
                                            @else
                                                Persetujuan Manager {{ auth()->user()->isDirektur() ? '(' . implode(', ', $item->getTargetDepartmentsList()) . ')' : auth()->user()->department }}:
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <form action="{{ route('calendar.reject', $item->id) }}" method="POST" class="inline" onsubmit="
                                                let reason = prompt('Masukkan alasan penolakan jadwal (wajib diisi):');
                                                if (!reason || reason.trim() === '') {
                                                    alert('Alasan penolakan wajib diisi!');
                                                    return false;
                                                }
                                                this.querySelector('input[name=notes]').value = reason;
                                                return true;
                                            ">
                                                @csrf
                                                <input type="hidden" name="notes" value="">
                                                <button type="submit" class="px-2.5 py-1 rounded-md bg-rose-500/20 hover:bg-rose-500 text-rose-300 hover:text-white border border-rose-500/40 text-[11px] font-semibold transition-all" title="Tolak Jadwal (Wajib Alasan)">
                                                    Tolak
                                                </button>
                                            </form>
                                            <form action="{{ route('calendar.approve', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold shadow transition-all flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">check</span>
                                                    <span>Setujui</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                <div class="pt-2 border-t border-[#232329] flex items-center justify-between text-[11px] font-mono text-slate-400">
                                    <div class="flex items-center gap-1.5 truncate">
                                        <span class="w-5 h-5 rounded-full bg-slate-800 text-slate-300 border border-slate-700 flex items-center justify-center text-[10px] font-bold shrink-0">
                                            {{ strtoupper(substr($item->creator->name ?? 'U', 0, 1)) }}
                                        </span>
                                        <div class="truncate">
                                            <span class="text-slate-200 font-semibold">{{ $item->creator->name ?? 'User' }}</span>
                                            <span class="text-[10px] text-slate-400">({{ ucfirst($item->creator->role ?? 'karyawan') }} · {{ $item->department ?? ($item->creator->department ?? 'General') }})</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-500 shrink-0">{{ $item->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- DAFTAR PENGAJUAN JADWAL YANG MEMERLUKAN REVIEW & PERSETUJUAN -->
    @if(isset($pendingReviewsList) && $pendingReviewsList->isNotEmpty())
        <div id="pending-reviews-section" class="bg-[#111113] rounded-2xl border border-amber-500/30 p-6 shadow-xl scroll-mt-6">
            <div class="flex items-center justify-between pb-4 border-b border-[#232329]">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">
                        <span class="material-symbols-outlined text-lg">rate_review</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Daftar Pengajuan Jadwal Menunggu Review Anda</h3>
                        <p class="text-xs text-slate-400">Jadwal harus di-review terlebih dahulu sebelum diberikan persetujuan atau penolakan</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono bg-amber-500/10 text-amber-400 border border-amber-500/30 font-semibold">
                    {{ $pendingReviewsList->count() }} Perlu Respon
                </span>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($pendingReviewsList as $pendingItem)
                    <div class="p-4 rounded-xl bg-[#16161a] border border-[#26262e] hover:border-amber-500/50 transition-all flex flex-col justify-between gap-3">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                @if($pendingItem->hasTargetDepartment('Executive') || $pendingItem->hasTargetDepartment('Direksi'))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-500/10 text-amber-300 border border-amber-500/30 font-semibold">
                                        Jadwal Direksi (via Sekretaris)
                                    </span>
                                @elseif($pendingItem->scope === 'departemen')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-500/10 text-blue-300 border border-blue-500/30">
                                        Jadwal Departemen {{ $pendingItem->department }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-500/10 text-purple-300 border border-purple-500/30">
                                        Antar Dept: {{ $pendingItem->department }} ➔ {{ implode(', ', $pendingItem->getTargetDepartmentsList()) }}
                                    </span>
                                @endif
                                <span class="text-[10px] font-mono text-slate-400">
                                    {{ \Carbon\Carbon::parse($pendingItem->date)->translatedFormat('d M Y') }}
                                </span>
                            </div>

                            <h4 class="font-headline font-bold text-sm text-white">
                                {{ $pendingItem->title }}
                            </h4>

                            @if($pendingItem->time_start || $pendingItem->time_end)
                                <div class="flex items-center gap-1.5 font-mono text-xs text-slate-400">
                                    <span class="material-symbols-outlined text-xs text-rose-400">schedule</span>
                                    <span>{{ substr($pendingItem->time_start, 0, 5) }} - {{ substr($pendingItem->time_end, 0, 5) }} WIB</span>
                                </div>
                            @endif

                            @if($pendingItem->description)
                                <p class="text-xs text-slate-400 line-clamp-2">
                                    {{ $pendingItem->description }}
                                </p>
                            @endif

                            <div class="text-[11px] font-mono text-slate-400 pt-2 border-t border-[#232329]">
                                Diajukan oleh: <strong class="text-slate-200">{{ $pendingItem->creator->name ?? 'User' }}</strong> ({{ $pendingItem->creator->role ?? 'Manager' }})
                            </div>
                        </div>

                        <!-- Action Review Buttons -->
                        <div class="pt-3 border-t border-[#232329] flex items-center justify-between gap-2">
                            <form action="{{ route('calendar.reject', $pendingItem->id) }}" method="POST" class="inline flex-1" onsubmit="
                                let reason = prompt('Masukkan alasan penolakan jadwal (wajib diisi):');
                                if (!reason || reason.trim() === '') {
                                    alert('Alasan penolakan wajib diisi!');
                                    return false;
                                }
                                this.querySelector('input[name=notes]').value = reason;
                                return true;
                            ">
                                @csrf
                                <input type="hidden" name="notes" value="">
                                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-rose-500/10 hover:bg-rose-500 text-rose-300 hover:text-white border border-rose-500/30 text-xs font-semibold transition-all" title="Tolak Jadwal (Wajib Alasan)">
                                    Tolak Jadwal
                                </button>
                            </form>
                            <form action="{{ route('calendar.approve', $pendingItem->id) }}" method="POST" class="inline flex-1">
                                @csrf
                                <button type="submit" class="w-full py-2 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold transition-all shadow flex items-center justify-center gap-1">
                                    <span class="material-symbols-outlined text-sm">check</span>
                                    <span>Review & Setujui</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- MODAL POPUP: Tambah & Edit Jadwal / Catatan -->
    <div x-cloak x-show="openScheduleModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-[#141822] rounded-2xl border border-slate-700/80 w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto"
             @click.away="openScheduleModal = false">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-[#e50914] text-2xl">event_available</span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white" x-text="editMode ? 'Edit Jadwal / Catatan' : 'Tambah Jadwal / Catatan Baru'"></h3>
                        <p class="text-xs text-slate-400">Kelola agenda jadwal dan kegiatan harian</p>
                    </div>
                </div>
                <button type="button" @click="openScheduleModal = false" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="editMode ? '{{ url('calendar') }}/' + modalForm.id : '{{ route('calendar.store') }}'" 
                  method="POST" 
                  class="mt-5 space-y-4">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <!-- SCOPE JADWAL / CAKUPAN PENGAJUAN -->
                @if(auth()->user()->isKaryawan())
                    <!-- Karyawan: Bisa memilih Individu (Pribadi) ATAU Pengajuan Jadwal ke Direksi (via Sekretaris) -->
                    <div class="p-3.5 rounded-xl bg-[#0e1017] border border-slate-800 space-y-2.5">
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400">Cakupan / Tingkat Jadwal *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="modalForm.scope === 'individu' ? 'bg-rose-500/10 border-rose-500/50 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="scope" value="individu" x-model="modalForm.scope" class="accent-rose-500">
                                <div>
                                    <span class="text-xs font-semibold block">Individu (Pribadi)</span>
                                    <span class="text-[10px] text-slate-400 font-mono">Langsung dijadwalkan</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="modalForm.scope === 'antar_departemen' ? 'bg-amber-500/10 border-amber-500/50 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="scope" value="antar_departemen" x-model="modalForm.scope" @click="modalForm.target_departments = ['Executive']" class="accent-amber-500">
                                <div>
                                    <span class="text-xs font-semibold block text-amber-300">Ke Direksi (via Sekretaris)</span>
                                    <span class="text-[10px] text-slate-400 font-mono">Review Sekretaris Direktur</span>
                                </div>
                            </label>
                        </div>
                        <div x-show="modalForm.scope === 'antar_departemen'" class="pt-1">
                            <input type="hidden" name="target_departments[]" value="Executive" :disabled="modalForm.scope !== 'antar_departemen'">
                            <p class="text-[11px] text-amber-400 font-mono flex items-center gap-1.5 bg-amber-500/10 p-2 rounded-lg border border-amber-500/30">
                                <span class="material-symbols-outlined text-xs">info</span>
                                Pengajuan jadwal ke Direktur wajib melalui verifikasi & persetujuan Sekretaris Direktur terlebih dahulu.
                            </p>
                        </div>
                    </div>
                @else
                    <!-- Manager / Direktur / Sekretaris: Bisa pilih Individu, Departemen, atau Antar Departemen -->
                    <div class="p-3.5 rounded-xl bg-[#0e1017] border border-slate-800 space-y-2.5">
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400">Cakupan / Tingkat Jadwal *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="modalForm.scope === 'individu' ? 'bg-rose-500/10 border-rose-500/50 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="scope" value="individu" x-model="modalForm.scope" class="accent-rose-500">
                                <span class="text-xs font-semibold">Individu</span>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="modalForm.scope === 'departemen' ? 'bg-rose-500/10 border-rose-500/50 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="scope" value="departemen" x-model="modalForm.scope" class="accent-rose-500">
                                <span class="text-xs font-semibold">Departemen</span>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-colors"
                                   :class="modalForm.scope === 'antar_departemen' ? 'bg-rose-500/10 border-rose-500/50 text-white' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                <input type="radio" name="scope" value="antar_departemen" x-model="modalForm.scope" class="accent-rose-500">
                                <span class="text-xs font-semibold">Antar Dept / Direksi</span>
                            </label>
                        </div>

                        <!-- Keterangan Scope Departemen -->
                        <div x-show="modalForm.scope === 'departemen'" class="pt-1">
                            <p class="text-[11px] text-amber-400 font-mono flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-xs">info</span>
                                Jadwal diajukan untuk Departemen {{ auth()->user()->department }} & disetujui oleh karyawan di departemen.
                            </p>
                        </div>

                        <!-- Pilihan Target Departemen jika Antar Departemen (Bisa Lebih Dari 1) -->
                        <div x-show="modalForm.scope === 'antar_departemen'" class="pt-2">
                            <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">
                                Pilih Departemen Tujuan * <span class="text-rose-400 text-[10px] lowercase font-sans">(bisa pilih lebih dari 1)</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2 p-2.5 bg-[#0e1017] rounded-xl border border-slate-700/80">
                                @foreach($availableTargetDepartments ?? ['HR', 'Finance', 'Bisnis Development', 'GA', 'Resepsionis', 'Trainer', 'Executive'] as $targetDept)
                                    <label class="flex items-center gap-2 p-2 rounded-lg border cursor-pointer transition-all text-xs"
                                           :class="modalForm.target_departments.includes('{{ $targetDept }}') ? 'bg-purple-500/15 border-purple-500/60 text-white font-semibold' : 'border-slate-800 text-slate-400 hover:border-slate-700'">
                                        <input type="checkbox" 
                                               name="target_departments[]" 
                                               value="{{ $targetDept }}" 
                                               x-model="modalForm.target_departments" 
                                               class="accent-purple-500 rounded">
                                        <span>{{ $targetDept === 'Executive' ? 'Direksi (via Sekretaris)' : $targetDept }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-purple-400 font-mono mt-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">approval</span>
                                Jadwal ke departemen di-review oleh Manager terkait. Jadwal ke Direksi di-review oleh Sekretaris Direktur.
                            </p>
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Nama Jadwal / Agenda *</label>
                    <input type="text" 
                           name="title" 
                           x-model="modalForm.title" 
                           required 
                           placeholder="contoh: Jadwal Kerja / Meeting / Supervisi..." 
                           class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#e50914]"/>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Tanggal *</label>
                        <input type="date" 
                               name="date" 
                               x-model="modalForm.date" 
                               required 
                               class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-[#e50914] font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Kategori</label>
                        <select name="category" 
                                x-model="modalForm.category" 
                                required 
                                class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-[#e50914] font-mono">
                            <option value="jadwal">📅 Jadwal Kerja</option>
                            <option value="kunjungan">📍 Kunjungan (Visit)</option>
                            <option value="meeting">👥 Meeting / Rapat</option>
                            <option value="tugas">✓ Tugas & Deadline</option>
                            <option value="catatan">📝 Catatan / Agenda</option>
                            <option value="pengumuman">📢 Pengumuman Umum</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Jam Mulai</label>
                        <input type="time" 
                               name="time_start" 
                               x-model="modalForm.time_start" 
                               class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-[#e50914] font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Jam Selesai</label>
                        <input type="time" 
                               name="time_end" 
                               x-model="modalForm.time_end" 
                               class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-[#e50914] font-mono"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Pilih Warna Label</label>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="color" value="rose" x-model="modalForm.color" class="accent-rose-500"/>
                            <span class="w-3.5 h-3.5 rounded-full bg-rose-500"></span>
                            <span class="text-xs text-slate-300">Merah (Rose)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="color" value="blue" x-model="modalForm.color" class="accent-blue-500"/>
                            <span class="w-3.5 h-3.5 rounded-full bg-blue-500"></span>
                            <span class="text-xs text-slate-300">Biru</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="color" value="emerald" x-model="modalForm.color" class="accent-emerald-500"/>
                            <span class="w-3.5 h-3.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs text-slate-300">Hijau</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="color" value="amber" x-model="modalForm.color" class="accent-amber-500"/>
                            <span class="w-3.5 h-3.5 rounded-full bg-amber-500"></span>
                            <span class="text-xs text-slate-300">Kuning</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1.5">Deskripsi / Catatan Tambahan</label>
                    <textarea name="description" 
                              x-model="modalForm.description" 
                              rows="3" 
                              placeholder="Detail jadwal, tujuan, lokasi, atau pihak yang terlibat..." 
                              class="w-full bg-[#0e1017] border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-[#e50914]"></textarea>
                </div>

                @if(auth()->user()->isDirektur() || auth()->user()->isHR() || auth()->user()->isManager())
                    <div class="flex items-center gap-2 p-3 bg-[#0e1017] rounded-xl border border-slate-800">
                        <input type="checkbox" 
                               name="is_company_wide" 
                               value="1" 
                               id="company_wide" 
                               x-model="modalForm.is_company_wide" 
                               class="accent-[#e50914] rounded"/>
                        <label for="company_wide" class="text-xs text-slate-300 cursor-pointer">
                            Tampilkan agenda ini untuk <strong>seluruh akun karyawan/manager</strong> di perusahaan
                        </label>
                    </div>
                @endif

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" 
                            @click="openScheduleModal = false" 
                            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs text-slate-300 rounded-xl font-medium transition-all">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 bg-[#e50914] hover:bg-[#c90812] text-white rounded-xl text-xs font-bold transition-all shadow-md">
                        <span x-text="editMode ? 'Simpan Perubahan' : 'Simpan Jadwal'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
