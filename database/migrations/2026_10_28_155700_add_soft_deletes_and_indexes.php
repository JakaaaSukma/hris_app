<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan Soft Deletes pada tabel kritis dan index pada kolom yang sering di-filter.
     */
    public function up(): void
    {
        // 1. Soft Deletes pada tabel kritis
        Schema::table('employees', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->softDeletes();
        });

        // 2. Tambahan Index pada kolom yang sering di-filter untuk optimasi performa
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('date');
            $table->index(['employee_id', 'date']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('employee_id');
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->index('status');
            $table->index('employee_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->index('employee_id');
            $table->index('period_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['status']);
            $table->dropIndex(['employee_id']);
        });

        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['status']);
            $table->dropIndex(['employee_id']);
        });

        Schema::table('salary_slips', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['period_month']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['employee_id', 'date']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
