<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeSupplyUsage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'usage_date' => 'date',
    ];

    /**
     * Barang ATK yang digunakan
     */
    public function officeSupply()
    {
        return $this->belongsTo(OfficeSupply::class, 'office_supply_id');
    }

    /**
     * Karyawan yang mengambil / menerima ATK
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Staf GA / User yang mencatat pengeluaran ATK
     */
    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}

