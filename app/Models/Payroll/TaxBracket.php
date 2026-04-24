<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class TaxBracket extends Model
{
    protected $table = 'tax_brackets';

    protected $fillable = [
        'tax_regime_id', 'income_from', 'income_to', 'rate_percent',
        'fixed_tax_amount', 'rebate_eligible',
    ];

    protected function casts(): array
    {
        return [
            'income_from' => 'decimal:6',
            'income_to' => 'decimal:6',
            'rate_percent' => 'decimal:6',
            'fixed_tax_amount' => 'decimal:6',
            'rebate_eligible' => 'boolean',
        ];
    }

    public function regime()
    {
        return $this->belongsTo(TaxRegime::class, 'tax_regime_id');
    }
}
