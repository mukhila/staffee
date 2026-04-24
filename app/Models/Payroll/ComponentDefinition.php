<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class ComponentDefinition extends Model
{
    protected $table = 'payroll_component_definitions';

    protected $fillable = [
        'code', 'name', 'category', 'component_type', 'calculation_method',
        'taxable', 'pro_ratable', 'affects_gross', 'affects_net', 'employer_only',
        'arrear_eligible', 'display_order', 'rounding_scale', 'status',
        'description', 'formula_expression', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'taxable' => 'boolean',
            'pro_ratable' => 'boolean',
            'affects_gross' => 'boolean',
            'affects_net' => 'boolean',
            'employer_only' => 'boolean',
            'arrear_eligible' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function dependencies()
    {
        return $this->hasMany(ComponentDependency::class, 'component_definition_id');
    }

    public function salaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'component_definition_id');
    }

    public function slipLines()
    {
        return $this->hasMany(PayrollSlipLine::class, 'component_definition_id');
    }
}
