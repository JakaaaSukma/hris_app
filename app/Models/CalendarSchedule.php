<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'date',
        'title',
        'description',
        'time_start',
        'time_end',
        'category',
        'color',
        'is_company_wide',
        'department',
        'created_by',
        'scope',
        'target_department',
        'target_departments',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_company_wide' => 'boolean',
        'approved_at' => 'datetime',
        'target_departments' => 'array',
    ];

    /**
     * Get target departments as array
     */
    public function getTargetDepartmentsList(): array
    {
        if (is_array($this->target_departments) && count($this->target_departments) > 0) {
            return $this->target_departments;
        }

        if (!empty($this->target_department)) {
            return array_map('trim', explode(',', $this->target_department));
        }

        return [];
    }

    /**
     * Check if a department is in the targets
     */
    public function hasTargetDepartment(?string $dept): bool
    {
        if (!$dept) {
            return false;
        }

        $list = $this->getTargetDepartmentsList();
        foreach ($list as $item) {
            if (strcasecmp($item, $dept) === 0) {
                return true;
            }
        }

        return false;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvals()
    {
        return $this->hasMany(CalendarScheduleApproval::class, 'calendar_schedule_id');
    }

    public function isIndividu(): bool
    {
        return $this->scope === 'individu';
    }

    public function isDepartemen(): bool
    {
        return $this->scope === 'departemen';
    }

    public function isAntarDepartemen(): bool
    {
        return $this->scope === 'antar_departemen';
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }
}
