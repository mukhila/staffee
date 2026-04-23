<?php

namespace App\Models\HR;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PromotionRequest extends Model
{
    protected $table = 'promotion_requests';

    protected $fillable = [
        'user_id', 'proposed_by',
        'current_role', 'proposed_role',
        'current_department_id', 'proposed_department_id',
        'current_designation', 'proposed_designation',
        'current_salary', 'proposed_salary',
        'effective_date', 'reason', 'status',
        'manager_approved_by', 'manager_approved_at', 'manager_notes',
        'hr_approved_by', 'hr_approved_at', 'hr_notes',
        'finance_approved_by', 'finance_approved_at', 'finance_notes',
        'announced_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_date'       => 'date',
            'manager_approved_at'  => 'datetime',
            'hr_approved_at'       => 'datetime',
            'finance_approved_at'  => 'datetime',
            'announced_at'         => 'datetime',
            'current_salary'       => 'decimal:2',
            'proposed_salary'      => 'decimal:2',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proposedBy()
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function currentDepartment()
    {
        return $this->belongsTo(Department::class, 'current_department_id');
    }

    public function proposedDepartment()
    {
        return $this->belongsTo(Department::class, 'proposed_department_id');
    }

    public function getSalaryIncreasePercentAttribute(): ?float
    {
        if (!$this->current_salary || $this->current_salary == 0) {
            return null;
        }
        return round((($this->proposed_salary - $this->current_salary) / $this->current_salary) * 100, 2);
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            'draft', 'pending_manager', 'manager_approved',
            'pending_hr', 'hr_approved', 'pending_finance',
        ]);
    }

    public function nextApproverRole(): ?string
    {
        return match ($this->status) {
            'draft', 'pending_manager' => 'manager',
            'manager_approved', 'pending_hr' => 'hr',
            'hr_approved', 'pending_finance' => 'finance',
            default => null,
        };
    }
}
