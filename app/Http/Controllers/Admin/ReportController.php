<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Bug;
use App\Models\LeaveRequest;
use App\Models\Payroll\PayrollSlip;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index()
    {
        $totalStaff     = User::where('role', '!=', 'admin')->count();
        $totalProjects  = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $totalTasks     = Task::count();
        $completedTasks = Task::where('status', 'completed')->count();
        $totalBugs      = Bug::count();
        $openBugs       = Bug::where('status', 'open')->count();
        $resolvedBugs   = Bug::where('status', 'resolved')->orWhere('status', 'closed')->count();

        // Attendance for last 30 days
        $fromDate = now()->subDays(29)->format('Y-m-d');
        $toDate   = now()->format('Y-m-d');

        $attendanceSummary = Attendance::selectRaw('date, COUNT(*) as total, SUM(status = "present") as present, SUM(status = "absent") as absent, SUM(status = "leave") as on_leave')
            ->whereBetween('date', [$fromDate, $toDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Bug severity distribution
        $bugsBySeverity = Bug::selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->get()
            ->pluck('count', 'severity');

        // Task status distribution
        $tasksByStatus = Task::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Project status distribution
        $projectsByStatus = Project::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Top staff by completed tasks
        $topPerformers = User::withCount(['tasks as completed_tasks' => function ($q) {
            $q->where('status', 'completed');
        }])->orderByDesc('completed_tasks')->limit(5)->get();

        return view('admin.reports.index', compact(
            'totalStaff', 'totalProjects', 'activeProjects',
            'totalTasks', 'completedTasks',
            'totalBugs', 'openBugs', 'resolvedBugs',
            'attendanceSummary', 'bugsBySeverity', 'tasksByStatus',
            'projectsByStatus', 'topPerformers'
        ));
    }

    public function attendance(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $records = Attendance::with('user')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date', 'desc')
            ->paginate(30);

        $summary = Attendance::selectRaw('status, COUNT(*) as count')
            ->whereBetween('date', [$from, $to])
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return view('admin.reports.attendance', compact('records', 'summary', 'from', 'to'));
    }

    public function projects(Request $request)
    {
        $projects = Project::with(['users', 'tasks', 'bugs'])->get()->map(function ($project) {
            $project->total_tasks     = $project->tasks->count();
            $project->completed_tasks = $project->tasks->where('status', 'completed')->count();
            $project->open_bugs       = $project->bugs->whereIn('status', ['open', 'in_progress'])->count();
            $project->progress        = $project->total_tasks > 0
                ? round(($project->completed_tasks / $project->total_tasks) * 100)
                : 0;
            return $project;
        });

        return view('admin.reports.projects', compact('projects'));
    }

    public function bugs(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $bugs = Bug::with(['project', 'assignedUser', 'reportedByUser'])
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        $bySeverity = Bug::selectRaw('severity, COUNT(*) as count')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('severity')->get()->pluck('count', 'severity');

        $byStatus = Bug::selectRaw('status, COUNT(*) as count')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->groupBy('status')->get()->pluck('count', 'status');

        return view('admin.reports.bugs', compact('bugs', 'bySeverity', 'byStatus', 'from', 'to'));
    }

    public function leaves(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $records = LeaveRequest::with(['user.department', 'leaveType'])
            ->whereBetween('start_date', [$from, $to])
            ->orderBy('start_date', 'desc')
            ->paginate(30);

        $byStatus = LeaveRequest::selectRaw('status, COUNT(*) as count')
            ->whereBetween('start_date', [$from, $to])
            ->groupBy('status')->get()->pluck('count', 'status');

        return view('admin.reports.leaves', compact('records', 'byStatus', 'from', 'to'));
    }

    public function payroll(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $slips = PayrollSlip::with(['employee.department', 'payrollRun'])
            ->whereHas('payrollRun', fn ($q) => $q->where('for_year', $year)->where('for_month', $mon))
            ->orderBy('net_pay', 'desc')
            ->paginate(30);

        $total = PayrollSlip::whereHas('payrollRun', fn ($q) => $q->where('for_year', $year)->where('for_month', $mon))
            ->selectRaw('SUM(gross_pay) as gross, SUM(total_deductions) as deductions, SUM(net_pay) as net')
            ->first();

        return view('admin.reports.payroll', compact('slips', 'total', 'month'));
    }

    // ── CSV Exports ───────────────────────────────────────────────────────────

    public function exportAttendance(Request $request): StreamedResponse
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $records = Attendance::with('user')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        return $this->streamCsv("attendance_{$from}_{$to}.csv", function () use ($records) {
            yield ['Employee', 'Department', 'Date', 'Status', 'Check In', 'Check Out', 'Hours'];
            foreach ($records as $r) {
                yield [
                    $r->user?->name,
                    $r->user?->department?->name,
                    $r->date,
                    $r->status,
                    $r->check_in,
                    $r->check_out,
                    $r->total_hours ?? '',
                ];
            }
        });
    }

    public function exportLeaves(Request $request): StreamedResponse
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $records = LeaveRequest::with(['user.department', 'leaveType'])
            ->whereBetween('start_date', [$from, $to])
            ->orderBy('start_date')
            ->get();

        return $this->streamCsv("leaves_{$from}_{$to}.csv", function () use ($records) {
            yield ['Employee', 'Department', 'Leave Type', 'From', 'To', 'Days', 'Status', 'Reason'];
            foreach ($records as $r) {
                yield [
                    $r->user?->name,
                    $r->user?->department?->name,
                    $r->leaveType?->name ?? $r->leave_type,
                    $r->start_date,
                    $r->end_date,
                    $r->total_days ?? '',
                    $r->status,
                    $r->reason,
                ];
            }
        });
    }

    public function exportPayroll(Request $request): StreamedResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        $slips = PayrollSlip::with(['employee.department'])
            ->whereHas('payrollRun', fn ($q) => $q->where('for_year', $year)->where('for_month', $mon))
            ->orderBy('net_pay', 'desc')
            ->get();

        return $this->streamCsv("payroll_{$month}.csv", function () use ($slips, $month) {
            yield ['Period', 'Employee', 'Department', 'Gross Pay', 'Total Deductions', 'Tax', 'Net Pay'];
            foreach ($slips as $s) {
                yield [
                    $month,
                    $s->employee?->name,
                    $s->employee?->department?->name,
                    $s->gross_pay,
                    $s->total_deductions,
                    $s->total_tax ?? 0,
                    $s->net_pay,
                ];
            }
        });
    }

    public function exportTime(Request $request): StreamedResponse
    {
        $from = $request->input('from', now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->input('to', now()->format('Y-m-d'));

        $entries = TimeTracker::with(['user.department', 'project'])
            ->whereNotNull('end_time')
            ->whereBetween(\DB::raw('DATE(start_time)'), [$from, $to])
            ->orderBy('start_time')
            ->get();

        return $this->streamCsv("time-logs_{$from}_{$to}.csv", function () use ($entries) {
            yield ['Employee', 'Department', 'Project', 'Date', 'Start', 'End', 'Hours', 'Description', 'Billable'];
            foreach ($entries as $e) {
                yield [
                    $e->user?->name,
                    $e->user?->department?->name,
                    $e->project?->name ?? '—',
                    $e->start_time?->format('Y-m-d'),
                    $e->start_time?->format('H:i'),
                    $e->end_time?->format('H:i'),
                    $e->hours_decimal,
                    $e->description,
                    $e->is_billable ? 'Yes' : 'No',
                ];
            }
        });
    }

    private function streamCsv(string $filename, callable $generator): StreamedResponse
    {
        return response()->streamDownload(function () use ($generator) {
            $handle = fopen('php://output', 'w');
            foreach ($generator() as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
