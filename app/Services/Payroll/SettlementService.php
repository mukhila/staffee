<?php

namespace App\Services\Payroll;

use App\Models\HR\FinalSettlement;
use App\Models\HR\TerminationRequest;
use App\Models\Payroll\ComponentDefinition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function __construct(
        private readonly PayrollCalculationService $calculationService,
    ) {}

    public function calculateFullAndFinal(User $user, string $lastWorkingDate): array
    {
        $profile = $user->profile;
        $salaryStructure = $user->activeSalaryStructure()->with('components.definition')->first();
        $baseSalary = (string) ($salaryStructure?->base_salary ?? $profile?->current_salary ?? '0');
        $dailyRate = $this->calculationService->calculateDailyRate($baseSalary, (int) ($salaryStructure?->standard_work_days ?? 26));

        // P-09: correct worked-days calculation using actual days in the final month
        $lwdCarbon           = Carbon::parse($lastWorkingDate);
        $pendingSalaryDays   = $lwdCarbon->copy()->startOfMonth()->diffInDays($lwdCarbon) + 1;
        $daysInMonth         = $lwdCarbon->daysInMonth;
        $finalMonthDailyRate = $this->calculationService->calculateDailyRate($baseSalary, $daysInMonth);
        $pendingSalaryAmount = $this->calculationService->multiplyAmount($finalMonthDailyRate, (string) $pendingSalaryDays);

        // P-11 step 6: use raw float years for gratuity rounding
        $rawYears = (float) ($profile?->years_of_service ?? 0);
        $gratuity = $this->calculateGratuity($user, $rawYears);

        $settlementYear = (int) Carbon::parse($lastWorkingDate)->year;

        // P-11 step 5: filter encashable leaves by both is_paid and is_encashable
        $encashableLeaveDays = (string) (\App\Models\Leave\LeaveBalance::where('user_id', $user->id)
            ->where('year', $settlementYear)
            ->whereHas('leaveType', fn ($query) => $query->where('is_paid', true)->where('is_encashable', true))
            ->selectRaw('COALESCE(SUM(available_balance - COALESCE(pending_days, 0)), 0) as total')
            ->value('total') ?? 0);

        $leaveEncashment = $this->calculationService->multiplyAmount($dailyRate, $encashableLeaveDays);
        $unpaidAdjustments = (string) \App\Models\Payroll\PayrollAdjustment::where('user_id', $user->id)
            ->where('adjustment_type', 'deduction')
            ->where('status', 'approved')
            ->sum('amount');

        $totalEarnings = $this->calculationService->addAmount($pendingSalaryAmount, $leaveEncashment);
        $totalEarnings = $this->calculationService->addAmount($totalEarnings, $gratuity);
        $totalDeductions = $this->calculationService->normalizeDecimal($unpaidAdjustments);

        // P-10: notice shortfall deduction
        $noticePenalty = $this->calculateNoticeShortfall($user, $lastWorkingDate, $dailyRate);
        $totalDeductions = $this->calculationService->addAmount($totalDeductions, $noticePenalty['notice_shortfall_deduction']);
        $netPayable = $this->calculationService->subtractAmount($totalEarnings, $totalDeductions);

        return [
            'basic_salary' => $this->calculationService->normalizeDecimal($baseSalary),
            'pending_salary_days' => $pendingSalaryDays,
            'pending_salary_amount' => $pendingSalaryAmount,
            'leave_encashment_days' => $this->calculationService->normalizeDecimal($encashableLeaveDays, 2),
            'leave_encashment_amount' => $leaveEncashment,
            'gratuity' => $gratuity,
            'total_earnings' => $totalEarnings,
            'pending_advances' => $this->calculationService->normalizeDecimal($unpaidAdjustments),
            'total_deductions' => $totalDeductions,
            'notice_shortfall_days' => $noticePenalty['notice_shortfall_days'],
            'notice_shortfall_deduction' => $noticePenalty['notice_shortfall_deduction'],
            'net_payable' => $netPayable,
            'currency' => $salaryStructure?->currency_code ?? $profile?->salary_currency ?? 'USD',
        ];
    }

    public function calculateGratuity(User $user, int|float $yearsOfService): string
    {
        $salaryStructure = $user->activeSalaryStructure()->first();
        $baseSalary = (string) ($salaryStructure?->base_salary ?? $user->profile?->current_salary ?? '0');

        if ($yearsOfService < 5) {
            return '0.000000';
        }

        // P-11 step 6: round up if partial year >= 6 months (0.5 years), floor otherwise
        $wholeYears   = (int) floor($yearsOfService);
        $partialYear  = $yearsOfService - $wholeYears;
        $roundedYears = $partialYear >= 0.5 ? $wholeYears + 1 : $wholeYears;

        $fifteenByTwentySix = $this->calculationService->divideAmount('15', '26');
        $gratuity = $this->calculationService->multiplyAmount(
            $this->calculationService->multiplyAmount($baseSalary, $fifteenByTwentySix),
            (string) $roundedYears
        );

        // Apply statutory ceiling from config (0 = no ceiling)
        $ceiling = (string) config('payroll.gratuity_ceiling', '0');
        if (bccomp($ceiling, '0', 6) > 0 && bccomp($gratuity, $ceiling, 6) > 0) {
            return $this->calculationService->normalizeDecimal($ceiling);
        }

        return $gratuity;
    }

    // P-10: calculate notice period shortfall and deduction
    private function calculateNoticeShortfall(User $user, string $lastWorkingDate, string $dailyRate): array
    {
        $requiredDays = (int) ($user->profile->notice_period_days ?? 0);

        if ($requiredDays === 0) {
            return ['notice_shortfall_days' => 0, 'notice_shortfall_deduction' => '0.000000'];
        }

        $resignation = $user->resignations()
            ->where('status', 'approved')
            ->latest('submitted_date')
            ->first();

        if (!$resignation) {
            return ['notice_shortfall_days' => 0, 'notice_shortfall_deduction' => '0.000000'];
        }

        if ($resignation->notice_waived) {
            return ['notice_shortfall_days' => 0, 'notice_shortfall_deduction' => '0.000000'];
        }

        $servedDays   = Carbon::parse($resignation->submitted_date)->diffInDays(Carbon::parse($lastWorkingDate));
        $shortfallDays = max(0, $requiredDays - $servedDays);

        if ($shortfallDays === 0) {
            return ['notice_shortfall_days' => 0, 'notice_shortfall_deduction' => '0.000000'];
        }

        $deductionAmount = $this->calculationService->multiplyAmount($dailyRate, (string) $shortfallDays);

        return [
            'notice_shortfall_days'      => $shortfallDays,
            'notice_shortfall_deduction' => $deductionAmount,
        ];
    }

    public function generateSettlementSlip(User $user, ?TerminationRequest $termination = null, ?int $actorId = null): FinalSettlement
    {
        if ($actorId === null) {
            throw new \InvalidArgumentException('An acting user must be supplied to generate a settlement slip.');
        }

        $termination ??= $user->terminations()->latest('last_working_date')->firstOrFail();
        $payload = $this->calculateFullAndFinal($user, $termination->last_working_date->toDateString());

        return DB::transaction(function () use ($termination, $user, $payload, $actorId) {
            return FinalSettlement::updateOrCreate(
                ['termination_id' => $termination->id],
                array_merge($payload, [
                    'user_id' => $user->id,
                    'last_working_date' => $termination->last_working_date,
                    'bonus' => '0.00',
                    'other_earnings' => [],
                    'other_deductions' => [],
                    'status' => 'pending_approval',
                    'calculated_by' => $actorId,
                ])
            );
        });
    }
}
