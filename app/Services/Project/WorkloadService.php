<?php

namespace App\Services\Project;

use App\Models\LeaveRequest;
use App\Models\Payroll\EmployeeSalaryStructure;
use App\Models\Shift\ShiftHoliday;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

/**
 * Who has more work than time over the coming weeks.
 *
 * Capacity: working days (Mon–Fri, minus public holidays and approved leave)
 * × the person's standard daily hours (from their salary structure, else 8).
 *
 * Load: for each open task with an estimate, the hours not yet logged are
 * spread evenly over the working days between its start and due date.
 * Overdue work is placed in the current week, because it is due now.
 */
class WorkloadService
{
    public const DEFAULT_DAILY_HOURS = 8.0;

    private array $holidayDates = [];
    private array $recurringHolidays = [];

    /**
     * @param  Collection<int, User>  $users
     * @return array{weeks: Collection<int, Carbon>, rows: Collection}
     */
    public function build(Collection $users, Carbon $from, int $weeks, ?int $projectId = null): array
    {
        $from  = $from->copy()->startOfWeek();
        $to    = $from->copy()->addWeeks($weeks)->subDay()->endOfDay();
        $today = Carbon::today();

        $weekStarts = collect(range(0, $weeks - 1))->map(fn ($i) => $from->copy()->addWeeks($i));
        $this->loadHolidays($from, $to);

        $userIds = $users->pluck('id');

        $tasks = Task::with('project:id,name')
            ->whereIn('assigned_to', $userIds)
            ->where('status', '!=', 'completed')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->get(['id', 'project_id', 'title', 'assigned_to', 'status', 'start_date', 'due_date', 'estimated_hours']);

        $logged = TimeTracker::whereIn('trackable_id', $tasks->pluck('id'))
            ->where('trackable_type', Task::class)
            ->whereNotNull('end_time')
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', '!=', 'rejected'))
            ->selectRaw('trackable_id, SUM(hours_decimal) as hours')
            ->groupBy('trackable_id')
            ->pluck('hours', 'trackable_id');

        $leave = LeaveRequest::whereIn('user_id', $userIds)
            ->whereIn('status', ['approved', 'auto_approved'])
            ->whereDate('from_date', '<=', $to->toDateString())
            ->whereDate('to_date', '>=', $from->toDateString())
            ->get(['user_id', 'from_date', 'to_date', 'half_day']);

        $dailyHours = $this->dailyHours($userIds);

        $rows = $users->map(function (User $user) use ($weekStarts, $tasks, $logged, $leave, $dailyHours, $today) {
            $hoursPerDay = $dailyHours[$user->id] ?? self::DEFAULT_DAILY_HOURS;
            $userLeave   = $leave->where('user_id', $user->id);

            $weeks = $weekStarts->mapWithKeys(function (Carbon $weekStart) use ($hoursPerDay, $userLeave) {
                $capacity = 0.0;
                $leaveDays = 0.0;
                foreach (CarbonPeriod::create($weekStart, $weekStart->copy()->addDays(4)) as $day) {
                    if ($this->isHoliday($day)) {
                        continue;
                    }
                    $off = $this->leaveFraction($userLeave, $day);
                    $leaveDays += $off;
                    $capacity += $hoursPerDay * (1 - $off);
                }

                return [$weekStart->toDateString() => [
                    'capacity'    => $capacity,
                    'load'        => 0.0,
                    'leave_days'  => $leaveDays,
                    'tasks'       => [],
                    'unestimated' => [],
                ]];
            })->all();

            foreach ($tasks->where('assigned_to', $user->id) as $task) {
                $this->place($task, $logged[$task->id] ?? 0, $weeks, $today);
            }

            $totalCapacity = array_sum(array_column($weeks, 'capacity'));
            $totalLoad     = array_sum(array_column($weeks, 'load'));

            return [
                'user'      => $user,
                'weeks'     => $weeks,
                'capacity'  => $totalCapacity,
                'load'      => $totalLoad,
                'peak_pct'  => collect($weeks)->map(fn ($w) => self::percent($w['load'], $w['capacity']))->max() ?? 0,
            ];
        });

        return ['weeks' => $weekStarts, 'rows' => $rows->sortByDesc('peak_pct')->values()];
    }

