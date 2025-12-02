<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BugController extends Controller
{
    public function index()
    {
        $bugs = \App\Models\Bug::where('reported_by', auth()->id())
            ->orWhere('assigned_to', auth()->id())
            ->with(['project', 'assignedUser'])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('staff.bugs.index', compact('bugs'));
    }

    public function create(\Illuminate\Http\Request $request)
    {
        $testCaseId = $request->query('test_case_id');
        $testCase = null;
        $projects = \App\Models\Project::whereHas('users', function($q) {
            $q->where('user_id', auth()->id());
        })->where('status', '!=', 'completed')->get();

        if ($testCaseId) {
            $testCase = \App\Models\TestCase::find($testCaseId);
        }

        return view('staff.bugs.create', compact('projects', 'testCase'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'required|exists:users,id',
            'severity' => 'required|in:low,medium,high,critical',
            'test_case_id' => 'nullable|exists:test_cases,id',
        ]);

        \App\Models\Bug::create([
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'assigned_to' => $request->assigned_to,
            'reported_by' => auth()->id(),
            'status' => 'open',
            'severity' => $request->severity,
            'test_case_id' => $request->test_case_id,
        ]);

        return redirect()->route('staff.bugs.index')->with('success', 'Bug reported successfully.');
    }

    public function edit(\App\Models\Bug $bug)
    {
        // Allow reporter or assignee to edit
        if ($bug->reported_by != auth()->id() && $bug->assigned_to != auth()->id()) {
            abort(403);
        }
        $projects = \App\Models\Project::whereHas('users', function($q) {
            $q->where('user_id', auth()->id());
        })->where('status', '!=', 'completed')->get();
        return view('staff.bugs.edit', compact('bug', 'projects'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\Bug $bug)
    {
        if ($bug->reported_by != auth()->id() && $bug->assigned_to != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'severity' => 'required|in:low,medium,high,critical',
        ]);

        $bug->update($request->only(['status', 'severity']));

        return redirect()->route('staff.bugs.index')->with('success', 'Bug updated successfully.');
    }
}
