<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $this->authorize('view-projects');

        $projects = \App\Models\Project::with('users')->get();
        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        $this->authorize('create-project');

        $users = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.projects.create', compact('users'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create-project');

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimation_time' => 'nullable|string',
            'budget_amount' => 'nullable|numeric|min:0|max:999999999999',
            'budget_currency' => 'nullable|required_with:budget_amount|string|size:3|alpha',
            'budget_hours' => 'nullable|numeric|min:0|max:9999999',
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('projects', 'public');
                $documents[] = $path;
            }
        }

        $project = \App\Models\Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimation_time' => $request->estimation_time,
            'budget_amount' => $request->filled('budget_amount') ? (string) $request->budget_amount : null,
            'budget_currency' => $request->filled('budget_currency') ? strtoupper($request->budget_currency) : null,
            'budget_hours' => $request->filled('budget_hours') ? (string) $request->budget_hours : null,
            'status' => 'pending',
            'created_by' => auth()->id(),
            'documents' => $documents,
        ]);

        if ($request->has('team_members')) {
            $project->users()->attach($request->team_members);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(\App\Models\Project $project)
    {
        $this->authorize('edit-project');

        $users = \App\Models\User::where('role', '!=', 'admin')->get();
        return view('admin.projects.edit', compact('project', 'users'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Project $project)
    {
        $this->authorize('edit-project');

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'estimation_time' => 'nullable|string',
            'budget_amount' => 'nullable|numeric|min:0|max:999999999999',
            'budget_currency' => 'nullable|required_with:budget_amount|string|size:3|alpha',
            'budget_hours' => 'nullable|numeric|min:0|max:9999999',
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
            'documents.*' => 'file|mimes:pdf,doc,docx,jpg,png|max:2048',
        ]);

        $documents = $project->documents ?? [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('projects', 'public');
                $documents[] = $path;
            }
        }

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'estimation_time' => $request->estimation_time,
            'budget_amount' => $request->filled('budget_amount') ? (string) $request->budget_amount : null,
            'budget_currency' => $request->filled('budget_currency') ? strtoupper($request->budget_currency) : null,
            'budget_hours' => $request->filled('budget_hours') ? (string) $request->budget_hours : null,
            'documents' => $documents,
        ]);

        if ($request->has('team_members')) {
            $project->users()->sync($request->team_members);
        }

        return redirect()->route('admin.projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(\App\Models\Project $project)
    {
        $this->authorize('archive-project');

        $project->delete();
        return redirect()->route('admin.projects.index')->with('success', 'Project deleted successfully.');
    }

    public function show(\App\Models\Project $project)
    {
        $project->load(['users', 'tasks', 'bugs', 'testCases']);

        $finance = app(\App\Services\Project\ProjectFinanceService::class)->summary($project);
        // Labour cost comes from salaries, so money is only shown to those allowed to see payroll figures.
        $canSeeMoney = auth()->user()->isAdmin() || auth()->user()->hasPermission('view-payroll-reports');

        return view('admin.projects.show', compact('project', 'finance', 'canSeeMoney'));
    }

    public function downloadDocument(\App\Models\Project $project, int $index)
    {
        $documents = $project->documents ?? [];
        if (!isset($documents[$index])) {
            abort(404);
        }
        $path = storage_path('app/public/' . $documents[$index]);
        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }
        return response()->download($path);
    }

    public function deleteDocument(\App\Models\Project $project, int $index)
    {
        $documents = $project->documents ?? [];
        if (!isset($documents[$index])) {
            abort(404);
        }
        \Illuminate\Support\Facades\Storage::disk('public')->delete($documents[$index]);
        unset($documents[$index]);
        $project->update(['documents' => array_values($documents)]);

        return redirect()->back()->with('success', 'Document deleted.');
    }

    public function timeline(\App\Models\Project $project)
    {
        // Grouped so the "or" stays inside this project (it used to pull in
        // dated tasks from every project).
        $tasks = $project->tasks()
            ->with(['assignedUser:id,name', 'blockers:id'])
            ->where(fn ($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date'))
            ->orderByRaw('COALESCE(start_date, due_date)')
            ->orderBy('due_date')
            ->get();

        $unscheduled = $project->tasks()->whereNull('start_date')->whereNull('due_date')->count();
        $milestones  = $project->milestones()->orderBy('due_date')->get();
        $sprints     = $project->sprints()->orderBy('start_date')->get();

        $dates = collect([$project->start_date, $project->end_date])
            ->merge($tasks->pluck('start_date'))
            ->merge($tasks->pluck('due_date'))
            ->merge($milestones->pluck('due_date'))
            ->merge($sprints->pluck('start_date'))
            ->merge($sprints->pluck('end_date'))
            ->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d));

        $rangeStart = ($dates->min() ?? now())->copy()->startOfWeek();
        $rangeEnd   = ($dates->max() ?? now()->addMonth())->copy()->endOfWeek();
        if ($rangeEnd->lt(now()->endOfWeek()) && $rangeStart->lte(now())) {
            $rangeEnd = now()->endOfWeek(); // keep "today" in view for live projects
        }
        $totalDays = max(1, (int) $rangeStart->diffInDays($rangeEnd) + 1);

        return view('admin.projects.timeline', compact(
            'project', 'tasks', 'milestones', 'sprints', 'unscheduled', 'rangeStart', 'rangeEnd', 'totalDays'
        ));
    }
}
