<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TimeLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = TimeTracker::with(['trackable', 'project', 'category'])
            ->forUser($user->id)
            ->completed();

        if ($request->filled('from')) {
            $query->whereDate('start_time', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('start_time', '<=', $request->to);
        }
        if ($request->filled('project')) {
            $query->where('project_id', $request->project);
        }

        $entries  = $query->orderByDesc('start_time')->paginate(25)->withQueryString();
        $projects = Project::whereHas('timeTrackers', fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')->get();

        // Tasks assigned to this user (for manual entry form)
        $tasks = Task::where('assigned_to', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->with('project')
            ->orderBy('title')
            ->get();

        $totalHours = round($entries->getCollection()->sum('duration_hours'), 2);

        return view('staff.time-log.index', compact('entries', 'projects', 'tasks', 'totalHours'));
    }

    // Entries older than this many days require admin approval
    const APPROVAL_THRESHOLD_DAYS = 7;

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'task_id'     => 'required|exists:tasks,id',
            'date'        => 'required|date|before_or_equal:today',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i|after:start_time',
            'description' => 'nullable|string|max:500',
            'reason'      => 'nullable|string|max:500',
        ]);

        $task  = Task::findOrFail($validated['task_id']);
        $start = Carbon::parse($validated['date'] . ' ' . $validated['start_time']);
        $end   = Carbon::parse($validated['date'] . ' ' . $validated['end_time']);
        $hours = round($start->diffInSeconds($end) / 3600, 4);

        $daysOld       = now()->diffInDays($start);
        $needsApproval = $daysOld >= self::APPROVAL_THRESHOLD_DAYS;

        TimeTracker::create([
            'user_id'         => $user->id,
            'trackable_id'    => $task->id,
            'trackable_type'  => Task::class,
            'project_id'      => $task->project_id,
            'start_time'      => $start,
            'end_time'        => $end,
            'hours_decimal'   => $hours,
            'description'     => $validated['description'],
            'notes'           => $validated['reason'],
            'is_billable'     => false,
            'approval_status' => $needsApproval ? 'pending' : null,
        ]);

        $message = $needsApproval
            ? 'Time entry submitted and awaiting admin approval (entry is older than ' . self::APPROVAL_THRESHOLD_DAYS . ' days).'
            : 'Time entry logged successfully.';

        return back()->with('success', $message);
    }

    public function destroy(TimeTracker $entry)
    {
        abort_if($entry->user_id !== auth()->id(), 403);
        $entry->delete();
        return back()->with('success', 'Entry deleted.');
    }
}
