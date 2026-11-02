@extends('layouts.app')

@section('title', 'Profil: ' . $employee->name)

@section('content')
<div class="flex flex-col gap-6">

    <!-- Back to directory bar -->
    <div class="flex items-center justify-between">
        <a href="{{ route('employees.index') }}" class="flex items-center gap-2 text-xs font-mono text-tertiary hover:text-on-surface transition-colors">
            <span class="material-symbols-outlined text-base">arrow_back</span>
            <span>Kembali ke Direktori Karyawan</span>
        </a>

        @if(auth()->user()->canManageEmployees() && (!auth()->user()->employee_id || (int)auth()->user()->employee_id !== (int)$employee->id))
            <!-- Tombol Hapus Data & Akun Karyawan -->
            <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" 
                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan {{ $employee->name }} (NIP: {{ $employee->nip }}) beserta akun login sistemnya? Tindakan ini tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-3 py-1.5 rounded-lg bg-red-950/50 hover:bg-red-900/70 text-red-400 hover:text-red-300 border border-red-500/40 text-xs font-semibold flex items-center gap-1.5 transition-all shadow-sm">
                    <span class="material-symbols-outlined text-base">delete</span>
                    <span>Hapus Karyawan & Akun</span>
                </button>
            </form>
        @endif
    </div>

    <!-- Employee Hero Card -->
    <div class="p-6 bg-surface-container rounded-2xl border border-[#282a2e]/60 shadow-md flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <img src="{{ $employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=160' }}" 
                 alt="{{ $employee->name }}" 
                 class="w-20 h-20 rounded-2xl object-cover ring-4 ring-[#282a2e] shadow-lg"/>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="font-headline font-bold text-2xl text-on-surface">{{ $employee->name }}</h1>
                    <span class="px-2.5 py-0.5 rounded font-mono text-xs {{ $employee->status === 'active' ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20' : 'bg-surface-container text-tertiary' }}">
                        {{ strtoupper($employee->status) }}
                    </span>
                </div>
                <p class="text-sm text-tertiary mt-0.5 font-medium">{{ $employee->role_title }} • {{ $employee->department }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs font-mono text-tertiary">
                    <span>NIP: <strong class="text-on-surface">{{ $employee->nip }}</strong></span>
                    <span>•</span>
                    <span>Bergabung: {{ \Carbon\Carbon::parse($employee->join_date)->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="p-3 bg-surface-container-low rounded-xl border border-[#282a2e]/60 text-center min-w-[110px]">
                <span class="font-mono text-[10px] text-tertiary uppercase block">Sisa Cuti</span>
                <span class="font-headline text-xl font-bold text-emerald-400">
                    {{ $employee->annual_leave_quota - $employee->annual_leave_used }} Hari
                </span>
            </div>

            <div class="p-3 bg-surface-container-low rounded-xl border border-[#282a2e]/60 text-center min-w-[110px]">
                <span class="font-mono text-[10px] text-tertiary uppercase block">Total Kehadiran</span>
                <span class="font-headline text-xl font-bold text-on-surface">
                    {{ $employee->attendances->count() }} Hari
                </span>
            </div>
        </div>
    </div>

    <!-- Profile Details & History Tab -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left col: Bio details -->
        <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 p-5 space-y-4">
            <h2 class="font-headline text-sm font-semibold text-on-surface uppercase tracking-wider font-mono text-tertiary">
                Informasi Personal & Kontak
            </h2>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="font-mono text-tertiary block">Email:</span>
                    <a href="mailto:{{ $employee->email }}" class="text-primary hover:underline font-medium">{{ $employee->email }}</a>
                </div>
                <div>
                    <span class="font-mono text-tertiary block">Nomor Telepon:</span>
                    <span class="text-on-surface font-medium">{{ $employee->phone ?? '—' }}</span>
                </div>
                <div>
                    <span class="font-mono text-tertiary block">Lokasi Penugasan:</span>
                    <span class="text-on-surface font-medium">{{ $employee->location }}</span>
                </div>
                <div>
                    <span class="font-mono text-tertiary block">Tipe Pekerjaan:</span>
                    <span class="text-on-surface font-medium">{{ $employee->employment_type }}</span>
                </div>
            </div>

            <div class="pt-4 border-t border-[#282a2e]/40">
                <a href="mailto:{{ $employee->email }}" 
                   class="w-full py-2 px-3 bg-surface-container-high hover:bg-surface-bright border border-[#333539] text-on-surface rounded-lg text-xs font-semibold flex items-center justify-center gap-2 transition-all">
                    <span class="material-symbols-outlined text-base text-tertiary">mail</span>
                    <span>Hubungi via Email</span>
                </a>
            </div>
        </div>

        <!-- Right 2 cols: History Logs -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Attendance History -->
            <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 overflow-hidden">
                <div class="p-4 border-b border-[#282a2e]/40 flex items-center justify-between">
                    <h3 class="font-headline text-sm font-semibold text-on-surface">Riwayat Kehadiran Terakhir</h3>
                    <span class="font-mono text-[10px] text-tertiary">Maksimal 30 log terakhir</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[10px] uppercase bg-surface-container-lowest/30">
                                <th class="px-4 py-2.5">Tanggal</th>
                                <th class="px-4 py-2.5">Masuk</th>
                                <th class="px-4 py-2.5">Pulang</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5">Mode</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/30 font-body">
                            @forelse($employee->attendances as $att)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-on-surface">{{ \Carbon\Carbon::parse($att->date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3 font-mono font-semibold text-on-surface">{{ substr($att->clock_in, 0, 8) }}</td>
                                    <td class="px-4 py-3 font-mono text-tertiary">{{ $att->clock_out ? substr($att->clock_out, 0, 8) : '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded font-mono text-[10px] {{ $att->status === 'on_time' ? 'bg-emerald-950/60 text-emerald-400' : 'bg-red-950/60 text-red-400' }}">
                                            {{ $att->status === 'on_time' ? 'Tepat' : 'Telat' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-tertiary">{{ $att->work_mode }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-tertiary font-mono">Belum ada riwayat presensi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave History -->
            <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/50 overflow-hidden">
                <div class="p-4 border-b border-[#282a2e]/40 flex items-center justify-between">
                    <h3 class="font-headline text-sm font-semibold text-on-surface">Riwayat Cuti & Izin</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-[#282a2e]/40 text-tertiary font-mono text-[10px] uppercase bg-surface-container-lowest/30">
                                <th class="px-4 py-2.5">Jenis</th>
                                <th class="px-4 py-2.5">Periode</th>
                                <th class="px-4 py-2.5">Durasi</th>
                                <th class="px-4 py-2.5">Alasan</th>
                                <th class="px-4 py-2.5 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/30 font-body">
                            @forelse($employee->leaveRequests as $l)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-on-surface">{{ $l->type }}</td>
                                    <td class="px-4 py-3 font-mono text-tertiary">{{ \Carbon\Carbon::parse($l->start_date)->format('d M') }} - {{ \Carbon\Carbon::parse($l->end_date)->format('d M Y') }}</td>
                                    <td class="px-4 py-3 font-mono">{{ $l->duration_days }} hari</td>
                                    <td class="px-4 py-3 text-tertiary max-w-xs truncate">{{ $l->reason }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="px-2 py-0.5 rounded font-mono text-[10px] {{ $l->status === 'approved' ? 'bg-emerald-950/60 text-emerald-400' : ($l->status === 'pending' ? 'bg-amber-950/60 text-amber-400' : 'bg-red-950/60 text-red-400') }}">
                                            {{ strtoupper($l->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-tertiary font-mono">Belum ada riwayat cuti.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
