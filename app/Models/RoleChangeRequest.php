<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleChangeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requested_by',
        'current_role',
        'target_role',
        'reason',
        'status',
        'approved_by',
        'rejection_reason',
        'action_at',
    ];

    protected $casts = [
        'action_at' => 'datetime',
    ];

    /**
     * Akun user karyawan/manager yang diajukan perubahan status rolenya.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Manager HRD yang membuat pengajuan perubahan role.
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Direktur yang menyetujui / menolak pengajuan.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
