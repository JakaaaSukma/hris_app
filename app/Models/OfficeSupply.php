<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeSupply extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Riwayat Pemakaian ATK
     */
    public function usages()
    {
        return $this->hasMany(OfficeSupplyUsage::class, 'office_supply_id');
    }

    /**
     * Cek apakah stok di bawah atau sama dengan batas minimum
     */
    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->min_stock && $this->stock_qty > 0;
    }

    /**
     * Cek apakah stok habis
     */
    public function isOutOfStock(): bool
    {
        return $this->stock_qty <= 0;
    }

    /**
     * Label Status Ketersediaan
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Habis';
        }
        if ($this->isLowStock()) {
            return 'Menipis';
        }
        return 'Tersedia';
    }

    /**
     * Warna Badge Status Ketersediaan
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'rose';
        }
        if ($this->isLowStock()) {
            return 'amber';
        }
        return 'emerald';
    }
}
