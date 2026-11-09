<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClientContract extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'date',
        'auto_renewal' => 'boolean',
        'contract_value' => 'decimal:2',
    ];

    /**
     * User Pembuat Data / Sekretaris yang Mendaftarkan
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pilihan Kategori Klien / Mitra
     */
    public static function categoryOptions(): array
    {
        return [
            'perusahaan_swasta' => 'Perusahaan Swasta / Industri',
            'lembaga_pendidikan' => 'Lembaga Pendidikan (SMK / Kampus)',
            'mitra_jepang' => 'Mitra Jepang (Kumiai / Kanso Dantai)',
            'instansi_pemerintah' => 'Instansi Pemerintah / BUMN / Balai',
            'lainnya' => 'Lainnya',
        ];
    }

    /**
     * Pilihan Jenis Kontrak / Dokumen Kerjasama
     */
    public static function contractTypeOptions(): array
    {
        return [
            'mou' => 'Nota Kesepahaman (MoU)',
            'pks' => 'Perjanjian Kerjasama (PKS)',
            'spk' => 'Surat Perintah Kerja (SPK)',
            'nda' => 'Non-Disclosure Agreement (NDA)',
            'addendum' => 'Addendum / Perpanjangan',
        ];
    }

    /**
     * Pilihan Status Kontrak
     */
    public static function statusOptions(): array
    {
        return [
            'active' => 'Aktif Berjalan',
            'expiring_soon' => 'Segera Berakhir (<= 30 Hari)',
            'expired' => 'Kedaluwarsa / Habis',
            'review_direktur' => 'Review Direktur',
            'draft' => 'Draft Penyusunan',
            'renewed' => 'Telah Diperpanjang',
            'terminated' => 'Dihentikan / Selesai',
        ];
    }

    /**
     * Label Kategori Klien
     */
    public function getCategoryLabelAttribute(): string
    {
        return static::categoryOptions()[$this->client_category] ?? ucfirst($this->client_category);
    }

    /**
     * Label Jenis Kontrak
     */
    public function getTypeLabelAttribute(): string
    {
        return static::contractTypeOptions()[$this->contract_type] ?? strtoupper($this->contract_type);
    }

    /**
     * Sisa Hari Menuju Berakhirnya Kontrak
     * Nilai positif: sisa hari; 0: habis hari ini; negatif: sudah lewat sekian hari
     */
    public function getDaysRemainingAttribute(): int
    {
        if (!$this->end_date) {
            return 999;
        }

        $today = now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();

        return (int) $today->diffInDays($end, false);
    }

    /**
     * Status yang Dihitung Secara Real-Time Berdasarkan Tanggal Berakhir
     */
    public function getComputedStatusAttribute(): string
    {
        // Jika status administratif statis, kembalikan langsung
        if (in_array($this->status, ['draft', 'review_direktur', 'renewed', 'terminated'], true)) {
            return $this->status;
        }

        $days = $this->days_remaining;

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }

    /**
     * Tingkat Urgensi Masa Berlaku Kontrak
     */
    public function getUrgencyLevelAttribute(): string
    {
        $comp = $this->computed_status;

        if ($comp === 'expired') {
            return 'expired';
        }

        if (in_array($comp, ['draft', 'review_direktur', 'renewed', 'terminated'], true)) {
            return 'neutral';
        }

        $days = $this->days_remaining;

        if ($days <= 14) {
            return 'critical'; // Merah / Bahaya
        }

        if ($days <= 30) {
            return 'warning'; // Oranye
        }

        if ($days <= 60) {
            return 'notice'; // Kuning
        }

        return 'safe'; // Hijau aman
    }

    /**
     * Format Nilai Kontrak Lengkap (Mata Uang & Nominal)
     */
    public function getFormattedContractValueAttribute(): ?string
    {
        if ($this->contract_value === null) {
            return null;
        }

        $curr = $this->currency ?: 'IDR';
        if ($curr === 'IDR') {
            return 'Rp ' . number_format($this->contract_value, 0, ',', '.');
        } elseif ($curr === 'JPY') {
            return '¥ ' . number_format($this->contract_value, 0, ',', '.');
        } elseif ($curr === 'USD') {
            return '$ ' . number_format($this->contract_value, 2, '.', ',');
        }

        return $curr . ' ' . number_format($this->contract_value, 2, ',', '.');
    }
}

