<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;

class KanbanController extends Controller
{
    public function index()
    {
        $tasks = Task::where('assigned_to', auth()->id())
            ->with('project')
            ->orderBy('due_date', 'asc')
            ->get();
            
        return view('staff.kanban.index', compact('tasks'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,review,completed'
        ]);

        $task = Task::where('id', $id)->where('assigned_to', auth()->id())->firstOrFail();
        $task->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
