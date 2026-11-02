<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceExcuse extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'action_at' => 'datetime',
        'manager_approved_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function managerApprover()
    {
        return $this->belongsTo(Employee::class, 'manager_approved_by');
    }

    public function isPendingManager(): bool
    {
        return $this->status === 'pending_manager';
    }

    public function isPendingHR(): bool
    {
        return in_array($this->status, ['pending_hr', 'pending']);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            'late_permission' => 'Izin Terlambat',
            'duty_trip' => 'Izin Dinas Luar',
            'missed_clock_out' => 'Keterangan Tidak Absen Pulang',
            default => $this->type,
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            'late_permission' => 'bg-amber-950/60 border border-amber-500/30 text-amber-400',
            'duty_trip' => 'bg-blue-950/60 border border-blue-500/30 text-blue-400',
            'missed_clock_out' => 'bg-purple-950/60 border border-purple-500/30 text-purple-400',
            default => 'bg-slate-800 text-slate-300',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'late_permission' => 'alarm_on',
            'duty_trip' => 'commute',
            'missed_clock_out' => 'logout',
            default => 'help_outline',
        };
    }
}
