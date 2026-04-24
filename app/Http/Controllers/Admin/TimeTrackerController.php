<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeCategory;
use App\Models\TimeTracker;
use App\Models\User;
use App\Models\Project;
use App\Models\Department;
use Illuminate\Http\Request;

class TimeTrackerController extends Controller
{
    public function index(Request $request)
    {
        $entries = TimeTracker::with(['user.department', 'category', 'project', 'trackable'])
            ->completed()
            ->when($request->user_id,    fn ($q) => $q->forUser($request->user_id))
            ->when($request->project_id, fn ($q) => $q->forProject($request->project_id))
            ->when($request->category_id,fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->billable !== null && $request->billable !== '',
                fn ($q) => $q->where('is_billable', (bool) $request->billable))
            ->when($request->from, fn ($q) => $q->where('start_time', '>=', $request->from))
            ->when($request->to,   fn ($q) => $q->where('start_time', '<=', $request->to . ' 23:59:59'))
            ->orderByDesc('start_time')
            ->paginate(50)
            ->withQueryString();

        $users      = User::active()->excludeAdmin()->orderBy('name')->get();
        $projects   = Project::orderBy('name')->get();
        $categories = TimeCategory::active()->ordered()->get();

        $totalHours    = $entries->sum('hours_decimal');
        $billableHours = $entries->where('is_billable', true)->sum('hours_decimal');
        $totalRevenue  = $entries->sum(fn ($e) => $e->revenue);

        return view('admin.time.index', compact(
            'entries', 'users', 'projects', 'categories',
            'totalHours', 'billableHours', 'totalRevenue'
        ));
    }

    public function destroy(TimeTracker $entry)
    {
        abort_if($entry->isRunning(), 422, 'Cannot delete a running timer.');
        $entry->delete();
        return back()->with('success', 'Time entry deleted.');
    }
}
