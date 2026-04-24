<?php

namespace App\Jobs\Leave;

use App\Models\Leave\LeaveBalance;
use App\Notifications\Leave\LowLeaveBalanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LowBalanceWarningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Warn when available days fall at or below this value. */
    private const THRESHOLD_DAYS = 3;

    public function handle(): void
    {
        $year  = now()->year;
        $count = 0;

        LeaveBalance::with(['user', 'leaveType'])
            ->forYear($year)
            ->get()
            ->filter(function (LeaveBalance $balance): bool {
                $available = $balance->effective_available;
                $maxDays   = $balance->leaveType->max_days_per_year ?? 0;

                // Only warn for types that have a cap and are running low (but not empty — that's a separate alert)
                return $maxDays > 0 && $available > 0 && $available <= self::THRESHOLD_DAYS;
            })
            ->each(function (LeaveBalance $balance) use (&$count): void {
                $balance->user->notify(new LowLeaveBalanceNotification($balance));
                $count++;
                Log::debug("LowBalanceWarning sent to user #{$balance->user_id} — {$balance->effective_available} days of {$balance->leaveType->code} remaining");
            });

        Log::info("LowBalanceWarningJob: {$count} warnings dispatched for {$year}");
    }
}
