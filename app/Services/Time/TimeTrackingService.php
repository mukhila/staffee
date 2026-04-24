<?php

namespace App\Services\Time;

use App\Models\BillableRate;
use App\Models\Project;
use App\Models\TimeCategory;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimeTrackingService
{
    public function __construct(private readonly BillableRateService $rateSvc) {}

    // ── Stop a running timer ──────────────────────────────────────────────────

    /**
     * Stop the active timer for a user with full billable metadata.
     *
     * @throws \RuntimeException if no running timer
     */
    public function stop(
        User $user,
        string $description,
        ?int $categoryId,
        ?string $notes = null,
        ?string $status = null,
    ): TimeTracker {
        $entry = TimeTracker::forUser($user->id)->running()->firstOrFail();

        $category    = $categoryId ? TimeCategory::find($categoryId) : null;
        $isBillable  = $category ? $category->is_billable : true;

        $entry->fill([
            'end_time'    => now(),
            'description' => $description,
            'category_id' => $categoryId,
            'is_billable' => $isBillable,
            'notes'       => $notes,
        ]);
        $entry->save();

        // Compute and store duration
        $entry->computeAndStoreHours();

        // Snapshot the effective rate
        $this->rateSvc->snapshotRate($entry);

        // Propagate status to trackable item
        if ($status && $entry->trackable) {
            $entry->trackable->update(['status' => $status]);
        }

        return $entry->fresh();
    }

    // ── Aggregation helpers ───────────────────────────────────────────────────

    /**
     * Total hours (billable + non-billable) for a user in a date range.
     */
    public function totalHours(User $user, Carbon $from, Carbon $to): float
    {
        return (float) TimeTracker::forUser($user->id)
            ->completed()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->sum('hours_decimal');
    }

    /**
     * Billable hours for a user in a date range.
     */
    public function billableHours(User $user, Carbon $from, Carbon $to, ?int $projectId = null): float
    {
        return (float) TimeTracker::forUser($user->id)
            ->completed()
            ->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->when($projectId, fn ($q) => $q->forProject($projectId))
            ->sum('hours_decimal');
    }

    /**
     * Hours broken down by category for a user in a date range.
     * Returns [category_name => hours, ...]
     */
    public function hoursByCategory(User $user, Carbon $from, Carbon $to): array
    {
        return TimeTracker::forUser($user->id)
            ->completed()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->join('time_categories', 'time_trackers.category_id', '=', 'time_categories.id')
            ->selectRaw('time_categories.name, time_categories.color, SUM(hours_decimal) as hours, SUM(is_billable * hours_decimal) as billable_hours')
            ->groupBy('time_categories.id', 'time_categories.name', 'time_categories.color')
            ->orderByDesc('hours')
            ->get()
            ->keyBy('name')
            ->map(fn ($row) => [
                'hours'          => (float) $row->hours,
                'billable_hours' => (float) $row->billable_hours,
                'color'          => $row->color,
            ])
            ->all();
    }

    /**
     * Hours broken down by project for a user.
     */
    public function hoursByProject(User $user, Carbon $from, Carbon $to): \Illuminate\Support\Collection
    {
        return TimeTracker::forUser($user->id)
            ->completed()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->whereNotNull('project_id')
            ->join('projects', 'time_trackers.project_id', '=', 'projects.id')
            ->selectRaw('projects.id, projects.name, SUM(hours_decimal) as total_hours, SUM(is_billable * hours_decimal) as billable_hours, SUM(is_billable * hours_decimal * COALESCE(rate_snapshot, 0)) as revenue')
            ->groupBy('projects.id', 'projects.name')
            ->orderByDesc('total_hours')
            ->get();
    }

    /**
     * Daily time series for chart rendering.
     * Returns [date => hours, ...]
     */
    public function dailySeries(User $user, Carbon $from, Carbon $to): array
    {
        $rows = TimeTracker::forUser($user->id)
            ->completed()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->selectRaw('DATE(start_time) as day, SUM(hours_decimal) as hours, SUM(is_billable * hours_decimal) as billable')
            ->groupByRaw('DATE(start_time)')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $series = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $series[$key] = [
                'total'    => (float) ($rows[$key]->hours    ?? 0),
                'billable' => (float) ($rows[$key]->billable ?? 0),
            ];
            $cursor->addDay();
        }

        return $series;
    }
}
