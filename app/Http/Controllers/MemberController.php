<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            // Hanya akun Resepsionis atau Direktur yang diizinkan mengakses
            if (!$user || (!$user->isResepsionis() && !$user->isDirektur())) {
                abort(403, 'Akses Ditolak: Halaman Data Member ini hanya dapat diakses oleh akun Resepsionis.');
            }
            return $next($request);
        });
    }

    /**
     * Tampilkan data member dengan filter, search, dan pagination
     */
    public function index(Request $request)
    {
        $filters = [
            'age' => $request->input('age'),
            'gender' => $request->input('gender'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'reference' => $request->input('reference'),
            'perusahaan_favorite' => $request->input('perusahaan_favorite'),
        ];

        $query = Member::select([
            'id', 'reference', 'tanggal_daftar', 'name', 'email', 'phone',
            'nik_ktp', 'tempat_lahir', 'tanggal_lahir', 'gender',
            'perusahaan_favorite', 'status', 'google_sheet_row_id', 'created_at'
        ]);

        // Filter: Hanya member 1 tahun terakhir atau yang tgl daftar null
        $oneYearAgo = now()->subYear();
        $query->where(function ($q) use ($oneYearAgo) {
            $q->where('tanggal_daftar', '>=', $oneYearAgo)
              ->orWhereNull('tanggal_daftar');
        });

        // Search text
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhere('nik_ktp', 'LIKE', "%{$search}%");
            });
        }

        // Filter Reference
        if (!empty($filters['reference'])) {
            $references = is_array($filters['reference']) ? $filters['reference'] : [$filters['reference']];
            $query->where(function ($q) use ($references) {
                foreach ($references as $ref) {
                    $q->orWhereRaw('LOWER(reference) LIKE ?', ["%" . strtolower($ref) . "%"]);
                }
            });
        }

        // Filter Gender
        if (!empty($filters['gender'])) {
            if ($filters['gender'] == 'L') {
                $query->where('gender', 'Laki-laki');
            } elseif ($filters['gender'] == 'P') {
                $query->where('gender', 'Perempuan');
            }
        }

        // Filter Status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filter Age
        if (!empty($filters['age'])) {
            $now = Carbon::now();
            if ($filters['age'] == 'under_25') {
                $date25YearsAgo = $now->copy()->subYears(25);
                $query->where('tanggal_lahir', '>', $date25YearsAgo);
            } elseif ($filters['age'] == 'above_25') {
                $date25YearsAgo = $now->copy()->subYears(25);
                $query->where('tanggal_lahir', '<=', $date25YearsAgo);
            }
        }

        // Filter Perusahaan Favorite
        if (!empty($filters['perusahaan_favorite'])) {
            $perusahaans = is_array($filters['perusahaan_favorite']) ? $filters['perusahaan_favorite'] : [$filters['perusahaan_favorite']];
            $query->where(function ($q) use ($perusahaans) {
                foreach ($perusahaans as $p) {
                    $q->orWhereRaw('LOWER(perusahaan_favorite) LIKE ?', ["%" . strtolower($p) . "%"]);
                }
            });
        }

        // Sorting: ID descending (terbaru)
        $members = $query->orderBy('id', 'desc')->paginate(25)->appends(request()->query());

        // Get unique references for dropdown filter
        $references = Cache::remember('member_references_hris', 1800, function () {
            return Member::whereNotNull('reference')->where('reference', '!=', '')
                ->distinct()
                ->pluck('reference')
                ->sort()
                ->values();
        });

        // Get unique Perusahaan Favorite for dropdown filter
        $perusahaanFavorites = Cache::remember('member_perusahaan_favorites_hris', 1800, function () {
            return Member::whereNotNull('perusahaan_favorite')->where('perusahaan_favorite', '!=', '')
                ->distinct()
                ->pluck('perusahaan_favorite')
                ->sort()
                ->values();
        });

        return view('receptionist.members.index', compact('members', 'references', 'perusahaanFavorites'));
    }

    /**
     * Form tambah member baru
     */
    public function create()
    {
        return view('receptionist.members.create');
    }

    /**
     * Simpan data member baru langsung ke database terintegrasi
     */
    public function store(Request $request)
    {
        $request->validate([
            'reference' => 'nullable|string|max:255',
            'tanggal_daftar' => 'required|date',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'nik_ktp' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'perusahaan_favorite' => 'nullable|string|max:255',
            'status' => 'required|in:sudah_kerja,gagal,belum_tes,sudah_training,belum_training',
        ]);

        Member::create([
            'reference' => $request->reference,
            'tanggal_daftar' => $request->tanggal_daftar,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'nik_ktp' => $request->nik_ktp,
            'tempat_lahir' => $request->tempat_lahir,
            'tanggal_lahir' => $request->tanggal_lahir,
            'gender' => $request->gender,
            'perusahaan_favorite' => $request->perusahaan_favorite,
            'status' => $request->status,
            'source' => 'local',
        ]);

        Cache::forget('member_references_hris');
        Cache::forget('member_perusahaan_favorites_hris');

        return redirect()->route('receptionist.members.index')->with('success', 'Data Member baru berhasil ditambahkan!');
    }

    /**
     * Form edit data member
     */
    public function edit($id)
    {
        $member = Member::findOrFail($id);
        return view('receptionist.members.edit', compact('member'));
    }

    /**
     * Update data member
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'reference' => 'nullable|string|max:255',
            'tanggal_daftar' => 'required|date',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'nik_ktp' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:255',
            'tanggal_lahir' => 'nullable|date',
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'perusahaan_favorite' => 'nullable|string|max:255',
            'status' => 'required|in:sudah_kerja,gagal,belum_tes,sudah_training,belum_training',
        ]);

        $member = Member::findOrFail($id);
        $member->update($request->all());

        return redirect()->route('receptionist.members.index')->with('success', 'Data member berhasil diperbarui!');
    }

    /**
     * Update status single inline via AJAX / Form
     */
    public function updateSingleMemberStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:belum_tes,sudah_training,belum_training,sudah_kerja,gagal'
        ]);

        try {
            $member = Member::findOrFail($id);
            $member->update(['status' => $request->status]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }
            return back()->with('success', 'Status member berhasil diperbarui!');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal mengubah status: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk update status member
     */
    public function bulkUpdateMemberStatus(Request $request)
    {
        $ids = $request->input('ids', $request->input('ids[]', []));
        $request->merge(['ids' => $ids]);

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'status' => 'required|in:belum_tes,sudah_training,belum_training,sudah_kerja,gagal'
        ]);

        try {
            Member::whereIn('id', $request->ids)->update(['status' => $request->status]);

            if ($request->wantsJson() || $request->acceptsJson()) {
                return response()->json(['success' => true]);
            }
            return back()->with('success', count($request->ids) . ' status member berhasil diperbarui!');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->acceptsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withErrors(['error' => 'Gagal mengupdate status: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus data member
     */
    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return redirect()->route('receptionist.members.index')->with('success', 'Data member berhasil dihapus!');
    }

    /**
     * Export data member ke file CSV / Excel
     */
    public function export(Request $request)
    {
        $query = Member::select([
            'id', 'reference', 'tanggal_daftar', 'name', 'email', 'phone', 
            'nik_ktp', 'tempat_lahir', 'tanggal_lahir', 'gender', 
            'perusahaan_favorite', 'status', 'created_at'
        ]);

        // Filter apply
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                  ->orWhere('nik_ktp', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('gender')) {
            if ($request->gender == 'L') {
                $query->where('gender', 'Laki-laki');
            } elseif ($request->gender == 'P') {
                $query->where('gender', 'Perempuan');
            }
        }

        if ($request->filled('status')) {
            if (is_array($request->status)) {
                $query->whereIn('status', $request->status);
            } else {
                $query->where('status', $request->status);
            }
        }

        $members = $query->orderBy('id', 'desc')->get();

        $filename = 'Data_Member_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No', 'Referensi', 'Tanggal Daftar', 'Nama Lengkap', 'NIK KTP',
                'Tempat Lahir', 'Tanggal Lahir', 'Umur', 'Jenis Kelamin',
                'Perusahaan Favorite', 'Email Aktif', 'Nomor WA', 'Status'
            ]);

            foreach ($members as $idx => $m) {
                fputcsv($file, [
                    $idx + 1,
                    $m->reference ?? '-',
                    $m->tanggal_daftar ? $m->tanggal_daftar->format('d/m/Y') : '-',
                    $m->name,
                    $m->nik_ktp ? "'" . $m->nik_ktp : '-',
                    $m->tempat_lahir ?? '-',
                    $m->tanggal_lahir ? $m->tanggal_lahir->format('d/m/Y') : '-',
                    $m->umur,
                    $m->gender ?? '-',
                    $m->perusahaan_favorite ?? '-',
                    $m->email ?? '-',
                    $m->phone ? "'" . $m->phone : '-',
                    ucwords(str_replace('_', ' ', $m->status)),
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
