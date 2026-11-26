<?php

namespace Tests\Feature;

use App\Models\FinanceInvoice;
use App\Models\FinanceInvoicePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinanceInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureDeviceSessionIsValid::class);
        Storage::fake('public');
    }

    public function test_non_finance_cannot_access_invoices()
    {
        $hrStaff = User::factory()->create([
            'role' => 'karyawan',
            'department' => 'HR',
        ]);

        $response = $this->actingAs($hrStaff)->get(route('finance.invoices.index'));
        $response->assertStatus(403);
    }

    public function test_finance_staff_and_direktur_can_create_and_issue_invoice()
    {
        $financeStaff = User::factory()->create([
            'role' => 'karyawan',
            'department' => 'Finance',
        ]);

        $direktur = User::factory()->create([
            'role' => 'direktur',
            'department' => 'Executive',
        ]);

        $this->actingAs($financeStaff)->get(route('finance.invoices.index'))->assertStatus(200);
        $this->actingAs($direktur)->get(route('finance.invoices.index'))->assertStatus(200);

        $attachment = UploadedFile::fake()->create('faktur.pdf', 100, 'application/pdf');

        $response = $this->actingAs($financeStaff)->post(route('finance.invoices.store'), [
            'invoice_number' => 'INV/FIN-GYK/2026/11/001',
            'invoice_type' => 'receivable',
            'title' => 'Termin I Pengiriman Tenaga Kerja Jepang',
            'counterparty_name' => 'Kumiai Asahi Tokyo',
            'counterparty_email' => 'acc@asahi.jp',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'IDR',
            'discount_amount' => 1000000,
            'tax_percentage' => 11,
            'action_mode' => 'issue',
            'attachment' => $attachment,
            'items' => [
                [
                    'description' => 'Biaya Penempatan Batch Nov',
                    'quantity' => 5,
                    'unit' => 'orang',
                    'unit_price' => 4000000,
                ],
            ],
        ]);

        $response->assertRedirect(route('finance.invoices.index'));

        // Subtotal = 20.000.000, Diskon = 1.000.000 -> Base = 19.000.000
        // Pajak 11% = 2.090.000 -> Total = 21.090.000
        $invoice = FinanceInvoice::where('invoice_number', 'INV/FIN-GYK/2026/11/001')->firstOrFail();
        $this->assertEquals('issued', $invoice->status);
        $this->assertEquals($financeStaff->id, $invoice->issued_by);
        $this->assertNotNull($invoice->issued_at);
        $this->assertEquals(20000000.00, (float) $invoice->subtotal);
        $this->assertEquals(2090000.00, (float) $invoice->tax_amount);
        $this->assertEquals(21090000.00, (float) $invoice->total_amount);

        // Pastikan halaman cetak & detail bisa diakses
        $this->actingAs($financeStaff)->get(route('finance.invoices.show', $invoice->id))->assertStatus(200);

        // Pastikan stream lampiran berhasil
        $this->actingAs($financeStaff)->get(route('finance.invoices.downloadAttachment', $invoice->id))->assertStatus(200);
    }

    public function test_computed_status_detects_overdue_and_due_countdown()
    {
        $overdueInvoice = FinanceInvoice::create([
            'invoice_number' => 'INV/TEST/OVERDUE',
            'invoice_type' => 'receivable',
            'title' => 'Tagihan Telat',
            'counterparty_name' => 'PT Telat Bayar',
            'issue_date' => Carbon::today()->subDays(20),
            'due_date' => Carbon::today()->subDays(5),
            'currency' => 'IDR',
            'subtotal' => 5000000,
            'total_amount' => 5000000,
            'paid_amount' => 0,
            'status' => 'issued',
        ]);

        $this->assertEquals(-5, $overdueInvoice->days_until_due);
        $this->assertEquals('overdue', $overdueInvoice->computed_status);
    }

    public function test_partial_and_full_payment_workflow_with_proof()
    {
        $financeManager = User::factory()->create([
            'role' => 'manager',
            'department' => 'Finance',
        ]);

        $invoice = FinanceInvoice::create([
            'invoice_number' => 'INV/TEST/PAY-01',
            'invoice_type' => 'receivable',
            'title' => 'Tagihan Pembayaran Bertahap',
            'counterparty_name' => 'PT Mitra Sejahtera',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(7),
            'currency' => 'IDR',
            'subtotal' => 10000000,
            'total_amount' => 10000000,
            'paid_amount' => 0,
            'status' => 'issued',
            'created_by' => $financeManager->id,
        ]);

        // Pembayaran tahap 1 (4.000.000)
        $proof1 = UploadedFile::fake()->create('bukti_dp.pdf', 50, 'application/pdf');
        $res1 = $this->actingAs($financeManager)->post(route('finance.invoices.recordPayment', $invoice->id), [
            'payment_date' => now()->toDateString(),
            'amount' => 4000000,
            'payment_method' => 'Transfer Bank',
            'reference_number' => 'TRF-001',
            'proof_file' => $proof1,
        ]);
        $res1->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('partial', $invoice->status);
        $this->assertEquals(4000000.00, (float) $invoice->paid_amount);
        $this->assertEquals(6000000.00, $invoice->remaining_amount);

        // Pembayaran melebihi sisa tagihan harus ditolak
        $resOver = $this->actingAs($financeManager)->post(route('finance.invoices.recordPayment', $invoice->id), [
            'payment_date' => now()->toDateString(),
            'amount' => 7000000,
            'payment_method' => 'Transfer Bank',
        ]);
        $resOver->assertSessionHas('error');

        // Pelunasan tahap 2 (6.000.000)
        $res2 = $this->actingAs($financeManager)->post(route('finance.invoices.recordPayment', $invoice->id), [
            'payment_date' => now()->toDateString(),
            'amount' => 6000000,
            'payment_method' => 'Transfer Bank',
            'reference_number' => 'TRF-002',
        ]);
        $res2->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(10000000.00, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, $invoice->remaining_amount);
        $this->assertNotNull($invoice->paid_at);

        $payment = FinanceInvoicePayment::where('invoice_id', $invoice->id)->whereNotNull('proof_file_path')->firstOrFail();
        $this->actingAs($financeManager)->get(route('finance.invoices.downloadPaymentProof', $payment->id))->assertStatus(200);
    }

    public function test_cancellation_requires_reason_and_delete_restricted_to_manager_or_direktur()
    {
        $financeStaff = User::factory()->create([
            'role' => 'karyawan',
            'department' => 'Finance',
        ]);
        $financeManager = User::factory()->create([
            'role' => 'manager',
            'department' => 'Finance',
        ]);

        $invoice = FinanceInvoice::create([
            'invoice_number' => 'INV/TEST/CANCEL',
            'invoice_type' => 'payable',
            'title' => 'Tagihan ATK Vendor',
            'counterparty_name' => 'CV Sumber Jaya',
            'issue_date' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(10),
            'currency' => 'IDR',
            'subtotal' => 500000,
            'total_amount' => 500000,
            'status' => 'draft',
        ]);

        // Cancel tanpa alasan -> validasi gagal
        $this->actingAs($financeStaff)
            ->post(route('finance.invoices.cancel', $invoice->id), ['cancel_reason' => ''])
            ->assertSessionHasErrors('cancel_reason');

        // Cancel dengan alasan valid -> sukses
        $this->actingAs($financeStaff)
            ->post(route('finance.invoices.cancel', $invoice->id), ['cancel_reason' => 'Revisi penawaran harga dari vendor'])
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertEquals($financeStaff->id, $invoice->cancelled_by);

        // Staff Finance tidak boleh hapus permanen
        $this->actingAs($financeStaff)
            ->delete(route('finance.invoices.destroy', $invoice->id))
            ->assertStatus(403);

        // Manager Finance boleh hapus permanen
        $this->actingAs($financeManager)
            ->delete(route('finance.invoices.destroy', $invoice->id))
            ->assertRedirect(route('finance.invoices.index'));
    }
}
