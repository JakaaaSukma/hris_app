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
        Schema::table('facility_tickets', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('description'); // URL / path file bukti foto kerusakan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facility_tickets', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
