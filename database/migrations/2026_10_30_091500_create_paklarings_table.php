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
        Schema::create('paklarings', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->nullable();
            $table->string('recipient_name');
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('program_or_position'); // e.g., "Program Pemagangan Kejuruan QC" atau "Staff Accounting"
            $table->string('period_text'); // e.g., "Periode Tanggal 20 Juni 2021 s/d 31 Desember 2025"
            $table->string('company_unit'); // e.g., "PT. Sungshin Best Indonesia"
            $table->string('city')->default('Tangerang');
            $table->date('issue_date');
            $table->string('signatory_name')->default('ANITA SANDRA DEWI');
            $table->string('signatory_title')->default('Direktur');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paklarings');
    }
};
