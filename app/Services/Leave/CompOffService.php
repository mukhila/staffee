<?php

namespace App\Services\Leave;

use App\Models\Attendance;
use App\Models\Leave\CompOffCredit;
use App\Models\Leave\LeaveType;
use App\Models\User;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Compensatory off: a day of leave earned by working on a weekend or public holiday.
 */
class CompOffService
{
    public const LEAVE_TYPE_CODE = 'COMP';

    /** Worked minutes below this earn half a day instead of a full day. */
    public const FULL_DAY_MINUTES = 300;

    public function __construct(
        private readonly LeaveBalanceService $balanceSvc,
        private readonly ShiftService $shiftSvc,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function request(User $employee, string $workedDate, string $reason): CompOffCredit
    {
        $date = Carbon::parse($workedDate)->startOfDay();

        if ($date->isFuture()) {
            throw new InvalidArgumentException('You cannot claim comp-off for a future date.');
        }

        if (!$this->isNonWorkingDay($date)) {
            throw new InvalidArgumentException('Comp-off can only be claimed for a weekend or public holiday.');
        }

        $attendance = Attendance::where('user_id', $employee->id)
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('check_in')
            ->first();

        if (!$attendance) {
            throw new InvalidArgumentException('No attendance was recorded for you on that date.');
        }

        $exists = CompOffCredit::where('user_id', $employee->id)
            ->whereDate('worked_date', $date->toDateString())
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('You have already claimed comp-off for that date.');
        }

        return CompOffCredit::create([
            'user_id'     => $employee->id,
            'worked_date' => $date->toDateString(),
            'days'        => $this->daysEarned($attendance),
            'reason'      => $reason,
            'status'      => 'pending',
        ]);
    }

    /**
     * Approve the claim and credit the days to the employee's comp-off leave balance.
     *
     * @throws InvalidArgumentException
     */
    public function approve(CompOffCredit $credit, User $reviewer, ?string $notes = null): void
    {
        $this->guardReviewable($credit, $reviewer);

        DB::transaction(function () use ($credit, $reviewer, $notes) {
            $balance = $this->balanceSvc->getBalance(
                $credit->user,
                $this->leaveType(),
                $credit->worked_date->year
            );
            $balance->increment('accrued_days', $credit->days);

            $credit->update([
                'status'       => 'approved',
                'reviewed_by'  => $reviewer->id,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function reject(CompOffCredit $credit, User $reviewer, string $notes): void
    {
        $this->guardReviewable($credit, $reviewer);

        $credit->update([
            'status'       => 'rejected',
            'reviewed_by'  => $reviewer->id,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);
    }

    /** The leave type comp-off days are credited to; created on first use. */
    public function leaveType(): LeaveType
    {
        return LeaveType::firstOrCreate(
            ['code' => self::LEAVE_TYPE_CODE],
            [
                'name'              => 'Compensatory Off',
                'category'          => 'special',
                'color'             => '#0ea5e9',
                'is_paid'           => true,
                'requires_approval' => true,
                'allow_half_day'    => true,
                'is_active'         => true,
                'description'       => 'Earned by working on a weekend or public holiday.',
            ]
        );
    }

    public function isNonWorkingDay(Carbon $date): bool
    {
        return $date->isWeekend() || $this->shiftSvc->isHoliday($date);
    }

    private function daysEarned(Attendance $attendance): string
    {
        $minutes = $attendance->worked_minutes;

        if (!$minutes && $attendance->check_in && $attendance->check_out) {
            $minutes = (int) Carbon::parse($attendance->check_in)->diffInMinutes(Carbon::parse($attendance->check_out));
        }

        // No check-out recorded: leave it to the approver, default to a full day.
        if (!$minutes) {
            return '1.0';
        }

        return $minutes >= self::FULL_DAY_MINUTES ? '1.0' : '0.5';
    }

    private function guardReviewable(CompOffCredit $credit, User $reviewer): void
    {
        if (!$credit->isPending()) {
            throw new InvalidArgumentException('This claim has already been reviewed.');
        }

        if ($credit->user_id === $reviewer->id) {
            throw new InvalidArgumentException('You cannot review your own claim.');
        }
    }
}
