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
        Schema::create('role_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Akun yang dituju
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Pemohon (Manager HR)
            $table->string('current_role'); // Role saat pengajuan (karyawan / manager)
            $table->string('target_role'); // Role yang dituju (manager / karyawan)
            $table->text('reason'); // Alasan promosi / demosi status akun
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Direktur yang menyetujui / menolak
            $table->text('rejection_reason')->nullable(); // Alasan jika ditolak oleh Direktur
            $table->timestamp('action_at')->nullable(); // Tanggal approval/rejection
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_change_requests');
    }
};
