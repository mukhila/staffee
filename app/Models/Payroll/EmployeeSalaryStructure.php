<?php

namespace App\Models\Payroll;

use App\Models\HR\SalaryRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    protected $table = 'employee_salary_structures';

    protected $fillable = [
        'user_id', 'grade_structure_id', 'pay_frequency', 'currency_code', 'annual_ctc',
        'monthly_base_salary', 'standard_work_days', 'standard_work_hours',
        'overtime_eligible', 'tax_regime_id', 'professional_tax_state_code',
        'pf_enabled', 'esi_enabled', 'status', 'effective_from', 'effective_to',
        'approval_status', 'approved_by', 'approved_at', 'created_by',
        'source_revision_id', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'annual_ctc' => 'decimal:6',
            'monthly_base_salary' => 'decimal:6',
            'version_no' => 'integer',
            'standard_work_hours' => 'decimal:4',
            'overtime_eligible' => 'boolean',
            'pf_enabled' => 'boolean',
            'esi_enabled' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade()
    {
        return $this->belongsTo(GradeStructure::class, 'grade_structure_id');
    }

    public function taxRegime()
    {
        return $this->belongsTo(TaxRegime::class, 'tax_regime_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sourceRevision()
    {
        return $this->belongsTo(SalaryRevision::class, 'source_revision_id');
    }

    public function components()
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'salary_structure_id')->orderBy('sequence');
    }

    public function payrollRunEmployees()
    {
        return $this->hasMany(PayrollRunEmployee::class, 'salary_structure_id');
    }

    public function payrollSlips()
    {
        return $this->hasMany(PayrollSlip::class, 'salary_structure_id');
    }

    public function scopeActive($query, ?string $date = null)
    {
        $date ??= now()->toDateString();

        return $query->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($builder) use ($date) {
                $builder->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            });
    }
}
