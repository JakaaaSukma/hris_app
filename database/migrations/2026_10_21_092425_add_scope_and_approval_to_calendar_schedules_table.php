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
        Schema::table('calendar_schedules', function (Blueprint $table) {
            $table->string('scope')->default('individu')->after('category'); // individu, departemen, antar_departemen
            $table->string('target_department')->nullable()->after('department'); // departemen tujuan jika antar_departemen
            $table->string('approval_status')->default('approved')->after('scope'); // approved, pending, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_notes')->nullable()->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_schedules', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn([
                'scope',
                'target_department',
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_notes',
            ]);
        });
    }
};
