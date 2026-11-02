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
        Schema::create('inventory_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('request_type', ['penambahan', 'perbaikan'])->default('penambahan');
            // Jika perbaikan inventaris yang sudah ada
            $table->foreignId('inventory_item_id')->nullable()->constrained('inventory_items')->nullOnDelete();
            // Nama barang (baik barang baru atau nama barang yang diperbaiki)
            $table->string('item_name');
            $table->string('category')->default('Hardware');
            $table->integer('quantity')->default(1);
            $table->decimal('estimated_cost', 15, 2)->default(0);
            $table->text('reason');
            $table->enum('urgency', ['normal', 'urgent'])->default('normal');
            
            // Pengaju (GA Staff / User)
            $table->foreignId('requested_by')->constrained('employees')->cascadeOnDelete();
            
            // Approval status: pending -> approved / rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_requests');
    }
};
