<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DailyStatusReportController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = \App\Models\DailyStatusReport::with('user');

        if ($user->isAdmin()) {
            // Admin sees all
        } elseif ($user->role !== 'staff') { // Assuming PM or other roles are leaders
             // Leader sees their staff's reports + their own
             $staffIds = \App\Models\User::where('reporting_to', $user->id)->pluck('id')->toArray();
             $staffIds[] = $user->id;
             $query->whereIn('user_id', $staffIds);
        } else {
            // Staff sees only their own
            $query->where('user_id', $user->id);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(10);
        return view('staff.dsr.index', compact('reports'));
    }

    public function create()
    {
        $today = now()->toDateString();
        
        $tasks = \App\Models\Task::where('assigned_to', auth()->id())
            ->whereDate('updated_at', $today)
            ->get()->map(function($item) {
                return [
                    'type' => 'Task',
                    'title' => $item->title,
                    'status' => $item->status,
                    'description' => $item->description,
                ];
            });

        $bugs = \App\Models\Bug::where('assigned_to', auth()->id())
            ->whereDate('updated_at', $today)
            ->get()->map(function($item) {
                return [
                    'type' => 'Bug',
                    'title' => $item->title,
                    'status' => $item->status,
                    'description' => $item->description, // Or maybe severity/steps?
                ];
            });
        
        $activities = $tasks->concat($bugs);

        return view('staff.dsr.create', compact('activities'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'report_date' => 'required|date',
            'task_name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'status' => 'required|string',
        ]);

        \App\Models\DailyStatusReport::create([
            'user_id' => auth()->id(),
            'report_date' => $request->report_date,
            'task_name' => $request->task_name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => $request->status,
        ]);

        return redirect()->route('staff.daily-status-reports.index')->with('success', 'Daily Status Report submitted successfully.');
    }

    public function edit(\App\Models\DailyStatusReport $dailyStatusReport)
    {
        if ($dailyStatusReport->user_id != auth()->id()) {
            abort(403);
        }
        return view('staff.dsr.edit', compact('dailyStatusReport'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\DailyStatusReport $dailyStatusReport)
    {
        if ($dailyStatusReport->user_id != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'task_name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'status' => 'required|string',
        ]);

        $dailyStatusReport->update($request->only(['task_name', 'description', 'start_time', 'end_time', 'status']));

        return redirect()->route('staff.daily-status-reports.index')->with('success', 'Daily Status Report updated successfully.');
    }
}
