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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_daily')->default(false)->after('scope'); // Menandakan apakah tugas adalah daily task
            $table->date('task_date')->nullable()->after('is_daily'); // Tanggal pelaksanaan tugas harian
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['is_daily', 'task_date']);
        });
    }
};
