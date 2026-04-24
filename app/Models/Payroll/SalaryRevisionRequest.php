<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SalaryRevisionRequest extends Model
{
    protected $table = 'salary_revision_requests';

    protected $fillable = [
        'user_id', 'current_salary_structure_id', 'proposed_grade_structure_id',
        'revision_type', 'effective_date', 'retroactive_from', 'proposed_currency_code',
        'proposed_base_salary', 'old_gross_monthly', 'new_gross_monthly',
        'impact_summary', 'reason', 'status', 'submitted_by', 'approved_by',
        'approved_at', 'rejected_by', 'rejected_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'retroactive_from' => 'date',
            'proposed_base_salary' => 'decimal:6',
            'old_gross_monthly' => 'decimal:6',
            'new_gross_monthly' => 'decimal:6',
            'impact_summary' => 'array',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function currentSalaryStructure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'current_salary_structure_id');
    }

    public function proposedGrade()
    {
        return $this->belongsTo(GradeStructure::class, 'proposed_grade_structure_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function componentChanges()
    {
        return $this->hasMany(SalaryRevisionRequestComponent::class, 'revision_request_id');
    }
}
