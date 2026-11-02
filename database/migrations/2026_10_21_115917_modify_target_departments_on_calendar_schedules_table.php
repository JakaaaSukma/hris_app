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
            $table->text('target_departments')->nullable()->after('target_department');
        });

        // Migrate existing single target_department data to json array in target_departments
        $existing = \Illuminate\Support\Facades\DB::table('calendar_schedules')
            ->whereNotNull('target_department')
            ->get();

        foreach ($existing as $item) {
            \Illuminate\Support\Facades\DB::table('calendar_schedules')
                ->where('id', $item->id)
                ->update([
                    'target_departments' => json_encode([$item->target_department]),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_schedules', function (Blueprint $table) {
            $table->dropColumn('target_departments');
        });
    }
};
