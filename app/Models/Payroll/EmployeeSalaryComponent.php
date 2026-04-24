<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryComponent extends Model
{
    protected $table = 'employee_salary_components';

    protected $fillable = [
        'salary_structure_id', 'component_definition_id', 'amount_type', 'amount',
        'percentage', 'basis_component_definition_id', 'min_amount', 'max_amount',
        'sequence', 'is_active', 'notes', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
            'percentage' => 'decimal:6',
            'min_amount' => 'decimal:6',
            'max_amount' => 'decimal:6',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function salaryStructure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'salary_structure_id');
    }

    public function definition()
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function basisComponent()
    {
        return $this->belongsTo(ComponentDefinition::class, 'basis_component_definition_id');
    }
}
