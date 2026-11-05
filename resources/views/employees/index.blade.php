@extends('layouts.app')

@section('title', 'Direktori Karyawan')

@section('content')
<div class="flex flex-col gap-6" x-data="{ 
    openAddEmployeeModal: false, 
    openBatchRegisterModal: false,
    openManageAccountModal: false, 
    openEditEmployeeModal: false,
    openRejectModal: false,
    openExportModal: false,
    exportDepartment: '{{ request('department', '') }}',
    exportStatus: '{{ request('status', '') }}',
    exportType: '',
    exportSearch: '{{ request('search', '') }}',
    rejectActionUrl: '',
    rejectTargetName: '',
    selectedAccount: null,
    selectedEmployee: null
}">

    <!-- Header Actions & Search -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 bg-surface-container rounded-xl border border-[#282a2e]/60 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-secondary-fixed"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Staff Management</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Direktori & Profil Karyawan</h1>
            <p class="font-mono text-xs text-tertiary">Data lengkap seluruh anggota tim, divisi, dan status operasional</p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            @if(auth()->user()->canExportEmployees())
                <button type="button" @click="openExportModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-emerald-950/80 to-teal-950/80 hover:from-emerald-900/90 hover:to-teal-900/90 text-xs font-mono font-bold text-emerald-300 border border-emerald-500/40 rounded-lg transition-all shadow-md group" title="Tarik / Ekspor Data Karyawan (Khusus Direktur & Manager HR)">
                    <span class="material-symbols-outlined text-base text-emerald-400 group-hover:scale-110 transition-transform">download</span>
                    <span>Tarik Data Karyawan</span>
                </button>
            @endif

            @if(auth()->user()->canManageEmployees())
                <button type="button" @click="openBatchRegisterModal = true" class="flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-high hover:bg-surface-bright text-xs font-mono font-semibold text-emerald-400 border border-emerald-500/30 rounded-lg transition-colors shadow-sm" title="Daftarkan Akun Massal berdasarkan Tabel Mesin Finger">
                    <span class="material-symbols-outlined text-base">table_view</span>
                    <span>Pendaftaran Massal (Tabel Mesin)</span>
                </button>
                <button @click="openAddEmployeeModal = true" class="flex items-center gap-2 px-4 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container rounded-lg text-xs font-semibold transition-all shadow-md">
                    <span class="material-symbols-outlined text-base">person_add</span>
                    <span>Tambah Karyawan Baru</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Panel Pengajuan Perubahan Status Role Akun (Approval Direktur & Info HRD) -->
    @if(isset($pendingRoleRequests) && $pendingRoleRequests->isNotEmpty())
        <div class="p-5 rounded-2xl bg-amber-950/20 border border-amber-500/30 shadow-lg space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-amber-500/20 text-amber-300 border border-amber-500/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-lg">admin_panel_settings</span>
                    </span>
                    <div>
                        <h2 class="font-headline font-bold text-sm text-amber-200">Menunggu Persetujuan Direktur: Perubahan Status Akun</h2>
                        <p class="text-xs text-amber-400/80">Pengajuan promosi atau penyesuaian role akun (Karyawan &harr; Manager) yang diajukan oleh HRD</p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold bg-amber-500/30 text-amber-200 border border-amber-500/40">
                    {{ $pendingRoleRequests->count() }} Menunggu
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                @foreach($pendingRoleRequests as $req)
                    <div class="p-4 rounded-xl bg-[#141822]/90 border border-amber-500/30 flex flex-col justify-between gap-3">
                        <div class="space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-headline font-bold text-xs text-white">
                                        {{ $req->user->employee ? $req->user->employee->name : $req->user->name }}
                                    </h4>
                                    <p class="text-[11px] text-slate-400 font-mono">{{ $req->user->email }} &bull; {{ $req->user->department }}</p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ strtoupper($req->current_role) }}
                                    </span>
                                    <span class="material-symbols-outlined text-xs text-amber-400">arrow_forward</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold {{ $req->target_role === 'manager' ? 'bg-blue-900/40 text-blue-300 border border-blue-500/30' : 'bg-emerald-900/40 text-emerald-300 border border-emerald-500/30' }}">
                                        {{ strtoupper($req->target_role) }}
                                    </span>
                                </div>
                            </div>
                            <div class="p-2.5 rounded-lg bg-black/40 border border-slate-800 text-xs text-slate-300">
                                <span class="font-mono text-[10px] text-amber-400 font-semibold block mb-0.5">Alasan Pengajuan oleh {{ $req->requester?->name ?? 'HR' }}:</span>
                                {{ $req->reason }}
                            </div>
                            <span class="text-[10px] text-slate-500 font-mono block">Diajukan: {{ $req->created_at->translatedFormat('d M Y H:i') }}</span>
                        </div>

                        @if(auth()->user()->isDirektur())
                            <!-- Tombol Aksi Persetujuan Direktur -->
                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800/80">
                                <button type="button" 
                                        @click="rejectActionUrl = '{{ route('employees.roleRequests.reject', $req->id) }}'; rejectTargetName = '{{ $req->user->employee ? $req->user->employee->name : $req->user->name }}'; openRejectModal = true"
                                        class="px-3 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center gap-1 transition-all">
                                    <span class="material-symbols-outlined text-sm">close</span>
                                    <span>Tolak</span>
                                </button>
                                <form action="{{ route('employees.roleRequests.approve', $req->id) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin menyetujui perubahan status role akun {{ $req->user->employee ? $req->user->employee->name : $req->user->name }} menjadi {{ strtoupper($req->target_role) }}?');">
                                    @csrf
                                    <button type="submit" 
                                            class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold flex items-center gap-1 shadow-md transition-all active:scale-95">
                                        <span class="material-symbols-outlined text-sm">check</span>
                                        <span>Setujui Perubahan</span>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="pt-2 border-t border-slate-800 text-[11px] text-amber-300/80 flex items-center gap-1.5 font-mono">
                                <span class="material-symbols-outlined text-xs">hourglass_top</span>
                                <span>Menunggu verifikasi & persetujuan Direktur Utama</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="p-4 bg-surface-container-low rounded-xl border border-[#282a2e]/50 flex flex-wrap items-center justify-between gap-4">
        <form action="{{ route('employees.index') }}" method="GET" class="flex flex-wrap items-center gap-3 flex-1">
            <div class="relative flex-1 min-w-[200px]">
                <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">search</span>
                <input type="text" 
                       name="search" 
                       value="{{ $search }}" 
                       placeholder="Cari nama, NIP, atau jabatan..." 
                       class="w-full pl-9 pr-3 py-1.5 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface placeholder:text-tertiary focus:outline-none focus:border-primary-container"/>
            </div>

            <select name="department" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                <option value="">Semua Departemen</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" class="bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                <option value="">Semua Status</option>
                <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="on_leave" {{ $status === 'on_leave' ? 'selected' : '' }}>Cuti</option>
                <option value="probation" {{ $status === 'probation' ? 'selected' : '' }}>Probation</option>
            </select>

            <button type="submit" class="px-3 py-1.5 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors border border-[#333539]">
                Filter
            </button>
            @if($search || $department || $status)
                <a href="{{ route('employees.index') }}" class="text-xs text-tertiary hover:underline">Reset</a>
            @endif
        </form>

        <span class="font-mono text-xs text-tertiary">
            Total: <strong class="text-on-surface">{{ $employees->total() }}</strong> Karyawan
        </span>
    </div>

    <!-- Employee Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($employees as $emp)
            <div class="bg-surface-container-low rounded-xl border border-[#282a2e]/60 p-5 flex flex-col justify-between hover:border-primary-container/40 transition-all shadow-sm group">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $emp->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=120' }}" 
                                 alt="{{ $emp->name }}" 
                                 class="w-12 h-12 rounded-xl object-cover ring-2 ring-[#282a2e] group-hover:ring-primary-container/40 transition-all"/>
                            <div>
                                <h3 class="font-headline font-semibold text-sm text-on-surface group-hover:text-primary transition-colors">
                                    <a href="{{ route('employees.show', $emp->id) }}">{{ $emp->name }}</a>
                                </h3>
                                <p class="text-xs text-tertiary font-body">{{ $emp->role_title }}</p>
                                <span class="font-mono text-[10px] text-tertiary/70">{{ $emp->nip }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5">
                            @if($emp->user)
                                <span class="px-2 py-0.5 rounded font-mono text-[9px] uppercase font-bold {{ $emp->user->role === 'direktur' ? 'bg-purple-950/60 text-purple-400 border border-purple-500/20' : ($emp->user->role === 'sekretaris' ? 'bg-amber-950/60 text-amber-400 border border-amber-500/20' : ($emp->user->role === 'manager' ? 'bg-blue-950/60 text-blue-400 border border-blue-500/20' : ($emp->user->role === 'asisten_manager' ? 'bg-violet-950/60 text-violet-400 border border-violet-500/20' : 'bg-surface-container-highest text-tertiary'))) }}">
                                    {{ $emp->user->role === 'asisten_manager' ? 'ASISTEN MGR' : ($emp->user->role === 'sekretaris' ? 'SEKRETARIS' : strtoupper($emp->user->role)) }}
                                </span>
                            @endif
                            <span class="px-2 py-0.5 rounded font-mono text-[10px] {{ $emp->status === 'active' ? 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/20' : 'bg-surface-container text-tertiary' }}">
                                {{ strtoupper($emp->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-[#282a2e]/40 space-y-1.5 text-xs text-tertiary font-body">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[11px] text-tertiary">Departemen:</span>
                            <span class="text-on-surface font-medium">{{ $emp->department }}{{ $emp->sub_department ? ' (' . $emp->sub_department . ')' : '' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[11px] text-tertiary">Email Akun:</span>
                            <span class="text-on-surface font-mono text-[11px] truncate max-w-[150px]">{{ $emp->email }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[11px] text-tertiary">Tipe Kontrak:</span>
                            <span class="text-on-surface font-medium">{{ $emp->employment_type }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#282a2e]/40 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-1.5">
                        <!-- Tombol Edit Data Pokok (NIP, Tanggal Bergabung, dll) -->
                        <button type="button" 
                                @click="selectedEmployee = {{ json_encode([
                                    'id' => $emp->id,
                                    'name' => $emp->name,
                                    'nip' => $emp->nip,
                                    'role_title' => $emp->role_title,
                                    'department' => $emp->department,
                                    'sub_department' => $emp->sub_department,
                                    'join_date' => $emp->join_date,
                                    'employment_type' => $emp->employment_type,
                                    'status' => $emp->status,
                                    'phone' => $emp->phone,
                                    'location' => $emp->location,
                                    'update_url' => route('employees.update', $emp->id),
                                ]) }}; openEditEmployeeModal = true"
                                class="px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-semibold flex items-center gap-1 transition-all"
                                title="Ubah NIP, Tanggal Bergabung, Jabatan, dll">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            <span>Edit Data</span>
                        </button>

                        <!-- Tombol Atur Akun Pengguna -->
                        <button type="button" 
                                @click="selectedAccount = {{ json_encode([
                                    'id' => $emp->id,
                                    'name' => $emp->name,
                                    'nip' => $emp->nip,
                                    'email' => $emp->email,
                                    'role_title' => $emp->role_title,
                                    'department' => $emp->department,
                                    'sub_department' => $emp->sub_department ?? ($emp->user?->sub_department ?? ''),
                                    'role' => $emp->user?->role ?? 'karyawan',
                                    'initial_role' => $emp->user?->role ?? 'karyawan',
                                    'reason' => '',
                                    'update_url' => route('employees.updateAccount', $emp->id),
                                ]) }}; openManageAccountModal = true"
                                class="px-2 py-1 rounded-lg bg-surface-container-high hover:bg-surface-bright text-slate-300 hover:text-white border border-[#333539] text-xs font-medium flex items-center gap-1 transition-colors">
                            <span class="material-symbols-outlined text-sm text-slate-400">admin_panel_settings</span>
                            <span>Akun</span>
                        </button>

                        @if(auth()->user()->canManageEmployees() && (!auth()->user()->employee_id || (int)auth()->user()->employee_id !== (int)$emp->id))
                            <!-- Tombol Hapus Data & Akun Karyawan -->
                            <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" 
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan {{ $emp->name }} (NIP: {{ $emp->nip }}) beserta akun login sistemnya? Tindakan ini tidak dapat dibatalkan.');"
                                  class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="px-2 py-1 rounded-lg bg-red-950/40 hover:bg-red-900/60 text-red-400 hover:text-red-300 border border-red-500/30 text-xs font-semibold flex items-center gap-1 transition-all"
                                        title="Hapus Karyawan & Akun">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                    <span>Hapus</span>
                                </button>
                            </form>
                        @endif
                    </div>

                    <a href="{{ route('employees.show', $emp->id) }}" class="text-rose-400 hover:underline font-semibold text-xs flex items-center gap-0.5">
                        <span>Detail</span>
                        <span class="material-symbols-outlined text-sm">chevron_right</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 py-16 text-center text-tertiary font-mono">
                Tidak ada karyawan yang sesuai dengan kriteria pencarian.
            </div>
        @endforelse
    </div>

    @if($employees->hasPages())
        <div class="p-4">
            {{ $employees->links() }}
        </div>
    @endif

    <!-- Modal Form Tambah Karyawan -->
    <div x-cloak x-show="openAddEmployeeModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 "
         x-transition:enter="transition ease-out duration-75"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-lg p-6 shadow-2xl relative"
             @click.away="openAddEmployeeModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="material-symbols-outlined text-primary-container text-2xl">person_add</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Tambah Karyawan Baru</h3>
                        <p class="text-xs text-tertiary">Registrasi identitas dan penugasan karyawan</p>
                    </div>
                </div>
                <button @click="openAddEmployeeModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form action="{{ route('employees.store') }}" method="POST" class="mt-5 space-y-3.5">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" name="nip" required placeholder="NEX-XXXXX" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required placeholder="Nama Karyawan" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Email Perusahaan</label>
                        <input type="email" name="email" required placeholder="nama@gyokai.com" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Nomor WhatsApp / Telp</label>
                        <input type="text" name="phone" placeholder="+62 812-XXXX-XXXX" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Jabatan / Role</label>
                        <input type="text" name="role_title" required placeholder="contoh: Talent Specialist / Staff" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Departemen / Posisi</label>
                        <select name="department" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                            <option value="HR">HR (Human Resources)</option>
                            <option value="Finance">Finance & Keuangan</option>
                            <option value="Bisnis Development">Bisnis Development</option>
                            <option value="GA">GA (General Affairs)</option>
                            <option value="Resepsionis">Resepsionis (Mandiri)</option>
                            <option value="Trainer">Trainer (Mandiri)</option>
                            <option value="Executive">Executive (Sekretariat Direksi / Sekretaris Direktur)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Tipe Kontrak</label>
                        <select name="employment_type" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                            <option value="Tetap (FTE)">Tetap (FTE)</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Magang">Magang</option>
                            <option value="Probation">Probation</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Tanggal Bergabung</label>
                        <input type="date" name="join_date" value="{{ now()->toDateString() }}" required class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <!-- Bagian Konfigurasi Akun Login Karyawan -->
                <div class="p-3 bg-surface-container-low/80 rounded-xl border border-[#333539] space-y-3">
                    <div class="flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-sm text-primary-container">manage_accounts</span>
                        <span class="text-xs font-mono uppercase tracking-wider text-on-surface font-semibold">Konfigurasi Akun Login HRIS</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Tingkat Hak Akses (Role)</label>
                            <select name="account_role" class="w-full bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                                <option value="karyawan" selected>Karyawan (Staf)</option>
                                <option value="asisten_manager">Asisten Manager</option>
                                <option value="sekretaris">Sekretaris Direktur</option>
                                <option value="manager">Manager Departemen</option>
                                @if(auth()->user()->isDirektur())
                                    <option value="direktur">Direktur</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-mono text-tertiary mb-1">Password Akun (Default: {{ env('DEFAULT_USER_PASSWORD', 'password123') }})</label>
                            <input type="text" name="password" placeholder="Kosongkan untuk default password" class="w-full bg-surface-container border border-[#333539] rounded-lg px-3 py-1.5 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                        </div>
                    </div>
                    <p class="text-[10px] text-tertiary font-mono">*Akun login dibuat otomatis dengan email (nama)@gyokai.com dan password default <strong>{{ env('DEFAULT_USER_PASSWORD', 'password123') }}</strong>.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openAddEmployeeModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container text-xs font-semibold rounded-lg transition-all shadow-md">
                        Simpan Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Pendaftaran Akun Massal (Sesuai Tabel / Mesin Finger) -->
    <div x-cloak x-show="openBatchRegisterModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         x-data="{
             batchRows: [
                 { no_id: 21, name: '', department: 'HR', role: 'karyawan', employment_type: 'Tetap', status: 'active', location: 'Jakarta', phone: '81234567890' }
             ],
             addRow() {
                 let nextId = this.batchRows.length > 0 ? (parseInt(this.batchRows[this.batchRows.length - 1].no_id) || 0) + 1 : 21;
                 this.batchRows.push({
                     no_id: nextId,
                     name: '',
                     department: 'HR',
                     role: 'karyawan',
                     employment_type: 'Tetap',
                     status: 'active',
                     location: 'Jakarta',
                     phone: '81234567890'
                 });
             },
             removeRow(index) {
                 if (this.batchRows.length > 1) {
                     this.batchRows.splice(index, 1);
                 }
             },
             pasteExcel(e) {
                 let text = (e.clipboardData || window.clipboardData).getData('text');
                 if (!text) return;
                 let lines = text.trim().split(/\r\n|\n|\r/);
                 let parsed = [];
                 lines.forEach((line) => {
                     let cols = line.split('\t');
                     if (cols.length >= 2) {
                         let firstCol = cols[0].trim();
                         // Abaikan jika header baris
                         if (firstCol.toLowerCase() === 'no id' || firstCol.toLowerCase() === 'no' || firstCol.toLowerCase() === 'id') return;

                         let noId = parseInt(firstCol) || (parsed.length + 2);
                         let name = cols[1] ? cols[1].trim() : '';
                         let dept = cols[2] ? cols[2].trim() : 'HR';
                         let role = cols[3] ? (cols[3].trim().toLowerCase() === 'manager' ? 'manager' : 'karyawan') : 'karyawan';
                         let empType = cols[4] ? cols[4].trim() : 'Tetap';
                         let status = cols[5] ? cols[5].trim().toLowerCase() : 'active';
                         let location = cols[6] ? cols[6].trim() : 'Jakarta';
                         let phone = cols[7] ? cols[7].trim() : '81234567890';

                         if (name) {
                             parsed.push({
                                 no_id: noId,
                                 name: name,
                                 department: dept,
                                 role: role,
                                 employment_type: empType,
                                 status: status,
                                 location: location,
                                 phone: phone
                             });
                         }
                     }
                 });
                 if (parsed.length > 0) {
                     this.batchRows = parsed;
                     e.preventDefault();
                 }
             }
         }">
        <div class="bg-surface-container rounded-2xl border border-emerald-500/40 w-full max-w-5xl p-6 shadow-2xl relative max-h-[90vh] flex flex-col" @click.away="openBatchRegisterModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <span class="p-2 rounded-xl bg-emerald-950/60 text-emerald-400 border border-emerald-500/30">
                        <span class="material-symbols-outlined text-2xl">table_chart</span>
                    </span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Pendaftaran Akun Massal (Sesuai Tabel / Mesin Finger)</h3>
                        <p class="text-xs text-tertiary font-mono">
                            Email otomatis: <span class="text-emerald-400 font-bold">(nama)@gyokai.com</span> &bull; Password default: <span class="text-emerald-400 font-bold">{{ env('DEFAULT_USER_PASSWORD', 'password123') }}</span>
                        </p>
                    </div>
                </div>
                <button @click="openBatchRegisterModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <!-- Petunjuk & Fitur Paste Excel -->
            <div class="mt-4 p-3 bg-emerald-950/20 border border-emerald-500/30 rounded-xl text-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-3 font-mono">
                <div class="text-emerald-300">
                    <span class="font-bold uppercase block text-emerald-400 mb-0.5">Tips Cepat: Mendukung Copy-Paste dari Excel / Spreadsheet!</span>
                    <span>Cukup copy data tabel Excel Anda (NO ID, NAMA, DEPARTEMEN, dst) lalu tekan <strong>Ctrl + V / Cmd + V</strong> di tabel bawah ini.</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="addRow()" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold flex items-center gap-1 shadow-sm transition-all text-xs">
                        <span class="material-symbols-outlined text-sm">add</span>
                        <span>Tambah Baris</span>
                    </button>
                </div>
            </div>

            <!-- Form Tabel Batch -->
            <form action="{{ route('employees.batchStore') }}" method="POST" class="mt-4 flex-1 flex flex-col min-h-0" @paste="pasteExcel($event)">
                @csrf
                <div class="overflow-x-auto overflow-y-auto flex-1 border border-[#282a2e] rounded-xl bg-surface-container-low max-h-[50vh]">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-surface-container-high sticky top-0 z-10 border-b border-[#282a2e] text-tertiary text-[11px] uppercase">
                            <tr>
                                <th class="px-3 py-2.5 w-16">NO ID (Mesin)</th>
                                <th class="px-3 py-2.5 min-w-[140px]">NAMA</th>
                                <th class="px-3 py-2.5 min-w-[130px]">DEPARTEMEN</th>
                                <th class="px-3 py-2.5 min-w-[110px]">JABATAN (ROLE)</th>
                                <th class="px-3 py-2.5 min-w-[100px]">IKATAN KERJA</th>
                                <th class="px-3 py-2.5 min-w-[90px]">STATUS</th>
                                <th class="px-3 py-2.5 min-w-[90px]">LOKASI</th>
                                <th class="px-3 py-2.5 min-w-[110px]">NO TELEPON</th>
                                <th class="px-3 py-2.5 text-center w-12">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#282a2e]/40">
                            <template x-for="(row, index) in batchRows" :key="index">
                                <tr class="hover:bg-surface-container/40">
                                    <td class="p-2">
                                        <input type="number" :name="'rows[' + index + '][no_id]'" x-model="row.no_id" required class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500 font-bold text-center"/>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" :name="'rows[' + index + '][name]'" x-model="row.name" required placeholder="Nama Lengkap" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500 font-semibold"/>
                                    </td>
                                    <td class="p-2">
                                        <select :name="'rows[' + index + '][department]'" x-model="row.department" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500">
                                            <option value="HR">HR</option>
                                            <option value="Finance">Finance</option>
                                            <option value="Bisnis Development">Bisnis Development</option>
                                            <option value="GA">GA</option>
                                            <option value="Resepsionis">Resepsionis</option>
                                            <option value="Trainer">Trainer</option>
                                            <option value="Executive">Executive</option>
                                        </select>
                                    </td>
                                    <td class="p-2">
                                        <select :name="'rows[' + index + '][role]'" x-model="row.role" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500">
                                            <option value="karyawan">Karyawan</option>
                                            <option value="asisten_manager">Asisten Manager</option>
                                            <option value="sekretaris">Sekretaris</option>
                                            <option value="manager">Manager</option>
                                        </select>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" :name="'rows[' + index + '][employment_type]'" x-model="row.employment_type" placeholder="TETAP" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500"/>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" :name="'rows[' + index + '][status]'" x-model="row.status" placeholder="active" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500"/>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" :name="'rows[' + index + '][location]'" x-model="row.location" placeholder="jakarta" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500"/>
                                    </td>
                                    <td class="p-2">
                                        <input type="text" :name="'rows[' + index + '][phone]'" x-model="row.phone" placeholder="81234567890" class="w-full bg-surface-container border border-[#333539] rounded px-2 py-1 text-xs text-on-surface focus:outline-none focus:border-emerald-500"/>
                                    </td>
                                    <td class="p-2 text-center">
                                        <button type="button" @click="removeRow(index)" class="text-tertiary hover:text-rose-400 p-1 rounded" title="Hapus baris">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-[#282a2e] mt-4">
                    <span class="text-xs text-tertiary font-mono">
                        Total baris pendaftaran: <strong class="text-emerald-400" x-text="batchRows.length"></strong> akun pegawai.
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="openBatchRegisterModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                            Batal
                        </button>
                        <button type="submit" class="flex items-center gap-1.5 px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold font-mono transition-all shadow-md">
                            <span class="material-symbols-outlined text-sm">how_to_reg</span>
                            <span>Daftarkan Seluruh Akun Sekarang</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Atur / Kelola Akun Karyawan, Manager, Direktur -->
    <div x-cloak x-show="openManageAccountModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 "
         x-show="selectedAccount">
        <div class="bg-surface-container rounded-2xl border border-[#333539] w-full max-w-md p-6 shadow-2xl relative" @click.away="openManageAccountModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-container text-xl">admin_panel_settings</span>
                    <div>
                        <h3 class="font-headline font-semibold text-base text-on-surface">Atur Akun Pengguna</h3>
                        <p class="text-xs text-tertiary font-mono" x-text="selectedAccount?.name + ' (' + selectedAccount?.nip + ')'"></p>
                    </div>
                </div>
                <button @click="openManageAccountModal = false" class="text-tertiary hover:text-on-surface">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="selectedAccount?.update_url" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Email Akun (Digunakan untuk Login)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">alternate_email</span>
                        <input type="email" name="email" required x-model="selectedAccount.email" class="w-full pl-9 pr-3 py-2 bg-surface-container-low border border-[#333539] rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                    <p class="text-[10px] text-tertiary mt-1">*Email ini digunakan pengguna untuk autentikasi masuk ke sistem.</p>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Tingkat Hak Akses (Level Akun)</label>
                    <select name="role" x-model="selectedAccount.role" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                        <option value="karyawan">👤 Karyawan (Staf Operasional)</option>
                        <option value="asisten_manager">💼 Asisten Manager (Pendamping Manager)</option>
                        <option value="sekretaris">📋 Sekretaris Direktur (Sekretariat Eksekutif)</option>
                        <option value="manager">👔 Manager Departemen (Pengelola Tim)</option>
                        @if(auth()->user()->isDirektur())
                            <option value="direktur">👑 Direktur (Hak Akses Penuh / Eksekutif)</option>
                        @endif
                    </select>

                    @if(auth()->user()->isDirektur())
                        <div x-show="selectedAccount && selectedAccount.role !== selectedAccount.initial_role" class="mt-2 p-2.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-300 flex items-start gap-2">
                            <span class="material-symbols-outlined text-sm shrink-0 text-amber-400">admin_panel_settings</span>
                            <span><strong>Pemberitahuan Direksi:</strong> Anda akan langsung memperbarui status akun ini. Sistem akan otomatis mengirimkan notifikasi resmi kepada seluruh Manager HRD.</span>
                        </div>
                    @else
                        <div x-show="selectedAccount && selectedAccount.role !== selectedAccount.initial_role" class="mt-2 p-2.5 rounded-lg bg-rose-500/10 border border-rose-500/30 text-[11px] text-rose-300 flex items-start gap-2">
                            <span class="material-symbols-outlined text-sm shrink-0 text-rose-400">hourglass_top</span>
                            <span><strong>Perlu Persetujuan Direktur:</strong> Pengubahan tingkat akun (Karyawan &harr; Manager) akan dikirimkan sebagai permohonan ke Direktur Utama sebelum resmi diterapkan.</span>
                        </div>
                    @endif
                </div>

                <!-- Input Alasan Perubahan Role (Wajib diisi jika HR mengubah role) -->
                @if(!auth()->user()->isDirektur())
                    <div x-show="selectedAccount && selectedAccount.role !== selectedAccount.initial_role" class="space-y-1">
                        <label class="block text-xs font-mono uppercase tracking-wider text-amber-400 mb-1">Alasan Promosi / Demosi *</label>
                        <textarea name="reason" 
                                  x-model="selectedAccount.reason"
                                  :required="selectedAccount && selectedAccount.role !== selectedAccount.initial_role"
                                  rows="2" 
                                  placeholder="Tuliskan pertimbangan atau alasan perubahan status akun untuk diteruskan ke Direktur Utama..." 
                                  class="w-full bg-surface-container-low border border-amber-500/40 rounded-lg p-2.5 text-xs text-on-surface focus:outline-none focus:border-amber-400 placeholder:text-tertiary"></textarea>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Departemen / Posisi Akun</label>
                    <select name="department" x-model="selectedAccount.department" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary-container">
                        <option value="HR">HR (Human Resources)</option>
                        <option value="Finance">Finance & Keuangan</option>
                        <option value="Bisnis Development">Bisnis Development</option>
                        <option value="GA">GA (General Affairs)</option>
                        <option value="Resepsionis">Resepsionis (Mandiri)</option>
                        <option value="Trainer">Trainer (Mandiri)</option>
                        <option value="Executive">Executive (Sekretariat Direksi / Sekretaris Direktur)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Reset Password Akun (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password" class="w-full bg-surface-container-low border border-[#333539] rounded-lg px-3 py-2 text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    <p class="text-[10px] text-tertiary mt-1">*Minimal 6 karakter jika ingin mengganti password baru.</p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-[#282a2e]">
                    <button type="button" @click="openManageAccountModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-primary-container hover:bg-red-700 text-on-primary-container text-xs font-semibold rounded-lg shadow-md transition-all">
                        Simpan Perubahan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Form Edit Data Pokok Karyawan / Manager (NIP, Tanggal Bergabung, dll) -->
    <div x-cloak x-show="openEditEmployeeModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 "
         x-show="selectedEmployee">
        <div class="bg-[#151922] rounded-2xl border border-slate-800 w-full max-w-lg p-6 shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="openEditEmployeeModal = false">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-500 text-xl">badge</span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Ubah Data Karyawan & Manager</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="selectedEmployee?.name + ' (NIP: ' + selectedEmployee?.nip + ')'"></p>
                    </div>
                </div>
                <button @click="openEditEmployeeModal = false" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="selectedEmployee?.update_url" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-3">
                    <!-- NIP Karyawan -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-rose-400 font-bold mb-1">
                            NIP (Nomor Induk Pegawai) *
                        </label>
                        <input type="text" name="nip" required x-model="selectedEmployee.nip" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none"/>
                        <p class="text-[10px] text-slate-500 mt-1">*Sinkron juga dengan ID Mesin Finger</p>
                    </div>

                    <!-- Tanggal Bergabung -->
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-rose-400 font-bold mb-1">
                            Tanggal Bergabung (Join Date) *
                        </label>
                        <input type="date" name="join_date" required x-model="selectedEmployee.join_date" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none"/>
                        <p class="text-[10px] text-slate-500 mt-1">*Mempengaruhi masa kerja & jatah cuti</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Nama Lengkap Karyawan / Manager *</label>
                    <input type="text" name="name" required x-model="selectedEmployee.name" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Jabatan / Role Title *</label>
                        <input type="text" name="role_title" required x-model="selectedEmployee.role_title" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Departemen / Divisi / Posisi *</label>
                        <select name="department" x-model="selectedEmployee.department" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="HR">HR (Human Resources)</option>
                            <option value="Finance">Finance & Keuangan</option>
                            <option value="Bisnis Development">Bisnis Development</option>
                            <option value="GA">GA (General Affairs)</option>
                            <option value="Resepsionis">Resepsionis (Mandiri)</option>
                            <option value="Trainer">Trainer (Mandiri)</option>
                            <option value="Executive">Executive (Sekretariat Direksi / Sekretaris Direktur)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Tipe Ikatan Kerja *</label>
                        <select name="employment_type" x-model="selectedEmployee.employment_type" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="Tetap (FTE)">Tetap (FTE)</option>
                            <option value="Kontrak">Kontrak</option>
                            <option value="Probation">Probation</option>
                            <option value="Magang">Magang</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Status Kepegawaian *</label>
                        <select name="status" x-model="selectedEmployee.status" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                            <option value="active">Active (Aktif)</option>
                            <option value="on_leave">On Leave (Cuti)</option>
                            <option value="probation">Probation</option>
                            <option value="inactive">Inactive (Non-aktif)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Nomor Telepon / WhatsApp</label>
                        <input type="text" name="phone" x-model="selectedEmployee.phone" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs font-mono text-white focus:outline-none"/>
                    </div>
                    <div>
                        <label class="block text-xs font-mono uppercase tracking-wider text-slate-400 mb-1">Lokasi Penugasan</label>
                        <input type="text" name="location" x-model="selectedEmployee.location" class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"/>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <button type="button" @click="openEditEmployeeModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white text-xs font-bold rounded-xl shadow-md transition-all active:scale-95">
                        Simpan Perubahan NIP & Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Penolakan Perubahan Status Role oleh Direktur -->
    <div x-cloak x-show="openRejectModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-[#151922] rounded-2xl border border-slate-800 w-full max-w-md p-6 shadow-2xl relative" @click.away="openRejectModal = false">
            <div class="flex items-center justify-between pb-3.5 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-rose-500 text-xl">cancel</span>
                    <div>
                        <h3 class="font-headline font-bold text-base text-white">Tolak Pengajuan Perubahan Role</h3>
                        <p class="text-xs text-slate-400 font-mono" x-text="rejectTargetName"></p>
                    </div>
                </div>
                <button @click="openRejectModal = false" class="text-slate-400 hover:text-white">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <form :action="rejectActionUrl" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-rose-400 mb-1">Alasan Penolakan (Wajib Diisi) *</label>
                    <textarea name="rejection_reason" required rows="3" 
                              placeholder="Tuliskan alasan penolakan secara jelas agar dapat dipahami oleh Manager HRD..."
                              class="w-full bg-slate-900 border border-slate-700 focus:border-rose-500 rounded-xl p-3 text-xs text-white placeholder:text-slate-500 focus:outline-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="openRejectModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-semibold rounded-xl shadow-md transition-all">
                        Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tarik / Ekspor Data Karyawan (Khusus Direktur Utama & Manager HR) -->
    @if(auth()->user()->canExportEmployees())
    <div x-cloak x-show="openExportModal" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80">
        <div class="bg-surface-container rounded-2xl border border-emerald-500/40 w-full max-w-lg p-6 shadow-2xl relative" @click.away="openExportModal = false">
            <div class="flex items-start justify-between pb-3.5 border-b border-[#282a2e]">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shadow-inner">
                        <span class="material-symbols-outlined text-2xl">download</span>
                    </div>
                    <div>
                        <h3 class="font-headline font-bold text-base text-on-surface">Tarik & Ekspor Data Karyawan</h3>
                        <p class="text-xs text-tertiary font-mono">Khusus Direktur Utama & Manager HR</p>
                    </div>
                </div>
                <button @click="openExportModal = false" class="text-tertiary hover:text-on-surface p-1">
                    <span class="material-symbols-outlined text-xl">close</span>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Filter Section -->
                <div class="p-4 rounded-xl bg-surface-container-low border border-[#282a2e]/60 space-y-3">
                    <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider font-semibold block">Kriteria / Parameter Penarikan Data</span>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono text-tertiary mb-1">Departemen / Divisi</label>
                            <select x-model="exportDepartment" class="w-full bg-surface-container border border-[#333539] focus:border-emerald-500 rounded-lg px-2.5 py-1.5 text-xs text-on-surface font-mono focus:outline-none">
                                <option value="">Semua Departemen</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-tertiary mb-1">Status Kepegawaian</label>
                            <select x-model="exportStatus" class="w-full bg-surface-container border border-[#333539] focus:border-emerald-500 rounded-lg px-2.5 py-1.5 text-xs text-on-surface font-mono focus:outline-none">
                                <option value="">Semua Status</option>
                                <option value="Active">Active (Aktif)</option>
                                <option value="Inactive">Inactive (Non-Aktif)</option>
                                <option value="Leave">Leave (Cuti)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-mono text-tertiary mb-1">Ikatan Kerja</label>
                            <select x-model="exportType" class="w-full bg-surface-container border border-[#333539] focus:border-emerald-500 rounded-lg px-2.5 py-1.5 text-xs text-on-surface font-mono focus:outline-none">
                                <option value="">Semua Tipe Ikatan</option>
                                <option value="Tetap">Tetap (Permanent)</option>
                                <option value="Kontrak">Kontrak (Contract)</option>
                                <option value="Magang">Magang (Internship)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-tertiary mb-1">Pencarian Nama / NIP</label>
                            <input type="text" x-model="exportSearch" placeholder="Semua atau kata kunci..." class="w-full bg-surface-container border border-[#333539] focus:border-emerald-500 rounded-lg px-2.5 py-1.5 text-xs text-on-surface font-mono focus:outline-none placeholder:text-tertiary/50">
                        </div>
                    </div>
                </div>

                <!-- Export Options Action Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <!-- Option 1: Native Excel .xlsx -->
                    <a :href="'{{ route('employees.export.excel') }}' + '?department=' + encodeURIComponent(exportDepartment) + '&status=' + encodeURIComponent(exportStatus) + '&employment_type=' + encodeURIComponent(exportType) + '&search=' + encodeURIComponent(exportSearch)" 
                       @click="setTimeout(() => openExportModal = false, 500)"
                       class="p-4 rounded-xl bg-gradient-to-br from-emerald-950/40 to-teal-950/40 border border-emerald-500/40 hover:border-emerald-400 transition-all flex flex-col justify-between group shadow-sm text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="p-2 rounded-lg bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-xl">table_chart</span>
                            </span>
                            <span class="font-mono text-[10px] text-emerald-400 font-bold px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-500/20">.XLSX / EXCEL</span>
                        </div>
                        <div>
                            <h4 class="font-headline font-bold text-sm text-white group-hover:text-emerald-300 transition-colors">Unduh Format Excel (.xlsx)</h4>
                            <p class="text-[11px] text-tertiary font-mono mt-0.5 leading-snug">File native Excel rapi, lengkap dengan kop resmi, styling warna, zebra striping, dan kolom terformat.</p>
                        </div>
                    </a>

                    <!-- Option 2: PDF Document -->
                    <a :href="'{{ route('employees.export.pdf') }}' + '?department=' + encodeURIComponent(exportDepartment) + '&status=' + encodeURIComponent(exportStatus) + '&employment_type=' + encodeURIComponent(exportType) + '&search=' + encodeURIComponent(exportSearch)" 
                       target="_blank"
                       @click="openExportModal = false"
                       class="p-4 rounded-xl bg-gradient-to-br from-rose-950/40 to-red-950/40 border border-rose-500/40 hover:border-rose-400 transition-all flex flex-col justify-between group shadow-sm text-left">
                        <div class="flex items-center justify-between mb-2">
                            <span class="p-2 rounded-lg bg-rose-500/20 text-rose-400 border border-rose-500/30 group-hover:scale-105 transition-transform">
                                <span class="material-symbols-outlined text-xl">picture_as_pdf</span>
                            </span>
                            <span class="font-mono text-[10px] text-rose-400 font-bold px-2 py-0.5 rounded bg-rose-950/60 border border-rose-500/20">.PDF / PRINT</span>
                        </div>
                        <div>
                            <h4 class="font-headline font-bold text-sm text-white group-hover:text-rose-300 transition-colors">Lihat & Cetak Laporan PDF</h4>
                            <p class="text-[11px] text-tertiary font-mono mt-0.5 leading-snug">Format resmi kop perusahaan, metrik ringkasan, dan kolom tanda tangan.</p>
                        </div>
                    </a>
                </div>

                <!-- Footer / Quick Action -->
                <div class="flex items-center justify-between pt-3 border-t border-[#282a2e] text-xs font-mono">
                    <span class="text-tertiary text-[11px]">Memuat seluruh data profil & status karyawan</span>
                    <button type="button" @click="openExportModal = false" class="px-4 py-2 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors font-mono">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
