<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Leave\LeavePolicy;
use App\Models\Leave\LeaveType;
use Illuminate\Http\Request;

class LeavePolicyController extends Controller
{
    public function index()
    {
        $policies = LeavePolicy::with('leaveType', 'department')
            ->orderBy('leave_type_id')
            ->get();

        return view('admin.leaves.policies.index', compact('policies'));
    }

    public function create()
    {
        $types       = LeaveType::active()->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('admin.leaves.policies.create', compact('types', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type_id'             => 'required|exists:leave_types,id',
            'name'                      => 'required|string|max:100',
            'department_id'             => 'nullable|exists:departments,id',
            'employee_level'            => 'nullable|string|max:50',
            'max_days_per_year'         => 'required|numeric|min:0',
            'carry_forward_days'        => 'required|numeric|min:0',
            'carry_forward_expiry_months' => 'required|integer|min:0|max:12',
            'accrual_method'            => 'required|in:immediate,monthly,quarterly,annual',
            'accrual_amount'            => 'required|numeric|min:0',
            'vesting_period_months'     => 'required|integer|min:0',
            'min_notice_days'           => 'required|integer|min:0',
            'max_consecutive_days'      => 'nullable|integer|min:1',
            'requires_manager_approval' => 'boolean',
            'requires_hr_approval'      => 'boolean',
            'auto_approve_days'         => 'nullable|integer|min:1',
        ]);

        $data['requires_manager_approval'] = $request->boolean('requires_manager_approval');
        $data['requires_hr_approval']      = $request->boolean('requires_hr_approval');

        LeavePolicy::create($data);
        return redirect()->route('admin.leaves.policies.index')->with('success', 'Policy created.');
    }

    public function destroy(LeavePolicy $policy)
    {
        $policy->delete();
        return back()->with('success', 'Policy deleted.');
    }
}
