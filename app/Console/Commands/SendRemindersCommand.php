<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim otomatis pengingat jadwal (H-1) dan pengingat deadline tugas (siklus 7 hari, H-3, dan H-1)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengecekan pengingat jadwal dan tugas...');
        
        $result = ReminderService::processReminders();

        $this->info("Berhasil memproses pengingat:");
        $this->line("- Notifikasi Jadwal (H-1): {$result['calendar_reminders']} terkirim");
        $this->line("- Notifikasi Tugas: {$result['task_reminders']} terkirim");

        return Command::SUCCESS;
    }
}
