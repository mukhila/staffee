<?php

namespace App\Services\Leave;

use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;

class LeaveBalanceService
{
    /**
     * Get or initialise a balance row for a user/type/year.
     */
    public function getBalance(User $user, LeaveType $type, ?int $year = null): LeaveBalance
    {
        return LeaveBalance::getOrCreate($user, $type, $year ?? now()->year);
    }

    /**
     * All balances for a user in a given year, loaded with type.
     */
    public function getBalanceSummary(User $user, int $year): \Illuminate\Support\Collection
    {
        return LeaveBalance::with('leaveType')
            ->forUser($user)
            ->forYear($year)
            ->get()
            ->keyBy('leave_type_id');
    }

    /**
     * Debit `used_days` and credit back `pending_days` when a leave is approved.
     * Call inside the same DB transaction as the approval.
     */
    public function consumeLeave(LeaveRequest $request): void
    {
        if (!$request->leave_type_id) {
            return;
        }

        $balance = $this->getBalance(
            $request->user,
            $request->leaveType,
            $request->from_date->year
        );

        $balance->increment('used_days', $request->days);
        $balance->decrement('pending_days', min($request->days, $balance->pending_days));
    }

    /**
     * Reserve days as pending when a request is submitted.
     */
    public function reservePending(LeaveRequest $request): void
    {
        if (!$request->leave_type_id) {
            return;
        }

        $balance = $this->getBalance(
            $request->user,
            $request->leaveType,
            $request->from_date->year
        );

        $balance->increment('pending_days', $request->days);
    }

    /**
     * Release pending reservation when a request is rejected or cancelled.
     */
    public function releasePending(LeaveRequest $request): void
    {
        if (!$request->leave_type_id) {
            return;
        }

        $balance = $this->getBalance(
            $request->user,
            $request->leaveType,
            $request->from_date->year
        );

        $balance->decrement('pending_days', min($request->days, $balance->pending_days));
    }

    /**
     * Release pending AND credit back used when an approved leave is cancelled.
     */
    public function reverseConsumption(LeaveRequest $request): void
    {
        if (!$request->leave_type_id) {
            return;
        }

        $balance = $this->getBalance(
            $request->user,
            $request->leaveType,
            $request->from_date->year
        );

        $balance->decrement('used_days', min($request->days, $balance->used_days));
    }

    /**
     * Check if the user has enough balance for the requested days.
     * Returns true if: unlimited type OR sufficient available balance.
     */
    public function hasSufficientBalance(User $user, LeaveType $type, float $days, int $year): bool
    {
        $policy = $type->getPolicyFor($user);

        if (!$policy || $policy->max_days_per_year == 0) {
            return true; // unlimited
        }

        $balance = $this->getBalance($user, $type, $year);
        return $balance->effective_available >= $days;
    }

    /**
     * Carry-forward year-end: move remaining balance (up to policy cap) to next year's opening.
     * Returns the number of days carried forward.
     */
    public function runYearEndCarryForward(User $user, LeaveType $type, int $fromYear): float
    {
        $policy = $type->getPolicyFor($user);
        if (!$policy || $policy->carry_forward_days <= 0) {
            return 0;
        }

        $currentBalance = $this->getBalance($user, $type, $fromYear);
        $remaining      = $currentBalance->effective_available;
        $carryForward   = min($remaining, $policy->carry_forward_days);

        if ($carryForward <= 0) {
            return 0;
        }

        $nextBalance = $this->getBalance($user, $type, $fromYear + 1);
        $nextBalance->increment('carry_forward_days', $carryForward);

        return $carryForward;
    }

    /**
     * Check for overlapping active requests for the same user within date range.
     */
    public function hasOverlap(User $user, Carbon $from, Carbon $to, ?int $excludeId = null): bool
    {
        return LeaveRequest::forUser($user->id)
            ->active()
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->where('from_date', '<=', $to->toDateString())
            ->where('to_date', '>=', $from->toDateString())
            ->exists();
    }
}
