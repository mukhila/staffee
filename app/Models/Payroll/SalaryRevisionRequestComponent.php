<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class SalaryRevisionRequestComponent extends Model
{
    protected $table = 'salary_revision_request_components';

    protected $fillable = [
        'revision_request_id', 'component_definition_id', 'old_amount',
        'new_amount', 'old_percentage', 'new_percentage', 'change_type',
    ];

    protected function casts(): array
    {
        return [
            'old_amount' => 'decimal:6',
            'new_amount' => 'decimal:6',
            'old_percentage' => 'decimal:6',
            'new_percentage' => 'decimal:6',
        ];
    }

    public function revisionRequest()
    {
        return $this->belongsTo(SalaryRevisionRequest::class, 'revision_request_id');
    }

    public function definition()
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }
}
