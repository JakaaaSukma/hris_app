<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class FinanceInvoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(FinanceInvoiceItem::class, 'invoice_id')->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(FinanceInvoicePayment::class, 'invoice_id')->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }

    public function contract()
    {
        return $this->belongsTo(ClientContract::class, 'client_contract_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Pilihan Jenis Invoice
     */
    public static function typeOptions(): array
    {
        return [
            'receivable' => 'Tagihan Masuk / Piutang Klien (AR)',
            'payable' => 'Tagihan Vendor / Kewajiban Bayar (AP)',
        ];
    }

    /**
     * Pilihan Status Invoice
     */
    public static function statusOptions(): array
    {
        return [
            'draft' => 'Draft (Belum Terbit)',
            'issued' => 'Terbit / Menunggu Pembayaran',
            'partial' => 'Dibayar Sebagian (Cicilan)',
            'due_soon' => 'Segera Jatuh Tempo (<= 7 Hari)',
            'overdue' => 'Jatuh Tempo (Overdue)',
            'paid' => 'Lunas (Paid)',
            'cancelled' => 'Dibatalkan',
        ];
    }

    /**
     * Pilihan Metode Pembayaran
     */
    public static function paymentMethodOptions(): array
    {
        return [
            'Transfer Bank' => 'Transfer Bank',
            'Virtual Account' => 'Virtual Account',
            'Tunai' => 'Tunai / Kas Kecil',
            'Giro / Cek' => 'Giro / Cek',
            'Lainnya' => 'Lainnya',
        ];
    }

    /**
     * Pilihan Mata Uang
     */
    public static function currencyOptions(): array
    {
        return [
            'IDR' => 'IDR (Rupiah)',
            'JPY' => 'JPY (Yen Jepang)',
            'USD' => 'USD (Dolar AS)',
        ];
    }

    /**
     * Generator Nomor Invoice Berikutnya (Contoh: INV/FIN-GYK/2026/11/001)
     */
    public static function generateNextInvoiceNumber(?string $date = null): string
    {
        $carbonDate = $date ? Carbon::parse($date) : now();
        $prefix = 'INV/FIN-GYK/' . $carbonDate->format('Y/m') . '/';

        $lastInvoice = static::withTrashed()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $nextSeq = 1;
        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_number, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        $candidate = $prefix . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
        while (static::withTrashed()->where('invoice_number', $candidate)->exists()) {
            $nextSeq++;
            $candidate = $prefix . str_pad((string) $nextSeq, 3, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    /**
     * Sisa Tagihan yang Belum Dibayar
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0.0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    /**
     * Persentase Pembayaran Terkumpul (0 - 100)
     */
    public function getPaymentProgressAttribute(): int
    {
        if ((float) $this->total_amount <= 0) {
            return $this->status === 'paid' ? 100 : 0;
        }
        return (int) min(100, round(((float) $this->paid_amount / (float) $this->total_amount) * 100));
    }

    /**
     * Sisa Hari Menuju Jatuh Tempo
     * Positif = sisa hari, 0 = hari ini jatuh tempo, Negatif = terlambat sekian hari
     */
    public function getDaysUntilDueAttribute(): int
    {
        if (!$this->due_date) {
            return 999;
        }

        $today = now()->startOfDay();
        $due = Carbon::parse($this->due_date)->startOfDay();

        return (int) $today->diffInDays($due, false);
    }

    /**
     * Status Terhitung Real-Time Berdasarkan Tanggal Jatuh Tempo & Pembayaran
     */
    public function getComputedStatusAttribute(): string
    {
        if (in_array($this->status, ['draft', 'paid', 'cancelled'], true)) {
            return $this->status;
        }

        if ($this->remaining_amount <= 0 && (float) $this->total_amount > 0) {
            return 'paid';
        }

        if ($this->days_until_due < 0) {
            return 'overdue';
        }

        if ($this->status === 'partial') {
            return 'partial';
        }

        return 'issued';
    }

    /**
     * Label Jenis Invoice
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->invoice_type === 'payable' ? 'Tagihan Vendor (AP)' : 'Tagihan Klien (AR)';
    }

    /**
     * Format Nominal dengan Mata Uang
     */
    public function formatCurrency(float $amount): string
    {
        $currency = strtoupper($this->currency ?? 'IDR');
        if ($currency === 'IDR') {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
        if ($currency === 'JPY') {
            return '¥ ' . number_format($amount, 0, ',', '.');
        }
        return '$ ' . number_format($amount, 2, '.', ',');
    }
}
