<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Bug;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

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
}
