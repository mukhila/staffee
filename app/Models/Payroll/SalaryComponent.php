<?php

namespace App\Models\Payroll;

class SalaryComponent extends EmployeeSalaryComponent
{
    protected $table = 'employee_salary_components';

    protected $fillable = [
        'salary_structure_id', 'component_definition_id', 'amount_type', 'amount',
        'percentage', 'basis_component_definition_id', 'min_amount', 'max_amount',
        'sequence', 'is_active', 'notes', 'metadata',
    ];

    public function structure()
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }
}
