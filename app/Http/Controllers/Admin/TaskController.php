<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $this->authorize('view-tasks');

        $tasks = \App\Models\Task::with(['project', 'assignedUser'])->get();
        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $this->authorize('create-task');

        $projects = \App\Models\Project::where('status', '!=', 'completed')->get();
        return view('admin.tasks.create', compact('projects'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $this->authorize('create-task');

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title'      => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date'   => 'nullable|date',
            'start_date' => 'nullable|date',
        ]);

        $task = \App\Models\Task::create([
            'project_id'  => $request->project_id,
            'title'       => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'status'      => 'pending',
            'due_date'    => $request->due_date,
            'start_date'  => $request->start_date,
        ]);

        \App\Models\Notification::create([
            'user_id' => $request->assigned_to,
            'type'    => 'task_assigned',
            'title'   => 'New Task Assigned',
            'message' => 'You have been assigned a new task: ' . $task->title,
            'url'     => route('staff.tasks.index'),
        ]);

        return redirect()->route('admin.tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(\App\Models\Task $task)
    {
        $task->load(['project', 'assignedUser', 'comments.user', 'blockers', 'blocking']);
        $candidateTasks = \App\Models\Task::where('project_id', $task->project_id)
            ->where('id', '!=', $task->id)
            ->whereNotIn('id', $task->blockers->pluck('id'))
            ->get();
        return view('admin.tasks.show', compact('task', 'candidateTasks'));
    }

    public function addDependency(\App\Models\Task $task, Request $request)
    {
        $request->validate(['blocker_id' => 'required|exists:tasks,id']);
        $blockerId = (int) $request->blocker_id;

        if ($blockerId === $task->id) {
            return back()->with('error', 'A task cannot block itself.');
        }

        $task->blockers()->syncWithoutDetaching([$blockerId]);
        return back()->with('success', 'Dependency added.');
    }

    public function removeDependency(\App\Models\Task $task, \App\Models\Task $blocker)
    {
        $task->blockers()->detach($blocker->id);
        return back()->with('success', 'Dependency removed.');
    }

    public function edit(\App\Models\Task $task)
    {
        $this->authorize('update-task');

        $projects = \App\Models\Project::where('status', '!=', 'completed')->get();
        return view('admin.tasks.edit', compact('task', 'projects'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Task $task)
    {
        $this->authorize('update-task');

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'status' => 'required|in:pending,in_progress,completed,review',
            'due_date' => 'nullable|date',
        ]);

        $task->update($request->all());

        return redirect()->route('admin.tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(\App\Models\Task $task)
    {
        $this->authorize('delete-task');

        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function getProjectMembers(\App\Models\Project $project)
    {
        return response()->json($project->users);
    }
}
