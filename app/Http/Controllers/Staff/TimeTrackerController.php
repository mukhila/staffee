<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimeTrackerController extends Controller
{
    public function start(Request $request)
    {
        $request->validate([
            'type' => 'required|in:task,bug',
            'id' => 'required|integer',
        ]);

        $modelClass = $request->type === 'task' ? \App\Models\Task::class : \App\Models\Bug::class;
        $item = $modelClass::findOrFail($request->id);

        if ($item->assigned_to != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if already running
        $existing = \App\Models\TimeTracker::where('user_id', auth()->id())
            ->whereNull('end_time')
            ->first();

        if ($existing) {
            return response()->json(['error' => 'You already have a timer running.'], 400);
        }

        \App\Models\TimeTracker::create([
            'user_id' => auth()->id(),
            'trackable_type' => $modelClass,
            'trackable_id' => $item->id,
            'start_time' => now(),
        ]);
        
        // Update item status to In Progress if not already
        if ($item->status === 'pending' || $item->status === 'not_started') {
            $item->update(['status' => 'in_progress']);
        }

        return response()->json(['success' => true]);
    }

    public function stop(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'status' => 'required|string',
        ]);

        $tracker = \App\Models\TimeTracker::where('user_id', auth()->id())
            ->whereNull('end_time')
            ->firstOrFail();

        $tracker->update([
            'end_time' => now(),
            'description' => $request->description,
        ]);

        // Update item status
        $item = $tracker->trackable;
        $item->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
