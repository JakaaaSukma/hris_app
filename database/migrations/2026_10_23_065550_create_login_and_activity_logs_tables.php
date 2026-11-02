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
        // 1. Tabel Session / Device Aktif untuk batas maksimal 3 device bersamaan
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('session_id')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name')->nullable(); // e.g. "Windows - Chrome", "Mac - Safari", "Android - Chrome"
            $table->timestamp('last_active_at')->useCurrent();
            $table->timestamps();
        });

        // 2. Tabel Log Riwayat Login
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('email');
            $table->enum('status', ['success', 'failed', 'logout', 'terminated'])->default('success');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_name')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Log Riwayat Aktivitas Akun
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // e.g. "CREATE_TASK", "SUBMIT_LEAVE", "CHANGE_PASSWORD", dll.
            $table->string('module'); // e.g. "Auth", "Calendar", "Leave", "Profile", dll.
            $table->text('description'); // Penjelasan ringkas aktivitas
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable(); // Payload / data terkait
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('user_devices');
    }
};
