<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Builder;

class SalaryStructure extends EmployeeSalaryStructure
{
    protected $table = 'employee_salary_structures';

    protected $fillable = [
        'user_id', 'grade_structure_id', 'pay_frequency', 'currency_code', 'annual_ctc',
        'monthly_base_salary', 'version_no', 'standard_work_days', 'standard_work_hours',
        'overtime_eligible', 'tax_regime_id', 'professional_tax_state_code',
        'pf_enabled', 'esi_enabled', 'status', 'effective_from', 'effective_to',
        'approval_status', 'approved_by', 'approved_at', 'created_by',
        'source_revision_id', 'reason',
    ];

    protected $appends = ['base_salary'];

    public function getBaseSalaryAttribute(): string
    {
        return (string) $this->monthly_base_salary;
    }

    public function setBaseSalaryAttribute(string|int|float $value): void
    {
        $this->attributes['monthly_base_salary'] = $value;
    }

    public function scopeActive(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query->where('status', 'active')
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $builder) use ($date) {
                $builder->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            });
    }
}
