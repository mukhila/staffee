<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Payroll\ComponentDefinition;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollSlip;
use App\Models\Payroll\SalaryStructure;
use App\Models\Payroll\StatutoryDeduction;
use App\Models\Payroll\TaxBracket;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PayrollCalculationService
{
    public function getGrossSalary(User $user, array $period): array
    {
        $salaryStructure = $this->resolveSalaryStructure($user, $period);
        $baseSalary = $this->normalizeDecimal((string) $salaryStructure->base_salary);
        $dailyRate = $this->calculateDailyRate($baseSalary, (int) $salaryStructure->standard_work_days);
        $monthlyHours = $this->multiplyAmount((string) $salaryStructure->standard_work_days, (string) $salaryStructure->standard_work_hours, 6);
        $hourlyRate = $this->calculateHourlyRate($baseSalary, $monthlyHours);

        $allowances = '0.000000';
        $otherEarnings = '0.000000';
        $earningLines = [];

        foreach ($salaryStructure->components()->with('definition')->get() as $component) {
            $definition = $component->definition;
            if (!$definition || $definition->category !== 'earning') {
                continue;
            }

            $amount = $this->resolveComponentAmount($component, $salaryStructure, $period, $baseSalary);

            if ($definition->code === 'BASIC') {
                $earningLines[] = $this->buildLinePayload($definition, $amount, [
                    'source_type' => 'salary_structure',
                    'calculation_basis' => 'Base salary from active salary structure',
                ]);
                continue;
            }

            if ($definition->component_type === 'allowance') {
                $allowances = $this->addAmount($allowances, $amount);
            } else {
                $otherEarnings = $this->addAmount($otherEarnings, $amount);
            }

            $earningLines[] = $this->buildLinePayload($definition, $amount, [
                'source_type' => 'salary_structure',
                'calculation_basis' => 'Recurring earning component',
            ]);
        }

        $overtimeHours = $this->getOvertimeHours($user, $period);
        $overtimePay = $this->multiplyAmount(
            $this->multiplyAmount($hourlyRate, '2.000000'),
            $overtimeHours
        );

        $leaveTaken = $this->getUnpaidLeaveDays($user, $period);
        $leaveDeduction = $this->adjustForLeave($baseSalary, $leaveTaken, (int) $salaryStructure->standard_work_days);

        $otherApprovedEarnings = $this->sumApprovedAdjustments($user, $period, 'earning');
        $gross = $this->addAmount($baseSalary, $allowances);
        $gross = $this->addAmount($gross, $overtimePay);
        $gross = $this->addAmount($gross, $otherEarnings);
        $gross = $this->addAmount($gross, $otherApprovedEarnings);
        $gross = $this->subtractAmount($gross, $leaveDeduction);

        $otDefinition = ComponentDefinition::where('code', 'OT_PAY')->first();
        if ($otDefinition && bccomp($overtimePay, '0', 6) === 1) {
            $earningLines[] = $this->buildLinePayload($otDefinition, $overtimePay, [
                'source_type' => 'time_tracking',
                'quantity' => $overtimeHours,
                'rate' => $this->multiplyAmount($hourlyRate, '2.000000'),
                'calculation_basis' => 'Hourly rate x 2 x overtime hours',
            ]);
        }

        $leaveDefinition = ComponentDefinition::where('code', 'LWP_DEDUCTION')->first();
        $leaveLine = null;
        if ($leaveDefinition && bccomp($leaveDeduction, '0', 6) === 1) {
            $leaveLine = $this->buildLinePayload($leaveDefinition, $leaveDeduction, [
                'source_type' => 'leave',
                'quantity' => $leaveTaken,
                'rate' => $dailyRate,
                'calculation_basis' => 'Daily rate x unpaid leave days',
            ]);
        }

        return [
            'salary_structure' => $salaryStructure,
            'base_salary' => $baseSalary,
            'daily_rate' => $dailyRate,
            'hourly_rate' => $hourlyRate,
            'allowances' => $allowances,
            'other_earnings' => $this->addAmount($otherEarnings, $otherApprovedEarnings),
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'unpaid_leave_days' => $leaveTaken,
            'leave_deduction' => $leaveDeduction,
            'gross' => $gross,
            'earning_lines' => $earningLines,
            'leave_line' => $leaveLine,
        ];
    }

    public function getDeductions(User $user, array $period, ?array $grossBreakdown = null): array
    {
        $grossBreakdown ??= $this->getGrossSalary($user, $period);
        $salaryStructure = $grossBreakdown['salary_structure'];
        $periodDate = Carbon::parse($period['period_end'] ?? now());
        $basicSalary = $grossBreakdown['base_salary'];
        $grossSalary = $grossBreakdown['gross'];

        $tax = $this->calculateTax($grossSalary, $salaryStructure->taxRegime?->brackets ?? collect());
        $pfEmployee = '0.000000';
        $pfEmployer = '0.000000';
        $esiEmployee = '0.000000';
        $esiEmployer = '0.000000';
        $professionalTax = '0.000000';

        $pfRule = StatutoryDeduction::active($periodDate->toDateString())->where('rule_type', 'pf')->first();
        if ($salaryStructure->pf_enabled && $pfRule) {
            $eligiblePfWage = $basicSalary;
            if ($pfRule->wage_ceiling && bccomp((string) $pfRule->wage_ceiling, $eligiblePfWage, 6) === -1) {
                $eligiblePfWage = $this->normalizeDecimal((string) $pfRule->wage_ceiling);
            }

            $pfEmployee = $this->multiplyAmount($eligiblePfWage, $this->percentToDecimal((string) ($pfRule->employee_rate ?? '0')));
            $pfEmployer = $this->multiplyAmount($eligiblePfWage, $this->percentToDecimal((string) ($pfRule->employer_rate ?? '0')));
        }

        $esiRule = StatutoryDeduction::active($periodDate->toDateString())->where('rule_type', 'esi')->first();
        if ($salaryStructure->esi_enabled && $esiRule) {
            $eligibleForEsi = !$esiRule->wage_ceiling || bccomp($grossSalary, (string) $esiRule->wage_ceiling, 6) <= 0;
            if ($eligibleForEsi) {
                $esiEmployee = $this->multiplyAmount($grossSalary, $this->percentToDecimal((string) ($esiRule->employee_rate ?? '0')));
                $esiEmployer = $this->multiplyAmount($grossSalary, $this->percentToDecimal((string) ($esiRule->employer_rate ?? '0')));
            }
        }

        $ptRule = StatutoryDeduction::active($periodDate->toDateString())
            ->where('rule_type', 'professional_tax')
            ->where(function ($query) use ($salaryStructure) {
                $query->whereNull('state_code')
                    ->orWhere('state_code', $salaryStructure->professional_tax_state_code);
            })
            ->first();

        if ($ptRule) {
            $professionalTax = $this->resolveProfessionalTax($grossSalary, $ptRule->slab_json ?? []);
        }

        $voluntaryDeductions = $this->sumApprovedAdjustments($user, $period, 'deduction');
        $leaveDeduction = $grossBreakdown['leave_deduction'];

        $deductionLines = [];
        foreach ([
            'INCOME_TAX' => $tax,
            'PF_EMPLOYEE' => $pfEmployee,
            'ESI_EMPLOYEE' => $esiEmployee,
            'PROF_TAX' => $professionalTax,
            'LOAN_RECOVERY' => $voluntaryDeductions,
        ] as $code => $amount) {
            $definition = ComponentDefinition::where('code', $code)->first();
            if ($definition && bccomp($amount, '0', 6) === 1) {
                $deductionLines[] = $this->buildLinePayload($definition, $amount, [
                    'source_type' => $code === 'LOAN_RECOVERY' ? 'manual_adjustment' : 'statutory',
                    'calculation_basis' => "Calculated {$definition->name}",
                ]);
            }
        }

        $employerLines = [];
        foreach ([
            'PF_EMPLOYER' => $pfEmployer,
            'ESI_EMPLOYER' => $esiEmployer,
        ] as $code => $amount) {
            $definition = ComponentDefinition::where('code', $code)->first();
            if ($definition && bccomp($amount, '0', 6) === 1) {
                $employerLines[] = $this->buildLinePayload($definition, $amount, [
                    'source_type' => 'statutory',
                    'calculation_basis' => "Calculated {$definition->name}",
                ]);
            }
        }

        $totalDeductions = '0.000000';
        foreach ([$tax, $pfEmployee, $esiEmployee, $professionalTax, $voluntaryDeductions] as $amount) {
            $totalDeductions = $this->addAmount($totalDeductions, $amount);
        }

        $taxableIncome = $this->subtractAmount($grossSalary, $pfEmployee);

        return [
            'tax' => $tax,
            'pf_employee' => $pfEmployee,
            'pf_employer' => $pfEmployer,
            'esi_employee' => $esiEmployee,
            'esi_employer' => $esiEmployer,
            'professional_tax' => $professionalTax,
            'voluntary_deductions' => $voluntaryDeductions,
            'leave_deduction' => $leaveDeduction,
            'total_deductions' => $totalDeductions,
            'taxable_income' => $taxableIncome,
            'deduction_lines' => $deductionLines,
            'employer_lines' => $employerLines,
        ];
    }

    public function getNetSalary(User $user, array $period): array
    {
        $gross = $this->getGrossSalary($user, $period);
        $deductions = $this->getDeductions($user, $period, $gross);
        $net = $this->subtractAmount($gross['gross'], $deductions['total_deductions']);

        return array_merge($gross, $deductions, [
            'net' => $net,
        ]);
    }

    public function calculateDailyRate(string|int|float $baseSalary, int $workingDays = 26): string
    {
        return $this->divideAmount((string) $baseSalary, (string) max($workingDays, 1));
    }

    public function adjustForLeave(string|int|float $salary, string|int|float $leaveTaken, int $workingDays = 26): string
    {
        $dailyRate = $this->calculateDailyRate((string) $salary, $workingDays);

        return $this->multiplyAmount($dailyRate, (string) $leaveTaken);
    }

    public function calculateTax(string|int|float $grossSalary, iterable $taxBrackets): string
    {
        $grossSalary = $this->normalizeDecimal((string) $grossSalary);
        $annualizedGross = $this->multiplyAmount($grossSalary, '12');
        $tax = '0.000000';

        $brackets = collect($taxBrackets)->sortBy('income_from');
        foreach ($brackets as $bracket) {
            $from = $this->normalizeDecimal((string) $bracket->income_from);
            $to = $bracket->income_to !== null ? $this->normalizeDecimal((string) $bracket->income_to) : null;

            if (bccomp($annualizedGross, $from, 6) <= 0) {
                continue;
            }

            $taxableInBracket = $to === null
                ? $this->subtractAmount($annualizedGross, $from)
                : $this->subtractAmount(
                    bccomp($annualizedGross, $to, 6) === 1 ? $to : $annualizedGross,
                    $from
                );

            if (bccomp($taxableInBracket, '0', 6) <= 0) {
                continue;
            }

            $rate = $this->percentToDecimal((string) $bracket->rate_percent);
            $tax = $this->addAmount($tax, (string) ($bracket->fixed_tax_amount ?? '0'));
            $tax = $this->addAmount($tax, $this->multiplyAmount($taxableInBracket, $rate));
        }

        return $this->divideAmount($tax, '12');
    }

    public function buildSlipFromPayload(PayrollSlip $slip, array $payload): PayrollSlip
    {
        $slip->payable_days = $this->normalizeDecimal(
            $this->addAmount(
                $payload['worked_days'] ?? '0.0000',
                $payload['paid_leave_days'] ?? '0.0000',
                4
            ),
            4
        );
        $slip->worked_days = $this->normalizeDecimal($payload['worked_days'] ?? '0', 4);
        $slip->paid_leave_days = $this->normalizeDecimal($payload['paid_leave_days'] ?? '0', 4);
        $slip->unpaid_leave_days = $this->normalizeDecimal($payload['unpaid_leave_days'] ?? '0', 4);
        $slip->overtime_hours = $this->normalizeDecimal($payload['overtime_hours'] ?? '0', 4);
        $slip->gross_earnings = $payload['gross'];
        $slip->total_deductions = $payload['total_deductions'];
        $slip->employer_contributions = $this->addAmount($payload['pf_employer'], $payload['esi_employer']);
        $slip->taxable_income = $payload['taxable_income'];
        $slip->tax_amount = $payload['tax'];
        $slip->net_pay = $payload['net'];

        return $slip;
    }

    public function addAmount(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcadd($this->normalizeDecimal($left, $scale), $this->normalizeDecimal($right, $scale), $scale);
    }

    public function subtractAmount(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcsub($this->normalizeDecimal($left, $scale), $this->normalizeDecimal($right, $scale), $scale);
    }

    public function multiplyAmount(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        return bcmul($this->normalizeDecimal($left, $scale), $this->normalizeDecimal($right, $scale), $scale);
    }

    public function divideAmount(string|int|float $left, string|int|float $right, int $scale = 6): string
    {
        if (bccomp($this->normalizeDecimal($right, $scale), '0', $scale) === 0) {
            return $this->normalizeDecimal('0', $scale);
        }

        return bcdiv($this->normalizeDecimal($left, $scale), $this->normalizeDecimal($right, $scale), $scale);
    }

    public function buildLinePayload(
        ComponentDefinition $definition,
        string $amount,
        array $attributes = []
    ): array {
        return [
            'component_definition_id' => $definition->id,
            'line_code' => $definition->code,
            'line_name' => $definition->name,
            'line_category' => $definition->category,
            'source_type' => $attributes['source_type'] ?? 'salary_structure',
            'source_reference_type' => $attributes['source_reference_type'] ?? null,
            'source_reference_id' => $attributes['source_reference_id'] ?? null,
            'calculation_basis' => $attributes['calculation_basis'] ?? null,
            'quantity' => isset($attributes['quantity']) ? $this->normalizeDecimal((string) $attributes['quantity'], 4) : null,
            'rate' => isset($attributes['rate']) ? $this->normalizeDecimal((string) $attributes['rate'], 6) : null,
            'amount' => $this->normalizeDecimal($amount, 6),
            'taxable_amount' => $definition->taxable ? $this->normalizeDecimal($amount, 6) : '0.000000',
            'is_ytd_included' => $attributes['is_ytd_included'] ?? true,
            'display_order' => $attributes['display_order'] ?? $definition->display_order,
            'metadata' => $attributes['metadata'] ?? null,
        ];
    }

    protected function resolveSalaryStructure(User $user, array $period): SalaryStructure
    {
        $date = $period['period_end'] ?? now()->toDateString();

        return SalaryStructure::with('components.definition', 'taxRegime.brackets')
            ->where('user_id', $user->id)
            ->active($date)
            ->orderByDesc('effective_from')
            ->firstOrFail();
    }

    protected function resolveComponentAmount($component, SalaryStructure $salaryStructure, array $period, string $baseSalary): string
    {
        $definition = $component->definition;
        if ($definition->code === 'BASIC') {
            return $baseSalary;
        }

        if ($component->amount_type === 'percentage' && $component->percentage !== null) {
            $basis = $component->basisComponent?->code === 'BASIC'
                ? $baseSalary
                : $this->normalizeDecimal((string) ($component->amount ?? '0'));

            return $this->multiplyAmount($basis, $this->percentToDecimal((string) $component->percentage));
        }

        $amount = $this->normalizeDecimal((string) ($component->amount ?? '0'));

        return $definition->pro_ratable
            ? $this->prorateAmount(
                $amount,
                $this->normalizeDecimal((string) ($period['payable_days'] ?? $salaryStructure->standard_work_days), 4),
                (string) $salaryStructure->standard_work_days
            )
            : $amount;
    }

    protected function getOvertimeHours(User $user, array $period): string
    {
        $attendanceMinutes = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$period['period_start'], $period['period_end']])
            ->sum('overtime_minutes');

        $timeHours = TimeTracker::query()
            ->completed()
            ->where('user_id', $user->id)
            ->whereBetween('start_time', [$period['period_start'] . ' 00:00:00', $period['period_end'] . ' 23:59:59'])
            ->whereHas('category', fn ($query) => $query->where('name', 'OT'))
            ->sum('hours_decimal');

        $attendanceHours = $this->divideAmount((string) $attendanceMinutes, '60');

        return $this->addAmount($attendanceHours, (string) $timeHours);
    }

    protected function getUnpaidLeaveDays(User $user, array $period): string
    {
        $requests = LeaveRequest::with('leaveType')
            ->approved()
            ->where('user_id', $user->id)
            ->where('from_date', '<=', $period['period_end'])
            ->where('to_date', '>=', $period['period_start'])
            ->get();

        $days = '0.0000';
        foreach ($requests as $leave) {
            if ($leave->leaveType?->is_paid) {
                continue;
            }

            $days = bcadd($days, $this->overlapLeaveDays($leave, $period), 4);
        }

        return $days;
    }

    protected function overlapLeaveDays(LeaveRequest $leave, array $period): string
    {
        $start = Carbon::parse($period['period_start'])->max($leave->from_date);
        $end = Carbon::parse($period['period_end'])->min($leave->to_date);

        if ($end->lt($start)) {
            return '0.0000';
        }

        $total = '0.0000';
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $total = bcadd($total, '1.0000', 4);
        }

        if ($leave->half_day && bccomp($total, '0', 4) === 1) {
            $total = bcsub($total, '0.5000', 4);
        }

        return $total;
    }

    protected function sumApprovedAdjustments(User $user, array $period, string $type): string
    {
        $sum = PayrollAdjustment::where('user_id', $user->id)
            ->where('adjustment_type', $type)
            ->where('status', 'approved')
            ->where(function ($query) use ($period) {
                $query->whereNull('start_period')
                    ->orWhere('start_period', '<=', Carbon::parse($period['period_end'])->format('Y-m'));
            })
            ->where(function ($query) use ($period) {
                $query->whereNull('end_period')
                    ->orWhere('end_period', '>=', Carbon::parse($period['period_start'])->format('Y-m'));
            })
            ->sum('amount');

        return $this->normalizeDecimal((string) $sum);
    }

    protected function resolveProfessionalTax(string $grossSalary, array $slabs): string
    {
        foreach ($slabs as $slab) {
            $from = $this->normalizeDecimal((string) ($slab['from'] ?? '0'));
            $to = isset($slab['to']) && $slab['to'] !== null ? $this->normalizeDecimal((string) $slab['to']) : null;

            if (bccomp($grossSalary, $from, 6) >= 0 && ($to === null || bccomp($grossSalary, $to, 6) <= 0)) {
                return $this->normalizeDecimal((string) ($slab['amount'] ?? '0'));
            }
        }

        return '0.000000';
    }

    protected function percentToDecimal(string $percent): string
    {
        return $this->divideAmount($percent, '100');
    }

    protected function prorateAmount(string $amount, string $payableDays, string $standardDays, int $scale = 6): string
    {
        $dailyRate = $this->divideAmount($amount, $standardDays, $scale);

        return $this->multiplyAmount($dailyRate, $payableDays, $scale);
    }

    protected function calculateHourlyRate(string $baseSalary, string $monthlyHours, int $scale = 6): string
    {
        return $this->divideAmount($baseSalary, $monthlyHours, $scale);
    }

    public function normalizeDecimal(string|int|float|null $value, int $scale = 6): string
    {
        $numeric = $value === null || $value === '' ? '0' : (string) $value;

        if (!str_contains($numeric, '.')) {
            return $numeric . '.' . str_repeat('0', $scale);
        }

        [$whole, $fraction] = explode('.', $numeric, 2);

        return $whole . '.' . str_pad(substr($fraction, 0, $scale), $scale, '0');
    }
}
