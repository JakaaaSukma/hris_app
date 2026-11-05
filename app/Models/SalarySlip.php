<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class SalarySlip extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowance_position' => 'decimal:2',
        'allowance_transport' => 'decimal:2',
        'allowance_meal' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deduction_bpjs_tk' => 'decimal:2',
        'deduction_bpjs_kes' => 'decimal:2',
        'deduction_tax_pph21' => 'decimal:2',
        'deduction_absence' => 'decimal:2',
        'deduction_other' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasPdf(): bool
    {
        return !empty($this->pdf_path) && (
            Storage::disk('public')->exists($this->pdf_path) || 
            file_exists(storage_path('app/public/' . $this->pdf_path)) || 
            file_exists(public_path('storage/' . $this->pdf_path))
        );
    }

    public function getPdfUrlAttribute(): ?string
    {
        if (empty($this->pdf_path)) {
            return null;
        }
        return asset('storage/' . ltrim($this->pdf_path, '/'));
    }
}
