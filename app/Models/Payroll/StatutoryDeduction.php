<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StatutoryDeduction extends Model
{
    protected $table = 'statutory_deduction_rules';

    protected $fillable = [
        'country_code', 'state_code', 'rule_type', 'employee_rate', 'employer_rate',
        'wage_ceiling', 'min_wage', 'max_amount', 'slab_json',
        'effective_from', 'effective_to', 'status',
    ];

    protected function casts(): array
    {
        return [
            'employee_rate' => 'decimal:6',
            'employer_rate' => 'decimal:6',
            'wage_ceiling' => 'decimal:6',
            'min_wage' => 'decimal:6',
            'max_amount' => 'decimal:6',
            'slab_json' => 'array',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
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
