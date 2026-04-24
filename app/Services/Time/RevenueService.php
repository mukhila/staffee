<?php

namespace App\Services\Time;

use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueService
{
    public function __construct(private readonly BillableRateService $rateSvc) {}

    // ── Single-user revenue ───────────────────────────────────────────────────

    /**
     * Total earned revenue for a user over a date range (BCMath precision).
     */
    public function calculateRevenue(User $user, Carbon $from, Carbon $to): float
    {
        $entries = TimeTracker::forUser($user->id)
            ->completed()
            ->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->whereNotNull('hours_decimal')
            ->whereNotNull('rate_snapshot')
            ->get(['hours_decimal', 'rate_snapshot', 'is_billable']);

        return $this->rateSvc->aggregateRevenue($entries);
    }

    // ── Project revenue ───────────────────────────────────────────────────────

    /**
     * Total revenue generated for a specific project in a date range.
     */
    public function projectRevenue(Project $project, Carbon $from, Carbon $to): float
    {
        $result = TimeTracker::completed()
            ->billable()
            ->forProject($project->id)
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->selectRaw('SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as total')
            ->value('total');

        return (float) bcadd((string) ($result ?? 0), '0', 2);
    }

    // ── Team / aggregate revenue ──────────────────────────────────────────────

    /**
     * Revenue by user for the entire team over a period.
     *
     * @return Collection<array{user_id, user_name, hours, revenue}>
     */
    public function teamRevenue(Carbon $from, Carbon $to): Collection
    {
        return TimeTracker::completed()
            ->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->join('users', 'time_trackers.user_id', '=', 'users.id')
            ->selectRaw('users.id as user_id, users.name as user_name,
                         SUM(hours_decimal) as hours,
                         SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as revenue')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->revenue = (float) bcadd((string) $row->revenue, '0', 2);
                $row->hours   = round((float) $row->hours, 2);
                return $row;
            });
    }

    /**
     * Monthly revenue totals for a calendar year.
     *
     * @return Collection<array{month, month_num, revenue, billable_hours}>
     */
    public function revenueByPeriod(int $year): Collection
    {
        $rows = TimeTracker::completed()
            ->billable()
            ->whereYear('start_time', $year)
            ->selectRaw('MONTH(start_time) as month_num,
                         SUM(hours_decimal) as hours,
                         SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as revenue')
            ->groupByRaw('MONTH(start_time)')
            ->orderBy('month_num')
            ->get()
            ->keyBy('month_num');

        $results = collect();
        for ($m = 1; $m <= 12; $m++) {
            $label = Carbon::create($year, $m, 1)->format('M');
            $row   = $rows->get($m);
            $results->push([
                'month'          => $label,
                'month_num'      => $m,
                'revenue'        => $row ? (float) bcadd((string) $row->revenue, '0', 2) : 0.0,
                'billable_hours' => $row ? round((float) $row->hours, 2)                 : 0.0,
            ]);
        }

        return $results;
    }

    // ── Unbilled hours & projections ──────────────────────────────────────────

    /**
     * Sum of completed billable hours where rate_snapshot is null (no rate configured
     * at the time — these entries have earned $0 but the time is not yet priced).
     */
    public function getUnbilledHours(User $user, ?Project $project = null): float
    {
        return (float) TimeTracker::forUser($user->id)
            ->completed()
            ->billable()
            ->whereNull('rate_snapshot')
            ->when($project, fn ($q) => $q->forProject($project->id))
            ->sum('hours_decimal');
    }

    /**
     * Estimate future revenue: estimated hours × current effective rate.
     */
    public function projectFutureRevenue(User $user, ?Project $project, float $estimatedHours): float
    {
        return $this->rateSvc->projectRevenue($user, $project, $estimatedHours);
    }

    // ── Revenue-per-project summary ───────────────────────────────────────────

    /**
     * Revenue breakdown per project over a date range.
     *
     * @return Collection<array{project_id, project_name, hours, revenue, entry_count}>
     */
    public function revenueByProject(Carbon $from, Carbon $to): Collection
    {
        return TimeTracker::completed()
            ->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->whereNotNull('project_id')
            ->join('projects', 'time_trackers.project_id', '=', 'projects.id')
            ->selectRaw('projects.id as project_id, projects.name as project_name,
                         SUM(hours_decimal) as hours,
                         SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as revenue,
                         COUNT(*) as entry_count')
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($row) {
                $row->revenue = (float) bcadd((string) $row->revenue, '0', 2);
                $row->hours   = round((float) $row->hours, 2);
                return $row;
            });
    }
}
