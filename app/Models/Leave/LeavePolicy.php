<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = [
        'leave_type_id', 'name', 'department_id', 'employee_level',
        'max_days_per_year', 'carry_forward_days', 'carry_forward_expiry_months',
        'accrual_method', 'accrual_amount', 'vesting_period_months',
        'min_notice_days', 'max_consecutive_days',
        'requires_manager_approval', 'requires_hr_approval', 'auto_approve_days',
        'is_active',
    ];

    protected $casts = [
        'requires_manager_approval' => 'boolean',
        'requires_hr_approval'      => 'boolean',
        'is_active'                 => 'boolean',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isAutoApprove(int $days): bool
    {
        return $this->auto_approve_days !== null && $days <= $this->auto_approve_days;
    }

    public function accrualPerPeriod(): float
    {
        return match ($this->accrual_method) {
            'monthly'   => round($this->max_days_per_year / 12, 2),
            'quarterly' => round($this->max_days_per_year / 4, 2),
            'annual'    => (float) $this->max_days_per_year,
            'immediate' => (float) $this->max_days_per_year,
            default     => 0,
        };
    }
}
