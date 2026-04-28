<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Project $project)
    {
        $milestones = $project->milestones()->withCount('tasks')->orderBy('due_date')->get();
        return view('admin.projects.milestones.index', compact('project', 'milestones'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
        ]);

        $project->milestones()->create($data + ['created_by' => auth()->id(), 'status' => 'pending']);

        return back()->with('success', 'Milestone created.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'required|date',
            'status'      => 'required|in:pending,in_progress,completed,missed',
        ]);

        if ($data['status'] === 'completed' && !$milestone->completed_at) {
            $data['completed_at'] = now();
        }

        $milestone->update($data);
        return back()->with('success', 'Milestone updated.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Milestone deleted.');
    }
}
