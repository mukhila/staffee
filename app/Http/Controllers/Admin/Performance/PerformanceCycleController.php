<?php

namespace App\Http\Controllers\Admin\Performance;

use App\Http\Controllers\Controller;
use App\Models\Performance\PerformanceCycle;
use App\Models\User;
use Illuminate\Http\Request;

class PerformanceCycleController extends Controller
{
    public function index()
    {
        $cycles = PerformanceCycle::with('createdBy')
            ->latest()
            ->paginate(20);

        return view('admin.performance.cycles.index', compact('cycles'));
    }

    public function create()
    {
        return view('admin.performance.cycles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:120',
            'cycle_type'            => 'required|in:annual,half_yearly,quarterly,probation,adhoc',
            'fiscal_year'           => 'nullable|string|max:9',
            'review_period_start'   => 'required|date',
            'review_period_end'     => 'required|date|after:review_period_start',
            'submission_deadline'   => 'required|date',
            'calibration_deadline'  => 'nullable|date',
            'status'                => 'required|in:draft,active,closed,archived',
        ]);

        $validated['created_by'] = auth()->id();

        $cycle = PerformanceCycle::create($validated);

        return redirect()
            ->route('admin.performance.cycles.show', $cycle)
            ->with('success', 'Performance cycle created successfully.');
    }

    public function show(PerformanceCycle $cycle)
    {
        $cycle->load(['reviews.reviewee', 'reviews.reviewer', 'createdBy']);

        $employees = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_id']);

        $managers = User::whereIn('role', ['admin', 'pm'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.performance.cycles.show', compact('cycle', 'employees', 'managers'));
    }

    public function close(PerformanceCycle $cycle)
    {
        $cycle->update(['status' => 'closed']);

        return back()->with('success', 'Cycle closed successfully.');
    }
}
