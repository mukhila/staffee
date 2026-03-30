<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = \App\Models\Task::where('assigned_to', auth()->id())
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('staff.tasks.index', compact('tasks'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Task $task)
    {
        if ($task->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,review',
            'comment' => 'nullable|string', // Assuming we might want comments later, but for now just status
        ]);

        $task->update([
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Task status updated successfully.');
    }
}
