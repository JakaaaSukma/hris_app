@extends('layouts.app')

@section('title', 'Pengaturan Profil Saya')

@section('content')
<div class="flex flex-col gap-6 max-w-4xl mx-auto" x-data="{
    activeTab: 'general',
    avatarPreview: '{{ $employee->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200' }}',
    presetAvatar(url) {
        this.avatarPreview = url;
        document.getElementById('avatar_url_input').value = url;
    },
    previewFile(event) {
        const file = event.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    }
}">

    <!-- Header Page -->
    <div class="p-6 bg-surface-container rounded-2xl border border-[#282a2e]/60 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                <span class="font-mono text-[10px] text-tertiary uppercase tracking-wider">Account Settings</span>
            </div>
            <h1 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Pengaturan Profil & Akun</h1>
            <p class="font-mono text-xs text-tertiary">Kelola data pribadi, foto identitas profil, email login, dan kata sandi Anda</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 rounded-lg font-mono text-xs font-bold uppercase {{ $user->isDirektur() ? 'bg-amber-950/60 text-amber-400 border border-amber-500/30' : ($user->isManager() ? 'bg-red-950/60 text-primary-fixed border border-red-500/30' : 'bg-emerald-950/60 text-emerald-400 border border-emerald-500/30') }}">
                {{ $user->role }}
            </span>
            @if($user->department)
                <span class="px-2.5 py-1 rounded-lg font-mono text-xs bg-surface-container-high border border-[#333539] text-tertiary">
                    Divisi {{ $user->department }}
                </span>
            @endif
        </div>
    </div>

    <!-- Form Pengaturan Profil -->
    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')
        <input type="hidden" name="avatar_url" id="avatar_url_input" value="{{ $employee->avatar_url ?? '' }}">

        <!-- Section 1: Foto Profil & Identitas Visual -->
        <div class="p-6 bg-surface-container-low rounded-2xl border border-[#282a2e]/70 shadow-sm space-y-5">
            <div class="flex items-center gap-2 pb-3 border-b border-[#282a2e]/60">
                <span class="material-symbols-outlined text-primary-container text-xl">account_circle</span>
                <h2 class="font-headline font-semibold text-base text-on-surface">Foto Profil Akun</h2>
            </div>

            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                <!-- Avatar Preview -->
                <div class="relative group">
                    <img :src="avatarPreview" 
                         alt="{{ $user->name }}" 
                         class="w-24 h-24 rounded-2xl object-cover ring-4 ring-primary-container/30 shadow-xl bg-surface-container"/>
                    <label for="avatar_file" class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white">
                        <span class="material-symbols-outlined text-xl">photo_camera</span>
                        <span class="text-[10px] font-mono mt-0.5">Ubah Foto</span>
                    </label>
                </div>

                <!-- Upload File & Preset Pilihan -->
                <div class="flex-1 space-y-3 text-center sm:text-left">
                    <div>
                        <h4 class="font-headline font-medium text-sm text-on-surface">Unggah Foto Baru</h4>
                        <p class="text-xs text-tertiary font-mono">Format yang didukung: JPG, PNG, WEBP (Maks. 2MB)</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <label class="px-3.5 py-2 bg-surface-container-high hover:bg-surface-bright border border-[#333539] rounded-lg text-xs font-semibold text-on-surface cursor-pointer flex items-center gap-2 transition-all">
                            <span class="material-symbols-outlined text-sm text-primary-container">upload</span>
                            <span>Pilih File dari Perangkat</span>
                            <input type="file" name="avatar_file" id="avatar_file" accept="image/*" class="hidden" @change="previewFile">
                        </label>
                    </div>

                    <!-- Pilihan Avatar Avatar Default Modern -->
                    <div class="pt-2">
                        <span class="block text-[10px] font-mono uppercase text-tertiary mb-2">Atau pilih avatar default cepat:</span>
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
                            @php
                                $presets = [
                                    'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150',
                                    'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150',
                                    'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150',
                                    'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150',
                                    'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150',
                                    'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150',
                                ];
                            @endphp
                            @foreach($presets as $p)
                                <button type="button" 
                                        @click="presetAvatar('{{ $p }}')" 
                                        class="w-8 h-8 rounded-full overflow-hidden border-2 border-transparent hover:border-primary-container transition-all hover:scale-110">
                                    <img src="{{ $p }}" class="w-full h-full object-cover"/>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Data Informasi Pribadi -->
        <div class="p-6 bg-surface-container-low rounded-2xl border border-[#282a2e]/70 shadow-sm space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-[#282a2e]/60">
                <span class="material-symbols-outlined text-primary-container text-xl">badge</span>
                <h2 class="font-headline font-semibold text-base text-on-surface">Informasi Akun & Data Pribadi</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Nama Lengkap</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">person</span>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full pl-9 pr-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Email Akun (Login)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">mail</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full pl-9 pr-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs font-mono text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                    <p class="text-[10px] text-tertiary mt-1">*Digunakan untuk masuk ke sistem HRIS.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Nomor WhatsApp / HP</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">call</span>
                        <input type="text" name="phone" value="{{ old('phone', $employee?->phone) }}" placeholder="+62 812-XXXX-XXXX" class="w-full pl-9 pr-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">NIP (Nomor Induk Pegawai)</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-2.5 text-tertiary text-base">fingerprint</span>
                        <input type="text" disabled value="{{ $employee?->nip ?? 'NEX-DIRECTOR' }}" class="w-full pl-9 pr-3 py-2 bg-surface-container/50 border border-[#333539] rounded-lg text-xs font-mono text-tertiary cursor-not-allowed"/>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Jabatan / Posisi</label>
                    <input type="text" disabled value="{{ $employee?->role_title ?? ($user->isDirektur() ? 'Direktur Utama' : 'Staff') }}" class="w-full px-3 py-2 bg-surface-container/50 border border-[#333539] rounded-lg text-xs text-tertiary cursor-not-allowed"/>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Departemen / Divisi</label>
                    <input type="text" disabled value="{{ $user->department ?? 'Direksi' }}" class="w-full px-3 py-2 bg-surface-container/50 border border-[#333539] rounded-lg text-xs text-tertiary cursor-not-allowed"/>
                </div>
            </div>
        </div>

        <!-- Section 3: Keamanan & Ganti Kata Sandi -->
        <div class="p-6 bg-surface-container-low rounded-2xl border border-[#282a2e]/70 shadow-sm space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-[#282a2e]/60">
                <span class="material-symbols-outlined text-primary-container text-xl">lock_reset</span>
                <h2 class="font-headline font-semibold text-base text-on-surface">Keamanan & Ubah Password</h2>
            </div>

            <p class="text-xs text-tertiary">Kosongkan kolom di bawah ini jika Anda tidak ingin mengubah password akun.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" placeholder="••••••••" class="w-full px-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Password Baru</label>
                    <input type="password" name="new_password" placeholder="Minimal 6 karakter" class="w-full px-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                </div>

                <div>
                    <label class="block text-xs font-mono uppercase tracking-wider text-tertiary mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="new_password_confirmation" placeholder="Ulangi password baru" class="w-full px-3 py-2 bg-surface-container border border-[#333539] rounded-lg text-xs text-on-surface focus:outline-none focus:border-primary-container"/>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('dashboard') }}" class="px-4 py-2.5 bg-surface-container-high hover:bg-surface-bright text-xs text-on-surface rounded-lg transition-colors">
                Batal
            </a>
            <button type="submit" class="flex items-center gap-2 px-6 py-2.5 bg-primary-container hover:bg-red-700 text-on-primary-container text-xs font-semibold rounded-lg shadow-lg shadow-primary-container/20 transition-all">
                <span class="material-symbols-outlined text-base">save</span>
                <span>Simpan Perubahan Profil</span>
            </button>
        </div>
    </form>

    <!-- ============================================================== -->
    <!-- SECTION 4: PERANGKAT AKTIF (MAKSIMAL 3 DEVICE BERSAMAAN)        -->
    <!-- ============================================================== -->
    <div class="p-6 bg-surface-container-low rounded-2xl border border-[#282a2e]/70 shadow-sm space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-[#282a2e]/60">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">devices</span>
                </span>
                <div>
                    <h2 class="font-headline font-semibold text-base text-on-surface">Perangkat Aktif (Maks. 3 Device)</h2>
                    <p class="text-xs text-tertiary">Daftar perangkat yang saat ini sedang login menggunakan akun Anda</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-bold {{ $activeDevices->count() >= 3 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                {{ $activeDevices->count() }} / 3 Perangkat
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
            @forelse($activeDevices as $device)
                @php
                    $isCurrent = ($device->session_id === $currentSessionId);
                @endphp
                <div class="p-4 rounded-xl {{ $isCurrent ? 'bg-purple-950/20 border-purple-500/40 ring-1 ring-purple-500/30' : 'bg-[#151922] border-slate-800' }} border flex flex-col justify-between gap-3">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="material-symbols-outlined text-xl {{ $isCurrent ? 'text-purple-400' : 'text-slate-400' }}">
                                {{ str_contains(strtolower($device->device_name), 'phone') || str_contains(strtolower($device->device_name), 'android') || str_contains(strtolower($device->device_name), 'ios') ? 'smartphone' : 'laptop_mac' }}
                            </span>
                            @if($isCurrent)
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-mono font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                    Sesi Saat Ini
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-mono text-slate-400 bg-slate-800 border border-slate-700">
                                    Aktif
                                </span>
                            @endif
                        </div>
                        <h4 class="font-headline font-bold text-xs text-white truncate" title="{{ $device->device_name }}">
                            {{ $device->device_name ?: 'Perangkat Tidak Dikenal' }}
                        </h4>
                        <p class="text-[11px] text-slate-400 font-mono">IP: {{ $device->ip_address ?: '127.0.0.1' }}</p>
                        <p class="text-[10px] text-slate-500 font-mono">
                            Aktif: {{ $device->last_active_at ? $device->last_active_at->diffForHumans() : 'Baru saja' }}
                        </p>
                    </div>

                    @if(!$isCurrent)
                        <form action="{{ route('profile.terminateDevice', $device->id) }}" method="POST"
                              onsubmit="return confirm('Apakah Anda yakin ingin memutuskan sesi perangkat ini? Pengguna pada perangkat tersebut akan langsung keluar.');"
                              class="pt-2 border-t border-slate-800/80">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-1.5 px-3 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[11px] font-semibold flex items-center justify-center gap-1.5 transition-all">
                                <span class="material-symbols-outlined text-xs">logout</span>
                                <span>Putuskan Sesi</span>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="col-span-3 py-6 text-center text-xs text-tertiary">
                    Tidak ada riwayat perangkat terdeteksi.
                </div>
            @endforelse
        </div>
        <p class="text-[11px] text-tertiary font-mono pt-1">
            *Catatan: Jika login dari perangkat ke-4, sesi perangkat yang paling lama tidak aktif akan otomatis diakhiri.
        </p>
    </div>

    <!-- ============================================================== -->
    <!-- SECTION 5 & 6: TAB RIWAYAT LOG LOGIN & LOG AKTIVITAS AKUN        -->
    <!-- ============================================================== -->
    <div class="p-6 bg-surface-container-low rounded-2xl border border-[#282a2e]/70 shadow-sm space-y-4" x-data="{ logTab: 'activity' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-[#282a2e]/60">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-lg">history</span>
                </span>
                <div>
                    <h2 class="font-headline font-semibold text-base text-on-surface">Audit Keamanan & Riwayat Log</h2>
                    <p class="text-xs text-tertiary">Pantau jejak aktivitas sistem dan riwayat login demi keamanan akun</p>
                </div>
            </div>

            <!-- Tab Switcher -->
            <div class="flex items-center gap-1.5 p-1 bg-[#141822] rounded-xl border border-slate-800">
                <button type="button" 
                        @click="logTab = 'activity'"
                        :class="logTab === 'activity' ? 'bg-primary-container text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">browse_activity</span>
                    <span>Log Aktivitas ({{ $activityLogs->count() }})</span>
                </button>
                <button type="button" 
                        @click="logTab = 'login'"
                        :class="logTab === 'login' ? 'bg-primary-container text-white font-bold' : 'text-slate-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs transition-all flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-sm">login</span>
                    <span>Log Login ({{ $loginLogs->count() }})</span>
                </button>
            </div>
        </div>

        <!-- 1. TABEL LOG AKTIVITAS -->
        <div x-show="logTab === 'activity'" class="space-y-3">
            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#121620] text-slate-400 font-mono uppercase text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Modul</th>
                            <th class="p-3">Aksi</th>
                            <th class="p-3">Deskripsi Aktivitas</th>
                            <th class="p-3">Perangkat / IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 bg-[#161a25]">
                        @forelse($activityLogs as $act)
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-3 font-mono text-slate-400 whitespace-nowrap">
                                    {{ $act->created_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                        {{ $act->module }}
                                    </span>
                                </td>
                                <td class="p-3 font-mono font-bold text-amber-400 whitespace-nowrap">
                                    {{ $act->action }}
                                </td>
                                <td class="p-3 text-slate-200">
                                    {{ $act->description }}
                                </td>
                                <td class="p-3 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $act->ip_address }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500 font-mono">
                                    Belum ada log aktivitas tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. TABEL LOG LOGIN -->
        <div x-show="logTab === 'login'" class="space-y-3">
            <div class="overflow-x-auto rounded-xl border border-slate-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#121620] text-slate-400 font-mono uppercase text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Perangkat & Browser</th>
                            <th class="p-3">Alamat IP</th>
                            <th class="p-3">Catatan / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 bg-[#161a25]">
                        @forelse($loginLogs as $log)
                            @php
                                $statusBadge = match($log->status) {
                                    'success' => 'bg-emerald-950/60 text-emerald-400 border-emerald-500/30',
                                    'failed' => 'bg-rose-950/60 text-rose-400 border-rose-500/30',
                                    'logout' => 'bg-slate-800 text-slate-300 border-slate-700',
                                    'terminated' => 'bg-amber-950/60 text-amber-400 border-amber-500/30',
                                    default => 'bg-slate-800 text-slate-400 border-slate-700'
                                };
                                $statusLabel = match($log->status) {
                                    'success' => 'LOGIN BERHASIL',
                                    'failed' => 'GAGAL LOGIN',
                                    'logout' => 'LOGOUT',
                                    'terminated' => 'SESI DIPUTUS',
                                    default => strtoupper($log->status)
                                };
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-3 font-mono text-slate-400 whitespace-nowrap">
                                    {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                                </td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold border {{ $statusBadge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="p-3 text-slate-200 whitespace-nowrap">
                                    {{ $log->device_name ?: 'Tidak Dikenal' }}
                                </td>
                                <td class="p-3 font-mono text-[11px] text-slate-400 whitespace-nowrap">
                                    {{ $log->ip_address ?: '-' }}
                                </td>
                                <td class="p-3 text-slate-400">
                                    {{ $log->failure_reason ?: ($log->status === 'success' ? 'Sesi terhubung normal' : '-') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500 font-mono">
                                    Belum ada log login tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
