<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveAccrualLog;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveAccrualService
{
    public function __construct(private readonly LeaveBalanceService $balanceService) {}

    /**
     * Run accrual for all active employees for all active leave types.
     * Called by the scheduler: monthly on the 1st, quarterly on 1st of Jan/Apr/Jul/Oct, annually on Jan 1st.
     *
     * @return int  Number of accrual records created
     */
    public function runAccrual(Carbon $accrualDate): int
    {
        $count = 0;

        LeaveType::active()->with('policies.department')->get()->each(function (LeaveType $type) use ($accrualDate, &$count) {
            User::active()->excludeAdmin()->each(function (User $user) use ($type, $accrualDate, &$count) {
                $policy = $type->getPolicyFor($user);
                if (!$policy || !$policy->is_active) {
                    return;
                }

                if (!$this->shouldAccrue($policy, $accrualDate)) {
                    return;
                }

                if (!$this->hasVested($user, $policy)) {
                    return;
                }

                $alreadyAccrued = LeaveAccrualLog::where('user_id', $user->id)
                    ->where('leave_type_id', $type->id)
                    ->whereDate('period_start', $this->periodStart($policy, $accrualDate)->toDateString())
                    ->exists();

                if ($alreadyAccrued) {
                    return; // idempotent
                }

                $this->creditAccrual($user, $type, $policy, $accrualDate);
                $count++;
            });
        });

        return $count;
    }

    /**
     * Manually initialise a balance with full annual allocation (immediate method or year-start seeding).
     */
    public function seedAnnualBalance(User $user, LeaveType $type, int $year): void
    {
        $policy = $type->getPolicyFor($user);
        if (!$policy) {
            return;
        }

        $balance = $this->balanceService->getBalance($user, $type, $year);

        if ($balance->accrued_days > 0) {
            return; // already seeded
        }

        DB::transaction(function () use ($balance, $policy, $user, $type, $year) {
            $amount = (float) $policy->max_days_per_year;

            $balance->increment('accrued_days', $amount);
            $balance->update(['last_accrual_date' => Carbon::create($year, 1, 1)]);

            LeaveAccrualLog::create([
                'user_id'        => $user->id,
                'leave_type_id'  => $type->id,
                'leave_balance_id' => $balance->id,
                'period_start'   => Carbon::create($year, 1, 1),
                'period_end'     => Carbon::create($year, 12, 31),
                'days_accrued'   => $amount,
                'accrual_method' => 'annual',
                'notes'          => "Annual allocation for {$year}",
            ]);
        });
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function shouldAccrue(LeavePolicy $policy, Carbon $date): bool
    {
        return match ($policy->accrual_method) {
            'monthly'   => true, // called every month, always accrues
            'quarterly' => in_array($date->month, [1, 4, 7, 10]),
            'annual'    => $date->month === 1 && $date->day === 1,
            'immediate' => false, // credited at balance init, not via scheduler
            default     => false,
        };
    }

    private function hasVested(User $user, LeavePolicy $policy): bool
    {
        if ($policy->vesting_period_months === 0) {
            return true;
        }

        $joinedAt = $user->profile?->date_of_joining ?? $user->created_at;
        return $joinedAt->addMonths($policy->vesting_period_months)->isPast();
    }

    private function periodStart(LeavePolicy $policy, Carbon $date): Carbon
    {
        return match ($policy->accrual_method) {
            'monthly'   => $date->copy()->startOfMonth(),
            'quarterly' => $date->copy()->startOfQuarter(),
            'annual'    => $date->copy()->startOfYear(),
            default     => $date->copy()->startOfMonth(),
        };
    }

    private function periodEnd(LeavePolicy $policy, Carbon $date): Carbon
    {
        return match ($policy->accrual_method) {
            'monthly'   => $date->copy()->endOfMonth(),
            'quarterly' => $date->copy()->endOfQuarter(),
            'annual'    => $date->copy()->endOfYear(),
            default     => $date->copy()->endOfMonth(),
        };
    }

    private function creditAccrual(User $user, LeaveType $type, LeavePolicy $policy, Carbon $date): void
    {
        $amount      = $policy->accrualPerPeriod();
        $periodStart = $this->periodStart($policy, $date);
        $periodEnd   = $this->periodEnd($policy, $date);

        DB::transaction(function () use ($user, $type, $policy, $amount, $periodStart, $periodEnd) {
            $balance = $this->balanceService->getBalance($user, $type, $periodStart->year);

            // Cap at max_days_per_year
            $maxAllowed = $policy->max_days_per_year - $balance->accrued_days;
            $credit     = min($amount, max(0, $maxAllowed));

            if ($credit <= 0) {
                return;
            }

            $balance->increment('accrued_days', $credit);
            $balance->update(['last_accrual_date' => $periodStart]);

            LeaveAccrualLog::create([
                'user_id'          => $user->id,
                'leave_type_id'    => $type->id,
                'leave_balance_id' => $balance->id,
                'period_start'     => $periodStart,
                'period_end'       => $periodEnd,
                'days_accrued'     => $credit,
                'accrual_method'   => $policy->accrual_method,
            ]);

            Log::debug("Accrued {$credit} days of {$type->code} for user #{$user->id}");
        });
    }
}
