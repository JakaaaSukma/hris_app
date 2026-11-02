<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadProject extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
