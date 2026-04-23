<?php

namespace App\Http\Controllers\Admin\HR;

use App\Events\TerminationInitiated;
use App\Http\Controllers\Controller;
use App\Models\HR\ExitChecklistItem;
use App\Models\HR\FinalSettlement;
use App\Models\HR\TerminationRequest;
use App\Models\User;
use App\Services\HR\LifecycleEventService;
use App\Services\HR\TerminationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminationController extends Controller
{
    public function __construct(
        private readonly LifecycleEventService $lifecycle,
        private readonly TerminationService    $termSvc,
    ) {}

    public function index()
    {
        $this->authorize('view-staff');

        $terminations = TerminationRequest::with('employee', 'initiatedBy')
            ->latest()
            ->paginate(15);

        return view('admin.hr.terminations.index', compact('terminations'));
    }

    public function create()
    {
        $this->authorize('delete-staff');

        $employees = User::active()->excludeAdmin()->withHrProfile()->get();

        return view('admin.hr.terminations.create', compact('employees'));
    }

    /** Step 1 — Initiate */
    public function store(Request $request)
    {
        $this->authorize('delete-staff');

        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'termination_type'  => 'required|in:voluntary_resignation,involuntary_dismissal,layoff,end_of_contract,retirement,mutual_separation,abandonment',
            'reason'            => 'required|string|max:2000',
            'last_working_date' => 'required|date|after_or_equal:today',
        ]);

        $employee    = User::findOrFail($request->user_id);
        $initiatedBy = auth()->user();

        $termination = DB::transaction(function () use ($request, $employee, $initiatedBy) {
            $termination = TerminationRequest::create([
                'user_id'           => $employee->id,
                'initiated_by'      => $initiatedBy->id,
                'termination_type'  => $request->termination_type,
                'reason'            => $request->reason,
                'last_working_date' => $request->last_working_date,
                'status'            => 'pending_approval',
            ]);

            $employee->update(['employment_status' => 'notice_period']);

            return $termination;
        });

        TerminationInitiated::dispatch($employee, $termination, $initiatedBy);

        return redirect()->route('admin.hr.terminations.show', $termination)
                         ->with('success', 'Termination initiated. Awaiting HR approval.');
    }

    public function show(TerminationRequest $termination)
    {
        $this->authorize('view-staff');

        $termination->load([
            'employee.profile', 'initiatedBy',
            'exitChecklist.items.responsible', 'exitChecklist.items.completedBy',
            'settlement',
        ]);

        return view('admin.hr.terminations.show', compact('termination'));
    }

    /** Step 2 — HR approval */
    public function approve(Request $request, TerminationRequest $termination)
    {
        $this->authorize('delete-staff');

        abort_if($termination->status !== 'pending_approval', 422, 'Already actioned.');

        $request->validate(['approval_notes' => 'nullable|string|max:500']);

        $termination->update([
            'status'          => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'approval_notes'  => $request->approval_notes,
        ]);

        // Creates exit checklist with default 17 items
        $this->lifecycle->processTermination($termination, auth()->user());

        return redirect()->route('admin.hr.terminations.show', $termination)
                         ->with('success', 'Termination approved. Exit checklist generated.');
    }

    /** Tick off a checklist item */
    public function completeChecklistItem(Request $request, TerminationRequest $termination, ExitChecklistItem $item)
    {
        $this->authorize('edit-staff');

        abort_if($item->checklist->termination_id !== $termination->id, 403);

        $item->complete(auth()->user(), $request->notes);

        return back()->with('success', 'Checklist item marked complete.');
    }

    /** Step 3 — Calculate & save settlement */
    public function calculateSettlement(TerminationRequest $termination)
    {
        $this->authorize('delete-staff');

        abort_if(!$termination->isSettlementReady(), 422,
            'Complete the exit checklist before calculating settlement.');

        $settlement = $this->termSvc->calculateSettlement($termination);

        return redirect()->route('admin.hr.terminations.show', $termination)
                         ->with('success', 'Settlement calculated. Review and approve.');
    }

    /** Finance approves the settlement amount */
    public function approveSettlement(Request $request, TerminationRequest $termination)
    {
        $this->authorize('delete-staff');

        $settlement = $termination->settlement;
        abort_if(!$settlement || $settlement->status !== 'pending_approval', 422, 'No settlement pending.');

        $settlement->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $termination->update(['settlement_status' => 'approved']);

        return redirect()->route('admin.hr.terminations.show', $termination)
                         ->with('success', 'Settlement approved.');
    }

    /** Step 4 — Mark payment done & complete offboarding */
    public function finalize(Request $request, TerminationRequest $termination)
    {
        $this->authorize('delete-staff');

        $request->validate([
            'payment_mode'      => 'required|in:bank_transfer,cheque,cash',
            'payment_reference' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $termination) {
            $termination->settlement?->update([
                'status'            => 'paid',
                'paid_at'           => now(),
                'payment_mode'      => $request->payment_mode,
                'payment_reference' => $request->payment_reference,
            ]);

            $this->termSvc->offboardEmployee($termination->employee);

            $this->lifecycle->completeTermination($termination, auth()->user());

            $termination->update([
                'status'            => 'completed',
                'settlement_status' => 'paid',
            ]);
        });

        return redirect()->route('admin.hr.terminations.show', $termination)
                         ->with('success', 'Termination finalized. Employee offboarded.');
    }
}
