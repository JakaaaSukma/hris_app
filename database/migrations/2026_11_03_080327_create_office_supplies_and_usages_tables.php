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
        // 1. Tabel Master Stok ATK Kantor (Office Supplies / Consumables)
        Schema::create('office_supplies', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 30)->unique();
            $table->string('name');
            $table->string('category')->default('Alat Tulis'); // Kertas & Buku, Alat Tulis & Koreksi, Filing & Dokumen, Tinta & Printer, Perekat & Pengikat, Aksesoris Meja, Lainnya
            $table->string('unit', 30)->default('Pcs'); // Rim, Pcs, Box, Pack, Lusin, Rol, Set, Buku
            $table->integer('stock_qty')->default(0);
            $table->integer('min_stock')->default(5); // Ambang batas minimum untuk peringatan stok menipis
            $table->string('location')->default('Lemari ATK GA Lt. 1');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Pencatatan Pemakaian / Distribusi ATK Kantor ke Karyawan & Departemen
        Schema::create('office_supply_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_supply_id')->constrained('office_supplies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('department'); // HR, Finance, Bisnis Development, Executive, Resepsionis, Trainer, GA
            $table->integer('quantity')->default(1);
            $table->date('usage_date');
            $table->string('purpose'); // Keperluan penggunaan ATK
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // Staf GA yang melayani & mencatat
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('office_supply_usages');
        Schema::dropIfExists('office_supplies');
    }
};
