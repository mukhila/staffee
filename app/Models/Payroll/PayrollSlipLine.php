<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class PayrollSlipLine extends Model
{
    protected $table = 'payroll_slip_lines';

    protected $fillable = [
        'payroll_slip_id', 'component_definition_id', 'line_code', 'line_name',
        'line_category', 'source_type', 'source_reference_type', 'source_reference_id',
        'calculation_basis', 'quantity', 'rate', 'amount', 'taxable_amount',
        'is_ytd_included', 'display_order', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:6',
            'amount' => 'decimal:6',
            'taxable_amount' => 'decimal:6',
            'is_ytd_included' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function slip()
    {
        return $this->belongsTo(PayrollSlip::class, 'payroll_slip_id');
    }

    public function definition()
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }
}
