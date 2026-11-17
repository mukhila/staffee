<?php

namespace App\Services\Reports\Concerns;

use App\Models\User;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

trait SelectsStaff
{
    /** Active, non-admin staff, optionally in one department. */
    protected function staff(?int $departmentId): Collection
    {
        return User::where('is_active', true)
            ->where('role', '!=', 'admin')
            ->when($departmentId, fn ($q, $d) => $q->where('department_id', $d))
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);
    }

    /** Weekdays in the period that are not public holidays. */
    protected function workingDays(Carbon $from, Carbon $to): int
    {
        $shifts = app(ShiftService::class);

        return collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->filter(fn (Carbon $d) => $d->isWeekday() && !$shifts->isHoliday($d))
            ->count();
    }

    protected static function hours(int|float|string|null $minutes): float
    {
        return round(((float) $minutes) / 60, 1);
    }
}
