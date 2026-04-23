<?php

namespace App\Services\Shift;

use App\Models\Shift\Shift;
use App\Models\Shift\ShiftAssignment;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use App\Notifications\ShiftAssignedNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    /**
     * Get the active ShiftAssignment for a user on a given date.
     */
    public function getAssignmentForDate(User $user, Carbon $date): ?ShiftAssignment
    {
        return ShiftAssignment::with('shift.patterns.days')
            ->where('user_id', $user->id)
            ->forDate($date)
            ->first();
    }

    /**
     * Resolve the effective Shift for a user on a date.
     * Returns null when: no assignment, holiday, or rotating day-off.
     */
    public function getShiftForDate(User $user, Carbon $date): ?Shift
    {
        if ($this->isHoliday($date)) {
            return null;
        }

        $assignment = $this->getAssignmentForDate($user, $date);
        if (!$assignment) {
            return null;
        }

        // Rotating shifts: check if this calendar day is a working day in the cycle
        if ($assignment->shift->isRotating() && !$assignment->isWorkingDay($date)) {
            return null;
        }

        // Check working_days config for fixed/flexible (Mon-Fri by default)
        $workingDays = $assignment->shift->working_days ?? ['Mon','Tue','Wed','Thu','Fri'];
        if (!in_array($date->format('D'), $workingDays)) {
            return null;
        }

        return $assignment->shift;
    }

    /**
     * Assign a shift to a user, closing any overlapping active assignment.
     * Guarantees no two active assignments exist for the same user on the same date.
     */
    public function assign(
        User $user,
        Shift $shift,
        Carbon $effectiveFrom,
        ?Carbon $effectiveTo,
        User $assignedBy,
        ?string $notes = null
    ): ShiftAssignment {
        return DB::transaction(function () use ($user, $shift, $effectiveFrom, $effectiveTo, $assignedBy, $notes) {
            // Supersede assignments whose date range overlaps [effectiveFrom, effectiveTo]
            ShiftAssignment::where('user_id', $user->id)
                ->where('status', 'active')
                ->where('effective_from', '<=', ($effectiveTo ?? '9999-12-31'))
                ->where(function ($q) use ($effectiveFrom) {
                    $q->whereNull('effective_to')
                      ->orWhere('effective_to', '>=', $effectiveFrom->toDateString());
                })
                ->each(function (ShiftAssignment $existing) use ($effectiveFrom) {
                    if ($existing->effective_from->lt($effectiveFrom)) {
                        // Trim the end of the existing assignment to the day before
                        $existing->update([
                            'effective_to' => $effectiveFrom->copy()->subDay()->toDateString(),
                            'status'       => 'superseded',
                        ]);
                    } else {
                        $existing->update(['status' => 'superseded']);
                    }
                });

            $assignment = ShiftAssignment::create([
                'user_id'             => $user->id,
                'shift_id'            => $shift->id,
                'effective_from'      => $effectiveFrom->toDateString(),
                'effective_to'        => $effectiveTo?->toDateString(),
                'assigned_by'         => $assignedBy->id,
                'status'              => 'active',
                'pattern_anchor_date' => $shift->isRotating() ? $effectiveFrom->toDateString() : null,
                'notes'               => $notes,
            ]);

            $user->notify(new ShiftAssignedNotification($assignment));

            return $assignment;
        });
    }

    /**
     * Assign the same shift to multiple users at once.
     */
    public function bulkAssign(
        array $userIds,
        Shift $shift,
        Carbon $effectiveFrom,
        ?Carbon $effectiveTo,
        User $assignedBy
    ): int {
        $count = 0;
        foreach ($userIds as $uid) {
            $user = User::find($uid);
            if ($user) {
                $this->assign($user, $shift, $effectiveFrom, $effectiveTo, $assignedBy);
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check whether a date is a configured holiday.
     * Recurring holidays match on month-day regardless of year.
     */
    public function isHoliday(Carbon $date): bool
    {
        return ShiftHoliday::where('is_active', true)
            ->where(function ($q) use ($date) {
                $q->where('date', $date->toDateString())
                  ->orWhere(function ($q2) use ($date) {
                      $q2->where('is_recurring', true)
                         ->whereRaw("DATE_FORMAT(`date`, '%m-%d') = ?", [$date->format('m-d')]);
                  });
            })
            ->exists();
    }

    /**
     * Users with no active shift assignment for a given date.
     */
    public function getUnassignedUsers(Carbon $date): Collection
    {
        $assignedUserIds = ShiftAssignment::forDate($date)->pluck('user_id');
        return User::active()->excludeAdmin()->whereNotIn('id', $assignedUserIds)->get();
    }

    /**
     * Shift utilisation for a date range: [shift_id => employee_count].
     */
    public function utilisationReport(Carbon $from, Carbon $to): Collection
    {
        return ShiftAssignment::with('shift')
            ->active()
            ->where('effective_from', '<=', $to->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $from->toDateString());
            })
            ->get()
            ->groupBy('shift_id')
            ->map(fn ($assignments) => [
                'shift'          => $assignments->first()->shift,
                'employee_count' => $assignments->pluck('user_id')->unique()->count(),
            ]);
    }
}
