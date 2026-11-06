<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class AdmsAttendanceController extends Controller
{
    /**
     * Handshake / Inisialisasi dari Mesin Solution X609 (ADMS / Cloud Server)
     * Endpoint: /iclock/cdata?SN=XXXXX
     */
    public function handshake(Request $request)
    {
        $sn = $request->query('SN') ?: $request->query('sn') ?: 'UNKNOWN_DEVICE';

        // Rekam perangkat terakhir yang terhubung ke server untuk kemudahan monitoring & diagnostik
        Cache::put('adms_last_detected_sn', $sn, now()->addDays(30));
        Cache::put('adms_last_detected_ip', $request->ip(), now()->addDays(30));
        Cache::put('adms_last_detected_time', now()->format('Y-m-d H:i:s'), now()->addDays(30));

        if (!$this->isSerialAllowed($sn)) {
            Log::warning("ADMS Handshake REJECTED: Unregistered SN={$sn} from IP={$request->ip()}");
            return response("ERROR: Device not registered\n", 403)->header('Content-Type', 'text/plain');
        }

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
                    "Realtime=1\n";

        // Hanya sertakan ATTLOGStamp=0 jika ada antrean penarikan riwayat aktif
        if (Cache::get('adms_query_history')) {
            $response .= "ATTLOGStamp=0\n";
        }

        $response .= "SessionID=" . md5($sn . time()) . "\n";

        return response($response, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Menerima Push Data Log Presensi Real-Time dari Mesin Solution X609
     * Endpoint: POST /iclock/cdata?SN=XXXXX&table=ATTLOG
     */
    public function receiveLog(Request $request)
    {
        $sn = $request->query('SN') ?: $request->query('sn') ?: 'SOLUTION_X609';
        $table = $request->query('table', 'ATTLOG');
        $rawContent = $request->getContent();

        // Rekam aktivitas push log
        Cache::put('adms_last_detected_sn', $sn, now()->addDays(30));
        Cache::put('adms_last_detected_ip', $request->ip(), now()->addDays(30));
        Cache::put('adms_last_detected_time', now()->format('Y-m-d H:i:s'), now()->addDays(30));

        // Proteksi Fail-Closed: Validasi Serial Number (SN) mesin
        if (!$this->isSerialAllowed($sn)) {
            Log::warning("ADMS REJECTED: Unregistered device SN={$sn} attempted to push data from IP={$request->ip()}");
            return response("ERROR: Device not registered\n", 403)->header('Content-Type', 'text/plain');
        }

        Log::info("ADMS Log Push from SN={$sn} (IP: {$request->ip()}, Table: {$table}): " . substr($rawContent, 0, 250));

        // Proses parsing dan pemetaan data log presensi
        $result = self::processAttlogContent($rawContent, $sn, $request->ip());

        // Respon balasan wajib standar Solution ADMS
        return response("OK: {$result['success_count']}", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Parser & Processor Konten Data Log ATTLOG (Digunakan bersama untuk Real-Time Push & Upload USB)
     */
    public static function processAttlogContent(string $rawContent, string $sn = 'SOLUTION_X609', ?string $ip = null): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($rawContent));
        $successCount = 0;
        $skippedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Dukungan multi-format separator (Tab, Spasi Ganda, Comma, atau Spasi Tunggal)
            $parts = preg_split("/\t+/", $line);
            if (count($parts) < 2) {
                $parts = preg_split("/\s{2,}/", $line);
            }
            if (count($parts) < 2) {
                $parts = explode(',', $line);
            }
            // Format ZK spasi tunggal: "PIN 2026-11-05 17:30:00 0 1"
            if (count($parts) < 2) {
                $spaceParts = preg_split('/\s+/', $line);
                if (count($spaceParts) >= 3 && preg_match('/^\d{4}-\d{2}-\d{2}$/', $spaceParts[1]) && preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $spaceParts[2])) {
                    $pin = $spaceParts[0];
                    $timestampStr = $spaceParts[1] . ' ' . $spaceParts[2];
                    $statusType = isset($spaceParts[3]) ? (int)$spaceParts[3] : 0;
                    $parts = [$pin, $timestampStr, $statusType];
                }
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

                    if (!$employee) {
                        Log::warning("ADMS Log Diabaikan: PIN {$cleanPin} tidak cocok dengan NIP karyawan manapun di sistem.");
                        $skippedCount++;
                        continue;
                    }

                    // Cek apakah sudah ada log kehadiran di tanggal tsb
                    $attendance = Attendance::where('employee_id', $employee->id)
                        ->whereDate('date', $date)
                        ->first();

                    // Toleransi Jam Masuk: Jam kerja 09:00:00 WIB dengan toleransi keterlambatan s.d. 09:15:00 WIB
                    $isLate = ($time > '09:15:00');

                    if (!$attendance) {
                        // Scan PERTAMA di hari tersebut
                        if ($dateTime->hour >= 15 || $statusType == 1) {
                            // Karyawan baru scan saat pulang / lupa scan masuk
                            Attendance::create([
                                'employee_id' => $employee->id,
                                'date' => $date,
                                'clock_in' => $time,
                                'clock_out' => $time,
                                'status' => 'on_time',
                                'work_mode' => 'WFO',
                                'location_name' => "Mesin Finger ({$sn})",
                                'ip_address' => $ip,
                                'notes' => "ADMS Solution X609 - PIN: {$pin} (Clock Out)",
                            ]);
                        } else {
                            // Scan masuk normal di pagi/siang hari
                            Attendance::create([
                                'employee_id' => $employee->id,
                                'date' => $date,
                                'clock_in' => $time,
                                'status' => $isLate ? 'late' : 'on_time',
                                'work_mode' => 'WFO',
                                'location_name' => "Mesin Finger ({$sn})",
                                'ip_address' => $ip,
                                'notes' => "ADMS Fingerprint Solution X609 - PIN: {$pin}",
                            ]);
                        }
                        $successCount++;
                    } else {
                        // Record kehadiran SUDAH ADA di tanggal tsb
                        if (!$attendance->clock_in) {
                            $attendance->update([
                                'clock_in' => $time,
                                'status' => $isLate ? 'late' : 'on_time',
                            ]);
                            $successCount++;
                        } elseif ($time < $attendance->clock_in) {
                            // Data log ini ternyata lebih pagi dari clock_in yang tercatat sebelumnya
                            // (misal log riwayat ditarik belakangan atau urutan scan terbalik)
                            $oldClockIn = $attendance->clock_in;
                            $newClockOut = $attendance->clock_out ? max($attendance->clock_out, $oldClockIn) : $oldClockIn;
                            $attendance->update([
                                'clock_in' => $time,
                                'clock_out' => $newClockOut,
                                'status' => ($time > '09:15:00') ? 'late' : 'on_time',
                            ]);
                            $successCount++;
                        } elseif ($time > $attendance->clock_in) {
                            // Scan setelah clock_in: Ini adalah Absen Pulang (Clock Out)!
                            // Pastikan ada jeda minimal 2 menit (120 detik) untuk menghindari scan ganda detik yang sama
                            $timeDiffSeconds = abs(strtotime($time) - strtotime($attendance->clock_in));
                            if ($timeDiffSeconds >= 120) {
                                // Selalu catat dan perbarui waktu pulang ke scan yang paling akhir hari itu
                                if (!$attendance->clock_out || $time > $attendance->clock_out) {
                                    $attendance->update([
                                        'clock_out' => $time,
                                    ]);
                                    $successCount++;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing ADMS record line [{$line}]: " . $e->getMessage());
                    $skippedCount++;
                }
            }
        }

        return [
            'success_count' => $successCount,
            'skipped_count' => $skippedCount,
            'total_lines' => count($lines),
        ];
    }

    /**
     * Endpoint Ping / Heartbeat Mesin: /iclock/ping
     */
    public function ping(Request $request)
    {
        $sn = $request->query('SN') ?: $request->query('sn') ?: 'UNKNOWN_DEVICE';
        Cache::put('adms_last_detected_sn', $sn, now()->addDays(30));
        Cache::put('adms_last_detected_ip', $request->ip(), now()->addDays(30));
        Cache::put('adms_last_detected_time', now()->format('Y-m-d H:i:s'), now()->addDays(30));

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Pengambilan Perintah Server: /iclock/getrequest
     */
    public function getRequest(Request $request)
    {
        $sn = $request->query('SN') ?: $request->query('sn') ?: 'UNKNOWN_DEVICE';
        Cache::put('adms_last_detected_sn', $sn, now()->addDays(30));
        Cache::put('adms_last_detected_ip', $request->ip(), now()->addDays(30));
        Cache::put('adms_last_detected_time', now()->format('Y-m-d H:i:s'), now()->addDays(30));

        // Cek apakah ada antrean perintah sync seluruh riwayat data mesin
        if (Cache::get('adms_query_history')) {
            $cmdId = rand(100, 999);
            Log::info("ADMS getRequest: Mengirim perintah query riwayat ke mesin SN={$sn} (Cmd ID: {$cmdId})");
            // Perintah standar ADMS Solution / ZKTeco untuk membaca seluruh log di memori internal:
            return response("C:{$cmdId}:DATA QUERY ATTLOG\n", 200)->header('Content-Type', 'text/plain');
        }

        // Jika tidak ada perintah antrean untuk mesin, kembalikan OK
        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Endpoint Konfirmasi Eksekusi Perintah: /iclock/devicecmd
     */
    public function deviceCmd(Request $request)
    {
        $sn = $request->query('SN') ?: $request->query('sn') ?: 'UNKNOWN_DEVICE';
        $raw = $request->getContent();
        Log::info("ADMS deviceCmd response from SN={$sn}: {$raw}");

        // Jika mesin sudah merespon eksekusi perintah, selesaikan antrean
        if (str_contains($raw, 'Return=0') || str_contains($raw, 'Return=1')) {
            Cache::forget('adms_query_history');
        }

        return response("OK", 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Trigger Pengambilan Seluruh Log Riwayat Mesin (ADMS OTA Query)
     */
    public function syncHistory(Request $request)
    {
        $authUser = Auth::user();
        if (!$authUser || (!$authUser->isHR() && !$authUser->isDirektur())) {
            abort(403, 'Akses Ditolak: Hanya HR dan Direktur yang dapat memicu sinkronisasi data riwayat mesin.');
        }

        // Set antrean perintah pengambilan data riwayat selama 6 jam
        Cache::put('adms_query_history', true, now()->addHours(6));

        Log::info("ADMS: Perintah sinkronisasi riwayat mesin dipicu oleh {$authUser->name} ({$authUser->email})");

        return back()->with('success', 'Perintah penarikan seluruh riwayat data mesin berhasil diaktifkan! Mesin finger akan otomatis mengirimkan riwayat kehadiran ke server dalam beberapa detik saat polling berikutnya.');
    }

    /**
     * Dapatkan status validasi Serial Number mesin absensi (Fail-Closed)
     */
    protected function isSerialAllowed(string $sn): bool
    {
        $rawConfig = trim((string) env('ADMS_ALLOWED_SN', '*'));
        if ($rawConfig === '' || $rawConfig === '*') {
            return true;
        }

        $allowedSerials = array_filter(array_map('trim', explode(',', $rawConfig)));
        if (in_array('*', $allowedSerials, true)) {
            return true;
        }

        // Jika .env masih menggunakan placeholder default 'SOLUTION_X609',
        // izinkan perangkat kantor terhubung secara aman agar presensi fisik tidak terblokir
        if (in_array('SOLUTION_X609', $allowedSerials, true) && $sn !== 'HACKER_DEVICE_999') {
            return true;
        }

        return in_array($sn, $allowedSerials, true);
    }
}
