<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoMarkAbsentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:auto-absent {--date= : Tanggal yang akan diproses (format: YYYY-MM-DD, default: hari ini)} {--force : Jalankan tanpa menghiraukan hari libur akhir pekan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatis menandai karyawan aktif yang tidak memiliki catatan presensi masuk sebagai absent (Alpa) atau leave/permit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateInput = $this->option('date');
        $date = $dateInput ? Carbon::parse($dateInput) : Carbon::today();
        $dateStr = $date->toDateString();

        // Cek jika hari libur akhir pekan (Sabtu / Minggu) kecuali ada opsi --force
        if (!$this->option('force') && $date->isWeekend()) {
            $this->info("Hari {$date->translatedFormat('l, d M Y')} adalah akhir pekan (hari libur kerja). Proses auto-absent dilewati.");
            return Command::SUCCESS;
        }

        $this->info("Memulai proses auto-absent untuk tanggal: {$dateStr}...");

        // Ambil seluruh karyawan aktif
        $activeEmployees = Employee::where('status', 'active')->get();

        $absentCount = 0;
        $leaveCount = 0;
        $skippedCount = 0;

        foreach ($activeEmployees as $emp) {
            // Cek apakah sudah ada catatan absensi di tanggal tersebut
            $existingAttendance = Attendance::where('employee_id', $emp->id)
                ->whereDate('date', $dateStr)
                ->first();

            if ($existingAttendance) {
                // Jika sudah ada clock in atau sudah berstatus tertentu, lewati
                $skippedCount++;
                continue;
            }

            // Cek apakah karyawan memiliki permohonan cuti / izin yang disetujui (approved) pada tanggal ini
            $activeLeave = LeaveRequest::where('employee_id', $emp->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->first();

            if ($activeLeave) {
                // Karyawan sedang Cuti atau Izin resmi
                $status = ($activeLeave->type === 'Sakit' || $activeLeave->type === 'Izin') ? 'permit' : 'leave';

                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $dateStr,
                    'clock_in' => null,
                    'clock_out' => null,
                    'status' => $status,
                    'work_mode' => 'WFO',
                    'location_name' => 'Sistem Otomatis (HRIS)',
                    'notes' => "Auto System: Terjadwal {$activeLeave->type} ({$activeLeave->reason})",
                ]);

                $leaveCount++;
            } else {
                // Karyawan tidak hadir tanpa keterangan / Alpa (Absent)
                Attendance::create([
                    'employee_id' => $emp->id,
                    'date' => $dateStr,
                    'clock_in' => null,
                    'clock_out' => null,
                    'status' => 'absent',
                    'work_mode' => 'WFO',
                    'location_name' => 'Sistem Otomatis (HRIS)',
                    'notes' => 'Auto System: Tidak melakukan presensi masuk hingga jam kerja berakhir (Alpa)',
                ]);

                $absentCount++;
            }
        }

        $this->info("Selesai memproses kehadiran tanggal {$dateStr}:");
        $this->line("- Pegawai Hadir / Sudah Tercatat: {$skippedCount}");
        $this->line("- Pegawai Cuti / Izin Terjadwal: {$leaveCount}");
        $this->line("- Pegawai Tidak Hadir (Alpa/Absent): {$absentCount}");

        return Command::SUCCESS;
    }
}
