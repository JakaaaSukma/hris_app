<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceApiController extends Controller
{
    /**
     * Get Today's Attendance Status & Information
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 422);
        }

        $today = Carbon::today()->toDateString();
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        $officeLat = (float) config('app.office_latitude', env('OFFICE_LATITUDE', -6.2614075));
        $officeLng = (float) config('app.office_longitude', env('OFFICE_LONGITUDE', 106.4433519));
        $officeRadius = (int) config('app.office_radius_meters', env('OFFICE_RADIUS_METERS', 500));

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today,
                'day_name' => Carbon::today()->locale('id')->isoFormat('dddd, D MMMM Y'),
                'server_time' => Carbon::now()->format('H:i:s'),
                'is_clocked_in' => (bool) ($attendance && $attendance->clock_in),
                'is_clocked_out' => (bool) ($attendance && $attendance->clock_out),
                'attendance' => $attendance ? [
                    'id' => $attendance->id,
                    'date' => $attendance->date,
                    'clock_in' => $attendance->clock_in ? substr($attendance->clock_in, 0, 5) : null,
                    'clock_out' => $attendance->clock_out ? substr($attendance->clock_out, 0, 5) : null,
                    'status' => $attendance->status,
                    'work_mode' => $attendance->work_mode,
                    'location_name' => $attendance->location_name,
                    'latitude' => $attendance->latitude,
                    'longitude' => $attendance->longitude,
                    'distance_meters' => $attendance->distance_meters,
                    'photo_in_url' => $attendance->photo_in_url,
                    'photo_out_url' => $attendance->photo_out_url,
                    'google_maps_url' => $attendance->google_maps_url,
                    'verification_method' => $attendance->verification_method,
                    'notes' => $attendance->notes,
                ] : null,
                'office_config' => [
                    'office_name' => config('app.office_name', env('OFFICE_NAME', 'SMK & LPK Gyokai Indonesia')),
                    'office_address' => config('app.office_address', env('OFFICE_ADDRESS', 'Jl.Pesona Wibawa Praja, Jeungjing, Kec. Cisoka, Kabupaten Tangerang, Banten 15730')),
                    'latitude' => $officeLat,
                    'longitude' => $officeLng,
                    'radius_meters' => $officeRadius,
                    'work_start_limit' => '09:15:00',
                ],
            ],
        ]);
    }

    /**
     * Record Face ID Attendance (Clock In / Clock Out) with GPS & Anti-Mock check
     */
    public function faceRecord(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan untuk akun ini.',
            ], 422);
        }

        // 1. Anti-Mock GPS Validation
        if ($request->boolean('is_mocked') || $request->input('is_mocked') === '1' || $request->input('is_mocked') === true) {
            return response()->json([
                'success' => false,
                'message' => 'Peringatan Keamanan: Terdeteksi penggunaan Fake GPS / Lokasi Palsu. Presensi ditolak demi menjaga integritas data operasional.',
            ], 422);
        }

        $request->validate([
            'photo' => 'nullable|string', // base64 string
            'file' => 'nullable|image|max:10240', // multipart file
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'type' => 'nullable|in:clock_in,clock_out,auto',
            'work_mode' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!$request->filled('photo') && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Foto presensi wajah wajib disertakan (baik via base64 atau unggahan file).',
            ], 422);
        }

        $today = Carbon::today()->toDateString();
        $now = Carbon::now();
        $nowTime = $now->toTimeString();

        // Cek data presensi hari ini
        $attendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        // Tentukan tipe aksi (auto / manual)
        $requestedType = $request->input('type', 'auto');
        if ($requestedType === 'auto') {
            if (!$attendance || !$attendance->clock_in) {
                $actionType = 'clock_in';
            } elseif (!$attendance->clock_out) {
                $actionType = 'clock_out';
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda telah menyelesaikan presensi masuk dan pulang untuk hari ini.',
                ], 422);
            }
        } else {
            $actionType = $requestedType;
        }

        // Simpan file foto wajah
        $folder = 'attendance_photos/' . $now->format('Y-m');
        $imgExt = 'jpg';
        $fileName = $folder . '/' . $employee->nip . '_' . $actionType . '_' . time() . '.' . $imgExt;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $imgExt = $file->getClientOriginalExtension() ?: 'jpg';
            $fileName = $folder . '/' . $employee->nip . '_' . $actionType . '_' . time() . '.' . $imgExt;
            Storage::disk('public')->put($fileName, file_get_contents($file->getRealPath()));
        } else {
            $base64Image = $request->input('photo');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $imgExt = strtolower($type[1]) === 'png' ? 'png' : 'jpg';
            }
            $decodedImage = base64_decode($base64Image);
            if (!$decodedImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format foto wajah tidak valid atau rusak.',
                ], 422);
            }
            $fileName = $folder . '/' . $employee->nip . '_' . $actionType . '_' . time() . '.' . $imgExt;
            Storage::disk('public')->put($fileName, $decodedImage);
        }

        // Koordinat Geofencing GPS Kantor Pusat (SMK & LPK Gyokai Cisoka)
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $officeLat = (float) config('app.office_latitude', env('OFFICE_LATITUDE', -6.2614075));
        $officeLng = (float) config('app.office_longitude', env('OFFICE_LONGITUDE', 106.4433519));
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
                $detectedLocation = "SMK & LPK Gyokai Indonesia ({$distanceMeters}m dari titik pusat)";
            } elseif ($distanceMeters !== null) {
                $detectedLocation = "Luar Radius Kantor ({$distanceMeters}m dari kantor)";
            } else {
                $detectedLocation = "Lokasi Geolocation Mobile";
            }
        }

        $workMode = $request->input('work_mode') ?: ($inOfficeRadius ? 'WFO' : 'WFH / Dinas Luar');
        $userNotes = $request->input('notes') ? trim($request->input('notes')) . " • " : "";
        $deviceInfo = $request->input('device_info') ?: $request->header('User-Agent');

        if ($actionType === 'clock_in') {
            if ($attendance && $attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan Presensi Masuk (Clock In) hari ini pada pukul ' . substr($attendance->clock_in, 0, 5) . ' WIB.',
                ], 422);
            }

            // Batas Masuk: 09:15:00 WIB
            $status = ($nowTime > '09:15:00') ? 'late' : 'on_time';
            $faceTag = "[Face ID Mobile Masuk: " . ($inOfficeRadius ? "Dalam Kantor ({$distanceMeters}m)" : "Luar Radius ({$distanceMeters}m)") . "]";
            $finalNotes = $userNotes . $faceTag;

            $attendance = Attendance::updateOrCreate(
                ['employee_id' => $employee->id, 'date' => $today],
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
                    'device_info' => substr($deviceInfo ?? 'Android Mobile App', 0, 250),
                    'ip_address' => $request->ip(),
                    'notes' => $finalNotes,
                ]
            );

            return response()->json([
                'success' => true,
                'action' => 'clock_in',
                'message' => 'Presensi Masuk Berhasil Terverifikasi! Wajah & GPS tercatat.',
                'data' => [
                    'id' => $attendance->id,
                    'date' => $today,
                    'time' => substr($nowTime, 0, 5) . ' WIB',
                    'status' => $status,
                    'status_label' => $status === 'on_time' ? 'Tepat Waktu' : 'Terlambat',
                    'distance_meters' => $distanceMeters,
                    'in_radius' => $inOfficeRadius,
                    'location' => $detectedLocation,
                    'photo_url' => $attendance->photo_in_url ?? Storage::disk('public')->url($fileName),
                ],
            ]);
        } else {
            // Clock Out
            if (!$attendance || !$attendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda belum melakukan Presensi Masuk hari ini. Silakan Clock In terlebih dahulu.',
                ], 422);
            }

            if ($attendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah melakukan Presensi Pulang (Clock Out) hari ini pada pukul ' . substr($attendance->clock_out, 0, 5) . ' WIB.',
                ], 422);
            }

            $faceTag = "[Face ID Mobile Pulang: " . ($inOfficeRadius ? "Dalam Kantor ({$distanceMeters}m)" : "Luar Radius ({$distanceMeters}m)") . "]";
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
                'message' => 'Presensi Pulang Berhasil Terverifikasi! Wajah & GPS tercatat.',
                'data' => [
                    'id' => $attendance->id,
                    'date' => $today,
                    'time' => substr($nowTime, 0, 5) . ' WIB',
                    'distance_meters' => $distanceMeters,
                    'in_radius' => $inOfficeRadius,
                    'location' => $detectedLocation,
                    'photo_url' => $attendance->photo_out_url ?? Storage::disk('public')->url($fileName),
                ],
            ]);
        }
    }

    /**
     * Get Attendance History (Monthly / Filtered)
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Profil karyawan tidak ditemukan.',
            ], 422);
        }

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $query = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'desc');

        $attendances = $query->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'date' => $item->date,
                'clock_in' => $item->clock_in ? substr($item->clock_in, 0, 5) : null,
                'clock_out' => $item->clock_out ? substr($item->clock_out, 0, 5) : null,
                'status' => $item->status,
                'status_label' => $item->status === 'on_time' ? 'Tepat Waktu' : ($item->status === 'late' ? 'Terlambat' : 'Absen'),
                'work_mode' => $item->work_mode,
                'location_name' => $item->location_name,
                'distance_meters' => $item->distance_meters,
                'photo_in_url' => $item->photo_in_url,
                'photo_out_url' => $item->photo_out_url,
                'verification_method' => $item->verification_method,
                'notes' => $item->notes,
            ];
        });

        $totalHadir = $attendances->whereNotNull('clock_in')->count();
        $totalTepatWaktu = $attendances->where('status', 'on_time')->count();
        $totalTerlambat = $attendances->where('status', 'late')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'statistics' => [
                    'total_present' => $totalHadir,
                    'total_on_time' => $totalTepatWaktu,
                    'total_late' => $totalTerlambat,
                ],
                'records' => $attendances,
            ],
        ]);
    }

    /**
     * Get Geofence Configuration for Mobile App
     */
    public function config(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'office_name' => config('app.office_name', env('OFFICE_NAME', 'SMK & LPK Gyokai Indonesia')),
                'office_address' => config('app.office_address', env('OFFICE_ADDRESS', 'Jl.Pesona Wibawa Praja, Jeungjing, Kec. Cisoka, Kabupaten Tangerang, Banten 15730')),
                'latitude' => (float) config('app.office_latitude', env('OFFICE_LATITUDE', -6.2614075)),
                'longitude' => (float) config('app.office_longitude', env('OFFICE_LONGITUDE', 106.4433519)),
                'radius_meters' => (int) config('app.office_radius_meters', env('OFFICE_RADIUS_METERS', 500)),
                'work_start_limit' => '09:15:00',
                'server_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'timezone' => config('app.timezone', 'Asia/Jakarta'),
            ],
        ]);
    }

    /**
     * Hitung Jarak Fisik antara 2 Titik Koordinat (Haversine Formula) dalam Meter
     */
    protected function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // Meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }
}

