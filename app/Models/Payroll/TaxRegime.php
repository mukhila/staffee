<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class TaxRegime extends Model
{
    protected $table = 'tax_regimes';

    protected $fillable = [
        'country_code', 'fiscal_year', 'regime_code', 'name', 'standard_deduction',
        'rebate_amount', 'surcharge_json', 'cess_percent', 'status',
        'effective_from', 'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'standard_deduction' => 'decimal:6',
            'rebate_amount' => 'decimal:6',
            'cess_percent' => 'decimal:6',
            'surcharge_json' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function brackets()
    {
        return $this->hasMany(TaxBracket::class, 'tax_regime_id')->orderBy('income_from');
    }
}
