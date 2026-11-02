    <?php

    namespace App\Http\Controllers;

    use App\Models\Attendance;
    use App\Models\AttendanceExcuse;
    use App\Models\Employee;
    use App\Models\User;
    use App\Notifications\GeneralNotification;
    use Carbon\Carbon;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;

    class AttendanceExcuseController extends Controller
    {
        /**
         * Tampilkan daftar pengajuan perizinan kehadiran (Izin Terlambat, Dinas Luar, Tidak Absen Pulang)
         */
        public function index(Request $request)
        {
            $authUser = Auth::user();
            $currentUser = $authUser->employee;
            $status = $request->query('status');
            $type = $request->query('type');

            $query = AttendanceExcuse::with(['employee', 'approver', 'managerApprover'])->orderBy('created_at', 'desc');

            // Hak Akses Visibilitas 2-Tier:
            // 1. Direktur Utama & Manager HR: Melihat seluruh perizinan dari semua departemen.
            // 2. Manager Departemen Non-HR (Finance, GA, BizDev, Resepsionis, Trainer):
            //    Melihat pengajuan dari anggota departemennya sendiri + pengajuan milik dirinya sendiri.
            // 3. Karyawan Biasa: HANYA melihat pengajuan miliknya sendiri.
            if ($authUser->isDirektur() || ($authUser->isHR() && $authUser->isManager())) {
                // Full access across all departments
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $query->where(function ($q) use ($userDept, $currentUser) {
                    $q->whereHas('employee', function ($sub) use ($userDept) {
                        $sub->where('department', $userDept);
                    });
                    if ($currentUser) {
                        $q->orWhere('employee_id', $currentUser->id);
                    }
                });
            } else {
                if ($currentUser) {
                    $query->where('employee_id', $currentUser->id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
            } elseif ($status) {
                $query->where('status', $status);
            }

            if ($type) {
                $query->where('type', $type);
            }

            $excuses = $query->paginate(15)->withQueryString();

            // Karyawan list untuk form pengajuan
            $employees = $authUser->canApproveLeaves()
                ? Employee::orderBy('name')->get()
                : ($currentUser ? Employee::where('id', $currentUser->id)->get() : Employee::all());

            // Counter metrik proporsional terhadap hak akses
            $pendingQuery = AttendanceExcuse::whereIn('status', ['pending', 'pending_manager', 'pending_hr']);
            $approvedQuery = AttendanceExcuse::where('status', 'approved');
            $rejectedQuery = AttendanceExcuse::where('status', 'rejected');

            if ($authUser->isDirektur() || ($authUser->isHR() && $authUser->isManager())) {
                // Full count
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $applyDeptScope = function ($q) use ($userDept, $currentUser) {
                    $q->where(function ($sub) use ($userDept, $currentUser) {
                        $sub->whereHas('employee', function ($e) use ($userDept) {
                            $e->where('department', $userDept);
                        });
                        if ($currentUser) {
                            $sub->orWhere('employee_id', $currentUser->id);
                        }
                    });
                };
                $applyDeptScope($pendingQuery);
                $applyDeptScope($approvedQuery);
                $applyDeptScope($rejectedQuery);
            } else {
                if ($currentUser) {
                    $pendingQuery->where('employee_id', $currentUser->id);
                    $approvedQuery->where('employee_id', $currentUser->id);
                    $rejectedQuery->where('employee_id', $currentUser->id);
                }
            }

            $pendingCount = $pendingQuery->count();
            $approvedCount = $approvedQuery->count();
            $rejectedCount = $rejectedQuery->count();

            // Counter per tipe
            $lateCount = (clone $pendingQuery)->where('type', 'late_permission')->count();
            $dutyCount = (clone $pendingQuery)->where('type', 'duty_trip')->count();
            $missedOutCount = (clone $pendingQuery)->where('type', 'missed_clock_out')->count();

            return view('attendance.excuses', compact(
                'excuses',
                'employees',
                'currentUser',
                'authUser',
                'status',
                'type',
                'pendingCount',
                'approvedCount',
                'rejectedCount',
                'lateCount',
                'dutyCount',
                'missedOutCount'
            ));
        }

        /**
         * Simpan pengajuan izin kehadiran baru oleh karyawan / manajer
         */
        public function store(Request $request)
        {
            $authUser = Auth::user();
            $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);

            // Jika bukan HR/Direktur, permohonan selalu atas nama dirinya sendiri
            $employeeId = $authUser->canApproveLeaves()
                ? ($request->employee_id ?? $currentEmpId)
                : $currentEmpId;

            if (!$employeeId) {
                return back()->with('error', 'Akun Anda belum terhubung dengan data karyawan.')->withInput();
            }

            $request->validate([
                'type' => 'required|in:late_permission,duty_trip,missed_clock_out',
                'date' => 'required|date',
                'expected_time' => 'nullable|string',
                'reason' => 'required|string|min:5|max:1000',
            ], [
                'type.required' => 'Pilih jenis izin kehadiran.',
                'date.required' => 'Tanggal permohonan wajib diisi.',
                'reason.required' => 'Alasan / keterangan izin wajib diisi minimal 5 karakter.',
            ]);

            $formattedTime = null;
            if ($request->filled('expected_time')) {
                try {
                    $formattedTime = Carbon::parse($request->expected_time)->format('H:i:s');
                } catch (\Exception $e) {
                    $formattedTime = null;
                }
            }

            // Cek duplikasi permohonan sejenis pada tanggal yang sama yang masih pending
            $existing = AttendanceExcuse::where('employee_id', $employeeId)
                ->where('date', $request->date)
                ->where('type', $request->type)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existing) {
                return back()->with('error', "Anda sudah memiliki pengajuan {$existing->type_name} yang aktif untuk tanggal " . Carbon::parse($request->date)->format('d M Y') . ".")->withInput();
            }

            $applicant = Employee::with('user')->find($employeeId);
            $applicantUser = $applicant?->user;
            $applicantDept = strtolower($applicant?->department ?? '');

            // ALUR BERJENJANG (TWO-TIER APPROVAL):
            // 1. Jika pemohon adalah Karyawan di departemen non-HR:
            //    -> Wajib mengajukan ke Manager Departemen terlebih dahulu (status: 'pending_manager').
            // 2. Jika pemohon adalah staf di departemen HR, atau Manager Departemen, atau Direktur:
            //    -> Langsung ke antrean persetujuan Manager HR / Direktur Utama (status: 'pending_hr').
            $isNonHrKaryawan = ($applicantUser?->isKaryawan() ?? true) && $applicantDept !== 'hr';
            $initialStatus = $isNonHrKaryawan ? 'pending_manager' : 'pending_hr';

            $excuse = AttendanceExcuse::create([
                'employee_id' => $employeeId,
                'type' => $request->type,
                'date' => $request->date,
                'expected_time' => $formattedTime,
                'reason' => $request->reason,
                'status' => $initialStatus,
            ]);

            // Otomatisasi Pencatatan Absen untuk Izin Terlambat (Dispensasi Keterlambatan Kantor):
            // Karyawan/manager yang mengajukan izin terlambat absennya secara otomatis dicatat sesuai perkiraan jam tiba yang diajukan.
            if ($request->type === 'late_permission') {
                $attDate = Carbon::parse($request->date)->toDateString();
                $arrivalTime = $formattedTime ?: '09:30:00';

                $attendance = Attendance::where('employee_id', $employeeId)
                    ->whereDate('date', $attDate)
                    ->first();

                $pendingNote = "Dispensasi Izin Terlambat Diajukan (Perkiraan Tiba: " . substr($arrivalTime, 0, 5) . " WIB - Menunggu Approval). Alasan: {$request->reason}";

                if (!$attendance) {
                    Attendance::create([
                        'employee_id' => $employeeId,
                        'date' => $attDate,
                        'clock_in' => $arrivalTime,
                        'clock_out' => null,
                        'status' => 'late',
                        'work_mode' => 'WFO',
                        'location_name' => 'Dispensasi Masuk Kantor',
                        'notes' => $pendingNote,
                    ]);
                } else {
                    $attData = [
                        'notes' => $pendingNote,
                    ];
                    if (!$attendance->clock_in) {
                        $attData['clock_in'] = $arrivalTime;
                    }
                    $attendance->update($attData);
                }
            }

            // Notifikasi Otomatis Berjenjang
            try {
                $applicantName = $applicant ? $applicant->name : $authUser->name;
                $typeName = $excuse->type_name;
                $formattedDate = Carbon::parse($request->date)->translatedFormat('d F Y');

                if ($initialStatus === 'pending_manager') {
                    $deptManagers = User::where('department', $applicant->department)
                        ->where('role', 'manager')
                        ->where('id', '!=', $authUser->id)
                        ->get();

                    foreach ($deptManagers as $mgr) {
                        $mgr->notify(new GeneralNotification(
                            "Persetujuan Izin Departemen Diperlukan",
                            "{$applicantName} ({$applicant->department}) mengajukan {$typeName} untuk tanggal {$formattedDate}. Mohon tinjau & setujui sebelum diteruskan ke Manager HR.",
                            route('attendance.excuses.index'),
                            $excuse->type_icon,
                            'amber'
                        ));
                    }
                } else {
                    $hrApprovers = User::where(function ($q) {
                        $q->where('role', 'direktur')
                        ->orWhere(function ($sub) {
                            $sub->where('department', 'HR')->where('role', 'manager');
                        });
                    })->where('id', '!=', $authUser->id)->get();

                    foreach ($hrApprovers as $approver) {
                        $approver->notify(new GeneralNotification(
                            "Pengajuan {$typeName} Baru",
                            "{$applicantName} ({$applicant->department}) mengajukan {$typeName} untuk tanggal {$formattedDate}.",
                            route('attendance.excuses.index'),
                            $excuse->type_icon,
                            'amber'
                        ));
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim notifikasi pengajuan izin kehadiran: ' . $e->getMessage());
            }

            $successMsg = ($initialStatus === 'pending_manager')
                ? "Permohonan {$excuse->type_name} berhasil diajukan dan sedang menunggu persetujuan dari Manager Departemen ({$applicant->department}). Setelah disetujui, permohonan akan otomatis diteruskan ke Manager HR."
                : "Permohonan {$excuse->type_name} berhasil dikirimkan dan menunggu verifikasi dari Manager HR.";

            return back()->with('success', $successMsg);
        }

        /**
         * Persetujuan / Penolakan Pengajuan Izin Kehadiran (2-Tier Approval)
         */
        public function updateStatus(Request $request, $id)
        {
            $authUser = Auth::user();
            $currentEmpId = $authUser->employee_id ?? ($authUser->employee?->id);

            $request->validate([
                'status' => 'required|in:approved,rejected',
                'rejection_note' => 'required_if:status,rejected|nullable|string|max:1000',
            ], [
                'rejection_note.required_if' => 'Alasan penolakan wajib disertakan saat menolak permohonan.',
            ]);

            $excuse = AttendanceExcuse::with('employee')->findOrFail($id);

            // Anti Self-Approval: Pemohon dilarang keras menyetujui pengajuannya sendiri
            if ($currentEmpId && (int)$excuse->employee_id === (int)$currentEmpId) {
                abort(403, 'Akses Ditolak: Prinsip Anti-Self-Approval melarang Anda menyetujui atau menolak pengajuan milik diri sendiri.');
            }

            $isStageManager = $excuse->status === 'pending_manager';
            $isStageHR = in_array($excuse->status, ['pending_hr', 'pending']);

            if ($isStageManager) {
                // TAHAP 1: Verifikasi oleh Manager Departemen Pemohon
                if (!$authUser->canApproveDepartmentLeave($excuse)) {
                    abort(403, 'Akses Ditolak: Permohonan ini berada pada tahap verifikasi Manager Departemen (' . $excuse->employee->department . '). Hanya Manager Departemen terkait atau Direktur Utama yang berwenang memproses.');
                }

                if ($request->status === 'approved') {
                    $excuse->update([
                        'status' => 'pending_hr',
                        'manager_approved_by' => $currentEmpId,
                        'manager_approved_at' => Carbon::now(),
                    ]);

                    // Sinkronisasi catatan log presensi untuk izin terlambat tahap Manager Departemen
                    if ($excuse->type === 'late_permission') {
                        $attDate = Carbon::parse($excuse->date)->toDateString();
                        $attendance = Attendance::where('employee_id', $excuse->employee_id)
                            ->whereDate('date', $attDate)
                            ->first();

                        $arrivalTime = $excuse->expected_time ?: '09:30:00';
                        $mgrName = $authUser->name;
                        $mgrNote = "Dispensasi Izin Terlambat (Estimasi tiba: " . substr($arrivalTime, 0, 5) . " WIB) telah disetujui Manager Dept ({$mgrName}) - Menunggu persetujuan final Manager HR. Alasan: {$excuse->reason}";

                        if (!$attendance) {
                            Attendance::create([
                                'employee_id' => $excuse->employee_id,
                                'date' => $attDate,
                                'clock_in' => $arrivalTime,
                                'clock_out' => null,
                                'status' => 'late',
                                'work_mode' => 'WFO',
                                'location_name' => 'Dispensasi Masuk Kantor',
                                'notes' => $mgrNote,
                            ]);
                        } else {
                            $attendance->update([
                                'notes' => $mgrNote,
                            ]);
                        }
                    }

                    // Notifikasi ke Tim HR & Direktur
                    try {
                        $hrApprovers = User::where(function ($q) {
                            $q->where('department', 'HR')->where('role', 'manager');
                        })->orWhere('role', 'direktur')->where('id', '!=', $authUser->id)->get();

                        $applicantName = $excuse->employee->name;
                        $deptName = $excuse->employee->department;

                        foreach ($hrApprovers as $approver) {
                            $approver->notify(new GeneralNotification(
                                "Rekomendasi Izin dari Manager " . $deptName,
                                "Manager {$deptName} ({$authUser->name}) telah menyetujui permohonan {$excuse->type_name} dari {$applicantName}. Menunggu verifikasi final HR.",
                                route('attendance.excuses.index'),
                                'verified',
                                'indigo'
                            ));
                        }

                        // Notifikasi ke Pemohon
                        $applicantUser = User::where('employee_id', $excuse->employee_id)->first();
                        if ($applicantUser) {
                            $applicantUser->notify(new GeneralNotification(
                                "Persetujuan Tahap 1 Disetujui",
                                "Permohonan {$excuse->type_name} Anda telah disetujui oleh Manager Departemen ({$authUser->name}) dan secara otomatis diteruskan ke Manager HR.",
                                route('attendance.excuses.index'),
                                'check_circle',
                                'emerald'
                            ));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Gagal notifikasi approval manager excuse: ' . $e->getMessage());
                    }

                    return back()->with('success', "Permohonan {$excuse->type_name} berhasil disetujui oleh Manager Departemen dan otomatis diteruskan ke Manager HR untuk persetujuan final.");
                } else {
                    $excuse->update([
                        'status' => 'rejected',
                        'approved_by' => $currentEmpId,
                        'action_at' => Carbon::now(),
                        'rejection_note' => $request->rejection_note,
                    ]);

                    // Sinkronisasi catatan log presensi jika ditolak Manager Departemen
                    if ($excuse->type === 'late_permission') {
                        $attDate = Carbon::parse($excuse->date)->toDateString();
                        $attendance = Attendance::where('employee_id', $excuse->employee_id)
                            ->whereDate('date', $attDate)
                            ->first();

                        if ($attendance) {
                            $mgrName = $authUser->name;
                            $attendance->update([
                                'notes' => "Dispensasi Izin Terlambat DITOLAK Manager Dept ({$mgrName}): {$request->rejection_note} (Alasan pengajuan: {$excuse->reason})",
                            ]);
                        }
                    }

                    try {
                        $applicantUser = User::where('employee_id', $excuse->employee_id)->first();
                        if ($applicantUser) {
                            $applicantUser->notify(new GeneralNotification(
                                "Permohonan {$excuse->type_name} Ditolak",
                                "Permohonan {$excuse->type_name} Anda ditolak oleh Manager Departemen ({$authUser->name}). Alasan: {$request->rejection_note}",
                                route('attendance.excuses.index'),
                                'cancel',
                                'rose'
                            ));
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Gagal notifikasi penolakan manager excuse: ' . $e->getMessage());
                    }

                    return back()->with('success', "Permohonan {$excuse->type_name} ditolak pada tingkat Manager Departemen.");
                }
            } elseif ($isStageHR) {
                // TAHAP 2: Verifikasi Final oleh Manager HR atau Direktur Utama
                if (!$authUser->canApproveLeaves()) {
                    abort(403, 'Akses Ditolak: Hanya Manager HR dan Direktur Utama yang memiliki wewenang untuk menyetujui atau menolak perizinan presensi final.');
                }

                DB::transaction(function () use ($excuse, $request, $currentEmpId, $authUser) {
                    $excuse->update([
                        'status' => $request->status,
                        'approved_by' => $currentEmpId,
                        'action_at' => Carbon::now(),
                        'rejection_note' => $request->rejection_note,
                    ]);

                    $attDate = Carbon::parse($excuse->date)->toDateString();
                    $attendance = Attendance::where('employee_id', $excuse->employee_id)
                        ->whereDate('date', $attDate)
                        ->first();

                    // Jika Disetujui (Approved), sinkronisasikan ke catatan log presensi karyawan
                    if ($request->status === 'approved') {
                        if ($excuse->type === 'duty_trip') {
                            if (!$attendance) {
                                Attendance::create([
                                    'employee_id' => $excuse->employee_id,
                                    'date' => $attDate,
                                    'clock_in' => $excuse->expected_time ?: '08:30:00',
                                    'clock_out' => '18:00:00',
                                    'status' => 'on_time',
                                    'work_mode' => 'Dinas Luar',
                                    'location_name' => 'Lokasi Dinas / Luar Kantor',
                                    'notes' => 'Izin Dinas Luar resmi disetujui HR: ' . $excuse->reason,
                                ]);
                            } else {
                                $attendance->update([
                                    'work_mode' => 'Dinas Luar',
                                    'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'Izin Dinas Luar resmi disetujui HR: ' . $excuse->reason,
                                ]);
                            }
                        } elseif ($excuse->type === 'late_permission') {
                            $arrivalTime = $excuse->expected_time ?: '09:30:00';
                            $hrName = $authUser->name;

                            // Cek riwayat persetujuan Manager Departemen di Tahap 1
                            $mgrName = $excuse->managerApprover?->name;
                            if (!$mgrName && $excuse->manager_approved_by) {
                                $mgrEmployee = Employee::find($excuse->manager_approved_by);
                                $mgrName = $mgrEmployee?->name;
                            }

                            $approverInfo = $mgrName
                                ? "Manager Dept: {$mgrName} & Manager HR: {$hrName}"
                                : "Manager HR: {$hrName}";

                            $dispensationNote = "Dispensasi Keterlambatan Kantor Resmi Disetujui [{$approverInfo}]. Alasan: {$excuse->reason}";

                            if (!$attendance) {
                                Attendance::create([
                                    'employee_id' => $excuse->employee_id,
                                    'date' => $attDate,
                                    'clock_in' => $arrivalTime,
                                    'clock_out' => null,
                                    'status' => 'on_time',
                                    'work_mode' => 'WFO',
                                    'location_name' => 'Dispensasi Kantor Disetujui',
                                    'notes' => $dispensationNote,
                                ]);
                            } else {
                                $updateData = [
                                    'status' => 'on_time',
                                    'notes' => $dispensationNote,
                                ];
                                if (!$attendance->clock_in) {
                                    $updateData['clock_in'] = $arrivalTime;
                                }
                                $attendance->update($updateData);
                            }
                        } elseif ($excuse->type === 'missed_clock_out') {
                            $clockOutTime = $excuse->expected_time ?: '18:00:00';
                            if ($attendance) {
                                $attendance->update([
                                    'clock_out' => $clockOutTime,
                                    'notes' => ($attendance->notes ? $attendance->notes . ' | ' : '') . 'Keterangan Lupa/Tidak Absen Pulang disetujui HR: ' . $excuse->reason,
                                ]);
                            } else {
                                Attendance::create([
                                    'employee_id' => $excuse->employee_id,
                                    'date' => $attDate,
                                    'clock_in' => '09:00:00',
                                    'clock_out' => $clockOutTime,
                                    'status' => 'on_time',
                                    'work_mode' => 'WFO',
                                    'location_name' => 'Keterangan Absen Pulang (HR)',
                                    'notes' => 'Keterangan Lupa/Tidak Absen Pulang disetujui HR: ' . $excuse->reason,
                                ]);
                            }
                        }
                    } else {
                        // Ditolak oleh HR
                        if ($excuse->type === 'late_permission' && $attendance) {
                            $hrName = $authUser->name;
                            $attendance->update([
                                'notes' => "Dispensasi Izin Terlambat DITOLAK Tim HR ({$hrName}): {$request->rejection_note} (Alasan pengajuan: {$excuse->reason})",
                            ]);
                        }
                    }
                });

                // Kirim notifikasi balasan ke pemohon
                try {
                    $applicantUser = User::where('employee_id', $excuse->employee_id)->first();
                    if ($applicantUser) {
                        $statusText = $request->status === 'approved' ? 'Disetujui' : 'Ditolak';
                        $color = $request->status === 'approved' ? 'emerald' : 'rose';
                        $icon = $request->status === 'approved' ? 'check_circle' : 'cancel';
                        $reasonMsg = $request->status === 'rejected' && $request->rejection_note ? " Alasan: {$request->rejection_note}" : "";

                        $applicantUser->notify(new GeneralNotification(
                            "Pengajuan {$excuse->type_name} {$statusText}",
                            "Permohonan {$excuse->type_name} Anda untuk tanggal {$excuse->date->format('d M Y')} telah {$statusText} oleh Tim HR ({$authUser->name}).{$reasonMsg}",
                            route('attendance.excuses.index'),
                            $icon,
                            $color
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::warning('Gagal notifikasi approval perizinan kehadiran: ' . $e->getMessage());
                }

                $resLabel = $request->status === 'approved' ? 'DISETUJUI' : 'DITOLAK';
                return back()->with('success', "Status permohonan {$excuse->type_name} berhasil diperbarui oleh Tim HR menjadi {$resLabel}.");
            } else {
                return back()->with('error', 'Permohonan ini telah selesai ditinjau sebelumnya.');
            }
        }
    }
