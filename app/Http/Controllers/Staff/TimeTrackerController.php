<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\TimeCategory;
use App\Models\TimeTracker;
use App\Services\Time\TimeTrackingService;
use Illuminate\Http\Request;

class TimeTrackerController extends Controller
{
    public function __construct(private readonly TimeTrackingService $trackingSvc) {}

    public function start(Request $request)
    {
        $request->validate([
            'type' => 'required|in:task,bug',
            'id'   => 'required|integer',
        ]);

        $modelClass = $request->type === 'task'
            ? \App\Models\Task::class
            : \App\Models\Bug::class;

        $item = $modelClass::findOrFail($request->id);

        if ($item->assigned_to != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $existing = TimeTracker::forUser(auth()->id())->running()->first();
        if ($existing) {
            return response()->json(['error' => 'You already have a timer running.'], 400);
        }

        // Resolve direct project_id from the trackable
        $projectId = match (true) {
            $request->type === 'task' => $item->project_id ?? null,
            default                   => $item->project_id ?? null,
        };

        TimeTracker::create([
            'user_id'        => auth()->id(),
            'trackable_type' => $modelClass,
            'trackable_id'   => $item->id,
            'project_id'     => $projectId,
            'start_time'     => now(),
        ]);

        if (in_array($item->status, ['pending', 'not_started'])) {
            $item->update(['status' => 'in_progress']);
        }

        return response()->json(['success' => true]);
    }

    public function stop(Request $request)
    {
        $request->validate([
            'description'      => 'required|string|max:1000',
            'status'           => 'required|string',
            'category_id'      => 'nullable|exists:time_categories,id',
            'notes'            => 'nullable|string|max:500',
            'resolution_notes' => 'nullable|string|max:5000',
        ]);

        try {
            $this->trackingSvc->stop(
                user:            auth()->user(),
                description:     $request->description,
                categoryId:      $request->category_id ? (int) $request->category_id : null,
                notes:           $request->notes,
                status:          $request->status,
                resolutionNotes: $request->resolution_notes,
            );
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Return active timer info for the authenticated user (for the live clock).
     */
    public function active()
    {
        $timer = TimeTracker::forUser(auth()->id())->running()->with('trackable')->first();

        if (!$timer) {
            return response()->json(['running' => false]);
        }

        return response()->json([
            'running'   => true,
            'seconds'   => now()->diffInSeconds($timer->start_time),
            'trackable' => $timer->trackable?->title ?? 'Unknown',
        ]);
    }

    /**
     * Categories for the stop modal dropdown.
     */
    public function categories()
    {
        return response()->json(
            TimeCategory::active()->ordered()->get(['id', 'name', 'is_billable', 'color'])
        );
    }
}
