<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdmsAttendanceController extends Controller
{
    /**
     * Handshake / Inisialisasi dari Mesin Solution X609 (ADMS / Cloud Server)
     * Endpoint: /iclock/cdata?SN=XXXXX
     */
    public function handshake(Request $request)
    {
        $sn = $request->query('SN', 'UNKNOWN_DEVICE');
        Log::info("ADMS Handshake received from Mesin Solution: SN={$sn}");

        // Format respon standar protokol ADMS ZKTeco / Solution
        // ServerVersion=3.0.1, PushVersion=2.0.4, RegistryCode, dll.
        $response = "GET OPTION FROM: {$sn}\n" .
                    "ServerVersion=3.0.1\n" .
                    "ServerName=GyokaiHR-Cloud\n" .
                    "PushVersion=2.0.4\n" .
                    "ErrorDelay=60\n" .
                    "RequestDelay=5\n" .
                    "TransTimes=00:00;14:05\n" .
                    "TransInterval=1\n" .
                    "TransTables=User Transaction\n" .
                    "Realtime=1\n" .
                    "SessionID=" . md5($sn . time()) . "\n";

        return response($response, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Menerima Push Data Log Presensi Real-Time dari Mesin Solution X609
     * Endpoint: POST /iclock/cdata?SN=XXXXX&table=ATTLOG
     */
    public function receiveLog(Request $request)
    {
        $sn = $request->query('SN', 'SOLUTION_X609');
        $table = $request->query('table', 'ATTLOG');
        $rawContent = $request->getContent();

        // Proteksi: Validasi Serial Number (SN) mesin — hanya mesin terdaftar yang bisa push data
        $allowedSerials = array_filter(explode(',', env('ADMS_ALLOWED_SN', '')));
        if (!empty($allowedSerials) && !in_array($sn, $allowedSerials)) {
            Log::warning("ADMS REJECTED: Unregistered device SN={$sn} attempted to push data from IP={$request->ip()}");
            return response("ERROR: Device not registered", 403);
        }

        Log::info("ADMS Log Push from {$sn} (Table: {$table}): " . substr($rawContent, 0, 200));

        // Format data baris ATTLOG pada mesin Solution X609:
        // Contoh: PIN\tTime\tStatus\tVerify\tWorkCode
        // Contoh isi: 101\t2026-10-21 08:15:30\t0\t1\t0
        $lines = preg_split("/\r\n|\n|\r/", trim($rawContent));
        $successCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Pisahkan berdasarkan tab atau spasi
            $parts = preg_split("/\t+|\s{2,}/", $line);
            if (count($parts) < 2) {
                // Alternatif format comma separated
                $parts = explode(',', $line);
            }

            if (count($parts) >= 2) {
                $pin = trim($parts[0]); // User PIN / NIP di mesin
                $timestampStr = trim($parts[1]); // Waktu absen: 2026-10-21 08:15:30
                $statusType = isset($parts[2]) ? (int)trim($parts[2]) : 0; // 0=CheckIn, 1=CheckOut

                try {
                    $dateTime = Carbon::parse($timestampStr);
                    $date = $dateTime->toDateString();
                    $time = $dateTime->toTimeString();

                    // Pencocokan PIN Mesin Finger ke NIP Karyawan Secara AKURAT & KETAT (STRICT):
                    // PIN di mesin finger HARUS sama dengan NIP karyawan di sistem:
                    // 1. Cocok langsung: $employee->nip === $pin (misal '51', '44444', atau 'NEX-00051')
                    // 2. Cocok format prefix standar jika PIN berupa angka murni: 'NEX-' + padded (misal '51' -> 'NEX-00051' / 'NEX-0051')
                    // 3. JIKA TIDAK COCOK SAMA SEKALI DENGAN NIP: Data diabaikan (TIDAK MUNCUL)
                    // DILARANG: mencocokkan id primary key (orWhere('id', pin)) atau substring (like '%pin%')
                    // karena dapat menyebabkan salah orang (misal PIN 41 masuk ke karyawan id=41 [Risma NIP 51] atau PIN 40 masuk ke karyawan id=40 [Rahmat NIP 44444]).
                    $cleanPin = trim($pin);
                    $paddedNip5 = is_numeric($cleanPin) ? 'NEX-' . str_pad((int)$cleanPin, 5, '0', STR_PAD_LEFT) : null;
                    $paddedNip4 = is_numeric($cleanPin) ? 'NEX-' . str_pad((int)$cleanPin, 4, '0', STR_PAD_LEFT) : null;
                    $integerNip = is_numeric($cleanPin) ? (string)(int)$cleanPin : null;

                    $employee = Employee::where(function ($q) use ($cleanPin, $paddedNip5, $paddedNip4, $integerNip) {
                        $q->where('nip', $cleanPin);
                        if ($paddedNip5) {
                            $q->orWhere('nip', $paddedNip5);
                        }
                        if ($paddedNip4) {
                            $q->orWhere('nip', $paddedNip4);
                        }
                        if ($integerNip) {
                            $q->orWhere('nip', $integerNip);
                        }
                    })->first();

                    // Jika PIN tidak cocok dengan NIP karyawan manapun di database,
                    // jangan tampilkan atau buat record presensi (abaikan log tidak valid)
                    if (!$employee) {
                        Log::warning("ADMS Log Diabaikan: PIN {$cleanPin} tidak cocok dengan NIP karyawan manapun di sistem.");
                        continue;
                    }

                    if ($employee) {
                        // Cek apakah sudah ada log kehadiran di tanggal tsb
                        $attendance = Attendance::where('employee_id', $employee->id)
                            ->whereDate('date', $date)
                            ->first();

                        // Toleransi Jam Masuk: Jam kerja 09:00:00 WIB dengan toleransi keterlambatan 15 menit (s.d. 09:15:00 WIB).
                        // Kedatangan <= 09:15:00 dihitung Tepat Waktu (on_time).
                        // Kedatangan > 09:15:00 dihitung Terlambat (late).
                        // Menggunakan perbandingan string waktu murni (H:i:s) untuk menghindari bug beda tanggal Carbon.
                        $isLate = ($time > '09:15:00');

                        // Aturan Jam Masuk & Jam Pulang:
                        // - Jam Kerja: 09.00 s/d 18.00 WIB.
                        // - Jam >= 15:00 WIB atau statusType = 1 menandakan Clock Out (Pulang).
                        // - Jam < 15:00 WIB menandakan Clock In (Masuk).
                        $isDepartureTime = ($statusType == 1 || $dateTime->hour >= 15);

                        if (!$attendance) {
                            if ($isDepartureTime) {
                                // Jika scan pertama kali dilakukan saat jam pulang (>= 15:00 atau >= 18:00 WIB),
                                // catat clock_out dan beri catatan khusus
                                Attendance::create([
                                    'employee_id' => $employee->id,
                                    'date' => $date,
                                    'clock_in' => $time,
                                    'clock_out' => $time,
                                    'status' => 'on_time',
                                    'work_mode' => 'WFO',
                                    'location_name' => "Mesin Finger ({$sn})",
                                    'ip_address' => $request->ip(),
                                    'notes' => "ADMS Solution X609 - PIN: {$pin} (Clock Out)",
                                ]);
                            } else {
                                // Belum ada absen sama sekali hari ini -> Catat Clock In
                                Attendance::create([
                                    'employee_id' => $employee->id,
                                    'date' => $date,
                                    'clock_in' => $time,
                                    'status' => $isLate ? 'late' : 'on_time',
                                    'work_mode' => 'WFO',
                                    'location_name' => "Mesin Finger ({$sn})",
                                    'ip_address' => $request->ip(),
                                    'notes' => "ADMS Fingerprint Solution X609 - PIN: {$pin}",
                                ]);
                            }
                            $successCount++;
                        } else {
                            // Sudah ada record kehadiran hari ini
                            if ($isDepartureTime) {
                                // Presensi Pulang (Clock Out)
                                // Jika jam sudah >= 15:00 (termasuk jam 18:00 ke atas), selalu update waktu pulang (clock_out)
                                $attendance->update([
                                    'clock_out' => $time,
                                ]);
                                $successCount++;
                            } elseif (!$attendance->clock_in) {
                                $attendance->update([
                                    'clock_in' => $time,
                                    'status' => $isLate ? 'late' : 'on_time',
                                ]);
                                $successCount++;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing ADMS record line [{$line}]: " . $e->getMessage());
                }
            }
        }

        // Respon balasan wajib standar Solution ADMS
        return response("OK: {$successCount}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Ping / Heartbeat Mesin: /iclock/ping
     */
    public function ping(Request $request)
    {
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Pengambilan Perintah Server: /iclock/getrequest
     */
    public function getRequest(Request $request)
    {
        // Jika tidak ada perintah antrean untuk mesin, kembalikan OK
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Konfirmasi Eksekusi Perintah: /iclock/devicecmd
     */
    public function deviceCmd(Request $request)
    {
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }
}
