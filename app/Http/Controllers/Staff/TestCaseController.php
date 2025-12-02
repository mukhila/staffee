<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestCaseController extends Controller
{
    public function index()
    {
        $testCases = \App\Models\TestCase::where('created_by', auth()->id())
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('staff.test_cases.index', compact('testCases'));
    }

    public function create()
    {
        $projects = \App\Models\Project::whereHas('users', function($q) {
            $q->where('user_id', auth()->id());
        })->where('status', '!=', 'completed')->get();
        return view('staff.test_cases.create', compact('projects'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        \App\Models\TestCase::create([
            'project_id' => $request->project_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('staff.test-cases.index')->with('success', 'Test Case created successfully.');
    }

    public function edit(\App\Models\TestCase $testCase)
    {
        if ($testCase->created_by != auth()->id()) {
            abort(403);
        }
        $projects = \App\Models\Project::whereHas('users', function($q) {
            $q->where('user_id', auth()->id());
        })->where('status', '!=', 'completed')->get();
        return view('staff.test_cases.edit', compact('testCase', 'projects'));
    }

    public function update(\Illuminate\Http\Request $request, \App\Models\TestCase $testCase)
    {
        if ($testCase->created_by != auth()->id()) {
            abort(403);
        }

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:pending,pass,fail,need_to_test',
        ]);

        $testCase->update($request->all());

        if ($request->status == 'fail') {
            // Redirect to bug creation with test case info
            return redirect()->route('staff.bugs.create', ['test_case_id' => $testCase->id])->with('warning', 'Test Case failed. Please create a bug.');
        }

        return redirect()->route('staff.test-cases.index')->with('success', 'Test Case updated successfully.');
    }
}
