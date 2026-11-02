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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('role_title');
            $table->string('department');
            $table->enum('employment_type', ['Tetap (FTE)', 'Kontrak', 'Magang', 'Probation'])->default('Tetap (FTE)');
            $table->enum('status', ['active', 'on_leave', 'probation', 'inactive'])->default('active');
            $table->date('join_date');
            $table->text('avatar_url')->nullable();
            $table->string('location')->default('Headquarters (Jakarta)');
            $table->integer('annual_leave_quota')->default(12);
            $table->integer('annual_leave_used')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
