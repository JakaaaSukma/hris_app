<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Models\RoleChangeRequest;
use App\Notifications\GeneralNotification;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $department = $request->query('department');
        $status = $request->query('status');
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        $query = Employee::with(['todayAttendance', 'user'])->orderBy('name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role_title', 'like', "%{$search}%");
            });
        }

        if ($department) {
            $query->where('department', $department);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $employees = $query->paginate(12);
        $departments = Employee::select('department')->distinct()->pluck('department');

        // Mengambil daftar pengajuan perubahan role yang pending atau riwayat terkini
        $pendingRoleRequests = RoleChangeRequest::with(['user.employee', 'requester', 'approver'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentRoleRequests = RoleChangeRequest::with(['user.employee', 'requester', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return view('employees.index', compact(
            'employees',
            'departments',
            'currentUser',
            'authUser',
            'search',
            'department',
            'status',
            'pendingRoleRequests',
            'recentRoleRequests'
        ));
    }

    public function show($id)
    {
        $employee = Employee::with(['user', 'attendances' => function ($q) {
            $q->orderBy('date', 'desc')->limit(30);
        }, 'leaveRequests' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(15);
        }])->findOrFail($id);

        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        return view('employees.show', compact('employee', 'currentUser', 'authUser'));
    }

    // Kelola / Update Akun Login Karyawan (Role, Departemen, Reset Password, Alur Approval)
    public function updateAccount(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $authUser = Auth::user();
        $user = $employee->user;

        $request->validate([
            'email' => 'required|email|max:255|unique:users,email,' . ($user?->id ?? 'NULL'),
            'role' => 'required|in:karyawan,asisten_manager,manager,direktur',
            'department' => 'required|string',
            'sub_department' => 'nullable|string',
            'password' => 'nullable|string|min:6',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Anti self-demotion / anti self-role tampering
        if ($user && $user->id === $authUser->id && $request->role !== $user->role) {
            return back()->with('error', 'Akses Ditolak: Anda tidak dapat mengubah tingkat role akun Anda sendiri.');
        }

        // KASUS 1: Akun belum ada -> Dibuat baru
        if (!$user) {
            $targetRole = $request->role;
            $subDept = $request->input('sub_department', $employee->sub_department);
            if ($request->department === 'HR' && !$subDept) {
                $subDept = ($targetRole === 'asisten_manager' || str_contains(strtolower($employee->role_title), 'ga')) ? 'GA' : 'HR';
            }
            $user = User::create([
                'name' => $employee->name,
                'email' => $request->email,
                'password' => Hash::make($request->password ?: 'password123'),
                'role' => $targetRole,
                'department' => $request->department,
                'sub_department' => $subDept,
                'employee_id' => $employee->id,
            ]);

            $employee->update([
                'email' => $request->email,
                'department' => $request->department,
                'sub_department' => $subDept,
            ]);

            return back()->with('success', "Akun login baru untuk {$employee->name} berhasil dibuat dengan role " . strtoupper($targetRole) . ".");
        }

        // KASUS 2: Akun sudah ada, cek perubahan role
        $currentRole = $user->role;
        $targetRole = $request->role;
        $roleChanged = ($currentRole !== $targetRole);

        // Update atribut dasar (email, departemen, password jika ada)
        $subDept = $request->input('sub_department', $user->sub_department);
        if ($request->department === 'HR' && !$subDept) {
            $subDept = ($targetRole === 'asisten_manager' || str_contains(strtolower($employee->role_title), 'ga')) ? 'GA' : 'HR';
        }

        $updateData = [
            'email' => $request->email,
            'department' => $request->department,
            'sub_department' => $subDept,
        ];
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Cek siapa yang melakukan aksi
        if ($roleChanged) {
            if ($authUser->isDirektur()) {
                // Direktur dapat langsung mengubah status akun secara instan
                $updateData['role'] = $targetRole;
                $user->update($updateData);

                $employee->update([
                    'email' => $request->email,
                    'department' => $request->department,
                    'sub_department' => $subDept,
                ]);

                // Beri notifikasi ke seluruh Manager HR terkait perubahan status akun ini
                $hrManagers = User::where('department', 'HR')->where('role', 'manager')->get();
                $directionText = ($targetRole === 'manager') ? 'promosi menjadi MANAGER' : 'penyesuaian status menjadi KARYAWAN';
                foreach ($hrManagers as $hrManager) {
                    $hrManager->notify(new GeneralNotification(
                        'Pembaruan Status Akun oleh Direktur',
                        "Direktur ({$authUser->name}) telah mengubah status akun {$employee->name} dari " . strtoupper($currentRole) . " ke " . strtoupper($targetRole) . ".",
                        route('employees.index'),
                        'manage_accounts',
                        'amber'
                    ));
                }

                // Beri notifikasi ke user yang bersangkutan
                $user->notify(new GeneralNotification(
                    'Perubahan Status Akun Anda',
                    "Status role akun Anda telah diperbarui menjadi " . strtoupper($targetRole) . " oleh Direktur Utama.",
                    route('profile.edit'),
                    'verified_user',
                    'emerald'
                ));

                return back()->with('success', "Status akun {$employee->name} berhasil diubah langsung menjadi " . strtoupper($targetRole) . ". Notifikasi otomatis telah dikirimkan ke Manager HRD.");
            } else {
                // Pengguna adalah HRD / Manager HR:
                // Sesuai aturan: Manager HRD dapat meningkatkan status akun dari karyawan ke manager begitupun sebaliknya DENGAN PERSETUJUAN DIREKTUR.
                
                // Pastikan tidak ada pengajuan pending yang sama untuk user ini
                $existingPending = RoleChangeRequest::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->first();

                if ($existingPending) {
                    return back()->with('error', "Sudah ada pengajuan perubahan status role yang masih pending persetujuan Direktur untuk {$employee->name}.");
                }

                $reason = $request->input('reason');
                if (empty($reason)) {
                    return back()->with('error', 'Wajib menyertakan alasan perubahan status akun untuk diteruskan ke Direktur.');
                }

                // Update data lain terlebih dahulu jika ada (seperti email/password) tanpa mengubah role
                $user->update($updateData);
                $employee->update([
                    'email' => $request->email,
                    'department' => $request->department,
                    'sub_department' => $subDept,
                ]);

                // Buat data pengajuan persetujuan ke Direktur
                $roleReq = RoleChangeRequest::create([
                    'user_id' => $user->id,
                    'requested_by' => $authUser->id,
                    'current_role' => $currentRole,
                    'target_role' => $targetRole,
                    'reason' => $reason,
                    'status' => 'pending',
                ]);

                // Kirim notifikasi ke Direktur Utama
                $directors = User::where('role', 'direktur')->get();
                $actionLabel = ($targetRole === 'manager') ? 'Promosi ke Manager' : 'Demosi ke Karyawan';
                foreach ($directors as $dir) {
                    $dir->notify(new GeneralNotification(
                        'Persetujuan Perubahan Status Akun',
                        "Manager HR ({$authUser->name}) mengajukan {$actionLabel} untuk {$employee->name} (dari " . strtoupper($currentRole) . " ke " . strtoupper($targetRole) . "). Alasan: {$reason}",
                        route('employees.index'),
                        'assignment_ind',
                        'rose'
                    ));
                }

                return back()->with('success', "Pengajuan perubahan status akun {$employee->name} menjadi " . strtoupper($targetRole) . " telah dikirimkan ke Direktur Utama untuk persetujuan.");
            }
        }

        // Jika tidak ada perubahan role, cukup update informasi akun biasa
        $user->update($updateData);
        $employee->update([
            'email' => $request->email,
            'department' => $request->department,
        ]);

        return back()->with('success', "Data akun login untuk {$employee->name} berhasil diperbarui.");
    }

    /**
     * Direktur menyetujui pengajuan perubahan role
     */
    public function approveRoleRequest(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->isDirektur()) {
            abort(403, 'Akses Ditolak: Hanya Direktur Utama yang berhak menyetujui perubahan status role akun.');
        }

        $roleReq = RoleChangeRequest::with(['user.employee', 'requester'])->findOrFail($id);

        if ($roleReq->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah pernah diproses sebelumnya.');
        }

        // Anti self-approval check
        if ($roleReq->requested_by === $authUser->id) {
            return back()->with('error', 'Akses Ditolak: Anti self-approval.');
        }

        $user = $roleReq->user;
        if (!$user) {
            return back()->with('error', 'Data pengguna terkait pengajuan ini tidak ditemukan.');
        }

        $oldRole = $user->role;
        $newRole = $roleReq->target_role;

        // Terapkan perubahan role ke akun user
        $user->update([
            'role' => $newRole,
        ]);

        // Catat approval
        $roleReq->update([
            'status' => 'approved',
            'approved_by' => $authUser->id,
            'action_at' => now(),
        ]);

        $employeeName = $user->employee ? $user->employee->name : $user->name;

        // Kirim notifikasi ke Manager HR yang mengajukan
        if ($roleReq->requester) {
            $roleReq->requester->notify(new GeneralNotification(
                'Pengajuan Perubahan Status Disetujui',
                "Direktur Utama telah MENYETUJUI pengajuan perubahan status akun {$employeeName} menjadi " . strtoupper($newRole) . ".",
                route('employees.index'),
                'check_circle',
                'emerald'
            ));
        }

        // Kirim notifikasi ke pengguna bersangkutan
        $user->notify(new GeneralNotification(
            'Selamat! Status Akun Anda Diperbarui',
            "Pengajuan peningkatan/perubahan status akun Anda menjadi " . strtoupper($newRole) . " telah disetujui resmi oleh Direktur Utama.",
            route('profile.edit'),
            'verified_user',
            'emerald'
        ));

        return back()->with('success', "Pengajuan perubahan status role {$employeeName} telah disetujui. Akun kini aktif sebagai " . strtoupper($newRole) . ".");
    }

    /**
     * Direktur menolak pengajuan perubahan role
     */
    public function rejectRoleRequest(Request $request, $id)
    {
        $authUser = Auth::user();
        if (!$authUser->isDirektur()) {
            abort(403, 'Akses Ditolak: Hanya Direktur Utama yang berhak menolak perubahan status role akun.');
        }

        $roleReq = RoleChangeRequest::with(['user.employee', 'requester'])->findOrFail($id);

        if ($roleReq->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah pernah diproses sebelumnya.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $rejectionReason = $request->input('rejection_reason');

        $roleReq->update([
            'status' => 'rejected',
            'approved_by' => $authUser->id,
            'rejection_reason' => $rejectionReason,
            'action_at' => now(),
        ]);

        $employeeName = $user = $roleReq->user ? ($roleReq->user->employee ? $roleReq->user->employee->name : $roleReq->user->name) : 'Karyawan';

        // Beri notifikasi penolakan ke Manager HR
        if ($roleReq->requester) {
            $roleReq->requester->notify(new GeneralNotification(
                'Pengajuan Perubahan Status Ditolak',
                "Direktur menolak pengajuan perubahan status akun {$employeeName} ke " . strtoupper($roleReq->target_role) . ". Alasan: {$rejectionReason}",
                route('employees.index'),
                'cancel',
                'rose'
            ));
        }

        return back()->with('success', "Pengajuan perubahan role {$employeeName} telah ditolak dengan alasan tercatat.");
    }

    // Update Data Pokok Karyawan/Manager (NIP, Tanggal Bergabung, Nama, Jabatan, Status, dll)
    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'nip' => 'required|string|max:30|unique:employees,nip,' . $employee->id,
            'name' => 'required|string|max:255',
            'role_title' => 'required|string|max:255',
            'department' => 'required|string',
            'sub_department' => 'nullable|string',
            'join_date' => 'required|date',
            'employment_type' => 'required|string',
            'status' => 'required|in:active,on_leave,probation,inactive',
            'phone' => 'nullable|string|max:30',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validated['department'] === 'HR' && empty($validated['sub_department'])) {
            $validated['sub_department'] = str_contains(strtolower($validated['role_title']), 'ga') ? 'GA' : 'HR';
        }

        $employee->update($validated);

        // Jika nama atau departemen diubah, sinkronkan juga ke akun user login-nya
        if ($employee->user) {
            $userSync = [
                'name' => $validated['name'],
                'department' => $validated['department'],
            ];
            if (array_key_exists('sub_department', $validated)) {
                $userSync['sub_department'] = $validated['sub_department'];
            }
            $employee->user->update($userSync);
        }

        return back()->with('success', "Data karyawan/manager {$employee->name} berhasil diperbarui (NIP: {$employee->nip}, Bergabung: " . \Carbon\Carbon::parse($employee->join_date)->format('d M Y') . ").");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nip' => 'required|unique:employees,nip|max:30',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:employees,email',
            'phone' => 'nullable|string',
            'role_title' => 'required|string',
            'department' => 'required|string',
            'sub_department' => 'nullable|string',
            'employment_type' => 'required|string',
            'join_date' => 'required|date',
            'location' => 'nullable|string',
            'account_role' => 'nullable|in:karyawan,asisten_manager,manager,direktur',
            'status' => 'nullable|in:active,on_leave,probation,inactive',
        ]);

        // Jika email kosong, buat otomatis (nama)@gyokai.com
        if (empty($validated['email'])) {
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validated['name']));
            $emailCandidate = $cleanName . '@gyokai.com';
            $counter = 1;
            while (Employee::where('email', $emailCandidate)->exists() || User::where('email', $emailCandidate)->exists()) {
                $emailCandidate = $cleanName . $counter . '@gyokai.com';
                $counter++;
            }
            $validated['email'] = $emailCandidate;
        }

        $role = $validated['account_role'] ?? 'karyawan';
        unset($validated['account_role']);

        if ($validated['department'] === 'HR' && empty($validated['sub_department'])) {
            $validated['sub_department'] = ($role === 'asisten_manager' || str_contains(strtolower($validated['role_title']), 'ga')) ? 'GA' : 'HR';
        }

        $validated['avatar_url'] = 'https://ui-avatars.com/api/?name=' . urlencode($validated['name']) . '&background=random&color=fff&size=150';
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['location'] = $validated['location'] ?? 'Jakarta';
        $validated['annual_leave_quota'] = 12;
        $validated['annual_leave_used'] = 0;

        $employee = Employee::create($validated);

        // Password default diambil dari .env (atau fallback aman)
        $defaultPassword = env('DEFAULT_USER_PASSWORD', 'password123');
        $password = $request->filled('password') ? $request->password : $defaultPassword;

        // Otomatis buat akun User Login
        User::create([
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => Hash::make($password),
            'role' => $role,
            'department' => $employee->department,
            'sub_department' => $employee->sub_department,
            'employee_id' => $employee->id,
        ]);

        return back()->with('success', "Data pegawai {$employee->name} & akun login berhasil didaftarkan (NIP: {$employee->nip}, Role: " . strtoupper($role) . ", Email: {$employee->email}, Password: {$password}).");
    }

    /**
     * Mendaftarkan banyak akun pegawai sekaligus (Batch / Mass Registration)
     * Format sesuai template tabel: NO ID, NAMA, DEPARTEMEN, JABATAN, TIPE IKATAN KERJA, STATUS, LOKASI, NO TELEPON
     */
    public function batchStore(Request $request)
    {
        // Hindari timeout PHP pada shared hosting
        @set_time_limit(180);
        @ini_set('max_execution_time', '180');

        $authUser = Auth::user();
        if (!$authUser->canManageEmployees()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk mendaftarkan akun karyawan.');
        }

        $request->validate([
            'rows' => 'required|array|min:1',
            'rows.*.no_id' => 'required|numeric',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.department' => 'required|string',
            'rows.*.role' => 'required|in:karyawan,manager',
            'rows.*.employment_type' => 'nullable|string',
            'rows.*.status' => 'nullable|string',
            'rows.*.location' => 'nullable|string',
            'rows.*.phone' => 'nullable|string',
        ]);

        $rows = $request->input('rows');
        $createdCount = 0;
        $updatedCount = 0;

        // Hash default password 1 KALI saja di luar loop untuk menghemat ribuan cycle CPU (menghindari timeout)
        $defaultPassword = env('DEFAULT_USER_PASSWORD', 'password123');
        $defaultPasswordHash = Hash::make($defaultPassword);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            foreach ($rows as $item) {
                if (empty($item['name'])) continue;

                $noId = (int)$item['no_id'];
                $nip = 'NEX-' . str_pad($noId, 5, '0', STR_PAD_LEFT);
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $item['name']));
                $email = $cleanName . '@gyokai.com';
                $role = strtolower(trim($item['role']));
                $dept = trim($item['department']);
                $phone = !empty($item['phone']) ? $item['phone'] : '81234567890';
                $location = !empty($item['location']) ? ucfirst(strtolower($item['location'])) : 'Jakarta';
                $empType = !empty($item['employment_type']) ? (strtoupper($item['employment_type']) === 'TETAP' ? 'Tetap (FTE)' : $item['employment_type']) : 'Tetap (FTE)';
                $status = !empty($item['status']) ? strtolower($item['status']) : 'active';
                $roleTitle = ($role === 'manager') ? "{$dept} Manager" : "{$dept} Staff";

                $isNew = !Employee::where('email', $email)->exists() && !Employee::where('nip', $nip)->exists();

                $employee = Employee::updateOrCreate(
                    ['nip' => $nip],
                    [
                        'name' => $item['name'],
                        'email' => $email,
                        'phone' => $phone,
                        'role_title' => $roleTitle,
                        'department' => $dept,
                        'employment_type' => $empType,
                        'status' => $status,
                        'location' => $location,
                        'join_date' => now()->toDateString(),
                        'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode($item['name']) . '&background=random&color=fff&size=150',
                        'annual_leave_quota' => 12,
                        'annual_leave_used' => 0,
                    ]
                );

                User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $item['name'],
                        'password' => $defaultPasswordHash,
                        'role' => $role,
                        'department' => $dept,
                        'employee_id' => $employee->id,
                    ]
                );

                if ($isNew) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return back()->with('success', "Pendaftaran massal berhasil diproses! Sebanyak {$createdCount} akun baru dibuat dan {$updatedCount} akun disinkronkan.");
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat pendaftaran akun: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Data Karyawan beserta Akun Penggunanya
     */
    public function destroy($id)
    {
        $authUser = Auth::user();

        // Hanya Direktur atau HR yang berhak menghapus data karyawan
        if (!$authUser->canManageEmployees()) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menghapus data karyawan.');
        }

        $employee = Employee::with('user')->findOrFail($id);

        // Keamanan: Pengguna dilarang menghapus akun atau profil dirinya sendiri
        if ($authUser->employee_id && (int) $authUser->employee_id === (int) $employee->id) {
            return back()->with('error', 'Akses Ditolak: Anda tidak dapat menghapus profil atau akun diri Anda sendiri.');
        }

        if ($employee->user && (int) $employee->user->id === (int) $authUser->id) {
            return back()->with('error', 'Akses Ditolak: Anda tidak dapat menghapus akun login yang sedang aktif digunakan.');
        }

        $empName = $employee->name;
        $empNip = $employee->nip;

        // Hapus akun login terlebih dahulu jika ada
        if ($employee->user) {
            $employee->user->delete();
        }

        // Hapus data karyawan
        $employee->delete();

        return redirect()->route('employees.index')->with('success', "Data karyawan {$empName} ({$empNip}) beserta akun login sistemnya berhasil dihapus.");
    }

    /**
     * Menarik / Ekspor Data Karyawan ke Format Native Microsoft Excel (.xlsx)
     * Desain Executive, Rapi, Lengkap dengan Kop Resmi, Info Metadata, Zebra Striping, & Auto-fit Widths
     * Khusus Direktur Utama dan Manager HR
     */
    public function exportExcel(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canExportEmployees()) {
            abort(403, 'Akses Ditolak: Hanya Direktur Utama dan Manager HR yang memiliki wewenang untuk menarik data karyawan.');
        }

        $query = Employee::with('user')->orderBy('department')->orderBy('name');

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role_title', 'like', "%{$search}%");
            });
        }

        $employees = $query->get();

        $deptLabel = $request->filled('department') ? $request->department : 'Semua Departemen';
        $statusLabel = $request->filled('status') ? $request->status : 'Semua Status';
        $typeLabel = $request->filled('employment_type') ? $request->employment_type : 'Semua Tipe';

        $cleanDeptFilename = $request->filled('department') ? str_replace(' ', '_', $request->department) : 'Semua_Divisi';
        $cleanStatusFilename = $request->filled('status') ? str_replace(' ', '_', $request->status) : 'Semua_Status';
        $filename = "Data_Karyawan_GYOKAI_{$cleanDeptFilename}_{$cleanStatusFilename}_" . date('Ymd_His') . ".xlsx";

        // Inisialisasi PhpSpreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Direktori Karyawan');
        $sheet->setShowGridLines(true);

        // 1. Header / Kop Surat Resmi Perusahaan
        $sheet->mergeCells('A2:O2');
        $sheet->setCellValue('A2', 'PT GYOKAI GLOBAL INDONESIA');
        $sheet->getStyle('A2')->getFont()->setName('Calibri')->setSize(16)->setBold(true)->getColor()->setRGB('881337');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->mergeCells('A3:O3');
        $sheet->setCellValue('A3', 'MASTER DATA & DIREKTORI KARYAWAN PERUSAHAAN');
        $sheet->getStyle('A3')->getFont()->setName('Calibri')->setSize(12)->setBold(true)->getColor()->setRGB('1E293B');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->mergeCells('A4:O4');
        $sheet->setCellValue('A4', 'Divisi Human Resources (HR) & People Operations • Dokumen Resmi Internal Perusahaan');
        $sheet->getStyle('A4')->getFont()->setName('Calibri')->setSize(9)->setItalic(true)->getColor()->setRGB('64748B');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // 2. Info / Metadata Strip (Baris 6)
        $sheet->setCellValue('A6', 'Tanggal Cetak:');
        $sheet->setCellValue('B6', \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB');
        $sheet->setCellValue('D6', 'Diekspor Oleh:');
        $sheet->setCellValue('E6', $authUser->name . ' (' . strtoupper($authUser->role) . ')');
        $sheet->setCellValue('G6', 'Parameter Filter:');
        $sheet->setCellValue('H6', "Divisi: {$deptLabel} | Status: {$statusLabel} | Ikatan: {$typeLabel}");
        $sheet->setCellValue('L6', 'Total Karyawan:');
        $sheet->setCellValue('M6', $employees->count() . ' Orang');

        $infoLabels = ['A6', 'D6', 'G6', 'L6'];
        foreach ($infoLabels as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('475569');
        }
        $infoValues = ['B6', 'E6', 'H6'];
        foreach ($infoValues as $cell) {
            $sheet->getStyle($cell)->getFont()->setBold(false)->setSize(9)->getColor()->setRGB('0F172A');
        }
        $sheet->getStyle('M6')->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('047857');

        // Style Background Box Metadata
        $sheet->getStyle('A6:O6')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle('A6:O6')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_HAIR)->getColor()->setRGB('CBD5E1');
        $sheet->getRowDimension(6)->setRowHeight(22);

        // 3. Table Headers (Baris 8)
        $headers = [
            'A8' => 'No',
            'B8' => 'NIP / ID Mesin',
            'C8' => 'Nama Lengkap Karyawan',
            'D8' => 'Email Perusahaan',
            'E8' => 'Nomor Telepon / WA',
            'F8' => 'Departemen',
            'G8' => 'Jabatan',
            'H8' => 'Role Akun',
            'I8' => 'Ikatan Kerja',
            'J8' => 'Status Akun',
            'K8' => 'Tgl Masuk',
            'L8' => 'Lokasi Penugasan',
            'M8' => 'Kuota Cuti',
            'N8' => 'Cuti Terpakai',
            'O8' => 'Sisa Cuti',
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => [
                'name' => 'Calibri',
                'size' => 10,
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'], // Dark Navy Slate
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ];

        $sheet->getStyle('A8:O8')->applyFromArray($headerStyle);
        $sheet->getRowDimension(8)->setRowHeight(28);

        // 4. Data Rows (Mulai Baris 9)
        $row = 9;
        $no = 1;

        foreach ($employees as $emp) {
            $remaining = max(0, ($emp->annual_leave_quota ?? 12) - ($emp->annual_leave_used ?? 0));
            $userRole = $emp->user ? strtoupper($emp->user->role) : 'NON-AKUN';
            $isActive = strtolower($emp->status ?? '') === 'active';
            $formattedPhone = $emp->phone ? (string)$emp->phone : '-';
            $formattedJoin = $emp->join_date ? \Carbon\Carbon::parse($emp->join_date)->format('d/m/Y') : '-';

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValueExplicit('B' . $row, $emp->nip ?? '-', DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $emp->name ?? '-');
            $sheet->setCellValue('D' . $row, $emp->email ?? '-');
            $sheet->setCellValueExplicit('E' . $row, $formattedPhone, DataType::TYPE_STRING);
            $sheet->setCellValue('F' . $row, $emp->department ?? '-');
            $sheet->setCellValue('G' . $row, $emp->role_title ?? '-');
            $sheet->setCellValue('H' . $row, $userRole);
            $sheet->setCellValue('I' . $row, $emp->employment_type ?? 'Tetap');
            $sheet->setCellValue('J' . $row, $emp->status ?? 'Active');
            $sheet->setCellValue('K' . $row, $formattedJoin);
            $sheet->setCellValue('L' . $row, $emp->location ?? 'Kantor Pusat');
            $sheet->setCellValue('M' . $row, $emp->annual_leave_quota ?? 12);
            $sheet->setCellValue('N' . $row, $emp->annual_leave_used ?? 0);
            $sheet->setCellValue('O' . $row, $remaining);

            // Row Styling & Zebra Striping
            $rowBg = ($row % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$row}:O{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rowBg);
            $sheet->getStyle("A{$row}:O{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $sheet->getStyle("A{$row}:O{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension($row)->setRowHeight(22);

            // Alignment spesifik per kolom
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true)->getColor()->setRGB('334155');

            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("C{$row}")->getFont()->setBold(true)->getColor()->setRGB('0F172A');

            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('2563EB');

            $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('0284C7');

            $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Status Styling
            $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if ($isActive) {
                $sheet->getStyle("J{$row}")->getFont()->setBold(true)->getColor()->setRGB('059669');
            } else {
                $sheet->getStyle("J{$row}")->getFont()->setBold(true)->getColor()->setRGB('DC2626');
            }

            $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Quota
            $sheet->getStyle("M{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$row}")->getFont()->setBold(true)->getColor()->setRGB($remaining > 3 ? '059669' : 'DC2626');

            $row++;
        }

        // 5. Total Row di akhir tabel
        $lastDataRow = $row - 1;
        $sheet->mergeCells("A{$row}:L{$row}");
        $sheet->setCellValue("A{$row}", "TOTAL DATA KARYAWAN TERDAFTAR (" . ($no - 1) . " ORANG)");
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('1E293B');

        $sheet->setCellValue("M{$row}", "=SUM(M9:M{$lastDataRow})");
        $sheet->setCellValue("N{$row}", "=SUM(N9:N{$lastDataRow})");
        $sheet->setCellValue("O{$row}", "=SUM(O9:O{$lastDataRow})");

        $sheet->getStyle("M{$row}:O{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("M{$row}:O{$row}")->getFont()->setBold(true)->setSize(10);
        $sheet->getStyle("O{$row}")->getFont()->getColor()->setRGB('047857');

        $sheet->getStyle("A{$row}:O{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E2E8F0');
        $sheet->getStyle("A{$row}:O{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('94A3B8');
        $sheet->getStyle("A{$row}:O{$row}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('0F172A');
        $sheet->getRowDimension($row)->setRowHeight(24);

        // 6. Auto-fit Column Widths dengan padding
        $columns = range('A', 'O');
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 7. Freeze Panes di bawah Header (A9)
        $sheet->freezePane('A9');

        // 8. Simpan ke temporary file dan unduh sebagai binary attachment
        // Membersihkan buffer output terlebih dahulu untuk mencegah byte sampah / korupsi file ZIP OpenXML
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'hris_emp_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, must-revalidate',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Fallback Ekspor CSV
     */
    public function exportCsv(Request $request)
    {
        // Alihkan ke exportExcel untuk hasil rapi dan native
        return $this->exportExcel($request);
    }

    /**
     * Menarik / Cetak Dokumen PDF Master Data Karyawan
     * Khusus Direktur Utama dan Manager HR
     */
    public function exportPdf(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser->canExportEmployees()) {
            abort(403, 'Akses Ditolak: Hanya Direktur Utama dan Manager HR yang memiliki wewenang untuk menarik data karyawan.');
        }

        $query = Employee::with('user')->orderBy('department')->orderBy('name');

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role_title', 'like', "%{$search}%");
            });
        }

        $employees = $query->get();

        // Statistik Agregat untuk Ringkasan Dokumen
        $totalEmployees = $employees->count();
        $totalActive = $employees->where('status', 'Active')->count();
        $totalPermanent = $employees->filter(fn($e) => strtolower($e->employment_type ?? '') === 'tetap' || strtolower($e->employment_type ?? '') === 'permanent')->count();
        $totalContract = $employees->filter(fn($e) => strtolower($e->employment_type ?? '') === 'kontrak' || strtolower($e->employment_type ?? '') === 'contract')->count();
        $deptCounts = $employees->groupBy('department')->map->count();

        $selectedDept = $request->query('department');
        $selectedStatus = $request->query('status');
        $selectedType = $request->query('employment_type');

        $reportTitle = 'Laporan Master Data & Direktori Karyawan';
        $reportCode = 'RPT-EMP-' . date('Ymd-His');
        $generatedAt = \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB';

        return view('reports.employees_pdf', compact(
            'employees',
            'authUser',
            'totalEmployees',
            'totalActive',
            'totalPermanent',
            'totalContract',
            'deptCounts',
            'selectedDept',
            'selectedStatus',
            'selectedType',
            'reportTitle',
            'reportCode',
            'generatedAt'
        ));
    }
}
