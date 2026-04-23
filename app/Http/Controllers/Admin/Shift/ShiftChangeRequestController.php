<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift\Shift;
use App\Models\Shift\ShiftChangeRequest;
use App\Notifications\ShiftChangeRequestNotification;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftChangeRequestController extends Controller
{
    public function __construct(private readonly ShiftService $shiftSvc) {}

    public function index(Request $request)
    {
        $requests = ShiftChangeRequest::with('requester', 'currentShift', 'requestedShift', 'reviewedBy')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.shifts.change-requests.index', compact('requests'));
    }

    public function approve(Request $request, ShiftChangeRequest $changeRequest)
    {
        abort_if(!$changeRequest->isPending(), 422, 'Already actioned.');

        $request->validate(['manager_notes' => 'nullable|string|max:500']);

        $changeRequest->update([
            'status'        => 'approved',
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'manager_notes' => $request->manager_notes,
        ]);

        // Apply the shift change (ShiftService::assign also fires ShiftAssignedNotification)
        $this->shiftSvc->assign(
            $changeRequest->requester,
            $changeRequest->requestedShift,
            Carbon::parse($changeRequest->effective_date),
            null,
            auth()->user(),
            'Approved shift change request #' . $changeRequest->id
        );

        // Notify the requester that their request was approved
        $changeRequest->requester->notify(
            new ShiftChangeRequestNotification($changeRequest, 'approved', auth()->user())
        );

        return back()->with('success', 'Shift change approved and applied.');
    }

    public function reject(Request $request, ShiftChangeRequest $changeRequest)
    {
        abort_if(!$changeRequest->isPending(), 422, 'Already actioned.');

        $request->validate(['manager_notes' => 'required|string|max:500']);

        $changeRequest->update([
            'status'        => 'rejected',
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'manager_notes' => $request->manager_notes,
        ]);

        $changeRequest->requester->notify(
            new ShiftChangeRequestNotification($changeRequest, 'rejected', auth()->user())
        );

        return back()->with('success', 'Shift change rejected.');
    }
}
