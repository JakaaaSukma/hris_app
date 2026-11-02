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
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->string('slip_number')->unique();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('period_month'); // Contoh: "Oktober 2026" atau "2026-10"
            $table->date('payment_date');
            
            // Komponen Pendapatan (Earnings)
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowance_position', 15, 2)->default(0); // Tunjangan Jabatan
            $table->decimal('allowance_transport', 15, 2)->default(0); // Tunjangan Transport
            $table->decimal('allowance_meal', 15, 2)->default(0); // Tunjangan Makan
            $table->decimal('overtime_pay', 15, 2)->default(0); // Lembur
            $table->decimal('bonus', 15, 2)->default(0); // Bonus / Insentif

            // Komponen Potongan (Deductions)
            $table->decimal('deduction_bpjs_tk', 15, 2)->default(0); // BPJS Ketenagakerjaan
            $table->decimal('deduction_bpjs_kes', 15, 2)->default(0); // BPJS Kesehatan
            $table->decimal('deduction_tax_pph21', 15, 2)->default(0); // PPh 21
            $table->decimal('deduction_absence', 15, 2)->default(0); // Potongan Keterlambatan / Alpha
            $table->decimal('deduction_other', 15, 2)->default(0); // Potongan Lainnya

            // Total Ringkasan
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0); // Gaji Bersih (Take Home Pay)

            // Status & Catatan
            $table->enum('status', ['draft', 'published', 'paid'])->default('paid');
            $table->string('payment_method')->default('Bank Transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
