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
        Schema::create('calendar_schedule_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_schedule_id')->constrained('calendar_schedules')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('approved'); // approved, rejected
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['calendar_schedule_id', 'user_id'], 'sched_user_approval_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calendar_schedule_approvals');
    }
};
