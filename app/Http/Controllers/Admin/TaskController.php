<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = \App\Models\Task::with(['project', 'assignedUser'])->get();
        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $projects = \App\Models\Project::where('status', '!=', 'completed')->get();
        return view('admin.tasks.create', compact('projects'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $task = \App\Models\Task::create([
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'status' => 'pending',
            'due_date' => $request->due_date,
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

    public function edit(\App\Models\Task $task)
    {
        $projects = \App\Models\Project::where('status', '!=', 'completed')->get();
        return view('admin.tasks.edit', compact('task', 'projects'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Task $task)
    {
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
        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted successfully.');
    }

    public function getProjectMembers(\App\Models\Project $project)
    {
        return response()->json($project->users);
    }
}
