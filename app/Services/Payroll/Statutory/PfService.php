<?php

namespace App\Services\Payroll\Statutory;

use App\Models\Payroll\StatutoryDeduction;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;

class PfService
{
    public function __construct(
        private readonly PayrollCalculationService $calc
    ) {}

    /**
     * Calculate employee PF contribution.
     *
     * If wage_ceiling > 0 and basicSalary > wage_ceiling, contribution is
     * calculated on wage_ceiling (not on full basicSalary).
     */
    public function calculateEmployee(string $basicSalary, StatutoryDeduction $rule): string
    {
        $base = $this->capAtCeiling($basicSalary, $rule);
        $rate = $this->calc->divideAmount((string) $rule->employee_rate, '100');

        return $this->calc->multiplyAmount($base, $rate);
    }

    /**
     * Calculate employer PF contribution.
     */
    public function calculateEmployer(string $basicSalary, StatutoryDeduction $rule): string
    {
        $base = $this->capAtCeiling($basicSalary, $rule);
        $rate = $this->calc->divideAmount((string) $rule->employer_rate, '100');

        return $this->calc->multiplyAmount($base, $rate);
    }

    /**
     * Calculate both PF contributions for a payroll period.
     *
     * @return array{employee_pf: string, employer_pf: string}
     */
    public function calculateForPeriod(User $user, string $period, string $basicSalary): array
    {
        $rule = StatutoryDeduction::active()
            ->where('rule_type', 'pf')
            ->first();

        if ($rule === null) {
            return [
                'employee_pf' => '0.000000',
                'employer_pf' => '0.000000',
            ];
        }

        return [
            'employee_pf' => $this->calculateEmployee($basicSalary, $rule),
            'employer_pf' => $this->calculateEmployer($basicSalary, $rule),
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function capAtCeiling(string $basicSalary, StatutoryDeduction $rule): string
    {
        $basicSalary = $this->calc->normalizeDecimal($basicSalary);

        if ($rule->wage_ceiling !== null && bccomp((string) $rule->wage_ceiling, '0', 6) > 0) {
            $ceiling = $this->calc->normalizeDecimal((string) $rule->wage_ceiling);
            if (bccomp($basicSalary, $ceiling, 6) > 0) {
                return $ceiling;
            }
        }

        return $basicSalary;
    }
}
