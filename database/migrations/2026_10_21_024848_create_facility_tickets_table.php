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
        Schema::create('facility_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // e.g. TKT-GA-2026-0001
            $table->foreignId('reporter_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title'); // Judul kendala / kerusakan
            $table->enum('category', [
                'Perangkat IT / Laptop',
                'Listrik & Pencahayaan',
                'AC & Pendingin Ruangan',
                'Plumbing & Toilet',
                'Furniture & Meja/Kursi',
                'Jaringan Internet & Wifi',
                'Fasilitas Umum Kantor',
                'Lainnya'
            ])->default('Fasilitas Umum Kantor');
            $table->string('location'); // Lokasi spesifik kejadian / ruang
            $table->text('description'); // Penjelasan kendala
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Progres penanganan oleh Tim GA
            // Status: open -> in_progress -> resolved -> closed
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->integer('progress_percentage')->default(0);
            $table->foreignId('handled_by')->nullable()->constrained('employees')->nullOnDelete(); // Petugas GA penanggung jawab
            $table->text('resolution_notes')->nullable(); // Catatan perbaikan dari tim GA
            $table->timestamp('resolved_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_tickets');
    }
};
