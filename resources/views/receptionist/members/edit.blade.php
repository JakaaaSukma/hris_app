@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between bg-slate-900/60 p-6 rounded-2xl border border-slate-800 shadow-xl">
        <div class="flex items-center gap-4">
            <a href="{{ route('receptionist.members.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition border border-slate-700">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
            </a>
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight">Edit Data Member</h1>
                <p class="text-xs text-slate-400 mt-0.5">ID: <span class="text-rose-400 font-mono">#{{ $member->id }}</span> — {{ $member->name }}</p>
            </div>
        </div>
    </div>

    <!-- Error Alert -->
    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
            <div class="font-semibold text-sm">Gagal Menyimpan Perubahan:</div>
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-slate-900/60 p-6 rounded-2xl border border-slate-800 shadow-xl">
        <form action="{{ route('receptionist.members.update', $member->id) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Tanggal Daftar -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal Daftar <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_daftar" value="{{ old('tanggal_daftar', $member->tanggal_daftar ? $member->tanggal_daftar->format('Y-m-d') : '') }}" required
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Referensi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Referensi <span class="text-slate-500">(Opsional)</span></label>
                    <input type="text" name="reference" value="{{ old('reference', $member->reference) }}" placeholder="Contoh: MS, Gusti, PABRIK"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Nama Lengkap -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $member->name) }}" required
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- NIK KTP -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">NIK KTP</label>
                    <input type="text" name="nik_ktp" value="{{ old('nik_ktp', $member->nik_ktp) }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Jenis Kelamin -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jenis Kelamin</label>
                    <select name="gender" class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="">Pilih Jenis Kelamin</option>
                        <option value="Laki-laki" {{ old('gender', $member->gender) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender', $member->gender) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <!-- Tempat Lahir -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tempat Lahir</label>
                    <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $member->tempat_lahir) }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Tanggal Lahir -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir', $member->tanggal_lahir ? $member->tanggal_lahir->format('Y-m-d') : '') }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Email Aktif</label>
                    <input type="email" name="email" value="{{ old('email', $member->email) }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Nomor WA -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', $member->phone) }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Perusahaan Favorite -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Perusahaan Favorite</label>
                    <input type="text" name="perusahaan_favorite" value="{{ old('perusahaan_favorite', $member->perusahaan_favorite) }}"
                           class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                </div>

                <!-- Status Seleksi -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="belum_tes" {{ old('status', $member->status) == 'belum_tes' ? 'selected' : '' }}>Belum Tes</option>
                        <option value="belum_training" {{ old('status', $member->status) == 'belum_training' ? 'selected' : '' }}>Belum Training</option>
                        <option value="sudah_training" {{ old('status', $member->status) == 'sudah_training' ? 'selected' : '' }}>Sudah Training</option>
                        <option value="sudah_kerja" {{ old('status', $member->status) == 'sudah_kerja' ? 'selected' : '' }}>Sudah Kerja</option>
                        <option value="gagal" {{ old('status', $member->status) == 'gagal' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('receptionist.members.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition border border-slate-700">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white transition shadow-lg shadow-rose-600/30">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
