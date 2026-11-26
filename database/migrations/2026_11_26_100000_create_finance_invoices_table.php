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
        Schema::create('finance_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 100)->unique();
            $table->string('invoice_type', 30)->default('receivable'); // receivable (Tagihan Masuk/Klien) | payable (Tagihan Vendor/Keluar)
            $table->string('title', 255); // Perihal / Judul Tagihan
            $table->string('counterparty_name', 255); // Nama Klien / Mitra / Vendor
            $table->string('counterparty_email', 150)->nullable();
            $table->string('counterparty_phone', 50)->nullable();
            $table->text('counterparty_address')->nullable();
            $table->foreignId('client_contract_id')->nullable()->constrained('client_contracts')->nullOnDelete();

            $table->date('issue_date'); // Tanggal Terbit Invoice
            $table->date('due_date'); // Tanggal Jatuh Tempo Pembayaran

            $table->string('currency', 10)->default('IDR'); // IDR, JPY, USD
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);

            $table->string('status', 30)->default('issued'); // draft, issued, partial, paid, overdue, cancelled

            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_account_holder', 150)->nullable();

            $table->string('attachment_path', 255)->nullable(); // File PDF faktur/lampiran opsional
            $table->text('notes')->nullable(); // Catatan & syarat pembayaran

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('invoice_number');
            $table->index('invoice_type');
            $table->index('status');
            $table->index('issue_date');
            $table->index('due_date');
            $table->index('counterparty_name');
        });

        Schema::create('finance_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('finance_invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('finance_invoices')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50)->default('Transfer Bank');
            $table->string('reference_number', 150)->nullable();
            $table->string('proof_file_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_invoice_payments');
        Schema::dropIfExists('finance_invoice_items');
        Schema::dropIfExists('finance_invoices');
    }
};
