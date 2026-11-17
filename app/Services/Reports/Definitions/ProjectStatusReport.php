<?php

namespace App\Services\Reports\Definitions;

use App\Models\Project;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use App\Services\Project\ProjectFinanceService;
use App\Services\Reports\ReportDefinition;
use Carbon\Carbon;

class ProjectStatusReport implements ReportDefinition
{
    public function __construct(private readonly ProjectFinanceService $finance) {}

    public function key(): string { return 'project-status'; }

    public function label(): string { return 'Project status'; }

    public function description(): string
    {
        return 'Open and overdue tasks, hours logged in the period and against budget for every live project. Labour cost columns are included only for people allowed to see payroll figures.';
    }

    public function canView(User $viewer): bool { return $viewer->hasPermission('view-projects'); }

    public function supportsDepartment(): bool { return false; }

    private function canSeeMoney(User $viewer): bool
    {
        return $viewer->isAdmin() || $viewer->hasPermission('view-payroll-reports');
    }

    public function headers(User $viewer): array
    {
        $headers = ['Project', 'Status', 'Open tasks', 'Overdue tasks', 'Hours this period', 'Hours total', 'Budget hours', 'Hours used %'];

        return $this->canSeeMoney($viewer)
            ? [...$headers, 'Currency', 'Labour cost', 'Budget', 'Budget used %', 'Projected cost']
            : $headers;
    }

    public function rows(Carbon $from, Carbon $to, ?int $departmentId, User $viewer): array
    {
        $money = $this->canSeeMoney($viewer);
        $today = Carbon::today()->toDateString();

        return Project::whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) use ($from, $to, $money, $today) {
                $f = $this->finance->summary($project);

                $periodHours = TimeTracker::where('project_id', $project->id)
                    ->whereNotNull('end_time')
                    ->where(fn ($q) => $q->whereNull('approval_status')->orWhere('approval_status', '!=', 'rejected'))
                    ->whereBetween('start_time', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->sum('hours_decimal');

                $open    = Task::where('project_id', $project->id)->where('status', '!=', 'completed');
                $row = [
                    $project->name,
                    $project->status,
                    (clone $open)->count(),
                    (clone $open)->whereNotNull('due_date')->whereDate('due_date', '<', $today)->count(),
                    round((float) $periodHours, 1),
                    round((float) $f['hours'], 1),
                    $f['budget_hours'] !== null ? round((float) $f['budget_hours'], 1) : null,
                    $f['hours_used_pct'],
                ];

                if ($money) {
                    $row = [
                        ...$row,
                        $f['currency'],
                        round((float) $f['cost'], 2),
                        $f['budget'] !== null ? round((float) $f['budget'], 2) : null,
                        $f['budget_used_pct'],
                        $f['projected_cost'] !== null ? round((float) $f['projected_cost'], 2) : null,
                    ];
                }

                return $row;
            })
            ->all();
    }

    public function summary(array $rows): array
    {
        if (!$rows) {
            return ['No live projects.'];
        }

        $overdue = array_sum(array_column($rows, 3));
        $over    = collect($rows)->filter(fn ($r) => ($r[7] ?? 0) > 100 || (($r[11] ?? 0) > 100))->pluck(0);

        $lines = [count($rows) . ' live projects, ' . round(array_sum(array_column($rows, 4)), 1) . ' hours logged in the period.'];
        if ($overdue > 0) {
            $lines[] = "{$overdue} tasks are overdue.";
        }
        if ($over->isNotEmpty()) {
            $lines[] = 'Over budget: ' . $over->implode(', ') . '.';
        }

        return $lines;
    }
}
