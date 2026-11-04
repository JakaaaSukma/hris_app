<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutgoingLetter extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'letter_date' => 'date',
        'signed_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Pembuat Surat / Sekretaris yang Mendaftarkan
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Konversi Angka Bulan Numerik (1-12) ke Format Angka Romawi
     */
    public static function convertMonthToRoman(int $month): string
    {
        $romans = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
            5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
        ];

        return $romans[$month] ?? 'I';
    }

    /**
     * Susun Format Baku Nomor Surat Keluar Resmi
     * Contoh: 001/SK/LPK-GYOKAI/XI/2026
     */
    public static function formatLetterNumber(int $sequence, string $category, string $orgCode, string $monthRoman, int $year): string
    {
        $paddedSeq = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        return "{$paddedSeq}/{$category}/{$orgCode}/{$monthRoman}/{$year}";
    }

    /**
     * Dapatkan Nomor Urut Berikutnya Berdasarkan Tahun
     */
    public static function getNextSequence(int $year): int
    {
        $maxSeq = static::where('year', $year)->max('sequence_number');
        return ($maxSeq ?? 0) + 1;
    }

    /**
     * Daftar Kategori / Klasifikasi Surat Resmi
     */
    public static function categoryOptions(): array
    {
        return [
            'SK' => 'Surat Keputusan (SK)',
            'SPK' => 'Surat Perjanjian Kerja / Kontrak (SPK)',
            'ST' => 'Surat Tugas / Perjalanan Dinas (ST)',
            'UND' => 'Surat Undangan Resmi (UND)',
            'SKet' => 'Surat Keterangan (SKet)',
            'SPm' => 'Surat Pemberitahuan (SPm)',
            'SPH' => 'Surat Penawaran Harga / Kerjasama (SPH)',
            'MOU' => 'Nota Kesepahaman / MoU (MOU)',
            'SE' => 'Surat Edaran (SE)',
            'BA' => 'Berita Acara (BA)',
            'SR' => 'Surat Rekomendasi (SR)',
            'SP' => 'Surat Peringatan (SP)',
        ];
    }

    /**
     * Daftar Kode Lembaga / Pengirim
     */
    public static function organizationOptions(): array
    {
        return [
            'LPK-GYOKAI' => 'LPK-GYOKAI (LPK Gyokai Indonesia Umum)',
            'DIR-GYOKAI' => 'DIR-GYOKAI (Direktur Utama / Direksi)',
            'SEK-GYOKAI' => 'SEK-GYOKAI (Sekretariat Direksi)',
            'HRD-GYOKAI' => 'HRD-GYOKAI (Human Resources Department)',
            'FIN-GYOKAI' => 'FIN-GYOKAI (Finance & Accounting)',
            'BD-GYOKAI' => 'BD-GYOKAI (Bisnis Development)',
        ];
    }

    /**
     * Label Status Surat
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Konsep (Draft)',
            'reserved' => 'Nomor Dipesan',
            'signed' => 'Ditandatangani',
            'sent' => 'Terkirim ke Tujuan',
            'archived' => 'Selesai & Diarsipkan',
            default => ucfirst($this->status),
        };
    }

    /**
     * Badge CSS Class untuk Status
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
            'reserved' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            'signed' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            'sent' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
            'archived' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            default => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
        };
    }

    /**
     * Badge CSS Class untuk Urgensi
     */
    public function getUrgencyBadgeAttribute(): string
    {
        return match ($this->urgency) {
            'penting' => 'bg-amber-950/60 text-amber-400 border-amber-500/30',
            'segera' => 'bg-rose-950/70 text-rose-400 border-rose-500/40 animate-pulse',
            'rahasia' => 'bg-purple-950/70 text-purple-300 border-purple-500/40',
            default => 'bg-slate-800/80 text-slate-400 border-slate-700/60',
        };
    }

    /**
     * Tautan URL File Berkas Scan Surat
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return route('secretary.letters.download', $this->id);
    }
}

