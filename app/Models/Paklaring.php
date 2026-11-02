<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Paklaring extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'certificate_number',
        'recipient_name',
        'employee_id',
        'program_or_position',
        'period_text',
        'company_unit',
        'city',
        'issue_date',
        'signatory_name',
        'signatory_title',
        'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
