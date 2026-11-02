<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\AttendanceResetRequest;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;
        $status = $request->query('status');

        // Rentang Tanggal (Date Range)
        $todayStr = Carbon::today()->toDateString();
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        // Fallback jika hanya parameter 'date' tunggal yang dikirim
        if (!$startDate && !$endDate) {
            $singleDate = $request->query('date', $todayStr);
            $startDate = $singleDate;
            $endDate = $singleDate;
        } elseif ($startDate && !$endDate) {
            $endDate = $startDate;
        } elseif (!$startDate && $endDate) {
            $startDate = $endDate;
        }

        // Validasi & Batasi Rentang Tanggal Maksimal 31 Hari (1 Bulan)
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        // Jika start date melebihi end date, swap
        if ($startCarbon->greaterThan($endCarbon)) {
            $temp = $startCarbon;
            $startCarbon = $endCarbon;
            $endCarbon = $temp;
        }

        // Jika selisih hari melebihi 31 hari, batasi end date ke 31 hari dari start date
        if ($startCarbon->diffInDays($endCarbon) > 31) {
            $endCarbon = $startCarbon->copy()->addDays(31);
            session()->flash('warning', 'Rentang tanggal dibatasi maksimal 31 hari (1 bulan) untuk menjaga performa sistem.');
        }

        $startDate = $startCarbon->toDateString();
        $endDate = $endCarbon->toDateString();
        $isRange = ($startDate !== $endDate);
        $date = $startDate; // backward compatibility

        $query = Attendance::with('employee')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc');

        // Scope attendance feed based on role & department:
        // 1. Direktur & Departemen HR (Manager & Karyawan HR): Melihat presensi seluruh karyawan perusahaan
        // 2. Manager divisi lain: Melihat dirinya sendiri dan karyawan pada departemennya
        // 3. Karyawan divisi lain: Hanya melihat dirinya sendiri
        $canViewAllAttendance = $authUser->isDirektur() || $authUser->isHR();

        if (!$canViewAllAttendance) {
            if ($authUser->isKaryawan()) {
                if ($currentUser) {
                    $query->where('employee_id', $currentUser->id);
                }
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $query->whereHas('employee', function ($q) use ($userDept, $currentUser) {
                    $q->where('department', $userDept);
                    if ($currentUser) {
                        $q->orWhere('id', $currentUser->id);
                    }
                });
            }
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->paginate(20)->withQueryString();
        
        $employees = $canViewAllAttendance
            ? Employee::orderBy('name')->get()
            : ($authUser->isKaryawan() && $currentUser 
                ? Employee::where('id', $currentUser->id)->get() 
                : ($authUser->isManager() ? Employee::where('department', $authUser->department)->orWhere('id', $currentUser?->id)->get() : Employee::orderBy('name')->get()));

        // Metrics for selected date range
        $totalPresentQuery = Attendance::whereBetween('date', [$startDate, $endDate]);
        $totalOnTimeQuery = Attendance::whereBetween('date', [$startDate, $endDate])->where('status', 'on_time');
        $totalLateQuery = Attendance::whereBetween('date', [$startDate, $endDate])->where('status', 'late');
        $totalAbsentQuery = Attendance::whereBetween('date', [$startDate, $endDate])->where('status', 'absent');

        if (!$canViewAllAttendance) {
            if ($authUser->isKaryawan() && $currentUser) {
                $totalPresentQuery->where('employee_id', $currentUser->id);
                $totalOnTimeQuery->where('employee_id', $currentUser->id);
                $totalLateQuery->where('employee_id', $currentUser->id);
                $totalAbsentQuery->where('employee_id', $currentUser->id);
            } elseif ($authUser->isManager()) {
                $userDept = $authUser->department;
                $deptFilter = function ($q) use ($userDept, $currentUser) {
                    $q->where('department', $userDept);
                    if ($currentUser) {
                        $q->orWhere('id', $currentUser->id);
                    }
                };
                $totalPresentQuery->whereHas('employee', $deptFilter);
                $totalOnTimeQuery->whereHas('employee', $deptFilter);
                $totalLateQuery->whereHas('employee', $deptFilter);
                $totalAbsentQuery->whereHas('employee', $deptFilter);
            }
        }

        $totalPresent = $totalPresentQuery->count();
        $totalOnTime = $totalOnTimeQuery->count();
        $totalLate = $totalLateQuery->count();
        $totalAbsent = $totalAbsentQuery->count();

        // Data Pengajuan Reset Kehadiran
        $pendingResetRequests = AttendanceResetRequest::with(['requester', 'approver'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentResetRequests = AttendanceResetRequest::with(['requester', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('action_at', 'desc')
            ->take(5)
            ->get();

        $canRequestReset = $authUser->isDirektur() || ($authUser->isHR() && $authUser->isManager());

        return view('attendance.index', compact(
            'attendances',
            'date',
            'startDate',
            'endDate',
            'isRange',
            'status',
            'employees',
            'currentUser',
            'authUser',
            'totalPresent',
            'totalOnTime',
            'totalLate',
            'totalAbsent',
            'pendingResetRequests',
            'recentResetRequests',
            'canRequestReset'
        ));
    }

    public function clockIn(Request $request)
    {
        $authUser = Auth::user();

        // Aturan: Hanya Manager HR dan Direktur yang boleh melakukan clock in manual lewat web
        if (!$authUser->canQuickClock()) {
            return back()->with('error', 'Pencatatan presensi manual via aplikasi hanya diperbolehkan untuk Manager HR dan Direktur Utama.');
        }

        $employeeId = $request->employee_id ?? $authUser->employee_id;

        $today = Carbon::today()->toDateString();
        $nowTime = Carbon::now()->toTimeString();

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && $attendance->clock_in) {
            return back()->with('error', 'Karyawan sudah melakukan clock in hari ini.');
        }

        // Jam masuk 09:00:00 WIB dengan toleransi keterlambatan 15 menit (s.d. 09:15:00 WIB)
        $status = ($nowTime > '09:15:00') ? 'late' : 'on_time';

        // Tentukan keterangan pencatat presensi
        $isSelf = ($authUser->employee_id && (int)$authUser->employee_id === (int)$employeeId);
        $roleTitle = $authUser->isDirektur() ? 'Direktur Utama' : ($authUser->isHR() && $authUser->isManager() ? 'Manager HR' : $authUser->role);
        
        $actorTag = $isSelf
            ? "[Absen Masuk Manual Mandiri oleh {$authUser->name} ({$roleTitle})]"
            : "[Diabsenkan Masuk oleh {$authUser->name} ({$roleTitle})]";

        $notes = $request->notes ? trim($request->notes) . " • " . $actorTag : $actorTag;

        Attendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $today],
            [
                'clock_in' => $nowTime,
                'status' => $status,
                'work_mode' => $request->work_mode ?? 'WFO',
                'location_name' => $request->location_name ?? 'Headquarters (Jakarta)',
                'ip_address' => $request->ip(),
                'notes' => $notes,
            ]
        );

        return back()->with('success', 'Presensi Masuk (Clock In) berhasil dicatat pada ' . Carbon::now()->format('H:i:s') . ' WIB.');
    }

    public function clockOut(Request $request)
    {
        $authUser = Auth::user();

        // Aturan: Hanya Manager HR dan Direktur yang boleh melakukan clock out manual lewat web
        if (!$authUser->canQuickClock()) {
            return back()->with('error', 'Pencatatan presensi pulang manual via aplikasi hanya diperbolehkan untuk Manager HR dan Direktur Utama.');
        }

        $employeeId = $request->employee_id ?? $authUser->employee_id;

        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance || !$attendance->clock_in) {
            return back()->with('error', 'Karyawan belum melakukan clock in hari ini.');
        }

        // Tentukan keterangan pencatat presensi pulang
        $isSelf = ($authUser->employee_id && (int)$authUser->employee_id === (int)$employeeId);
        $roleTitle = $authUser->isDirektur() ? 'Direktur Utama' : ($authUser->isHR() && $authUser->isManager() ? 'Manager HR' : $authUser->role);

        $actorTag = $isSelf
            ? "[Absen Pulang Manual Mandiri oleh {$authUser->name} ({$roleTitle})]"
            : "[Diabsenkan Pulang oleh {$authUser->name} ({$roleTitle})]";

        $existingNotes = $attendance->notes ?? '';
        $newNotes = $existingNotes 
            ? (str_contains($existingNotes, $actorTag) ? $existingNotes : $existingNotes . " • " . $actorTag)
            : $actorTag;

        $attendance->update([
            'clock_out' => Carbon::now()->toTimeString(),
            'notes' => $newNotes,
        ]);

        return back()->with('success', 'Presensi Pulang (Clock Out) berhasil dicatat pada ' . Carbon::now()->format('H:i:s') . ' WIB.');
    }

    /**
     * Presensi Mandiri Biometrik Wajah (Face ID) & Geolocation GPS
     * Dapat digunakan oleh seluruh Karyawan, Manager, dan Direksi
     */
    public function faceRecord(Request $request)
    {
        $authUser = Auth::user();
        $currentUser = $authUser->employee;

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terhubung dengan data profil karyawan.'
            ], 422);
        }

        $request->validate([
            'photo' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'type' => 'nullable|in:clock_in,clock_out,auto',
            'work_mode' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $nowTime = $now->toTimeString();

        // Cari atau periksa kehadiran hari ini
        $attendance = Attendance::where('employee_id', $currentUser->id)
            ->whereDate('date', $today)
            ->first();

        // Tentukan tipe aksi secara otomatis atau manual
        $requestedType = $request->input('type', 'auto');
        if ($requestedType === 'auto') {
            if (!$attendance || !$attendance->clock_in) {
                $actionType = 'clock_in';
            } elseif (!$attendance->clock_out) {
                $actionType = 'clock_out';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda telah menyelesaikan presensi masuk dan pulang untuk hari ini.'
                ], 422);
            }
        } else {
            $actionType = $requestedType;
        }

        // Decode dan simpan foto selfie wajah dari Base64
        $base64Image = $request->input('photo');
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            $imgExt = strtolower($type[1]) === 'png' ? 'png' : 'jpg';
        } else {
            $imgExt = 'jpg';
        }

        $decodedImage = base64_decode($base64Image);
        if (!$decodedImage) {
            return response()->json([
                'success' => false,
                'message' => 'Format foto wajah tidak valid atau rusak.'
            ], 422);
        }

        $folder = 'attendance_photos/' . $now->format('Y-m');
        $fileName = $folder . '/' . $currentUser->nip . '_' . $actionType . '_' . time() . '.' . $imgExt;
        Storage::disk('public')->put($fileName, $decodedImage);

        // Koordinat Geofencing GPS Kantor (Default: Jakarta HQ / Configurable)
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $officeLat = (float) config('app.office_latitude', env('OFFICE_LATITUDE', -6.2088));
        $officeLng = (float) config('app.office_longitude', env('OFFICE_LONGITUDE', 106.8456));
        $allowedRadius = (int) config('app.office_radius_meters', env('OFFICE_RADIUS_METERS', 500));

        $distanceMeters = null;
        $inOfficeRadius = false;
        if ($latitude && $longitude) {
            $distanceMeters = $this->calculateDistanceMeters((float)$latitude, (float)$longitude, $officeLat, $officeLng);
            $inOfficeRadius = ($distanceMeters <= $allowedRadius);
        }

        $detectedLocation = $request->input('location_name');
        if (!$detectedLocation) {
            if ($inOfficeRadius) {
                $detectedLocation = "Kantor Pusat Gyokai ({$distanceMeters}m dari titik pusat)";
            } elseif ($distanceMeters !== null) {
                $detectedLocation = "Luar Radius Kantor ({$distanceMeters}m dari kantor)";
            } else {
                $detectedLocation = "Lokasi Geolocation Tidak Diketahui";
            }
        }

        $workMode = $request->input('work_mode') ?: ($inOfficeRadius ? 'WFO' : 'WFH / Dinas Luar');
        $userNotes = $request->input('notes') ? trim($request->input('notes')) . " • " : "";
        $deviceInfo = $request->header('User-Agent');

        if ($actionType === 'clock_in') {
            if ($attendance && $attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan Presensi Masuk (Clock In) hari ini pada pukul ' . substr($attendance->clock_in, 0, 5) . ' WIB.'
                ], 422);
            }

            // Batas Masuk: 09:15:00 WIB
            $status = ($nowTime > '09:15:00') ? 'late' : 'on_time';
            $faceTag = "[Face ID & GPS Masuk: " . ($inOfficeRadius ? "Dalam Kantor ({$distanceMeters}m)" : "Luar Radius ({$distanceMeters}m)") . "]";
            $finalNotes = $userNotes . $faceTag;

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $currentUser->id, 'date' => $today],
                [
                    'clock_in' => $nowTime,
                    'status' => $status,
                    'work_mode' => $workMode,
                    'location_name' => $detectedLocation,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'photo_in' => $fileName,
                    'distance_meters' => $distanceMeters,
                    'verification_method' => 'face_location',
                    'device_info' => substr($deviceInfo ?? '', 0, 250),
                    'ip_address' => $request->ip(),
                    'notes' => $finalNotes,
                ]
            );

            return response()->json([
                'success' => true,
                'action' => 'clock_in',
                'message' => 'Presensi Masuk Berhasil Terverifikasi! Wajah & Titik GPS tercatat.',
                'time' => substr($nowTime, 0, 5) . ' WIB',
                'status' => $status,
                'status_label' => $status === 'on_time' ? 'Tepat Waktu' : 'Terlambat',
                'distance' => $distanceMeters,
                'location' => $detectedLocation,
                'photo_url' => Storage::disk('public')->url($fileName),
            ]);
        } else {
            // Clock Out
            if (!$attendance || !$attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan Presensi Masuk hari ini. Silakan Clock In terlebih dahulu.'
                ], 422);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan Presensi Pulang (Clock Out) hari ini pada pukul ' . substr($attendance->clock_out, 0, 5) . ' WIB.'
                ], 422);
            }

            $faceTag = "[Face ID & GPS Pulang: " . ($inOfficeRadius ? "Dalam Kantor ({$distanceMeters}m)" : "Luar Radius ({$distanceMeters}m)") . "]";
            $existingNotes = $attendance->notes ?? '';
            $finalNotes = $existingNotes ? $existingNotes . " • " . $faceTag : $faceTag;

            $attendance->update([
                'clock_out' => $nowTime,
                'photo_out' => $fileName,
                'latitude' => $latitude ?? $attendance->latitude,
                'longitude' => $longitude ?? $attendance->longitude,
                'distance_meters' => $distanceMeters ?? $attendance->distance_meters,
                'verification_method' => 'face_location',
                'notes' => $finalNotes,
            ]);

            return response()->json([
                'success' => true,
                'action' => 'clock_out',
                'message' => 'Presensi Pulang Berhasil Terverifikasi! Wajah & Titik GPS tercatat.',
                'time' => substr($nowTime, 0, 5) . ' WIB',
                'distance' => $distanceMeters,
                'location' => $detectedLocation,
                'photo_url' => Storage::disk('public')->url($fileName),
            ]);
        }
    }

    /**
     * Hitung Jarak antara Dua Koordinat GPS Menggunakan Rumus Haversine (Hasil dalam Meter)
     */
    private function calculateDistanceMeters($lat1, $lon1, $lat2, $lon2): int
    {
        $earthRadius = 6371000; // Radius Bumi dalam meter
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }

    /**
     * Mengajukan permohonan reset kehadiran seluruh karyawan & manager.
     * Hanya dapat diajukan oleh Manager HRD atau Direktur Utama.
     */
    public function requestReset(Request $request)
    {
        $authUser = Auth::user();

        // Verifikasi hak pengajuan: Hanya Manager HRD atau Direktur
        $isHRManager = $authUser->isHR() && $authUser->isManager();
        $isDirektur = $authUser->isDirektur();

        if (!$isHRManager && !$isDirektur) {
            return back()->with('error', 'Hanya Manager HRD dan Direktur Utama yang berwenang mengajukan reset kehadiran.');
        }

        // Cek jika sudah ada pengajuan pending
        $hasPending = AttendanceResetRequest::where('status', 'pending')->exists();
        if ($hasPending) {
            return back()->with('error', 'Masih terdapat pengajuan reset kehadiran yang berstatus pending (menunggu persetujuan).');
        }

        $validated = $request->validate([
            'scope' => 'required|in:all_time,date_range',
            'start_date' => 'nullable|required_if:scope,date_range|date',
            'end_date' => 'nullable|required_if:scope,date_range|date|after_or_equal:start_date',
            'reason' => 'required|string|min:10|max:1000',
        ], [
            'scope.required' => 'Pilih cakupan waktu reset kehadiran.',
            'start_date.required_if' => 'Tanggal awal wajib diisi untuk rentang tanggal.',
            'end_date.required_if' => 'Tanggal akhir wajib diisi untuk rentang tanggal.',
            'end_date.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'reason.required' => 'Alasan pengajuan reset kehadiran wajib diisi secara jelas.',
            'reason.min' => 'Alasan pengajuan minimal 10 karakter.',
        ]);

        $requesterRole = $isDirektur ? 'direktur' : 'manager_hr';

        $resetRequest = AttendanceResetRequest::create([
            'requested_by' => $authUser->id,
            'requester_role' => $requesterRole,
            'scope' => $validated['scope'],
            'start_date' => $validated['scope'] === 'date_range' ? $validated['start_date'] : null,
            'end_date' => $validated['scope'] === 'date_range' ? $validated['end_date'] : null,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Kirim notifikasi ke pihak peninjau (Bilateral)
        $scopeText = $validated['scope'] === 'all_time' 
            ? 'Semua Waktu' 
            : 'Rentang ' . Carbon::parse($validated['start_date'])->isoFormat('D MMM Y') . ' s/d ' . Carbon::parse($validated['end_date'])->isoFormat('D MMM Y');

        if ($isHRManager) {
            // Manager HRD mengajukan -> Notifikasi ke Direktur Utama
            $direkturs = User::where('role', 'direktur')->get();
            foreach ($direkturs as $dir) {
                $dir->notify(new GeneralNotification(
                    'Pengajuan Reset Kehadiran dari Manager HRD',
                    "Manager HRD ({$authUser->name}) mengajukan permohonan reset data kehadiran ({$scopeText}) dengan alasan: {$validated['reason']}. Menunggu persetujuan Anda.",
                    route('attendance.index'),
                    'history_toggle_off',
                    'amber'
                ));
            }
        } else {
            // Direktur mengajukan -> Notifikasi ke Manager HRD
            $hrManagers = User::where('role', 'manager')
                ->where(function ($q) {
                    $q->where('department', 'HR')->orWhere('department', 'hr');
                })
                ->get();
            foreach ($hrManagers as $hrm) {
                $hrm->notify(new GeneralNotification(
                    'Pengajuan Reset Kehadiran dari Direktur Utama',
                    "Direktur Utama ({$authUser->name}) mengajukan permohonan reset data kehadiran ({$scopeText}) dengan alasan: {$validated['reason']}. Menunggu persetujuan Anda.",
                    route('attendance.index'),
                    'history_toggle_off',
                    'amber'
                ));
            }
        }

        return back()->with('success', 'Pengajuan reset kehadiran berhasil diajukan dan sedang menunggu persetujuan dari ' . ($isHRManager ? 'Direktur Utama' : 'Manager HRD') . '.');
    }

    /**
     * Menyetujui pengajuan reset kehadiran dan mengeksekusi penghapusan data.
     * Alur:
     * - Jika pemohon = Manager HRD, maka yang berhak menyetujui HANYA Direktur Utama.
     * - Jika pemohon = Direktur Utama, maka yang berhak menyetujui HANYA Manager HRD.
     * - Anti self-approval ketat: Pembuat pengajuan tidak dapat menyetujui pengajuannya sendiri.
     * - Setelah dieksekusi: Broadcast notifikasi resmi ke SELURUH Karyawan & Manager.
     */
    public function approveReset(Request $request, $id)
    {
        $authUser = Auth::user();
        $resetRequest = AttendanceResetRequest::with('requester')->findOrFail($id);

        if ($resetRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Anti self-approval check
        if ($resetRequest->requested_by === $authUser->id) {
            return back()->with('error', 'Tindakan ditolak! Anda tidak diizinkan menyetujui pengajuan yang Anda buat sendiri.');
        }

        // Validasi hak persetujuan bilateral
        $isHRManager = $authUser->isHR() && $authUser->isManager();
        $isDirektur = $authUser->isDirektur();

        if ($resetRequest->requester_role === 'manager_hr') {
            // Pengajuan dibuat oleh Manager HRD -> Hanya Direktur yang berhak approve
            if (!$isDirektur) {
                return back()->with('error', 'Hanya Direktur Utama yang berhak menyetujui pengajuan reset kehadiran dari Manager HRD.');
            }
        } elseif ($resetRequest->requester_role === 'direktur') {
            // Pengajuan dibuat oleh Direktur -> Hanya Manager HRD yang berhak approve
            if (!$isHRManager) {
                return back()->with('error', 'Hanya Manager HRD yang berhak menyetujui pengajuan reset kehadiran dari Direktur Utama.');
            }
        } else {
            return back()->with('error', 'Format role pengaju tidak valid.');
        }

        // Eksekusi penghapusan data kehadiran seluruh karyawan & manager
        $deletedCount = 0;
        DB::transaction(function () use ($resetRequest, $authUser, &$deletedCount) {
            if ($resetRequest->scope === 'all_time') {
                $deletedCount = Attendance::count();
                Attendance::query()->delete();
            } else {
                $deletedCount = Attendance::whereBetween('date', [
                    $resetRequest->start_date->toDateString(),
                    $resetRequest->end_date->toDateString()
                ])->count();

                Attendance::whereBetween('date', [
                    $resetRequest->start_date->toDateString(),
                    $resetRequest->end_date->toDateString()
                ])->delete();
            }

            $resetRequest->update([
                'status' => 'approved',
                'approved_by' => $authUser->id,
                'action_at' => Carbon::now(),
                'deleted_records_count' => $deletedCount,
            ]);
        });

        // Broadcast Notifikasi Resmi ke SELURUH Karyawan, Manager, & Direktur
        $scopeDetail = $resetRequest->scope === 'all_time'
            ? 'seluruh periode waktu'
            : 'periode ' . $resetRequest->start_date->isoFormat('D MMMM Y') . ' s/d ' . $resetRequest->end_date->isoFormat('D MMMM Y');

        $requesterTitle = $resetRequest->requester_role === 'direktur' ? 'Direktur Utama' : 'Manager HRD';
        $approverTitle = $isDirektur ? 'Direktur Utama' : 'Manager HRD';

        $broadcastTitle = 'Pengumuman Resmi: Reset Data Kehadiran Karyawan & Manager';
        $broadcastMessage = "Data presensi/kehadiran untuk {$scopeDetail} telah resmi direset ({$deletedCount} data terhapus) setelah disetujui oleh {$approverTitle} ({$authUser->name}) atas pengajuan {$requesterTitle} ({$resetRequest->requester?->name}). Alasan reset: \"{$resetRequest->reason}\".";

        $allUsers = User::all();
        foreach ($allUsers as $user) {
            $user->notify(new GeneralNotification(
                $broadcastTitle,
                $broadcastMessage,
                route('attendance.index'),
                'delete_sweep',
                'rose'
            ));
        }

        return back()->with('success', "Pengajuan reset kehadiran berhasil disetujui. Sebanyak {$deletedCount} data kehadiran telah dihapus, dan pengumuman resmi telah disiarkan ke seluruh karyawan dan manager.");
    }

    /**
     * Menolak pengajuan reset kehadiran (Wajib alasan penolakan).
     */
    public function rejectReset(Request $request, $id)
    {
        $authUser = Auth::user();
        $resetRequest = AttendanceResetRequest::with('requester')->findOrFail($id);

        if ($resetRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Anti self-rejection / validation
        if ($resetRequest->requested_by === $authUser->id) {
            return back()->with('error', 'Anda tidak dapat menolak pengajuan Anda sendiri.');
        }

        $isHRManager = $authUser->isHR() && $authUser->isManager();
        $isDirektur = $authUser->isDirektur();

        if ($resetRequest->requester_role === 'manager_hr' && !$isDirektur) {
            return back()->with('error', 'Hanya Direktur Utama yang berhak menolak pengajuan reset kehadiran dari Manager HRD.');
        }

        if ($resetRequest->requester_role === 'direktur' && !$isHRManager) {
            return back()->with('error', 'Hanya Manager HRD yang berhak menolak pengajuan reset kehadiran dari Direktur Utama.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:5|max:1000',
        ], [
            'rejection_reason.required' => 'Alasan penolakan pengajuan wajib disertakan.',
            'rejection_reason.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $resetRequest->update([
            'status' => 'rejected',
            'approved_by' => $authUser->id,
            'rejection_reason' => $validated['rejection_reason'],
            'action_at' => Carbon::now(),
        ]);

        // Beritahu pemohon bahwa pengajuannya ditolak
        if ($resetRequest->requester) {
            $reviewerTitle = $isDirektur ? 'Direktur Utama' : 'Manager HRD';
            $resetRequest->requester->notify(new GeneralNotification(
                'Pengajuan Reset Kehadiran Ditolak',
                "Pengajuan reset kehadiran Anda telah ditolak oleh {$reviewerTitle} ({$authUser->name}). Alasan: \"{$validated['rejection_reason']}\".",
                route('attendance.index'),
                'cancel',
                'rose'
            ));
        }

        return back()->with('success', 'Pengajuan reset kehadiran telah ditolak dan alasan telah disampaikan kepada pemohon.');
    }
}

