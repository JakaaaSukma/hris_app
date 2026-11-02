<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityTicket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reporter_id');
    }

    public function handler()
    {
        return $this->belongsTo(Employee::class, 'handled_by');
    }
}
