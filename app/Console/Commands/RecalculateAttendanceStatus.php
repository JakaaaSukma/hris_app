<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RecalculateAttendanceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:recalculate-status {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitung ulang status presensi (on_time vs late) secara akurat berdasarkan batas jam masuk 09:00:00 WIB dan toleransi 15 menit (09:15:00 WIB)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date');
        $query = \App\Models\Attendance::whereNotNull('clock_in');

        if ($date) {
            $query->whereDate('date', $date);
        }

        $records = $query->get();
        $updatedCount = 0;

        foreach ($records as $att) {
            // Hanya evaluasi record yang statusnya bukan cuti, izin, atau alpa
            if (in_array($att->status, ['leave', 'permit', 'absent'])) {
                continue;
            }

            $clockInTime = substr($att->clock_in, 0, 8);
            // Datang <= 09:15:00 WIB adalah Tepat Waktu (on_time), > 09:15:00 WIB adalah Terlambat (late)
            $expectedStatus = ($clockInTime > '09:15:00') ? 'late' : 'on_time';

            if ($att->status !== $expectedStatus) {
                $att->update(['status' => $expectedStatus]);
                $updatedCount++;
                $empName = $att->employee ? $att->employee->name : 'Karyawan';
                $this->line("Update ID {$att->id} ({$empName}): Clock In {$clockInTime} => Status diubah ke {$expectedStatus}");
            }
        }

        $this->info("Selesai! {$updatedCount} data presensi berhasil disinkronisasi statusnya.");
        return 0;
    }
}
