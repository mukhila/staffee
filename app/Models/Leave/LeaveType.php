<?php

namespace App\Models\Leave;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name', 'code', 'category', 'color', 'is_paid', 'requires_approval',
        'max_days_per_year', 'allow_half_day', 'requires_document', 'is_active', 'description',
    ];

    protected $casts = [
        'is_paid'            => 'boolean',
        'requires_approval'  => 'boolean',
        'allow_half_day'     => 'boolean',
        'requires_document'  => 'boolean',
        'is_active'          => 'boolean',
    ];

    const CATEGORIES = [
        'paid_annual'  => 'Paid Annual Leave',
        'paid_casual'  => 'Paid Casual Leave',
        'paid_medical' => 'Paid Medical Leave',
        'unpaid'       => 'Unpaid Leave',
        'maternity'    => 'Maternity Leave',
        'paternity'    => 'Paternity Leave',
        'sick'         => 'Sick Leave',
        'special'      => 'Special Leave',
        'custom'       => 'Custom',
    ];

    const CATEGORY_COLORS = [
        'paid_annual'  => 'primary',
        'paid_casual'  => 'info',
        'paid_medical' => 'success',
        'unpaid'       => 'secondary',
        'maternity'    => 'pink',
        'paternity'    => 'teal',
        'sick'         => 'warning',
        'special'      => 'purple',
        'custom'       => 'dark',
    ];

    public function policies()
    {
        return $this->hasMany(LeavePolicy::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests()
    {
        return $this->hasMany(\App\Models\LeaveRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getPolicyFor(\App\Models\User $user): ?LeavePolicy
    {
        return $this->policies()
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('department_id', $user->department_id)
                       ->orWhereNull('department_id');
                });
            })
            ->orderByRaw('department_id IS NULL ASC') // department-specific wins
            ->first();
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }
}
