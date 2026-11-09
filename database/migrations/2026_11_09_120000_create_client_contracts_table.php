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
        Schema::create('client_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 100)->unique();
            $table->string('title', 255); // Judul/Perihal Kerjasama MoU/Kontrak
            $table->string('client_name', 255); // Nama Klien / Mitra Perusahaan / Lembaga
            $table->string('client_category', 50)->default('perusahaan_swasta'); // perusahaan_swasta, lembaga_pendidikan, mitra_jepang, instansi_pemerintah, lainnya
            $table->string('contract_type', 50)->default('mou'); // mou, pks, spk, nda, addendum
            $table->string('client_pic_name', 150)->nullable(); // PIC / Contact Person Klien
            $table->string('client_pic_phone', 50)->nullable(); // No HP / WhatsApp PIC
            $table->string('client_pic_email', 150)->nullable(); // Email PIC
            $table->text('client_address')->nullable(); // Alamat Kantor / Kampus Klien
            $table->date('start_date'); // Tanggal Mulai Berlaku
            $table->date('end_date'); // Tanggal Berakhir Kontrak
            $table->string('status', 50)->default('active'); // draft, review_direktur, active, expiring_soon, expired, renewed, terminated
            $table->boolean('auto_renewal')->default(false); // Perpanjangan Otomatis
            $table->decimal('contract_value', 15, 2)->nullable(); // Nilai Kontrak (opsional)
            $table->string('currency', 10)->default('IDR'); // IDR, JPY, USD
            $table->string('draft_file_path', 255)->nullable(); // Berkas Draft MoU (.pdf/.doc/.docx)
            $table->string('signed_file_path', 255)->nullable(); // Berkas Final Bertanda Tangan (.pdf)
            $table->text('notes')->nullable(); // Klausul penting / catatan perpanjangan
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('signed_at')->nullable(); // Tanggal Tanda Tangan Resmi
            $table->timestamps();

            $table->index('contract_number');
            $table->index('client_name');
            $table->index('end_date');
            $table->index('status');
            $table->index('client_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contracts');
    }
};

