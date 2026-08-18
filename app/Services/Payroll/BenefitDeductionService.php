<?php

namespace App\Services\Payroll;

use App\Models\Payroll\EmployeeBenefitDeduction;
use App\Models\User;
use Illuminate\Support\Collection;

class BenefitDeductionService
{
    /**
     * Create a benefit deduction record for an employee.
     *
     * @throws \InvalidArgumentException if effective_to is before effective_from
     */
    public function createDeduction(User $user, array $data, int $actorId): EmployeeBenefitDeduction
    {
        if (
            isset($data['effective_to']) &&
            $data['effective_to'] !== null &&
            $data['effective_to'] !== '' &&
            $data['effective_to'] < $data['effective_from']
        ) {
            throw new \InvalidArgumentException('effective_to cannot be before effective_from.');
        }

        return EmployeeBenefitDeduction::create([
            'user_id'        => $user->id,
            'benefit_name'   => $data['benefit_name'],
            'benefit_type'   => $data['benefit_type'],
            'amount'         => (string) $data['amount'],
            'effective_from' => $data['effective_from'],
            'effective_to'   => $data['effective_to'] ?? null,
            'frequency'      => $data['frequency'] ?? 'monthly',
            'status'         => 'active',
            'notes'          => $data['notes'] ?? null,
            'created_by'     => $actorId,
        ]);
    }

    /**
     * Pause an active deduction.
     */
    public function pause(EmployeeBenefitDeduction $deduction, int $actorId): void
    {
        $deduction->update(['status' => 'paused']);
    }

    /**
     * Resume a paused deduction.
     */
    public function resume(EmployeeBenefitDeduction $deduction, int $actorId): void
    {
        $deduction->update(['status' => 'active']);
    }

    /**
     * Terminate a deduction (sets status to 'terminated' and effective_to to today).
     */
    public function terminate(EmployeeBenefitDeduction $deduction, int $actorId): void
    {
        $deduction->update([
            'status'       => 'terminated',
            'effective_to' => now()->toDateString(),
        ]);
    }

    /**
     * Return all active deductions effective in a given period for a user.
     * Used by PayrollCalculationService during a payroll run.
     */
    public function activeDeductionsForPeriod(User $user, string $period): Collection
    {
        return EmployeeBenefitDeduction::where('user_id', $user->id)
            ->active()
            ->effectiveForPeriod($period)
            ->get();
    }
}
