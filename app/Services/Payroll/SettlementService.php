<?php

namespace App\Services\Payroll;

use App\Models\HR\FinalSettlement;
use App\Models\HR\TerminationRequest;
use App\Models\Payroll\ComponentDefinition;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
        $pendingSalaryDays = (int) now()->parse($lastWorkingDate)->day;
        $pendingSalaryAmount = $this->calculationService->multiplyAmount($dailyRate, (string) $pendingSalaryDays);

        $yearsOfService = (int) floor($profile?->years_of_service ?? 0);
        $gratuity = $this->calculateGratuity($user, $yearsOfService);

        $encashableLeaveDays = (string) \App\Models\Leave\LeaveBalance::where('user_id', $user->id)
            ->whereHas('leaveType', fn ($query) => $query->where('is_paid', true))
            ->sum('balance');

        $leaveEncashment = $this->calculationService->multiplyAmount($dailyRate, $encashableLeaveDays);
        $unpaidAdjustments = (string) \App\Models\Payroll\PayrollAdjustment::where('user_id', $user->id)
            ->where('adjustment_type', 'deduction')
            ->where('status', 'approved')
            ->sum('amount');

        $totalEarnings = $this->calculationService->addAmount($pendingSalaryAmount, $leaveEncashment);
        $totalEarnings = $this->calculationService->addAmount($totalEarnings, $gratuity);
        $totalDeductions = $this->calculationService->normalizeDecimal($unpaidAdjustments);
        $netPayable = $this->calculationService->subtractAmount($totalEarnings, $totalDeductions);

        return [
            'basic_salary' => $this->calculationService->normalizeDecimal($baseSalary),
            'pending_salary_days' => $pendingSalaryDays,
            'pending_salary_amount' => $pendingSalaryAmount,
            'leave_encashment_days' => $this->calculationService->normalizeDecimal($encashableLeaveDays, 2),
            'leave_encashment_amount' => $leaveEncashment,
            'gratuity' => $gratuity,
            'total_earnings' => $totalEarnings,
            'pending_advances' => $totalDeductions,
            'total_deductions' => $totalDeductions,
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

        $fifteenByTwentySix = $this->calculationService->divideAmount('15', '26');

        return $this->calculationService->multiplyAmount(
            $this->calculationService->multiplyAmount($baseSalary, $fifteenByTwentySix),
            (string) $yearsOfService
        );
    }

    public function generateSettlementSlip(User $user, ?TerminationRequest $termination = null): FinalSettlement
    {
        $termination ??= $user->terminations()->latest('last_working_date')->firstOrFail();
        $payload = $this->calculateFullAndFinal($user, $termination->last_working_date->toDateString());

        return DB::transaction(function () use ($termination, $user, $payload) {
            return FinalSettlement::updateOrCreate(
                ['termination_id' => $termination->id],
                array_merge($payload, [
                    'user_id' => $user->id,
                    'last_working_date' => $termination->last_working_date,
                    'bonus' => '0.00',
                    'other_earnings' => [],
                    'notice_shortfall_days' => 0,
                    'notice_shortfall_deduction' => '0.00',
                    'other_deductions' => [],
                    'status' => 'pending_approval',
                    'calculated_by' => Auth::id() ?? User::query()->value('id'),
                ])
            );
        });
    }
}
