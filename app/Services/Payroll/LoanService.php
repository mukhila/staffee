<?php

namespace App\Services\Payroll;

use App\Models\Payroll\EmployeeLoan;
use App\Models\Payroll\EmployeeLoanInstallment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LoanService
{
    /**
     * Create a loan and generate the full installment schedule.
     */
    public function createLoan(User $user, array $data, int $actorId): EmployeeLoan
    {
        $principal      = (string) $data['principal_amount'];
        $installmentAmt = (string) $data['installment_amount'];
        $totalCount     = (int) $data['total_installments'];

        return DB::transaction(function () use ($user, $data, $principal, $installmentAmt, $totalCount) {
            $loan = EmployeeLoan::create([
                'user_id'                => $user->id,
                'loan_type'              => $data['loan_type'],
                'principal_amount'       => $principal,
                'issued_date'            => $data['issued_date'],
                'recovery_start_period'  => $data['recovery_start_period'],
                'installment_amount'     => $installmentAmt,
                'total_installments'     => $totalCount,
                'remaining_balance'      => $principal,
                'status'                 => 'active',
                'notes'                  => $data['notes'] ?? null,
            ]);

            // Generate installment rows month-by-month
            $startPeriod = Carbon::createFromFormat('Y-m', $data['recovery_start_period'])->startOfMonth();

            for ($i = 0; $i < $totalCount; $i++) {
                $period = $startPeriod->copy()->addMonths($i)->format('Y-m');

                // Last installment gets whatever is left to avoid rounding drift
                if ($i === $totalCount - 1) {
                    $alreadyScheduled = bcmul($installmentAmt, (string) ($totalCount - 1), 6);
                    $amount = bcsub($principal, $alreadyScheduled, 6);
                    // Clamp to zero if principal was somehow over-collected
                    if (bccomp($amount, '0', 6) < 0) {
                        $amount = '0.000000';
                    }
                } else {
                    $amount = $installmentAmt;
                }

                EmployeeLoanInstallment::create([
                    'loan_id'          => $loan->id,
                    'due_period'       => $period,
                    'scheduled_amount' => $amount,
                    'recovered_amount' => '0.000000',
                    'status'           => 'pending',
                ]);
            }

            return $loan;
        });
    }

    /**
     * Return all pending installments due in a given period (e.g. "2026-09") for a user.
     */
    public function dueInstallments(User $user, string $period): Collection
    {
        return EmployeeLoanInstallment::whereHas('loan', function ($query) use ($user) {
            $query->where('user_id', $user->id)->where('status', 'active');
        })
        ->where('due_period', $period)
        ->where('status', 'pending')
        ->get();
    }

    /**
     * Mark an installment as recovered after payroll has processed it.
     */
    public function markRecovered(EmployeeLoanInstallment $installment, string $amount, ?int $slipLineId = null): void
    {
        DB::transaction(function () use ($installment, $amount, $slipLineId) {
            $installment->update([
                'recovered_amount'    => $amount,
                'status'              => 'processed',
                'payroll_slip_line_id' => $slipLineId,
            ]);

            $this->updateLoanBalance($installment->loan);
        });
    }

    /**
     * Reduce the loan's remaining_balance after an installment is recovered.
     * Marks the loan as completed when balance reaches zero.
     */
    private function updateLoanBalance(EmployeeLoan $loan): void
    {
        $loan->refresh();

        $totalRecovered = '0.000000';
        foreach ($loan->installments()->whereIn('status', ['processed'])->get() as $inst) {
            $totalRecovered = bcadd($totalRecovered, (string) $inst->recovered_amount, 6);
        }

        $newBalance = bcsub((string) $loan->principal_amount, $totalRecovered, 6);
        if (bccomp($newBalance, '0', 6) < 0) {
            $newBalance = '0.000000';
        }

        $status = bccomp($newBalance, '0', 6) === 0 ? 'completed' : $loan->status;

        $loan->update([
            'remaining_balance' => $newBalance,
            'status'            => $status,
        ]);
    }
}
