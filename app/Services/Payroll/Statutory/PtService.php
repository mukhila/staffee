<?php

namespace App\Services\Payroll\Statutory;

use App\Models\Payroll\StatutoryDeduction;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;

class PtService
{
    public function __construct(
        private readonly PayrollCalculationService $calc
    ) {}

    /**
     * Calculate Professional Tax for a payroll period.
     *
     * PT is slab-based. Slabs are stored in the `slab_json` column as an array
     * of objects with `from`, `to` (nullable), and `amount` keys:
     *   [{"from": 0, "to": 15000, "amount": 0}, {"from": 15001, "to": null, "amount": 200}]
     *
     * The matching slab is the one where:
     *   grossSalary >= slab.from  AND (slab.to is null OR grossSalary <= slab.to)
     *
     * Returns '0.000000' when no active rule or no matching slab.
     */
    public function calculateForPeriod(User $user, string $period, string $grossSalary): string
    {
        $rule = StatutoryDeduction::active()
            ->where('rule_type', 'professional_tax')
            ->first();

        if ($rule === null) {
            return '0.000000';
        }

        return $this->resolveFromSlabs($grossSalary, (array) ($rule->slab_json ?? []));
    }

    /**
     * Find the matching slab and return its PT amount.
     *
     * Exposed as public to allow direct testing with custom slab arrays.
     */
    public function resolveFromSlabs(string $grossSalary, array $slabs): string
    {
        $gross = $this->calc->normalizeDecimal($grossSalary);

        foreach ($slabs as $slab) {
            $from = $this->calc->normalizeDecimal((string) ($slab['from'] ?? '0'));
            $to   = isset($slab['to']) && $slab['to'] !== null
                ? $this->calc->normalizeDecimal((string) $slab['to'])
                : null;

            if (bccomp($gross, $from, 6) >= 0 && ($to === null || bccomp($gross, $to, 6) <= 0)) {
                return $this->calc->normalizeDecimal((string) ($slab['amount'] ?? '0'));
            }
        }

        return '0.000000';
    }
}
