<?php

namespace App\Services\Time;

use App\Models\Department;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UtilizationService
{
    /** Threshold below which a user is flagged as under-utilised (%). */
    const UNDERUTILIZATION_THRESHOLD = 80.0;

    public function __construct(private readonly TimeTrackingService $timeSvc) {}

    // ── Per-user metrics ──────────────────────────────────────────────────────

    /**
     * Full utilization breakdown for one user over a date range.
     *
     * @return array{
     *   total_hours: float,
     *   billable_hours: float,
     *   non_billable_hours: float,
     *   utilization_pct: float,
     *   revenue: float,
     *   underutilized: bool,
     *   by_category: array,
     *   by_project: Collection,
     * }
     */
    public function getUserMetrics(User $user, Carbon $from, Carbon $to): array
    {
        $total    = $this->timeSvc->totalHours($user, $from, $to);
        $billable = $this->timeSvc->billableHours($user, $from, $to);
        $nonBill  = round($total - $billable, 4);

        $utilizationPct = $total > 0 ? round($billable / $total * 100, 1) : 0.0;

        $revenue = (float) TimeTracker::forUser($user->id)
            ->completed()
            ->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->selectRaw('SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as rev')
            ->value('rev');

        return [
            'total_hours'       => $total,
            'billable_hours'    => $billable,
            'non_billable_hours'=> $nonBill,
            'utilization_pct'   => $utilizationPct,
            'revenue'           => round($revenue, 2),
            'underutilized'     => $total > 0 && $utilizationPct < self::UNDERUTILIZATION_THRESHOLD,
            'by_category'       => $this->timeSvc->hoursByCategory($user, $from, $to),
            'by_project'        => $this->timeSvc->hoursByProject($user, $from, $to),
        ];
    }

    // ── Team / department metrics ─────────────────────────────────────────────

    /**
     * Utilization summary for every active non-admin user in a date range.
     *
     * @return Collection<array{user: User, ...metrics}>
     */
    public function getTeamMetrics(Carbon $from, Carbon $to, ?int $departmentId = null): Collection
    {
        return User::active()
            ->excludeAdmin()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->with('department')
            ->get()
            ->map(fn (User $user) => array_merge(
                ['user' => $user],
                $this->getUserMetrics($user, $from, $to)
            ));
    }

    /**
     * Per-department average utilization.
     *
     * @return Collection<array{department: Department, avg_utilization: float, total_hours: float, revenue: float}>
     */
    public function getDepartmentMetrics(Carbon $from, Carbon $to): Collection
    {
        return Department::where('is_active', true)
            ->with('users')
            ->get()
            ->map(function (Department $dept) use ($from, $to) {
                $members = $dept->users->filter(fn ($u) => $u->role !== 'admin');
                if ($members->isEmpty()) {
                    return null;
                }

                $metrics = $members->map(fn ($u) => $this->getUserMetrics($u, $from, $to));

                return [
                    'department'       => $dept,
                    'member_count'     => $members->count(),
                    'total_hours'      => round($metrics->sum('total_hours'), 2),
                    'billable_hours'   => round($metrics->sum('billable_hours'), 2),
                    'avg_utilization'  => $members->count() > 0
                        ? round($metrics->average('utilization_pct'), 1)
                        : 0.0,
                    'revenue'          => round($metrics->sum('revenue'), 2),
                ];
            })
            ->filter()
            ->values();
    }

    // ── Alert queries ─────────────────────────────────────────────────────────

    /**
     * Users whose utilization is below the configured threshold.
     */
    public function getUnderutilizedUsers(Carbon $from, Carbon $to, float $threshold = self::UNDERUTILIZATION_THRESHOLD): Collection
    {
        return $this->getTeamMetrics($from, $to)
            ->filter(fn ($m) => $m['total_hours'] > 0 && $m['utilization_pct'] < $threshold)
            ->sortBy('utilization_pct')
            ->values();
    }

    /**
     * Monthly utilization trend: [{month, avg_utilization, total_hours, billable_hours}, ...]
     */
    public function getMonthlyTrend(int $year): Collection
    {
        $results = collect();

        for ($month = 1; $month <= 12; $month++) {
            $from = Carbon::create($year, $month, 1)->startOfMonth();
            $to   = $from->copy()->endOfMonth();

            if ($from->isFuture()) {
                break;
            }

            $total    = (float) TimeTracker::completed()->forDateRange($from->toDateString(), $to->toDateString())->sum('hours_decimal');
            $billable = (float) TimeTracker::completed()->billable()->forDateRange($from->toDateString(), $to->toDateString())->sum('hours_decimal');
            $revenue  = (float) TimeTracker::completed()->billable()->forDateRange($from->toDateString(), $to->toDateString())
                ->selectRaw('SUM(hours_decimal * COALESCE(rate_snapshot, 0))')->value('SUM(hours_decimal * COALESCE(rate_snapshot, 0))');

            $results->push([
                'month'         => $from->format('M'),
                'month_num'     => $month,
                'total_hours'   => round($total, 2),
                'billable_hours'=> round($billable, 2),
                'utilization'   => $total > 0 ? round($billable / $total * 100, 1) : 0.0,
                'revenue'       => round((float) $revenue, 2),
            ]);
        }

        return $results;
    }
}
