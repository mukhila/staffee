<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Services\Project\WorkloadService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProjectInsightsController extends Controller
{
    public function __construct(private readonly WorkloadService $workload) {}

    /** Team workload over the coming weeks. */
    public function workload(Request $request)
    {
        $filters = $request->validate([
            'department_id' => 'nullable|integer|exists:departments,id',
            'project_id'    => 'nullable|integer|exists:projects,id',
            'weeks'         => 'nullable|integer|in:2,4,6,8',
            'from'          => 'nullable|date',
        ]);

        $weeks = (int) ($filters['weeks'] ?? 4);
        $from  = isset($filters['from']) ? Carbon::parse($filters['from']) : Carbon::today();

        $users = User::where('is_active', true)
            ->where('role', '!=', 'admin')
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->where('department_id', $d))
            ->when($filters['project_id'] ?? null, function ($q, $p) {
                // Members of the project, plus anyone with a task on it.
                $q->where(fn ($inner) => $inner
                    ->whereHas('projects', fn ($pq) => $pq->where('projects.id', $p))
                    ->orWhereIn('id', \App\Models\Task::where('project_id', $p)->whereNotNull('assigned_to')->select('assigned_to')));
            })
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'department_id']);

        $data = $this->workload->build($users, $from, $weeks, $filters['project_id'] ?? null);

        return view('admin.projects.workload', [
            'weeks'       => $data['weeks'],
            'rows'        => $data['rows'],
            'filters'     => $filters + ['weeks' => $weeks],
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'projects'    => Project::whereNotIn('status', ['completed', 'cancelled'])->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
