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
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->string('letter_number', 100)->unique();
            $table->unsignedInteger('sequence_number')->default(1);
            $table->string('letter_category', 50)->default('SKet'); // SK, SPK, ST, UND, SKet, SPm, SPH, MOU, SE, BA, SR, dll
            $table->string('organization_code', 50)->default('LPK-GYOKAI'); // LPK-GYOKAI, DIR-GYOKAI, SEK-GYOKAI, HRD-GYOKAI, FIN-GYOKAI
            $table->string('month_roman', 10)->default('XI');
            $table->unsignedSmallInteger('year')->default(2026);
            $table->date('letter_date');
            $table->string('recipient', 255); // Ditujukan Kepada (Nama Orang / Mitra / Instansi)
            $table->text('recipient_address')->nullable(); // Alamat / Kota Penerima
            $table->string('subject', 255); // Perihal / Hal Surat
            $table->text('regarding_summary')->nullable(); // Ringkasan Isi Surat
            $table->enum('urgency', ['biasa', 'penting', 'segera', 'rahasia'])->default('biasa');
            $table->enum('status', ['draft', 'reserved', 'signed', 'sent', 'archived'])->default('signed');
            $table->string('signer_name', 150)->default('Hendra Wijaya');
            $table->string('signer_title', 150)->default('Direktur Utama');
            $table->string('file_path', 255)->nullable(); // Berkas digital PDF/Scan surat bertanda tangan
            $table->text('notes')->nullable(); // Catatan resi, ekspedisi, atau kearsipan
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['year', 'sequence_number']);
            $table->index('letter_date');
            $table->index('letter_category');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