    /** Load as a share of capacity; any load with no capacity counts as 999%. */
    public static function percent(float $load, float $capacity): int
    {
        if ($load <= 0) {
            return 0;
        }

        return $capacity > 0 ? (int) round($load / $capacity * 100) : 999;
    }

    private function place(Task $task, float|string $logged, array &$weeks, Carbon $today): void
    {
        $firstWeek = array_key_first($weeks);
        $lastWeek  = array_key_last($weeks);
        $windowEnd = Carbon::parse($lastWeek)->addDays(4);

        $due   = $task->due_date?->copy();
        $start = $task->start_date?->copy() ?? $today->copy();
        if ($start->lt($today)) {
            $start = $today->copy();
        }

        $overdue = $due && $due->lt($today);
        if ($overdue || !$due || $due->lt($start)) {
            $due = $overdue ? $today->copy() : ($due ?? $start->copy());
            if ($due->lt($start)) {
                $due = $start->copy();
            }
        }

        if ($task->estimated_hours === null) {
            // Can't size it; list it in the week it is due so it is not invisible.
            $key = $this->weekKey($due, $weeks);
            if ($key) {
                $weeks[$key]['unestimated'][] = $this->taskInfo($task, null, $overdue);
            }

            return;
        }

        $remaining = max(0.0, (float) $task->estimated_hours - (float) $logged);
        if ($remaining <= 0) {
            return;
        }

        $days = collect(CarbonPeriod::create($start, $due))
            ->filter(fn (Carbon $d) => $d->isWeekday() && !$this->isHoliday($d))
            ->values();

        if ($days->isEmpty()) {
            $days = collect([$start->isWeekend() ? $start->copy()->next(Carbon::MONDAY) : $start]);
        }

        $perDay = $remaining / $days->count();

        $byWeek = [];
        foreach ($days as $day) {
            if ($day->lt(Carbon::parse($firstWeek)) || $day->gt($windowEnd)) {
                continue;
            }
            $key = $this->weekKey($day, $weeks);
            if ($key) {
                $byWeek[$key] = ($byWeek[$key] ?? 0) + $perDay;
            }
        }

        foreach ($byWeek as $key => $hours) {
            $weeks[$key]['load'] += $hours;
            $weeks[$key]['tasks'][] = $this->taskInfo($task, $hours, $overdue);
        }
    }

    private function taskInfo(Task $task, ?float $hours, bool $overdue): array
    {
        return [
            'id'      => $task->id,
            'title'   => $task->title,
            'project' => $task->project?->name,
            'due'     => $task->due_date?->toDateString(),
            'hours'   => $hours,
            'overdue' => $overdue,
        ];
    }

    private function weekKey(Carbon $day, array $weeks): ?string
    {
        $key = $day->copy()->startOfWeek()->toDateString();

        return array_key_exists($key, $weeks) ? $key : null;
    }

    private function leaveFraction(Collection $leave, Carbon $day): float
    {
        foreach ($leave as $l) {
            if ($day->betweenIncluded($l->from_date, $l->to_date)) {
                return $l->half_day && $l->from_date->isSameDay($l->to_date) ? 0.5 : 1.0;
            }
        }

        return 0.0;
    }

    /** @return array<int, float> */
    private function dailyHours(Collection $userIds): array
    {
        return EmployeeSalaryStructure::whereIn('user_id', $userIds)
            ->active()
            ->orderBy('effective_from')
            ->get(['user_id', 'standard_work_hours'])
            ->mapWithKeys(fn ($s) => [$s->user_id => (float) ($s->standard_work_hours ?: self::DEFAULT_DAILY_HOURS)])
            ->all();
    }

    private function loadHolidays(Carbon $from, Carbon $to): void
    {
        $holidays = ShiftHoliday::where('is_active', true)->get(['date', 'is_recurring']);

        $this->holidayDates      = $holidays->where('is_recurring', false)->map(fn ($h) => Carbon::parse($h->date)->toDateString())->flip()->all();
        $this->recurringHolidays = $holidays->where('is_recurring', true)->map(fn ($h) => Carbon::parse($h->date)->format('m-d'))->flip()->all();
    }

    private function isHoliday(Carbon $day): bool
    {
        return isset($this->holidayDates[$day->toDateString()]) || isset($this->recurringHolidays[$day->format('m-d')]);
    }
}
