<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'body'       => 'required_without:attachment|nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $path = $name = $type = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('task-attachments', 'public');
            $name = $file->getClientOriginalName();
            $type = $file->getClientMimeType();
        }

        TaskComment::create([
            'task_id'         => $task->id,
            'user_id'         => auth()->id(),
            'body'            => $request->body,
            'attachment_path' => $path,
            'attachment_name' => $name,
            'attachment_type' => $type,
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(TaskComment $comment)
    {
        abort_if($comment->user_id !== auth()->id() && !auth()->user()->isAdmin(), 403);
        $comment->delete();
        return back()->with('success', 'Comment deleted.');
    }
}
