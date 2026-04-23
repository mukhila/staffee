<?php

namespace App\Http\Controllers\Admin\HR;

use App\Events\EmployeePromoted;
use App\Http\Controllers\Controller;
use App\Models\HR\PromotionRequest;
use App\Models\User;
use App\Services\HR\LifecycleEventService;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(private readonly LifecycleEventService $lifecycle) {}

    public function index()
    {
        $this->authorize('view-staff');

        $promotions = PromotionRequest::with('employee', 'proposedBy', 'currentDepartment', 'proposedDepartment')
            ->latest()
            ->paginate(15);

        return view('admin.hr.promotions.index', compact('promotions'));
    }

    public function create()
    {
        $this->authorize('edit-staff');

        $employees   = User::active()->excludeAdmin()->withHrProfile()->get();
        $departments = \App\Models\Department::where('is_active', true)->get();
        $roles       = \App\Models\Role::where('is_active', true)->get();

        return view('admin.hr.promotions.create', compact('employees', 'departments', 'roles'));
    }

    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'user_id'                => 'required|exists:users,id',
            'proposed_role'          => 'required|exists:roles,slug',
            'proposed_department_id' => 'required|exists:departments,id',
            'proposed_designation'   => 'nullable|string|max:255',
            'proposed_salary'        => 'nullable|numeric|min:0',
            'effective_date'         => 'required|date|after_or_equal:today',
            'reason'                 => 'required|string|max:1000',
        ]);

        $employee = User::findOrFail($request->user_id);

        PromotionRequest::create([
            'user_id'                 => $employee->id,
            'proposed_by'             => auth()->id(),
            'current_role'            => $employee->role,
            'proposed_role'           => $request->proposed_role,
            'current_department_id'   => $employee->department_id,
            'proposed_department_id'  => $request->proposed_department_id,
            'current_designation'     => $employee->designation,
            'proposed_designation'    => $request->proposed_designation,
            'current_salary'          => $employee->currentSalary(),
            'proposed_salary'         => $request->proposed_salary,
            'effective_date'          => $request->effective_date,
            'reason'                  => $request->reason,
            'status'                  => 'pending_manager',
        ]);

        return redirect()->route('admin.hr.promotions.index')
                         ->with('success', 'Promotion proposal submitted for approval.');
    }

    public function show(PromotionRequest $promotion)
    {
        $this->authorize('view-staff');
        $promotion->load('employee', 'proposedBy', 'currentDepartment', 'proposedDepartment',
                         'managerApprovedBy', 'hrApprovedBy', 'financeApprovedBy');
        return view('admin.hr.promotions.show', compact('promotion'));
    }

    /**
     * Advance the approval chain by one step.
     * Step is inferred from current status; rejections stop the chain.
     */
    public function approve(Request $request, PromotionRequest $promotion)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'notes'    => 'nullable|string|max:500',
        ]);

        $user     = auth()->user();
        $approved = $request->decision === 'approved';

        $update = match ($promotion->status) {
            'pending_manager' => [
                'status'               => $approved ? 'manager_approved' : 'manager_rejected',
                'manager_approved_by'  => $user->id,
                'manager_approved_at'  => now(),
                'manager_notes'        => $request->notes,
            ],
            'manager_approved' => [
                'status'           => $approved ? 'hr_approved' : 'hr_rejected',
                'hr_approved_by'   => $user->id,
                'hr_approved_at'   => now(),
                'hr_notes'         => $request->notes,
            ],
            'hr_approved' => [
                'status'               => $approved ? 'finance_approved' : 'finance_rejected',
                'finance_approved_by'  => $user->id,
                'finance_approved_at'  => now(),
                'finance_notes'        => $request->notes,
            ],
            default => null,
        };

        abort_if(!$update, 422, 'This promotion cannot be actioned in its current state.');

        $promotion->update($update);

        // All 3 approvals done — execute
        if ($promotion->fresh()->status === 'finance_approved') {
            $this->lifecycle->applyPromotion($promotion, $user);
            EmployeePromoted::dispatch($promotion->employee, $promotion, $user);
        }

        $label = $approved ? 'approved' : 'rejected';
        return redirect()->route('admin.hr.promotions.show', $promotion)
                         ->with('success', "Promotion {$label} successfully.");
    }

    public function destroy(PromotionRequest $promotion)
    {
        $this->authorize('edit-staff');
        abort_if(!$promotion->isPending(), 422, 'Only pending proposals can be withdrawn.');
        $promotion->update(['status' => 'withdrawn']);
        return redirect()->route('admin.hr.promotions.index')->with('success', 'Proposal withdrawn.');
    }
}
