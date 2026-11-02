@extends('layouts.app')

@section('title', 'Panduan Penggunaan & Diagram Alur Sistem HRIS')

@section('content')
<div class="space-y-8" x-data="{ 
    activeTab: 'flowchart',
    selectedRole: '{{ auth()->user()->isDirektur() ? 'direktur' : (auth()->user()->isManager() ? 'manager' : 'karyawan') }}',
    searchKeyword: ''
}">

    <!-- Header Hero Banner with Rich Aesthetics -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-rose-950/40 to-slate-900 border border-rose-500/30 p-6 lg:p-8 shadow-2xl">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-300 font-mono text-xs">
                    <span class="material-symbols-outlined text-sm text-rose-400">menu_book</span>
                    <span>Standard Operating Procedure (SOP) & Architecture Workflow</span>
                </div>
                <h1 class="font-headline font-extrabold text-2xl lg:text-3xl text-white tracking-tight">
                    Panduan Penggunaan & Diagram Alur Sistem
                </h1>
                <p class="text-sm text-slate-300 leading-relaxed">
                    Pedoman terpadu hak akses, aturan bisnis, dan diagram alur operasional per modul dalam sistem HRIS. Halaman ini dapat diakses oleh seluruh tingkatan pengguna (Direktur Utama, Para Manager, dan Seluruh Karyawan).
                </p>
            </div>

            <!-- Role Indicator & Quick Profile Badge -->
            <div class="flex flex-col sm:flex-row md:flex-col items-start md:items-end gap-2.5">
                <div class="px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700/70 text-right">
                    <div class="text-[10px] font-mono text-slate-400 uppercase tracking-wider">Peran Akun Anda Saat Ini</div>
                    <div class="flex items-center gap-2 justify-end mt-0.5">
                        <span class="w-2 h-2 rounded-full {{ auth()->user()->isDirektur() ? 'bg-amber-400' : (auth()->user()->isManager() ? 'bg-rose-400' : 'bg-emerald-400') }} animate-pulse"></span>
                        <span class="font-headline font-bold text-sm text-white capitalize">{{ auth()->user()->name }}</span>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30 uppercase font-semibold">
                            {{ auth()->user()->role }} • {{ auth()->user()->department ?? 'All' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mt-8 flex flex-wrap gap-2 border-t border-slate-800/80 pt-4">
            <button @click="activeTab = 'flowchart'" 
                    :class="activeTab === 'flowchart' ? 'bg-rose-600 text-white font-bold shadow-lg shadow-rose-600/30 border-rose-500' : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800 border-slate-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">account_tree</span>
                <span>Diagram Alur (Workflow Engine)</span>
            </button>

            <button @click="activeTab = 'role_guide'" 
                    :class="activeTab === 'role_guide' ? 'bg-rose-600 text-white font-bold shadow-lg shadow-rose-600/30 border-rose-500' : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800 border-slate-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">badge</span>
                <span>Panduan Berdasarkan Peran (Role-Based)</span>
            </button>

            <button @click="activeTab = 'modules'" 
                    :class="activeTab === 'modules' ? 'bg-rose-600 text-white font-bold shadow-lg shadow-rose-600/30 border-rose-500' : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800 border-slate-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">view_in_ar</span>
                <span>Panduan Fitur per Modul</span>
            </button>

            <button @click="activeTab = 'rules'" 
                    :class="activeTab === 'rules' ? 'bg-rose-600 text-white font-bold shadow-lg shadow-rose-600/30 border-rose-500' : 'bg-slate-800/60 text-slate-400 hover:text-white hover:bg-slate-800 border-slate-700/60'"
                    class="px-4 py-2 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">gavel</span>
                <span>Aturan Ketat & Integritas Data</span>
            </button>
        </div>
    </div>

    <!-- TAB 1: DIAGRAM ALUR (WORKFLOW ENGINE) -->
    <div x-show="activeTab === 'flowchart'" class="space-y-6" x-transition:enter="transition ease-out duration-150">
        
        <!-- Workflow 1: Presensi & Izin Kehadiran -->
        <div class="glass-card rounded-2xl p-6 border border-slate-800 bg-[#11151f]/90 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">fingerprint</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">1. Diagram Alur Presensi & Perizinan Kehadiran</h2>
                        <p class="text-xs text-slate-400">Mekanisme mesin Solution X609, presensi manual web, dan perizinan kehadiran</p>
                    </div>
                </div>
                <span class="font-mono text-[10px] px-2.5 py-1 rounded bg-rose-500/10 text-rose-300 border border-rose-500/20">
                    ADMS Cloud + Web Approval
                </span>
            </div>

            <!-- Visual Flowchart Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-2">
                <!-- Step 1 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-rose-400 font-bold uppercase tracking-wider">Tahap 1</span>
                        <h3 class="font-headline font-semibold text-sm text-white mt-1">Perekaman Absensi</h3>
                        <p class="text-xs text-slate-400 mt-2">
                            Karyawan melakukan scan pada mesin finger <strong>Solution X609</strong> di kantor (sinkronisasi ADMS otomatis via NIP).
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] font-mono text-emerald-400 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">sync</span>
                        <span>Auto Push ke Server</span>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-amber-400 font-bold uppercase tracking-wider">Tahap 2</span>
                        <h3 class="font-headline font-semibold text-sm text-white mt-1">Kalkulasi Toleransi</h3>
                        <p class="text-xs text-slate-400 mt-2">
                            Sistem mengkalkulasi jam kedatangan (Toleransi 15 menit): <br>
                            &bull; <strong>&le; 09:15:00 WIB:</strong> Tepat Waktu<br>
                            &bull; <strong>&gt; 09:15:00 WIB:</strong> Terlambat
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] font-mono text-amber-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">timer</span>
                        <span>Batas Toleransi 09:15:00 WIB</span>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-blue-400 font-bold uppercase tracking-wider">Tahap 3</span>
                        <h3 class="font-headline font-semibold text-sm text-white mt-1">Pengajuan Perizinan</h3>
                        <p class="text-xs text-slate-400 mt-2">
                            Jika terlambat, dinas keluar, atau lupa scan pulang, staf/manager mengajukan perizinan di menu <strong>Izin & Dispensasi</strong>.
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] font-mono text-blue-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">event_upcoming</span>
                        <span>Semua Akun Berhak</span>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 flex flex-col justify-between">
                    <div>
                        <span class="font-mono text-[10px] text-purple-400 font-bold uppercase tracking-wider">Tahap 4</span>
                        <h3 class="font-headline font-semibold text-sm text-white mt-1">Verifikasi & Approval</h3>
                        <p class="text-xs text-slate-400 mt-2">
                            Permohonan ditinjau khusus oleh <strong>Manager HR</strong> atau <strong>Direktur Utama</strong>. Jika disetujui, kalkulasi jam langsung disesuaikan.
                        </p>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-800/80 text-[11px] font-mono text-purple-300 flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">verified</span>
                        <span>Manager HR / Direktur</span>
                    </div>
                </div>
            </div>

            <!-- Catatan Audit Trail Presensi Web -->
            <div class="p-3.5 rounded-xl bg-blue-950/40 border border-blue-500/20 text-xs text-slate-300 flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-400 text-lg flex-shrink-0 mt-0.5">info</span>
                <div>
                    <strong class="text-blue-300">Catatan Khusus Presensi Web:</strong>
                    Tombol <em>Clock In</em>, <em>Clock Out</em>, dan <em>Presensi Cepat</em> di web <strong>hanya aktif bagi Manager HR dan Direktur Utama</strong>. Seluruh aktivitas presensi manual mandiri atau mengabsenkan orang lain akan tercatat permanen di catatan kehadiran: <code class="px-1.5 py-0.5 bg-slate-900 rounded text-blue-300 font-mono">[Absen Masuk Manual Mandiri oleh ...]</code> atau <code class="px-1.5 py-0.5 bg-slate-900 rounded text-blue-300 font-mono">[Diabsenkan Masuk oleh ...]</code>.
                </div>
            </div>
        </div>

        <!-- Workflow 2: Cuti & Dispensasi -->
        <div class="glass-card rounded-2xl p-6 border border-slate-800 bg-[#11151f]/90 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">event_busy</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">2. Diagram Alur Pengajuan Cuti & Dispensasi</h2>
                        <p class="text-xs text-slate-400">Proses permohonan cuti tahunan, sakit, melahirkan, dan dispensasi dinas</p>
                    </div>
                </div>
                <span class="font-mono text-[10px] px-2.5 py-1 rounded bg-amber-500/10 text-amber-300 border border-amber-500/20">
                    Strict Approval Flow
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-2">
                <!-- Step 1 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-amber-400 font-bold uppercase tracking-wider">Langkah 1: Input Pengajuan</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Pengajuan via Modul Cuti</h3>
                    <p class="text-xs text-slate-400 mt-2">
                        Karyawan/Manager mengisi formulir: jenis cuti (Tahunan, Sakit, Melahirkan, Izin Khusus), tanggal mulai & selesai, serta alasan.
                    </p>
                    <div class="mt-3 text-[11px] font-mono text-slate-400">
                        Status Awal: <span class="text-amber-400 font-bold">Menunggu Manager HR / Direktur</span>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-cyan-400 font-bold uppercase tracking-wider">Langkah 2: Otorisasi & Review</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Review Manager HR / Direktur</h3>
                    <p class="text-xs text-slate-400 mt-2">
                        Pengajuan masuk ke antrean approval Manager HR dan Direktur Utama. <em>Staf HR biasa hanya dapat melihat riwayat pengajuannya sendiri.</em>
                    </p>
                    <div class="mt-3 text-[11px] font-mono text-slate-400">
                        Anti Self-Approval: <span class="text-rose-400 font-bold">Dilarang Approve Diri Sendiri</span>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-emerald-400 font-bold uppercase tracking-wider">Langkah 3: Putusan & Notifikasi</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Disetujui atau Ditolak</h3>
                    <p class="text-xs text-slate-400 mt-2">
                        &bull; <strong>Disetujui:</strong> Otomatis masuk ke kalender dan log absensi sebagai status cuti.<br>
                        &bull; <strong>Ditolak:</strong> Wajib menyertakan catatan penolakan resmi bagi pemohon.
                    </p>
                    <div class="mt-3 text-[11px] font-mono text-emerald-300">
                        Auto Notifikasi Lonceng & Rekap Log
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflow 3: Reimbursement & Penggajian (Finance) -->
        <div class="glass-card rounded-2xl p-6 border border-slate-800 bg-[#11151f]/90 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">payments</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">3. Diagram Alur Reimbursement & Keuangan</h2>
                        <p class="text-xs text-slate-400">Prosedur klaim biaya operasional, verifikasi bukti, dan transfer reimbursement</p>
                    </div>
                </div>
                <span class="font-mono text-[10px] px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                    Finance Segregation
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 pt-2">
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-slate-400 font-bold uppercase">1. Pengajuan</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Upload Bukti Kwitansi</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Pegawai menginput nominal klaim, judul biaya, dan mengunggah foto struk/nota asli.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-amber-400 font-bold uppercase">2. Audit Finance</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Verifikasi Bukti</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Tim Finance memverifikasi keaslian nota, kepatuhan pagu anggaran kantor, dan keabsahan klaim.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-blue-400 font-bold uppercase">3. Keputusan</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Persetujuan / Tolak</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Manager Finance / Direktur menetapkan status Approved atau Rejected dengan catatan alasan.
                    </p>
                </div>
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800">
                    <span class="font-mono text-[10px] text-emerald-400 font-bold uppercase">4. Pencairan</span>
                    <h3 class="font-headline font-semibold text-sm text-white mt-1">Paid & Rekapitulasi</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Finance menandai klaim sebagai <em>Paid</em> setelah dana ditransfer ke rekening pegawai.
                    </p>
                </div>
            </div>
        </div>

        <!-- Workflow 4: Reset Kehadiran Dua Arah (Bilateral Approval) -->
        <div class="glass-card rounded-2xl p-6 border border-slate-800 bg-[#11151f]/90 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">history_toggle_off</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">4. Alur Reset Kehadiran Massal (Bilateral Approval)</h2>
                        <p class="text-xs text-slate-400">Mekanisme keamanan ganda untuk mereset log kehadiran seluruh perusahaan</p>
                    </div>
                </div>
                <span class="font-mono text-[10px] px-2.5 py-1 rounded bg-rose-500/10 text-rose-300 border border-rose-500/20 font-bold">
                    Dual-Key Security
                </span>
            </div>

            <div class="p-4 rounded-xl bg-slate-900/70 border border-slate-800 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/60">
                        <div class="flex items-center gap-2 text-rose-400 font-bold text-xs uppercase font-mono">
                            <span class="material-symbols-outlined text-base">person</span>
                            <span>Kasus A: Diajukan oleh Manager HR</span>
                        </div>
                        <p class="text-xs text-slate-300 mt-2">
                            Jika <strong>Manager HR</strong> mengajukan reset log presensi (karena kendala mesin atau pergantian periode), maka pengajuan tersebut <strong>wajib disetujui oleh Direktur Utama</strong> sebelum database dibersihkan.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-800/40 border border-slate-700/60">
                        <div class="flex items-center gap-2 text-amber-400 font-bold text-xs uppercase font-mono">
                            <span class="material-symbols-outlined text-base">shield_person</span>
                            <span>Kasus B: Diajukan oleh Direktur Utama</span>
                        </div>
                        <p class="text-xs text-slate-300 mt-2">
                            Jika <strong>Direktur Utama</strong> yang mengajukan reset, maka tombol persetujuan berpindah ke <strong>Manager HR</strong>. Direktur tidak dapat mengeksekusi reset sendirian tanpa persetujuan HR.
                        </p>
                    </div>
                </div>

                <div class="text-xs text-slate-400 pt-2 border-t border-slate-800 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-400 text-sm">notifications_active</span>
                    <span>Saat disetujui, sistem otomatis menyiarkan notifikasi resmi ke seluruh akun karyawan & manager.</span>
                </div>
            </div>
        </div>

    </div>

    <!-- TAB 2: PANDUAN BERDASARKAN PERAN (ROLE-BASED) -->
    <div x-show="activeTab === 'role_guide'" class="space-y-6" x-transition:enter="transition ease-out duration-150">
        
        <!-- Role Selector Buttons -->
        <div class="flex flex-wrap gap-2.5">
            <button @click="selectedRole = 'karyawan'" 
                    :class="selectedRole === 'karyawan' ? 'bg-emerald-600 text-white font-bold shadow-lg shadow-emerald-600/30 border-emerald-500' : 'bg-slate-800/60 text-slate-400 hover:text-white border-slate-700/60'"
                    class="px-5 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">badge</span>
                <span>Role: Karyawan / Staff</span>
            </button>

            <button @click="selectedRole = 'manager'" 
                    :class="selectedRole === 'manager' ? 'bg-rose-600 text-white font-bold shadow-lg shadow-rose-600/30 border-rose-500' : 'bg-slate-800/60 text-slate-400 hover:text-white border-slate-700/60'"
                    class="px-5 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">supervisor_account</span>
                <span>Role: Manager Departemen</span>
            </button>

            <button @click="selectedRole = 'direktur'" 
                    :class="selectedRole === 'direktur' ? 'bg-amber-600 text-white font-bold shadow-lg shadow-amber-600/30 border-amber-500' : 'bg-slate-800/60 text-slate-400 hover:text-white border-slate-700/60'"
                    class="px-5 py-2.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border">
                <span class="material-symbols-outlined text-base">admin_panel_settings</span>
                <span>Role: Direktur Utama</span>
            </button>
        </div>

        <!-- Role: Karyawan -->
        <div x-show="selectedRole === 'karyawan'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-emerald-500/30 bg-[#11151f]/90 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">badge</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">Panduan Hak Akses & Tugas: Karyawan / Staff</h2>
                        <p class="text-xs text-slate-400">Hak dan kewajiban operasional harian staff di sistem HRIS</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-300">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-400 text-base">check_circle</span>
                            <span>Fitur yang Dapat Diakses</span>
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-slate-400 pl-1 leading-relaxed">
                            <li><strong>Presensi Harian:</strong> Melakukan scan jari pada mesin Solution X609 di kantor sebelum 09:00 WIB (toleransi s.d. 09:15 WIB).</li>
                            <li><strong>Izin & Dispensasi:</strong> Mengajukan izin terlambat, dinas luar kota/lapangan, atau keterangan lupa absen pulang.</li>
                            <li><strong>Cuti:</strong> Mengajukan cuti tahunan, cuti sakit, melahirkan, atau izin dispensasi khusus.</li>
                            <li><strong>Slip Gaji & Reimbursement:</strong> Melihat riwayat slip gaji pribadi dan mengunggah klaim reimbursement biaya kerja.</li>
                            <li><strong>Kalender:</strong> Meninjau agenda kantor dan membuat agenda pribadi (individu).</li>
                            <li><strong>Chat Departemen:</strong> Berkomunikasi dengan rekan satu divisi dan manager departemen.</li>
                            <li><strong>Fasilitas:</strong> Melaporkan kerusakan aset kantor atau kendala inventaris ke tim General Affairs.</li>
                        </ul>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-400 text-base">block</span>
                            <span>Batasan Hak Akses (Prinsip Keamanan)</span>
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-slate-400 pl-1 leading-relaxed">
                            <li><strong>Tanpa Tombol Clock In Web:</strong> Karyawan tidak dapat mencatat presensi manual via web (wajib via mesin finger).</li>
                            <li><strong>Data Terisolasi:</strong> Karyawan (termasuk staf HR biasa) hanya dapat melihat data cuti, izin, dan slip gaji milik pribadinya sendiri.</li>
                            <li><strong>Tanpa Hak Approval:</strong> Karyawan tidak memiliki tombol untuk menyetujui pengajuan apapun.</li>
                            <li><strong>Tanpa Akses Reset:</strong> Karyawan dilarang mengakses modul reset data kehadiran.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role: Manager -->
        <div x-show="selectedRole === 'manager'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-rose-500/30 bg-[#11151f]/90 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">supervisor_account</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">Panduan Hak Akses & Tugas: Manager Departemen</h2>
                        <p class="text-xs text-slate-400">Kewenangan supervisi, delegasi tugas, approval berjenjang, dan komunikasi direksi</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-300">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-rose-400 text-base">verified_user</span>
                            <span>Kewenangan Khusus Manager</span>
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-slate-400 pl-1 leading-relaxed">
                            <li><strong>Supervisi Tim:</strong> Memberikan tugas kerja (Task) dan memantau progres tugas anggota divisi.</li>
                            <li><strong>Jadwal Antar-Departemen:</strong> Mengajukan agenda kalender koordinasi lintas departemen.</li>
                            <li><strong>Menu Chat Ganda:</strong> Akses ke <em>Chat Departemen</em> bersama anggota tim dan <em>Chat Direksi</em> eksklusif langsung ke Direktur Utama.</li>
                            <li><strong>Approval Berdasarkan Divisi:</strong>
                                <br>&bull; <strong>Manager HR:</strong> Memiliki wewenang eksklusif menyetujui Cuti, Izin, Dispensasi, dan pengajuan Reset Kehadiran.
                                <br>&bull; <strong>Manager Finance:</strong> Menyetujui klaim Reimbursement dan penerbitan slip gaji.
                                <br>&bull; <strong>Manager GA:</strong> Menyetujui pengajuan inventaris & perbaikan fasilitas.
                            </li>
                        </ul>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-400 text-base">gavel</span>
                            <span>Aturan Strict Manager</span>
                        </h3>
                        <ul class="list-disc list-inside space-y-1 text-slate-400 pl-1 leading-relaxed">
                            <li><strong>Anti Self-Approval:</strong> Manager dilarang keras menyetujui pengajuan cuti atau reimbursement miliknya sendiri (wajib dialihkan ke Direktur).</li>
                            <li><strong>Presensi Web Khusus HR:</strong> Hanya <strong>Manager HR</strong> yang memiliki tombol presensi manual / presensi cepat di web. Manager departemen lain wajib absen via finger Solution X609.</li>
                            <li><strong>Wajib Alasan Penolakan:</strong> Setiap tindakan penolakan pengajuan wajib menyertakan alasan tertulis yang valid.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role: Direktur -->
        <div x-show="selectedRole === 'direktur'" class="space-y-4">
            <div class="glass-card rounded-2xl p-6 border border-amber-500/30 bg-[#11151f]/90 space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-800">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center">
                        <span class="material-symbols-outlined text-xl">admin_panel_settings</span>
                    </div>
                    <div>
                        <h2 class="font-headline font-bold text-base text-white">Panduan Hak Akses: Direktur Utama (Executive)</h2>
                        <p class="text-xs text-slate-400">Hak veto tertinggi, global view seluruh modul, audit laporan, dan persetujuan final</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-300">
                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-1.5 text-amber-400">
                            <span class="material-symbols-outlined text-base">visibility</span>
                            <span>Global Dashboard</span>
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Melihat rekap statistik KPI, kehadiran real-time seluruh divisi, monitoring pipeline deals Bisnis Development, aset GA, dan pengeluaran reimbursement finance.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-1.5 text-emerald-400">
                            <span class="material-symbols-outlined text-base">how_to_reg</span>
                            <span>Full Approval Power</span>
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Memiliki wewenang menyetujui seluruh pengajuan cuti karyawan/manager, klaim reimbursement besar, pengajuan reset kehadiran HR, dan jadwal koordinasi antar-departemen.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                        <h3 class="font-headline font-bold text-white flex items-center gap-1.5 text-blue-400">
                            <span class="material-symbols-outlined text-base">print</span>
                            <span>Audit & PDF Report</span>
                        </h3>
                        <p class="text-slate-400 leading-relaxed">
                            Dapat mencetak dan mengunduh laporan PDF audit presensi harian/rentang tanggal, laporan KPI bulanan, dan log aktivitas keamanan sistem secara lengkap.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TAB 3: PANDUAN PER MODUL -->
    <div x-show="activeTab === 'modules'" class="space-y-4" x-transition:enter="transition ease-out duration-150">
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            
            <!-- Modul 1: Presensi -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-rose-500/10 text-rose-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">schedule</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Presensi & Kehadiran</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Perekaman data presensi real-time via Solution X609. Dilengkapi filter rentang tanggal (hingga 31 hari) dan export laporan PDF format A4.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-rose-300">
                    Menu: <a href="{{ route('attendance.index') }}" class="underline hover:text-white">Kehadiran</a>
                </div>
            </div>

            <!-- Modul 2: Cuti & Izin -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">event_busy</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Cuti & Dispensasi</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Form pengajuan cuti tahunan, sakit, melahirkan, serta dispensasi. Verifikasi ketat hanya oleh Manager HR dan Direktur Utama.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-amber-300">
                    Menu: <a href="{{ route('leave.index') }}" class="underline hover:text-white">Cuti & Izin</a>
                </div>
            </div>

            <!-- Modul 3: Kalender & Jadwal -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">calendar_month</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Kalender & Agenda</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Manajemen jadwal berjenjang (Personal, Departemen, Antar-Departemen, Perusahaan). Dilengkapi alur persetujuan lintas divisi.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-blue-300">
                    Menu: <a href="{{ route('calendar.index') }}" class="underline hover:text-white">Kalender & Jadwal</a>
                </div>
            </div>

            <!-- Modul 4: Keuangan & Slip Gaji -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">payments</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Keuangan & Slip Gaji</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Pengajuan reimbursement biaya operasional dengan upload kwitansi, penerbitan slip gaji resmi, dan pengiriman massal via email.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-emerald-300">
                    Menu: <a href="{{ route('finance.index') }}" class="underline hover:text-white">Keuangan & Slip Gaji</a>
                </div>
            </div>

            <!-- Modul 5: Chat & Komunikasi -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">forum</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Chat Departemen & Direksi</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Saluran pesan instan internal tim departemen serta kanal khusus komunikasi para manager langsung ke Direktur Utama.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-cyan-300">
                    Menu: <a href="{{ route('chat.index') }}" class="underline hover:text-white">Menu Chat</a>
                </div>
            </div>

            <!-- Modul 6: Fasilitas & Helpdesk GA -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between">
                <div>
                    <div class="w-9 h-9 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center mb-3">
                        <span class="material-symbols-outlined text-lg">home_repair_service</span>
                    </div>
                    <h3 class="font-headline font-bold text-sm text-white">Fasilitas & Inventaris GA</h3>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        Pelaporan kendala inventaris atau AC/fasilitas kantor oleh seluruh staf, serta manajemen tiket perbaikan oleh Tim General Affairs.
                    </p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] font-mono text-orange-300">
                    Menu: <a href="{{ route('facilities.index') }}" class="underline hover:text-white">Fasilitas & Kendala</a>
                </div>
            </div>

        </div>

    </div>

    <!-- TAB 4: ATURAN KETAT & INTEGRITAS -->
    <div x-show="activeTab === 'rules'" class="space-y-4" x-transition:enter="transition ease-out duration-150">
        
        <div class="glass-card rounded-2xl p-6 border border-slate-800 bg-[#11151f]/90 space-y-4">
            <h2 class="font-headline font-bold text-base text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-rose-400">security</span>
                <span>Aturan Ketat Integritas Data & Otorisasi Sistem</span>
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-300 pt-2">
                
                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <strong class="text-rose-400 text-sm flex items-center gap-1.5 font-headline">
                        <span class="material-symbols-outlined text-base">block</span>
                        <span>1. Larangan Anti Self-Approval</span>
                    </strong>
                    <p class="text-slate-400 leading-relaxed">
                        Pengguna yang membuat pengajuan (baik itu Cuti, Izin, Reimbursement, maupun Inventaris) <strong>dilarang keras menyetujui pengajuannya sendiri</strong>. Sistem memblokir aksi approval mandiri di tingkat view dan backend controller.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <strong class="text-amber-400 text-sm flex items-center gap-1.5 font-headline">
                        <span class="material-symbols-outlined text-base">manage_search</span>
                        <span>2. Pencatatan Audit Trail Permanen</span>
                    </strong>
                    <p class="text-slate-400 leading-relaxed">
                        Setiap persetujuan wajib mencatat identitas penyetuju (<code class="px-1 py-0.5 bg-slate-800 text-amber-300 rounded font-mono">approved_by</code>) dan stempel waktu (<code class="px-1 py-0.5 bg-slate-800 text-amber-300 rounded font-mono">approved_at</code>). Setiap tindakan presensi manual web mencatat identitas pelaku secara otomatis.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <strong class="text-blue-400 text-sm flex items-center gap-1.5 font-headline">
                        <span class="material-symbols-outlined text-base">visibility_off</span>
                        <span>3. Isolasi Data Permohonan HR</span>
                    </strong>
                    <p class="text-slate-400 leading-relaxed">
                        Karyawan dan staf HR biasa <strong>hanya dapat melihat data permohonan miliknya sendiri</strong>. Antrean global permohonan seluruh karyawan hanya dapat diakses oleh <strong>Manager HR</strong> dan <strong>Direktur Utama</strong>.
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-900/60 border border-slate-800 space-y-2">
                    <strong class="text-emerald-400 text-sm flex items-center gap-1.5 font-headline">
                        <span class="material-symbols-outlined text-base">vpn_key</span>
                        <span>4. Keamanan Sesi & Ganti Password Mandiri</span>
                    </strong>
                    <p class="text-slate-400 leading-relaxed">
                        Setiap pengguna wajib menjaga keamanan akunnya dan dapat memperbarui password serta memantau perangkat yang sedang login secara mandiri di menu <strong>Pengaturan Profil</strong>.
                    </p>
                </div>

            </div>
        </div>

    </div>

</div>
@endsection
