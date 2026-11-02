@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-900/60 p-6 rounded-2xl border border-slate-800 shadow-xl backdrop-blur-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-lg shadow-rose-500/20 text-white">
                <span class="material-symbols-outlined text-2xl">groups</span>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-bold text-white tracking-tight">Bank Data Member LPK Gyokai</h1>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Live Sync Gyokai02
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Sinkronisasi dua arah real-time dengan Portal Company <span class="text-slate-300 font-mono">lpkgyokai.com</span>. Total Data: <strong class="text-white">{{ number_format($members->total()) }}</strong> kandidat.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('receptionist.members.export', request()->query()) }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-200 hover:text-white hover:bg-slate-700 transition border border-slate-700 shadow-sm">
                <span class="material-symbols-outlined text-[18px]">download</span>
                <span>Export Data</span>
            </a>
            <a href="{{ route('receptionist.members.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-500 hover:to-red-500 text-white transition shadow-lg shadow-rose-600/30">
                <span class="material-symbols-outlined text-[18px]">person_add</span>
                <span>Tambah Member Baru</span>
            </a>
        </div>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="flex items-center gap-3 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="flex items-start gap-3 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-sm">
            <span class="material-symbols-outlined text-lg mt-0.5">error</span>
            <div>
                <div class="font-semibold mb-1">Terjadi Kesalahan:</div>
                <ul class="list-disc list-inside text-xs space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Filter & Search Card (Persis seperti Company Panel Gyokai02) -->
    <div class="bg-slate-900/60 p-5 rounded-2xl border border-slate-800 shadow-lg">
        <form method="GET" action="{{ route('receptionist.members.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                
                <!-- Cari -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Cari</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-[18px]">search</span>
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Nama, Email, NIK..." 
                               class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl pl-9 pr-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition">
                    </div>
                </div>

                <!-- Referensi Dropdown -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Referensi</label>
                    <select name="reference" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="">Semua Referensi</option>
                        @foreach($references as $ref)
                            <option value="{{ $ref }}" {{ request('reference') == $ref ? 'selected' : '' }}>{{ $ref }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jenis Kelamin -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jenis Kelamin</label>
                    <select name="gender" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="">Semua</option>
                        <option value="L" {{ request('gender') == 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ request('gender') == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <!-- Umur -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Umur</label>
                    <select name="age" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="">Semua</option>
                        <option value="under_25" {{ request('age') == 'under_25' ? 'selected' : '' }}>Di bawah 25</option>
                        <option value="above_25" {{ request('age') == 'above_25' ? 'selected' : '' }}>25 Tahun Ke atas</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status</label>
                    <select name="status" class="w-full bg-slate-800/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-rose-500 transition">
                        <option value="">Semua Status</option>
                        <option value="belum_tes" {{ request('status') == 'belum_tes' ? 'selected' : '' }}>Belum Tes</option>
                        <option value="sudah_training" {{ request('status') == 'sudah_training' ? 'selected' : '' }}>Sudah Training</option>
                        <option value="belum_training" {{ request('status') == 'belum_training' ? 'selected' : '' }}>Belum Training</option>
                        <option value="sudah_kerja" {{ request('status') == 'sudah_kerja' ? 'selected' : '' }}>Sudah Kerja</option>
                        <option value="gagal" {{ request('status') == 'gagal' ? 'selected' : '' }}>Gagal</option>
                    </select>
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="md:col-span-1 flex items-center gap-1.5">
                    <button type="submit" class="flex-1 py-2 px-3 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white transition text-center shadow">
                        Filter
                    </button>
                    <a href="{{ route('receptionist.members.index') }}" class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition border border-slate-700" title="Reset Filter">
                        <span class="material-symbols-outlined text-[16px]">restart_alt</span>
                    </a>
                </div>

            </div>
        </form>
    </div>

    <!-- Bulk Action Toolbar -->
    <div id="bulkActionToolbar" class="hidden items-center justify-between p-4 rounded-xl bg-slate-800/90 border border-slate-700 shadow-md">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-rose-400">check_box</span>
            <span class="text-xs text-slate-200"><strong id="selectedCount" class="text-white">0</strong> data member terpilih</span>
        </div>
        <div class="flex items-center gap-2">
            <select id="bulkStatusSelect" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                <option value="belum_tes">Set: Belum Tes</option>
                <option value="sudah_training">Set: Sudah Training</option>
                <option value="belum_training">Set: Belum Training</option>
                <option value="sudah_kerja">Set: Sudah Kerja</option>
                <option value="gagal">Set: Gagal</option>
            </select>
            <button type="button" onclick="executeBulkUpdate()" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white transition">
                Terapkan Masal
            </button>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-slate-900/60 rounded-2xl border border-slate-800 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/80 uppercase font-mono text-[10px] text-slate-400 border-b border-slate-800">
                    <tr>
                        <th class="p-3.5 text-center w-10">
                            <input type="checkbox" id="selectAllCheckbox" class="rounded border-slate-700 bg-slate-800 text-rose-600 focus:ring-rose-500">
                        </th>
                        <th class="py-3.5 px-3 text-center w-12">No</th>
                        <th class="py-3.5 px-3 w-24">Referensi</th>
                        <th class="py-3.5 px-3 text-center w-28">Tanggal Daftar</th>
                        <th class="py-3.5 px-3 min-w-[160px]">Nama Lengkap</th>
                        <th class="py-3.5 px-3 w-36">NIK KTP</th>
                        <th class="py-3.5 px-3 w-28">Tempat Lahir</th>
                        <th class="py-3.5 px-3 text-center w-28">Tanggal Lahir</th>
                        <th class="py-3.5 px-3 text-center w-20">Jenis Kelamin</th>
                        <th class="py-3.5 px-3 min-w-[160px]">Perusahaan Favorite</th>
                        <th class="py-3.5 px-3 min-w-[180px]">Email Aktif</th>
                        <th class="py-3.5 px-3 w-32">Nomor WA</th>
                        <th class="py-3.5 px-3 w-36">Status</th>
                        <th class="py-3.5 px-3 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($members as $index => $member)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-3 text-center">
                                <input type="checkbox" class="member-item-checkbox rounded border-slate-700 bg-slate-800 text-rose-600 focus:ring-rose-500" value="{{ $member->id }}">
                            </td>
                            <td class="py-3 px-3 text-center font-mono text-slate-500">
                                {{ $members->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-3">
                                @if($member->reference)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-cyan-500/15 text-cyan-400 border border-cyan-500/30 uppercase tracking-wider">
                                        {{ $member->reference }}
                                    </span>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="font-medium text-slate-200">
                                    {{ $member->tanggal_daftar ? $member->tanggal_daftar->format('d/m/Y') : ($member->created_at ? $member->created_at->format('d/m/Y') : '-') }}
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    {{ $member->waktu_terdaftar }}
                                </div>
                            </td>
                            <td class="py-3 px-3 font-semibold text-white">
                                {{ $member->name }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-400">
                                {{ $member->nik_ktp ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                {{ $member->tempat_lahir ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="font-medium text-slate-200">
                                    {{ $member->tanggal_lahir ? $member->tanggal_lahir->format('d/m/Y') : '-' }}
                                </div>
                                <div class="text-[10px] text-slate-400 font-mono">
                                    {{ $member->umur }}
                                </div>
                            </td>
                            <td class="py-3 px-3 text-center text-slate-300">
                                {{ $member->gender ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-slate-300">
                                {{ $member->perusahaan_favorite ?? '-' }}
                            </td>
                            <td class="py-3 px-3 text-slate-400">
                                {{ $member->email ?? '-' }}
                            </td>
                            <td class="py-3 px-3 font-mono">
                                @if($member->phone)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $member->phone) }}" target="_blank" class="text-rose-400 hover:text-rose-300 font-bold transition flex items-center gap-1">
                                        <span>{{ $member->phone }}</span>
                                        <span class="material-symbols-outlined text-[13px]">open_in_new</span>
                                    </a>
                                @else
                                    <span class="text-slate-600">-</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                @php
                                    $statusClass = match($member->status) {
                                        'sudah_kerja' => 'bg-emerald-950/70 text-emerald-300 border-emerald-500/40',
                                        'sudah_training' => 'bg-blue-950/70 text-blue-300 border-blue-500/40',
                                        'belum_training' => 'bg-amber-950/70 text-amber-300 border-amber-500/40',
                                        'gagal' => 'bg-rose-950/70 text-rose-300 border-rose-500/40',
                                        default => 'bg-slate-800 text-slate-300 border-slate-700',
                                    };
                                @endphp
                                <select onchange="updateSingleStatus({{ $member->id }}, this.value, this)" 
                                        class="w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer {{ $statusClass }}">
                                    <option value="belum_tes" {{ $member->status == 'belum_tes' ? 'selected' : '' }}>Belum Tes</option>
                                    <option value="sudah_training" {{ $member->status == 'sudah_training' ? 'selected' : '' }}>Sudah Training</option>
                                    <option value="belum_training" {{ $member->status == 'belum_training' ? 'selected' : '' }}>Belum Training</option>
                                    <option value="sudah_kerja" {{ $member->status == 'sudah_kerja' ? 'selected' : '' }}>Sudah Kerja</option>
                                    <option value="gagal" {{ $member->status == 'gagal' ? 'selected' : '' }}>Gagal</option>
                                </select>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('receptionist.members.edit', $member->id) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition" title="Edit">
                                        <span class="material-symbols-outlined text-[16px]">edit</span>
                                    </a>
                                    <form action="{{ route('receptionist.members.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus member {{ $member->name }}? Data di portal Company Gyokai02 juga akan ikut terhapus.')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition" title="Hapus">
                                            <span class="material-symbols-outlined text-[16px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="p-8 text-center text-slate-500">
                                <span class="material-symbols-outlined text-4xl mb-2 text-slate-600 block">inbox</span>
                                Tidak ada data member yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs text-slate-400">
            <div>
                Menampilkan <strong class="text-white">{{ $members->firstItem() ?? 0 }}</strong> sampai <strong class="text-white">{{ $members->lastItem() ?? 0 }}</strong> dari <strong class="text-white">{{ number_format($members->total()) }}</strong> data member
            </div>
            <div>
                {{ $members->links() }}
            </div>
        </div>
    </div>

</div>

<!-- Inline JavaScript for Real-time Update -->
<script>
    const csrfToken = '{{ csrf_token() }}';

    // Bulk Select Logic
    const selectAll = document.getElementById('selectAllCheckbox');
    const itemCheckboxes = document.querySelectorAll('.member-item-checkbox');
    const bulkToolbar = document.getElementById('bulkActionToolbar');
    const selectedCount = document.getElementById('selectedCount');

    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.member-item-checkbox:checked');
        if (checked.length > 0) {
            bulkToolbar.classList.remove('hidden');
            bulkToolbar.classList.add('flex');
            selectedCount.textContent = checked.length;
        } else {
            bulkToolbar.classList.add('hidden');
            bulkToolbar.classList.remove('flex');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            itemCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkToolbar();
        });
    }

    itemCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkToolbar);
    });

    // Realtime Single Status Update via AJAX
    async function updateSingleStatus(memberId, newStatus, selectElement) {
        selectElement.disabled = true;

        const statusClasses = {
            'sudah_kerja': 'w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer bg-emerald-950/70 text-emerald-300 border-emerald-500/40',
            'sudah_training': 'w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer bg-blue-950/70 text-blue-300 border-blue-500/40',
            'belum_training': 'w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer bg-amber-950/70 text-amber-300 border-amber-500/40',
            'gagal': 'w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer bg-rose-950/70 text-rose-300 border-rose-500/40',
            'belum_tes': 'w-full text-xs font-semibold py-1 px-2 rounded-lg border transition cursor-pointer bg-slate-800 text-slate-300 border-slate-700'
        };

        try {
            const res = await fetch(`{{ url('/receptionist/members') }}/${memberId}/update-status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ status: newStatus })
            });

            if (res.ok) {
                // Update styling instantly
                if (statusClasses[newStatus]) {
                    selectElement.className = statusClasses[newStatus];
                }
                selectElement.classList.add('ring-2', 'ring-emerald-500');
                setTimeout(() => {
                    selectElement.classList.remove('ring-2', 'ring-emerald-500');
                }, 1000);
            } else {
                alert('Gagal mengupdate status member. Silakan coba lagi.');
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi kesalahan jaringan.');
        } finally {
            selectElement.disabled = false;
        }
    }

    // Realtime Bulk Status Update via AJAX
    async function executeBulkUpdate() {
        const checkedBoxes = Array.from(document.querySelectorAll('.member-item-checkbox:checked'));
        const ids = checkedBoxes.map(cb => cb.value);
        const status = document.getElementById('bulkStatusSelect').value;

        if (ids.length === 0) return;

        if (!confirm(`Ubah status ${ids.length} member terpilih menjadi "${status.replace('_', ' ')}"? Perubahan akan langsung tersinkron ke portal Company Gyokai02.`)) {
            return;
        }

        try {
            const res = await fetch(`{{ route('receptionist.members.bulkUpdateStatus') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ ids: ids, status: status })
            });

            if (res.ok) {
                window.location.reload();
            } else {
                alert('Gagal memperbarui status masal.');
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi kendala koneksi.');
        }
    }
</script>
@endsection
