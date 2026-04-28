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
        return view('admin.projects.show', compact('project'));
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
        $tasks = $project->tasks()
            ->with('assignedUser')
            ->whereNotNull('start_date')
            ->orWhereNotNull('due_date')
            ->orderBy('start_date')
            ->orderBy('due_date')
            ->get();

        // Gantt boundaries: project start/end or min/max of task dates
        $minDate = $tasks->filter(fn ($t) => $t->start_date)->min('start_date')
            ?? $tasks->filter(fn ($t) => $t->due_date)->min('due_date')
            ?? $project->start_date
            ?? now()->toDateString();

        $maxDate = $tasks->filter(fn ($t) => $t->due_date)->max('due_date')
            ?? $project->end_date
            ?? now()->addMonth()->toDateString();

        $rangeStart = \Carbon\Carbon::parse($minDate)->startOfMonth();
        $rangeEnd   = \Carbon\Carbon::parse($maxDate)->endOfMonth();
        $totalDays  = max(1, $rangeStart->diffInDays($rangeEnd));

        return view('admin.projects.timeline', compact('project', 'tasks', 'rangeStart', 'rangeEnd', 'totalDays'));
    }
}
