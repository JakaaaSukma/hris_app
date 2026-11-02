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
        Schema::create('attendance_reset_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Pemohon (Manager HRD atau Direktur)
            $table->enum('requester_role', ['manager_hr', 'direktur']);
            $table->enum('scope', ['all_time', 'date_range', 'single_date'])->default('all_time');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('reason'); // Alasan wajib
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null'); // Penyetuju (Direktur jika pemohon Manager HRD, atau sebaliknya)
            $table->text('rejection_reason')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->unsignedInteger('deleted_records_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reset_requests');
    }
};
