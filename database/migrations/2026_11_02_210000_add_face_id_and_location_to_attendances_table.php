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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location_name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('photo_in')->nullable()->after('longitude');
            $table->string('photo_out')->nullable()->after('photo_in');
            $table->integer('distance_meters')->nullable()->after('photo_out');
            $table->string('verification_method')->default('adms_fingerprint')->after('distance_meters');
            $table->string('device_info')->nullable()->after('verification_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'photo_in',
                'photo_out',
                'distance_meters',
                'verification_method',
                'device_info',
            ]);
        });
    }
};
