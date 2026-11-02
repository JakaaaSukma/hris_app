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
        Schema::create('calendar_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->string('title');
            $table->text('description')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('category')->default('catatan'); // kunjungan, meeting, tugas, catatan, pengumuman, cuti
            $table->string('color')->default('rose'); // rose, emerald, amber, blue, purple, cyan
            $table->boolean('is_company_wide')->default(false); // jika true, terlihat oleh seluruh akun
            $table->string('department')->nullable(); // jika null/spesifik departemen
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['date', 'user_id']);
            $table->index(['date', 'is_company_wide']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_schedules');
    }
};
