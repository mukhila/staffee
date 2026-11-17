<?php

namespace App\Services\Project;

use App\Models\Payroll\EmployeeSalaryStructure;
use App\Models\Project;
use App\Models\TimeTracker;
use Carbon\Carbon;

/**
 * Budget against actuals for a project.
 *
 * - Hours: completed time entries on the project, excluding rejected ones.
 * - Labour cost: each person's hours × their hourly cost on that day, where
 *   hourly cost = monthly base salary ÷ (standard work days × standard hours)
 *   from the salary structure in force on the date of the entry.
 * - Billable value: billable hours × the rate captured on each entry.
 *
 * All money is BCMath strings with 6 decimal places.
 */
class ProjectFinanceService
{
    private const SCALE = 6;

    /** @var array<string, ?string> cache of hourly cost by "userId|date" */
    private array $hourlyCost = [];

    public function summary(Project $project): array
    {
        $entries = TimeTracker::where('project_id', $project->id)
            ->whereNotNull('end_time')
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', '!=', 'rejected'))
            ->get(['id', 'user_id', 'start_time', 'end_time', 'hours_decimal', 'is_billable', 'rate_snapshot', 'approval_status']);

        $hours          = '0';
        $billableHours  = '0';
        $pendingHours   = '0';
        $cost           = '0';
        $billable       = '0';
        $uncostedHours  = '0';
        $unratedHours   = '0';
        $byPerson       = [];

        foreach ($entries as $entry) {
            $h = $this->hours($entry);
            if (bccomp($h, '0', self::SCALE) <= 0) {
                continue;
            }

            $hours = bcadd($hours, $h, self::SCALE);
            if ($entry->approval_status === 'pending') {
                $pendingHours = bcadd($pendingHours, $h, self::SCALE);
            }

            $rate = $this->hourlyCost($entry->user_id, Carbon::parse($entry->start_time));
            $entryCost = '0';
            if ($rate === null) {
                $uncostedHours = bcadd($uncostedHours, $h, self::SCALE);
            } else {
                $entryCost = bcmul($h, $rate, self::SCALE);
                $cost = bcadd($cost, $entryCost, self::SCALE);
            }

            if ($entry->is_billable) {
                $billableHours = bcadd($billableHours, $h, self::SCALE);
                if ($entry->rate_snapshot === null) {
                    $unratedHours = bcadd($unratedHours, $h, self::SCALE);
                } else {
                    $billable = bcadd($billable, bcmul($h, (string) $entry->rate_snapshot, self::SCALE), self::SCALE);
                }
            }

            $byPerson[$entry->user_id] ??= ['hours' => '0', 'cost' => '0'];
            $byPerson[$entry->user_id]['hours'] = bcadd($byPerson[$entry->user_id]['hours'], $h, self::SCALE);
            $byPerson[$entry->user_id]['cost']  = bcadd($byPerson[$entry->user_id]['cost'], $entryCost, self::SCALE);
        }

        // Forecast from task estimates: what is still to do on open tasks.
        $remainingHours = '0';
        $loggedByTask = TimeTracker::where('project_id', $project->id)
            ->whereNotNull('end_time')
            ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', '!=', 'rejected'))
            ->where('trackable_type', \App\Models\Task::class)
            ->selectRaw('trackable_id, SUM(hours_decimal) as hours')
            ->groupBy('trackable_id')
            ->pluck('hours', 'trackable_id');

        $estimatedTotal = '0';
        $unestimatedOpen = 0;
        foreach ($project->tasks()->get(['id', 'status', 'estimated_hours']) as $task) {
            if ($task->estimated_hours === null) {
                if ($task->status !== 'completed') {
                    $unestimatedOpen++;
                }
                continue;
            }
            $estimatedTotal = bcadd($estimatedTotal, (string) $task->estimated_hours, self::SCALE);

            if ($task->status === 'completed') {
                continue;
            }
            $left = bcsub((string) $task->estimated_hours, (string) ($loggedByTask[$task->id] ?? '0'), self::SCALE);
            if (bccomp($left, '0', self::SCALE) > 0) {
                $remainingHours = bcadd($remainingHours, $left, self::SCALE);
            }
        }

        $costedHours   = bcsub($hours, $uncostedHours, self::SCALE);
        $avgCostPerHr  = bccomp($costedHours, '0', self::SCALE) > 0 ? bcdiv($cost, $costedHours, self::SCALE) : null;
        $projectedCost = $avgCostPerHr !== null ? bcadd($cost, bcmul($remainingHours, $avgCostPerHr, self::SCALE), self::SCALE) : null;
        $projectedHours = bcadd($hours, $remainingHours, self::SCALE);

        $budget      = $project->budget_amount !== null ? (string) $project->budget_amount : null;
        $budgetHours = $project->budget_hours !== null ? (string) $project->budget_hours : null;

        return [
            'currency'          => $project->budget_currency,
            'budget'            => $budget,
            'budget_hours'      => $budgetHours,
            'hours'             => $hours,
            'billable_hours'    => $billableHours,
            'pending_hours'     => $pendingHours,
            'cost'              => $cost,
            'billable_value'    => $billable,
            'margin'            => bcsub($billable, $cost, self::SCALE),
            'uncosted_hours'    => $uncostedHours,
            'unrated_hours'     => $unratedHours,
            'remaining_hours'   => $remainingHours,
            'estimated_hours'   => $estimatedTotal,
            'unestimated_open'  => $unestimatedOpen,
            'projected_hours'   => $projectedHours,
            'projected_cost'    => $projectedCost,
            'budget_used_pct'   => $this->percent($cost, $budget),
            'hours_used_pct'    => $this->percent($hours, $budgetHours),
            'projected_pct'     => $projectedCost !== null ? $this->percent($projectedCost, $budget) : null,
            'by_person'         => $byPerson,
        ];
    }

    /**
     * Hourly labour cost for a person on a date, or null when they have no
     * salary structure in force then.
     */
    public function hourlyCost(int $userId, Carbon $date): ?string
    {
        $key = $userId . '|' . $date->toDateString();

        if (array_key_exists($key, $this->hourlyCost)) {
            return $this->hourlyCost[$key];
        }

        $structure = EmployeeSalaryStructure::where('user_id', $userId)
            ->active($date->toDateString())
            ->orderByDesc('effective_from')
            ->first(['monthly_base_salary', 'standard_work_days', 'standard_work_hours']);

        $rate = null;
        if ($structure && $structure->monthly_base_salary !== null) {
            $days  = (string) max(1, (int) ($structure->standard_work_days ?: 26));
            $hours = (string) max(1, (float) ($structure->standard_work_hours ?: 8));
            $rate  = bcdiv((string) $structure->monthly_base_salary, bcmul($days, $hours, self::SCALE), self::SCALE);
        }

        return $this->hourlyCost[$key] = $rate;
    }

    private function hours(TimeTracker $entry): string
    {
        if ($entry->hours_decimal !== null) {
            return (string) $entry->hours_decimal;
        }

        $seconds = Carbon::parse($entry->start_time)->diffInSeconds(Carbon::parse($entry->end_time));

        return bcdiv((string) max(0, (int) $seconds), '3600', self::SCALE);
    }

    private function percent(string $value, ?string $of): ?int
    {
        if ($of === null || bccomp($of, '0', self::SCALE) <= 0) {
            return null;
        }

        return (int) bcdiv(bcmul($value, '100', self::SCALE), $of, 0);
    }
}
