<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sprint;
use App\Models\Project;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Project $project)
    {
        $sprints = $project->sprints()->withCount('tasks')->orderByDesc('start_date')->get();
        return view('admin.projects.sprints.index', compact('project', 'sprints'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        $project->sprints()->create($data + ['created_by' => auth()->id(), 'status' => 'planned']);

        return back()->with('success', 'Sprint created.');
    }

    public function update(Request $request, Project $project, Sprint $sprint)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
            'status'     => 'required|in:planned,active,completed,cancelled',
        ]);

        // Only one sprint can be active per project
        if ($data['status'] === 'active') {
            $project->sprints()
                ->where('id', '!=', $sprint->id)
                ->where('status', 'active')
                ->update(['status' => 'planned']);
        }

        $sprint->update($data);
        return back()->with('success', 'Sprint updated.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        $sprint->delete();
        return back()->with('success', 'Sprint deleted.');
    }

    public function show(Project $project, Sprint $sprint)
    {
        $sprint->load(['tasks.assignedUser']);
        $milestones = $project->milestones()->get();
        return view('admin.projects.sprints.show', compact('project', 'sprint', 'milestones'));
    }
}
