<?php

namespace App\Services\Payroll\Statutory;

use App\Models\Payroll\StatutoryDeduction;
use App\Models\User;
use App\Services\Payroll\PayrollCalculationService;

class EsiService
{
    public function __construct(
        private readonly PayrollCalculationService $calc
    ) {}

    /**
     * Determine ESI eligibility.
     *
     * An employee is eligible for ESI only when grossSalary <= wage_ceiling.
     * (ESI has an upper threshold — above it, no contribution is made.)
     */
    public function isEligible(string $grossSalary, StatutoryDeduction $rule): bool
    {
        if ($rule->wage_ceiling === null || bccomp((string) $rule->wage_ceiling, '0', 6) === 0) {
            return true;
        }

        $ceiling = $this->calc->normalizeDecimal((string) $rule->wage_ceiling);
        $gross   = $this->calc->normalizeDecimal($grossSalary);

        return bccomp($gross, $ceiling, 6) <= 0;
    }

    /**
     * Calculate employee ESI contribution.
     *
     * Returns '0.000000' when not eligible.
     */
    public function calculateEmployee(string $grossSalary, StatutoryDeduction $rule): string
    {
        if (! $this->isEligible($grossSalary, $rule)) {
            return '0.000000';
        }

        $rate = $this->calc->divideAmount((string) $rule->employee_rate, '100');

        return $this->calc->multiplyAmount($grossSalary, $rate);
    }

    /**
     * Calculate employer ESI contribution.
     *
     * Returns '0.000000' when not eligible.
     */
    public function calculateEmployer(string $grossSalary, StatutoryDeduction $rule): string
    {
        if (! $this->isEligible($grossSalary, $rule)) {
            return '0.000000';
        }

        $rate = $this->calc->divideAmount((string) $rule->employer_rate, '100');

        return $this->calc->multiplyAmount($grossSalary, $rate);
    }

    /**
     * Calculate both ESI contributions for a payroll period.
     *
     * @return array{employee_esi: string, employer_esi: string, eligible: bool}
     */
    public function calculateForPeriod(User $user, string $period, string $grossSalary): array
    {
        $rule = StatutoryDeduction::active()
            ->where('rule_type', 'esi')
            ->first();

        if ($rule === null) {
            return [
                'employee_esi' => '0.000000',
                'employer_esi' => '0.000000',
                'eligible'     => false,
            ];
        }

        $eligible = $this->isEligible($grossSalary, $rule);

        return [
            'employee_esi' => $eligible ? $this->calculateEmployee($grossSalary, $rule) : '0.000000',
            'employer_esi' => $eligible ? $this->calculateEmployer($grossSalary, $rule) : '0.000000',
            'eligible'     => $eligible,
        ];
    }
}
