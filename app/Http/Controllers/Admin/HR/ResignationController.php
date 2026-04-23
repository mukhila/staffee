<?php

namespace App\Http\Controllers\Admin\HR;

use App\Events\ResignationSubmitted;
use App\Http\Controllers\Controller;
use App\Models\HR\ResignationRequest;
use App\Models\User;
use App\Services\HR\LifecycleEventService;
use Illuminate\Http\Request;

class ResignationController extends Controller
{
    public function __construct(private readonly LifecycleEventService $lifecycle) {}

    public function index()
    {
        $this->authorize('view-staff');

        $resignations = ResignationRequest::with('employee', 'manager')
            ->latest()
            ->paginate(15);

        return view('admin.hr.resignations.index', compact('resignations'));
    }

    public function show(ResignationRequest $resignation)
    {
        $this->authorize('view-staff');
        $resignation->load('employee', 'manager', 'hrReviewer', 'termination');
        return view('admin.hr.resignations.show', compact('resignation'));
    }

    /** HR creates resignation on behalf of employee (walk-in submission). */
    public function store(Request $request)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'user_id'              => 'required|exists:users,id',
            'reason'               => 'required|string|max:2000',
            'resignation_type'     => 'required|in:voluntary,mutual_separation',
            'requested_last_date'  => 'required|date|after:today',
            'notice_waived'        => 'boolean',
            'waiver_reason'        => 'nullable|string|max:500',
        ]);

        $employee          = User::findOrFail($request->user_id);
        $noticeDays        = $employee->profile?->notice_period_days ?? 30;

        $resignation = ResignationRequest::create([
            'user_id'             => $employee->id,
            'submitted_date'      => today(),
            'requested_last_date' => $request->requested_last_date,
            'notice_period_days'  => $noticeDays,
            'resignation_type'    => $request->resignation_type,
            'reason'              => $request->reason,
            'notice_waived'       => $request->boolean('notice_waived'),
            'waiver_reason'       => $request->waiver_reason,
            'status'              => 'manager_reviewing',
            'manager_id'          => $employee->reporting_to,
        ]);

        ResignationSubmitted::dispatch($employee, $resignation);

        return redirect()->route('admin.hr.resignations.show', $resignation)
                         ->with('success', 'Resignation recorded and manager notified.');
    }

    /** Manager accepts/rejects the resignation. */
    public function managerDecision(Request $request, ResignationRequest $resignation)
    {
        $this->authorize('edit-staff');

        $request->validate([
            'decision'      => 'required|in:accepted,rejected',
            'manager_notes' => 'nullable|string|max:500',
        ]);

        $resignation->update([
            'manager_decision'    => $request->decision,
            'manager_reviewed_at' => now(),
            'manager_notes'       => $request->manager_notes,
            'status'              => $request->decision === 'accepted' ? 'manager_accepted' : 'manager_rejected',
        ]);

        $label = $request->decision === 'accepted' ? 'accepted' : 'rejected';
        return redirect()->route('admin.hr.resignations.show', $resignation)
                         ->with('success', "Resignation {$label} by manager.");
    }

    /** HR gives final approval → triggers notice period + creates termination. */
    public function hrApprove(Request $request, ResignationRequest $resignation)
    {
        $this->authorize('manage-leave'); // HR-level permission

        abort_if($resignation->status !== 'manager_accepted', 422,
            'Manager must accept before HR can approve.');

        $this->lifecycle->approveResignation($resignation, auth()->user());

        return redirect()->route('admin.hr.resignations.show', $resignation)
                         ->with('success', 'Resignation approved. Notice period commenced.');
    }

    public function withdraw(Request $request, ResignationRequest $resignation)
    {
        $this->authorize('edit-staff');
        abort_if(!$resignation->isWithdrawable(), 422, 'This resignation can no longer be withdrawn.');

        $resignation->update([
            'status'             => 'withdrawn',
            'withdrawal_reason'  => $request->reason,
            'withdrawn_at'       => now(),
        ]);

        $resignation->employee->update(['employment_status' => 'active']);

        return redirect()->route('admin.hr.resignations.show', $resignation)
                         ->with('success', 'Resignation withdrawn successfully.');
    }
}
