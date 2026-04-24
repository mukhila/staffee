<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class ComponentDependency extends Model
{
    protected $table = 'payroll_component_dependencies';

    protected $fillable = [
        'component_definition_id', 'basis_component_definition_id', 'percentage',
        'cap_amount', 'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:6',
            'cap_amount' => 'decimal:6',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function component()
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function basisComponent()
    {
        return $this->belongsTo(ComponentDefinition::class, 'basis_component_definition_id');
    }
}
