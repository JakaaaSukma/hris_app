<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_excuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            // Jenis: late_permission (Izin Terlambat), duty_trip (Izin Dinas Luar), missed_clock_out (Keterangan Tidak Absen Pulang)
            $table->enum('type', ['late_permission', 'duty_trip', 'missed_clock_out']);
            $table->date('date');
            $table->time('expected_time')->nullable(); // Perkiraan jam hadir atau jam keluar aktual
            $table->text('reason'); // Alasan / keterangan dinas luar / kendala tidak absen pulang
            $table->string('attachment_url')->nullable(); // Dokumen pendukung jika ada
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('action_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['employee_id', 'date']);
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_excuses');
    }
};
