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
        Schema::table('salary_slips', function (Blueprint $table) {
            if (!Schema::hasColumn('salary_slips', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('salary_slips', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('pdf_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_slips', function (Blueprint $table) {
            if (Schema::hasColumn('salary_slips', 'original_filename')) {
                $table->dropColumn('original_filename');
            }
            if (Schema::hasColumn('salary_slips', 'pdf_path')) {
                $table->dropColumn('pdf_path');
            }
        });
    }
};
