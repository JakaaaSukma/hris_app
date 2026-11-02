<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarScheduleApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'calendar_schedule_id',
        'user_id',
        'status',
        'notes',
    ];

    public function schedule()
    {
        return $this->belongsTo(CalendarSchedule::class, 'calendar_schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
