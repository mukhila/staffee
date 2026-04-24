<?php

namespace App\Http\Controllers\Admin\Time;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\TimeTracker;
use App\Models\User;
use App\Services\Time\TimeTrackingService;
use App\Services\Time\UtilizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TimeReportController extends Controller
{
    public function __construct(
        private readonly TimeTrackingService $timeSvc,
        private readonly UtilizationService $utilizationSvc,
    ) {}

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $totalHours    = (float) TimeTracker::completed()->forDateRange($from->toDateString(), $to->toDateString())->sum('hours_decimal');
        $billableHours = (float) TimeTracker::completed()->billable()->forDateRange($from->toDateString(), $to->toDateString())->sum('hours_decimal');
        $totalRevenue  = (float) TimeTracker::completed()->billable()->forDateRange($from->toDateString(), $to->toDateString())
            ->selectRaw('SUM(hours_decimal * COALESCE(rate_snapshot, 0))')->value('SUM(hours_decimal * COALESCE(rate_snapshot, 0))');

        $utilization  = $totalHours > 0 ? round($billableHours / $totalHours * 100, 1) : 0;
        $trend        = $this->utilizationSvc->getMonthlyTrend(now()->year);
        $departments  = $this->utilizationSvc->getDepartmentMetrics($from, $to);
        $underutilized= $this->utilizationSvc->getUnderutilizedUsers($from, $to);

        return view('admin.time.reports.index', compact(
            'from', 'to', 'totalHours', 'billableHours', 'totalRevenue',
            'utilization', 'trend', 'departments', 'underutilized'
        ));
    }

    public function utilization(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $deptId      = $request->department ? (int) $request->department : null;
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $metrics = $this->utilizationSvc->getTeamMetrics($from, $to, $deptId)
            ->sortByDesc('utilization_pct')
            ->values();

        return view('admin.time.reports.utilization', compact('from', 'to', 'metrics', 'departments', 'deptId'));
    }

    public function revenue(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);
        $projectId   = $request->project ? (int) $request->project : null;
        $projects    = Project::orderBy('name')->get();

        $byProject = TimeTracker::completed()->billable()
            ->forDateRange($from->toDateString(), $to->toDateString())
            ->whereNotNull('project_id')
            ->when($projectId, fn ($q) => $q->forProject($projectId))
            ->join('projects', 'time_trackers.project_id', '=', 'projects.id')
            ->join('users', 'time_trackers.user_id', '=', 'users.id')
            ->selectRaw('projects.id as project_id, projects.name as project_name,
                         users.id as user_id, users.name as user_name,
                         SUM(hours_decimal) as hours,
                         SUM(hours_decimal * COALESCE(rate_snapshot, 0)) as revenue,
                         COUNT(*) as entry_count')
            ->groupBy('projects.id', 'projects.name', 'users.id', 'users.name')
            ->orderByDesc('revenue')
            ->get();

        $grandTotal = round($byProject->sum('revenue'), 2);
        $totalHours = round($byProject->sum('hours'), 2);

        return view('admin.time.reports.revenue', compact(
            'from', 'to', 'byProject', 'grandTotal', 'totalHours', 'projects', 'projectId'
        ));
    }

    /**
     * Invoice-ready export: billable entries for a project in CSV.
     */
    public function export(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'from'       => 'required|date',
            'to'         => 'required|date|after_or_equal:from',
        ]);

        $entries = TimeTracker::completed()->billable()
            ->forProject($request->project_id)
            ->forDateRange($request->from, $request->to)
            ->with(['user', 'category', 'trackable'])
            ->orderBy('start_time')
            ->get();

        $project = Project::findOrFail($request->project_id);
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$project->name}-time-{$request->from}-{$request->to}.csv\"",
        ];

        $csv = "Date,Employee,Category,Task/Bug,Hours,Rate,Revenue,Description\n";
        foreach ($entries as $e) {
            $csv .= implode(',', [
                $e->start_time->format('Y-m-d'),
                '"' . str_replace('"', '""', $e->user->name) . '"',
                '"' . str_replace('"', '""', $e->category?->name ?? 'Uncategorised') . '"',
                '"' . str_replace('"', '""', $e->trackable?->title ?? '') . '"',
                number_format((float) $e->hours_decimal, 2),
                number_format((float) ($e->rate_snapshot ?? 0), 2),
                number_format($e->revenue, 2),
                '"' . str_replace('"', '""', $e->description ?? '') . '"',
            ]) . "\n";
        }

        return response($csv, 200, $headers);
    }

    private function resolveDateRange(Request $request): array
    {
        $from = $request->from ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to   = $request->to   ? Carbon::parse($request->to)->endOfDay()   : now()->endOfDay();
        return [$from, $to];
    }
}
