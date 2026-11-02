<?php

namespace App\Services;

use App\Models\CalendarSchedule;
use App\Models\Task;
use App\Models\User;
use App\Notifications\GeneralNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderService
{
    /**
     * Memproses seluruh pengingat (jadwal & tugas) untuk user tertentu atau seluruh sistem.
     * Menggunakan tabel notifications untuk mencegah duplikasi notifikasi pada hari/jadwal yang sama.
     */
    public static function processReminders(?int $targetUserId = null): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $calendarRemindersSent = 0;
        $taskRemindersSent = 0;

        // ==========================================
        // 1. REMINDER PENJADWALAN: 1 HARI SEBELUM JADWAL
        // ==========================================
        $scheduleQuery = CalendarSchedule::with(['user', 'creator'])
            ->whereDate('date', $tomorrow->toDateString())
            ->where('approval_status', 'approved');

        $schedules = $scheduleQuery->get();

        foreach ($schedules as $schedule) {
            $scheduleDateFormatted = Carbon::parse($schedule->date)->translatedFormat('d F Y');
            $timeInfo = $schedule->time_start ? " (Pukul {$schedule->time_start}" . ($schedule->time_end ? " - {$schedule->time_end}" : "") . " WIB)" : "";
            
            // Tentukan siapa saja yang harus menerima pengingat jadwal
            $recipients = collect();

            if ($schedule->scope === 'individu') {
                if ($schedule->user) {
                    $recipients->push($schedule->user);
                }
            } elseif ($schedule->scope === 'departemen') {
                // Semua karyawan dalam departemen tersebut + pembuat jadwal
                $deptUsers = User::where('department', $schedule->department)->get();
                $recipients = $recipients->merge($deptUsers);
            } elseif ($schedule->scope === 'antar_departemen') {
                // Manager/anggota dari departemen target dan departemen asal
                $targetDepts = $schedule->getTargetDepartmentsList();
                $allDepts = array_unique(array_merge([$schedule->department], $targetDepts));
                $deptUsers = User::whereIn('department', $allDepts)->get();
                $recipients = $recipients->merge($deptUsers);
            }

            if ($schedule->is_company_wide) {
                $recipients = User::all();
            }

            // Filter target user jika ada parameter $targetUserId
            if ($targetUserId) {
                $recipients = $recipients->where('id', $targetUserId);
            }

            $recipients = $recipients->unique('id');

            foreach ($recipients as $recipient) {
                // Cek apakah pengingat untuk jadwal ini sudah pernah dikirimkan hari ini
                $identifierTag = "schedule_reminder_{$schedule->id}_{$today->toDateString()}";
                $alreadySent = DB::table('notifications')
                    ->where('notifiable_id', $recipient->id)
                    ->where('notifiable_type', User::class)
                    ->where('data', 'like', "%{$identifierTag}%")
                    ->exists();

                if (!$alreadySent) {
                    $recipient->notify(new GeneralNotification(
                        'Pengingat: Jadwal Besok',
                        "Besok ({$scheduleDateFormatted}) ada agenda '{$schedule->title}'{$timeInfo}. [tag:{$identifierTag}]",
                        route('calendar.index', ['date' => $schedule->date->toDateString()]),
                        'event_available',
                        'amber'
                    ));
                    $calendarRemindersSent++;
                }
            }
        }

        // ==========================================
        // 2. REMINDER TUGAS (TASK)
        // Aturan:
        // - Jika selisih hari total (dari created_at ke due_date) > 7 hari:
        //   maka setiap 7 hari sekali ada reminder (sisa hari kelipatan 7, misal: sisa 28, 21, 14, 7 hari).
        // - Jika durasi / sisa deadline < 7 hari (atau mendekati batas akhir):
        //   diingatkan sejak 3 hari sebelum deadline (H-3) dan 1 hari sebelum deadline (H-1).
        // ==========================================
        $tasksQuery = Task::with(['assignedEmployee.user', 'creator.user'])
            ->whereNotIn('status', ['completed'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', $today->toDateString());

        $tasks = $tasksQuery->get();

        foreach ($tasks as $task) {
            $dueDate = Carbon::parse($task->due_date)->startOfDay();
            $createdDate = Carbon::parse($task->created_at)->startOfDay();
            
            // Sisa hari menuju deadline dari hari ini
            $daysLeft = $today->diffInDays($dueDate, false);
            
            // Total durasi tugas sejak dibuat hingga deadline
            $totalSpanDays = $createdDate->diffInDays($dueDate, false);

            $shouldRemind = false;
            $reminderType = '';

            if ($totalSpanDays > 7) {
                // Aturan 1: Jika jangka waktu deadline > 7 hari, maka setiap 7 hari sekali ada reminder
                if ($daysLeft > 0 && ($daysLeft % 7 === 0)) {
                    $shouldRemind = true;
                    $reminderType = "interval_7_days (sisa {$daysLeft} hari)";
                }
            }

            // Aturan 2: Diingatkan sejak 3 hari sebelum deadline dan 1 hari sebelum deadline
            // (berlaku untuk deadline di bawah 7 hari, serta periode kritis H-3 dan H-1)
            if ($daysLeft === 3) {
                $shouldRemind = true;
                $reminderType = "H-3 (sisa 3 hari)";
            } elseif ($daysLeft === 1) {
                $shouldRemind = true;
                $reminderType = "H-1 (besok deadline)";
            }

            if ($shouldRemind) {
                $dueDateFormatted = $dueDate->translatedFormat('d F Y');
                
                // Tentukan penerima: PIC/assigned employee dan pembuat tugas
                $recipients = collect();

                if ($task->assignedEmployee && $task->assignedEmployee->user) {
                    $recipients->push($task->assignedEmployee->user);
                } elseif ($task->scope === 'department') {
                    // Jika tugas departemen tanpa PIC tunggal, kirim ke seluruh user departemen
                    $deptUsers = User::where('department', $task->department)->get();
                    $recipients = $recipients->merge($deptUsers);
                }

                if ($task->creator && $task->creator->user) {
                    $recipients->push($task->creator->user);
                }

                if ($targetUserId) {
                    $recipients = $recipients->where('id', $targetUserId);
                }

                $recipients = $recipients->unique('id');

                foreach ($recipients as $recipient) {
                    $tag = "task_reminder_{$task->id}_{$daysLeft}d_{$today->toDateString()}";
                    $alreadySent = DB::table('notifications')
                        ->where('notifiable_id', $recipient->id)
                        ->where('notifiable_type', User::class)
                        ->where('data', 'like', "%{$tag}%")
                        ->exists();

                    if (!$alreadySent) {
                        $priorityColor = match($daysLeft) {
                            1 => 'rose',
                            3 => 'amber',
                            default => 'blue'
                        };

                        $urgencyText = ($daysLeft === 1) 
                            ? "Tenggat waktu BESOK ({$dueDateFormatted})" 
                            : "Tenggat waktu dalam {$daysLeft} hari ({$dueDateFormatted})";

                        $recipient->notify(new GeneralNotification(
                            "Pengingat Tugas: {$task->title}",
                            "{$urgencyText}. Progres saat ini: {$task->progress_percentage}%. Status: " . strtoupper($task->status) . ". [tag:{$tag}]",
                            route('tasks.index'),
                            'timer',
                            $priorityColor
                        ));
                        $taskRemindersSent++;
                    }
                }
            }
        }

        return [
            'calendar_reminders' => $calendarRemindersSent,
            'task_reminders' => $taskRemindersSent,
            'processed_at' => now()->toDateTimeString(),
        ];
    }
}
