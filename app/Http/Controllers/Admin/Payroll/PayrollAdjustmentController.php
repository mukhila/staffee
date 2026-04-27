<?php

namespace App\Http\Controllers\Admin\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\ComponentDefinition;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollCalendar;
use App\Models\User;
use Illuminate\Http\Request;

class PayrollAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-staff');

        $adjustments = PayrollAdjustment::with(['employee', 'definition', 'creator', 'approver'])
            ->when($request->status,   fn ($q) => $q->where('status', $request->status))
            ->when($request->employee, fn ($q) => $q->where('user_id', $request->employee))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $employees = User::active()->excludeAdmin()->orderBy('name')->get();

        return view('admin.payroll.adjustments.index', compact('adjustments', 'employees'));
    }

    public function create()
    {
        $this->authorize('edit-staff');

        $employees   = User::active()->excludeAdmin()->with('department')->orderBy('name')->get();
        $definitions = ComponentDefinition::where('status', 'active')
            ->whereIn('category', ['earning', 'deduction'])
            ->orderBy('display_order')
            ->get();
        $calendars   = PayrollCalendar::orderByDesc('id')->limit(12)->get();

        return view('admin.payroll.adjustments.create', compact('employees', 'definitions', 'calendars'));
    }

    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $validated = $request->validate([
            'user_id'                 => 'required|exists:users,id',
            'component_definition_id' => 'required|exists:payroll_component_definitions,id',
            'adjustment_type'         => 'required|in:addition,deduction',
            'amount'                  => 'required|numeric|min:0.01',
            'reason'                  => 'required|string|max:2000',
            'recurrence'              => 'required|in:one-time,monthly,fixed_installments',
            'start_period'            => 'required|date',
            'end_period'              => 'nullable|date|after_or_equal:start_period',
            'remaining_installments'  => 'nullable|integer|min:1',
        ]);

        PayrollAdjustment::create(array_merge($validated, [
            'status'     => 'pending',
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('admin.payroll.adjustments.index')
            ->with('success', 'Payroll adjustment submitted for approval.');
    }

    public function show(PayrollAdjustment $adjustment)
    {
        $this->authorize('view-staff');
        $adjustment->load(['employee.department', 'definition', 'creator', 'approver']);
        return view('admin.payroll.adjustments.show', compact('adjustment'));
    }

    public function approve(Request $request, PayrollAdjustment $adjustment)
    {
        $this->authorize('edit-staff');
        abort_if($adjustment->status !== 'pending', 422, 'Only pending adjustments can be approved.');

        $request->validate(['notes' => 'nullable|string|max:500']);

        $adjustment->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Adjustment approved.');
    }

    public function reject(Request $request, PayrollAdjustment $adjustment)
    {
        $this->authorize('edit-staff');
        abort_if($adjustment->status !== 'pending', 422, 'Only pending adjustments can be rejected.');

        $request->validate(['notes' => 'required|string|max:500']);

        $adjustment->update([
            'status'      => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Adjustment rejected.');
    }

    public function cancel(PayrollAdjustment $adjustment)
    {
        $this->authorize('edit-staff');
        abort_if(!in_array($adjustment->status, ['pending', 'approved']), 422, 'Cannot cancel this adjustment.');

        $adjustment->update(['status' => 'cancelled']);

        return back()->with('success', 'Adjustment cancelled.');
    }
}
