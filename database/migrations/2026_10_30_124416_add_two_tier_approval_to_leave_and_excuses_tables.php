<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi tabel leave_requests
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status VARCHAR(30) DEFAULT 'pending'");
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('manager_approved_by')->nullable()->after('status')->constrained('employees')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->text('manager_note')->nullable()->after('manager_approved_at');
        });

        // 2. Modifikasi tabel attendance_excuses
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE attendance_excuses MODIFY COLUMN status VARCHAR(30) DEFAULT 'pending'");
        }

        Schema::table('attendance_excuses', function (Blueprint $table) {
            $table->foreignId('manager_approved_by')->nullable()->after('status')->constrained('employees')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable()->after('manager_approved_by');
            $table->text('manager_note')->nullable()->after('manager_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['manager_approved_by']);
            $table->dropColumn(['manager_approved_by', 'manager_approved_at', 'manager_note']);
        });

        Schema::table('attendance_excuses', function (Blueprint $table) {
            $table->dropForeign(['manager_approved_by']);
            $table->dropColumn(['manager_approved_by', 'manager_approved_at', 'manager_note']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leave_requests MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
            DB::statement("ALTER TABLE attendance_excuses MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        }
    }
};
