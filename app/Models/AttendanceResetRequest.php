<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceResetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requested_by',
        'requester_role',
        'scope',
        'start_date',
        'end_date',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
        'action_at',
        'deleted_records_count',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'action_at' => 'datetime',
    ];

    /**
     * Pemohon reset (Manager HRD atau Direktur)
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Penyetuju reset (Direktur jika pemohon Manager HRD, atau Manager HRD jika pemohon Direktur)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
