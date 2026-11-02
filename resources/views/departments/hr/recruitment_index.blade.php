@extends('layouts.app')

@section('title', 'HR - Rekrutmen & Onboarding Karyawan')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openVacancyModal: false, 
    openCandidateModal: false, 
    detailCandidateModal: false, 
    selectedCandidate: null,
    activeTab: 'pipeline' 
}">

    <!-- Header Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Department Feature • Human Resources (HR)</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Talent Acquisition & Onboarding</h1>
            <p class="font-mono text-xs text-tertiary">Kelola lowongan pekerjaan, alur seleksi kandidat (pipeline), dan checklist orientasi onboarding pegawai baru</p>
        </div>

        <div class="flex items-center gap-2.5">
            <button @click="openVacancyModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high hover:bg-surface-bright text-on-surface border border-[#333539] rounded-lg text-xs font-semibold transition-all shadow-sm">
                <span class="material-symbols-outlined text-base text-rose-400">post_add</span>
                <span>Buat Lowongan</span>
            </button>

            <button @click="openCandidateModal = true" class="flex items-center gap-1.5 px-4 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded-lg text-xs font-semibold transition-all shadow-md">
                <span class="material-symbols-outlined text-base">person_add</span>
                <span>Tambah Pelamar</span>
            </button>
        </div>
    </div>

    <!-- Metric Cards -->
    @php
        $screeningPct = $totalCandidates > 0 ? round(($screeningCount / $totalCandidates) * 100, 1) : 0;
        $offeringPct = $totalCandidates > 0 ? round(($offeringCount / $totalCandidates) * 100, 1) : 0;
        $onboardingPct = $totalCandidates > 0 ? round(($onboardingCount / $totalCandidates) * 100, 1) : 0;
    @endphp
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <span class="font-mono text-[10px] text-tertiary uppercase">Total Pelamar Masuk</span>
                <div class="font-headline text-2xl font-bold text-on-surface mt-1">{{ $totalCandidates }} Pelamar</div>
            </div>
            <div class="p-2.5 rounded-lg bg-surface-container text-tertiary">
                <span class="material-symbols-outlined text-xl">folder_shared</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Screening & Interview</span>
                    <span class="px-2 py-0.5 rounded bg-blue-950/60 border border-blue-500/30 text-blue-400 font-mono text-[10px] font-bold">
                        {{ $screeningPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-blue-400 mt-1">{{ $screeningCount }} Kandidat</div>
            </div>
            <div class="p-2.5 rounded-lg bg-blue-950/40 text-blue-400 border border-blue-500/20">
                <span class="material-symbols-outlined text-xl">psychology</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Offering Letter</span>
                    <span class="px-2 py-0.5 rounded bg-amber-950/60 border border-amber-500/30 text-amber-400 font-mono text-[10px] font-bold">
                        {{ $offeringPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-amber-400 mt-1">{{ $offeringCount }} Nego</div>
            </div>
            <div class="p-2.5 rounded-lg bg-amber-950/40 text-amber-400 border border-amber-500/20">
                <span class="material-symbols-outlined text-xl">contract_edit</span>
            </div>
        </div>

        <div class="bg-surface-container-low p-4 rounded-xl border border-[#282a2e]/50 flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-[10px] text-tertiary uppercase">Sedang Onboarding</span>
                    <span class="px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 font-mono text-[10px] font-bold">
                        Hired: {{ $onboardingPct }}%
                    </span>
                </div>
                <div class="font-headline text-2xl font-bold text-emerald-400 mt-1">{{ $onboardingCount }} Pegawai Baru</div>
            </div>
            <div class="p-2.5 rounded-lg bg-emerald-950/40 text-emerald-400 border border-emerald-500/20">
                <span class="material-symbols-outlined text-xl">how_to_reg</span>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs: Pipeline Rekrutmen vs Checklist Onboarding vs Lowongan Aktif -->
    <div class="flex items-center gap-2 border-b border-[#282a2e]/60 pb-1">
        <button @click="activeTab = 'pipeline'" 
                :class="activeTab === 'pipeline' ? 'text-rose-400 border-rose-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">view_kanban</span>
            <span>Pipeline Pelamar Rekrutmen</span>
        </button>

        <button @click="activeTab = 'onboarding'" 
                :class="activeTab === 'onboarding' ? 'text-emerald-400 border-emerald-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">checklist</span>
            <span>Checklist Onboarding ({{ $onboardingList->count() }})</span>
        </button>

        <button @click="activeTab = 'vacancies'" 
                :class="activeTab === 'vacancies' ? 'text-blue-400 border-blue-500 font-semibold' : 'text-tertiary border-transparent hover:text-on-surface'"
                class="px-4 py-2 border-b-2 text-xs font-mono flex items-center gap-2 transition-all">
            <span class="material-symbols-outlined text-base">work</span>
            <span>Daftar Lowongan Pekerjaan ({{ $vacancies->count() }})</span>
        </button>
    </div>

    <!-- TAB 1: PIPELINE REKRUTMEN -->
    <div x-show="activeTab === 'pipeline'" class="space-y-4">
        <!-- Filter Bar -->
        <div class="p-3.5 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-3">
            <form action="{{ route('hr.recruitment.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
                <select name="vacancy_id" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Lowongan / Posisi</option>
                    @foreach($vacancies as $v)
                        <option value="{{ $v->id }}" {{ $vacancyFilter == $v->id ? 'selected' : '' }}>{{ $v->title }} ({{ $v->department }})</option>
                    @endforeach
                </select>

                <select name="stage" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                    <option value="">Semua Tahapan Seleksi</option>
                    <option value="applied" {{ $stageFilter === 'applied' ? 'selected' : '' }}>Applied (Berkas Masuk)</option>
                    <option value="screening" {{ $stageFilter === 'screening' ? 'selected' : '' }}>Screening CV</option>
                    <option value="interview" {{ $stageFilter === 'interview' ? 'selected' : '' }}>Interview HR / User</option>
                    <option value="offering" {{ $stageFilter === 'offering' ? 'selected' : '' }}>Offering Stage</option>
                    <option value="hired" {{ $stageFilter === 'hired' ? 'selected' : '' }}>Hired (Masuk Onboarding)</option>
                    <option value="rejected" {{ $stageFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                @if($stageFilter || $vacancyFilter)
                    <a href="{{ route('hr.recruitment.index') }}" class="text-xs text-tertiary hover:underline">Reset</a>
                @endif
            </form>
        </div>

        <!-- Tabel Pelamar -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-[#282a2e]/60 bg-surface-container font-mono text-[10px] text-tertiary uppercase">
                            <th class="px-5 py-3.5">Kandidat Pelamar</th>
                            <th class="px-5 py-3.5">Posisi & Divisi</th>
                            <th class="px-5 py-3.5">Tahapan Seleksi</th>
                            <th class="px-5 py-3.5">Jadwal Interview / Join</th>
                            <th class="px-5 py-3.5">Catatan Seleksi HR</th>
                            <th class="px-5 py-3.5 text-right">Ubah Tahapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#282a2e]/30 font-body">
                        @forelse($candidates as $c)
                            <tr class="hover:bg-surface-container transition-colors">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-sm text-on-surface">{{ $c->name }}</div>
                                    <div class="text-xs text-tertiary font-mono">{{ $c->email }} • {{ $c->phone ?? '—' }}</div>
                                </td>
                                <td class="px-5 py-4 font-mono">
                                    <span class="text-on-surface font-medium block">{{ $c->applied_role }}</span>
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-surface-container-highest text-tertiary">{{ $c->department }}</span>
                                </td>
                                <td class="px-5 py-4 font-mono text-[10px]">
                                    @if($c->stage === 'hired')
                                        <span class="px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-bold uppercase">HIRED (ONBOARDING)</span>
                                    @elseif($c->stage === 'offering')
                                        <span class="px-2 py-0.5 rounded bg-amber-950/60 text-amber-400 border border-amber-500/20 font-semibold uppercase">OFFERING LETTER</span>
                                    @elseif($c->stage === 'interview')
                                        <span class="px-2 py-0.5 rounded bg-purple-950/60 text-purple-400 border border-purple-500/20 font-semibold uppercase">INTERVIEW</span>
                                    @elseif($c->stage === 'screening')
                                        <span class="px-2 py-0.5 rounded bg-blue-950/60 text-blue-400 border border-blue-500/20 font-semibold uppercase">CV SCREENING</span>
                                    @elseif($c->stage === 'rejected')
                                        <span class="px-2 py-0.5 rounded bg-red-950/60 text-red-400 border border-red-500/20 uppercase">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-surface-container text-tertiary uppercase">APPLIED</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-tertiary">
                                    @if($c->stage === 'interview' && $c->interview_date)
                                        <span class="text-purple-300">Intv: {{ \Carbon\Carbon::parse($c->interview_date)->format('d M Y') }}</span>
                                    @elseif($c->stage === 'hired' && $c->expected_join_date)
                                        <span class="text-emerald-300">Join: {{ \Carbon\Carbon::parse($c->expected_join_date)->format('d M Y') }}</span>
                                    @else
                                        <span>—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-xs text-tertiary italic max-w-xs truncate">
                                    {{ $c->notes ?: '—' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <form action="{{ route('hr.recruitment.updateStage', $c->id) }}" method="POST" class="inline-flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <select name="stage" class="bg-surface-container-high border border-[#333539] text-[10px] text-on-surface rounded px-2 py-1 focus:outline-none">
                                            <option value="applied" {{ $c->stage === 'applied' ? 'selected' : '' }}>Applied</option>
                                            <option value="screening" {{ $c->stage === 'screening' ? 'selected' : '' }}>Screening</option>
                                            <option value="interview" {{ $c->stage === 'interview' ? 'selected' : '' }}>Interview</option>
                                            <option value="offering" {{ $c->stage === 'offering' ? 'selected' : '' }}>Offering</option>
                                            <option value="hired" {{ $c->stage === 'hired' ? 'selected' : '' }}>Hired</option>
                                            <option value="rejected" {{ $c->stage === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                        </select>
                                        <button type="submit" class="p-1 rounded bg-surface-container-high hover:bg-surface-bright text-tertiary hover:text-on-surface transition-colors" title="Simpan Tahapan">
                                            <span class="material-symbols-outlined text-sm">check</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-tertiary font-mono">
                                    Belum ada data kandidat pelamar pada filter ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($candidates->hasPages())
                <div class="p-4 border-t border-[#282a2e]/40">{{ $candidates->links() }}</div>
            @endif
        </div>
    </div>

    <!-- TAB 2: CHECKLIST ONBOARDING (PEGAWAI BARU) -->
    <div x-show="activeTab === 'onboarding'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @forelse($onboardingList as $ob)
                <div class="p-5 bg-surface-container-low rounded-xl border border-[#282a2e]/70 shadow-sm space-y-4">
                    <div class="flex items-start justify-between pb-3 border-b border-[#282a2e]/60">
                        <div>
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-emerald-950/60 text-emerald-400 border border-emerald-500/20 font-semibold uppercase">
                                STATUS: HIRED • ONBOARDING
                            </span>
                            <h3 class="font-headline font-bold text-base text-on-surface mt-1.5">{{ $ob->name }}</h3>
                            <p class="text-xs text-tertiary font-mono">{{ $ob->applied_role }} • Divisi {{ $ob->department }}</p>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-mono text-tertiary block">Target Mulai Bekerja</span>
                            <span class="text-xs font-bold text-on-surface font-mono">{{ $ob->expected_join_date ? \Carbon\Carbon::parse($ob->expected_join_date)->format('d M Y') : 'Segera' }}</span>
                        </div>
                    </div>

                    <!-- Checklist Form -->
                    <form action="{{ route('hr.recruitment.updateOnboarding', $ob->id) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-2 bg-surface-container/40 p-3 rounded-lg border border-[#282a2e]/40">
                            <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                                <input type="checkbox" name="onboarding_doc_submitted" value="1" {{ $ob->onboarding_doc_submitted ? 'checked' : '' }} class="rounded border-gray-600 text-emerald-500 focus:ring-0">
                                <span>1. Berkas Administrasi Lengkap (KTP, NPWP, Ijazah, Buku Rekening)</span>
                            </label>

                            <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                                <input type="checkbox" name="onboarding_device_allocated" value="1" {{ $ob->onboarding_device_allocated ? 'checked' : '' }} class="rounded border-gray-600 text-emerald-500 focus:ring-0">
                                <span>2. Alokasi Laptop & Akun Kerja (Email & Sistem GA)</span>
                            </label>

                            <label class="flex items-center gap-2.5 cursor-pointer text-xs text-on-surface">
                                <input type="checkbox" name="onboarding_orientation_done" value="1" {{ $ob->onboarding_orientation_done ? 'checked' : '' }} class="rounded border-gray-600 text-emerald-500 focus:ring-0">
                                <span>3. Sesi Orientasi Budaya Kerja & Pengenalan Tim HR</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            @if(!$ob->converted_employee_id)
                                <label class="flex items-center gap-2 text-xs text-amber-300 font-mono cursor-pointer">
                                    <input type="checkbox" name="convert_to_employee" value="1" class="rounded border-gray-600 text-amber-500">
                                    <span>Aktifkan langsung ke Direktori Pegawai</span>
                                </label>
                            @else
                                <span class="text-[11px] font-mono text-emerald-400 flex items-center gap-1">
                                    <span class="material-symbols-outlined text-sm">check_circle</span>
                                    <span>Terdaftar di Pegawai (NIP: {{ $ob->employee?->nip }})</span>
                                </span>
                            @endif

                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-surface-container-high hover:bg-surface-bright border border-[#333539] text-xs font-semibold text-on-surface transition-colors shadow-sm">
                                Simpan Checklist
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="col-span-2 p-8 text-center bg-surface-container-low rounded-xl border border-[#282a2e]/50 text-tertiary font-mono">
                    Belum ada kandidat yang dalam tahap onboarding saat ini. Pindahkan kandidat pelamar ke status "Hired" untuk memulai proses onboarding.
                </div>
            @endforelse
        </div>
    </div>

    <!-- TAB 3: DAFTAR LOWONGAN PEKERJAAN -->
    <div x-show="activeTab === 'vacancies'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($vacancies as $vac)
                <div class="p-5 bg-surface-container-low rounded-xl border border-[#282a2e]/70 shadow-sm flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-surface-container-highest text-tertiary">
                                Divisi {{ $vac->department }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono {{ $vac->status === 'open' ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20' : 'bg-surface-container text-tertiary' }}">
                                {{ strtoupper($vac->status) }}
                            </span>
                        </div>
                        <h3 class="font-headline font-bold text-base text-on-surface">{{ $vac->title }}</h3>
                        <p class="text-xs text-tertiary font-mono mt-0.5">{{ $vac->employment_type }} • Kuota: {{ $vac->quota }} Orang</p>
                        @if($vac->description)
                            <p class="text-xs text-on-surface-variant mt-3 line-clamp-3 leading-relaxed">{{ $vac->description }}</p>
                        @endif
                    </div>

                    <div class="pt-3 border-t border-[#282a2e]/50 flex items-center justify-between">
                        <span class="text-xs font-mono text-tertiary">{{ $vac->candidates_count }} Pelamar terdaftar</span>
                        <a href="{{ route('hr.recruitment.index', ['vacancy_id' => $vac->id]) }}" class="text-xs font-semibold text-rose-400 hover:underline flex items-center gap-1">
                            <span>Lihat Pelamar</span>
                            <span class="material-symbols-outlined text-xs">arrow_forward</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-8 text-center bg-surface-container-low rounded-xl border border-[#282a2e]/50 text-tertiary font-mono">
                    Belum ada lowongan pekerjaan yang dibuka. Klik "Buat Lowongan" untuk membuka rekrutmen baru.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Buat Lowongan Baru -->
    <div x-cloak x-show="openVacancyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openVacancyModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-400 text-xl">post_add</span>
                    <h3 class="font-headline font-semibold text-base text-on-surface">Buka Lowongan Pekerjaan Baru</h3>
                </div>
                <button @click="openVacancyModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('hr.recruitment.storeVacancy') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Judul Posisi / Job Title</label>
                    <input type="text" name="title" required placeholder="contoh: Senior Backend Engineer" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Departemen</label>
                        <select name="department" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="HR">Human Resources (HR)</option>
                            <option value="Finance">Finance & Keuangan</option>
                            <option value="Bisnis Development">Bisnis Development</option>
                            <option value="GA">General Affairs (GA)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Tipe Pekerjaan</label>
                        <select name="employment_type" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                            <option value="Tetap (FTE)">Tetap (Full-Time)</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Magang">Magang (Internship)</option>
                            <option value="Probation">Probation</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Kuota Penerimaan (Orang)</label>
                        <input type="number" name="quota" value="1" min="1" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Lokasi Penempatan</label>
                        <input type="text" name="location" value="Headquarters (Jakarta)" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Deskripsi Persyaratan & Tugas</label>
                    <textarea name="description" rows="3" placeholder="Kualifikasi pendidikan, pengalaman, dan tugas utama..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openVacancyModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold rounded-lg shadow-md">Publikasikan Lowongan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Pelamar Baru -->
    <div x-cloak x-show="openCandidateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 ">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative" @click.away="openCandidateModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-400 text-xl">person_add</span>
                    <h3 class="font-headline font-semibold text-base text-on-surface">Pendaftaran Pelamar / Kandidat</h3>
                </div>
                <button @click="openCandidateModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('hr.recruitment.storeCandidate') }}" method="POST" class="mt-4 space-y-3.5">
                @csrf
                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Pilih Lowongan Pekerjaan</label>
                    <select name="job_vacancy_id" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none">
                        @foreach($vacancies as $v)
                            <option value="{{ $v->id }}">{{ $v->title }} (Divisi {{ $v->department }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Nama Lengkap Kandidat</label>
                    <input type="text" name="name" required placeholder="contoh: Dian Anggraeni, S.Kom" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Alamat Email</label>
                        <input type="email" name="email" required placeholder="dian@example.com" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">No. WhatsApp / Telepon</label>
                        <input type="text" name="phone" placeholder="0812xxxxxx" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Jadwal Interview (Opsional)</label>
                        <input type="date" name="interview_date" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-tertiary mb-1">Perkiraan Tanggal Masuk (Join)</label>
                        <input type="date" name="expected_join_date" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono text-tertiary mb-1">Catatan HR (Review Awal / Screening)</label>
                    <textarea name="notes" rows="2" placeholder="Kesan interview, ekspektasi gaji, kualifikasi teknis..." class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openCandidateModal = false" class="px-4 py-2 bg-surface-container-high text-xs text-on-surface rounded-lg">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white text-xs font-semibold rounded-lg shadow-md">Simpan Data Pelamar</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
