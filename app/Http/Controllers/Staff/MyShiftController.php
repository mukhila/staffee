<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Shift\Shift;
use App\Models\Shift\ShiftAssignment;
use App\Models\Shift\ShiftChangeRequest;
use App\Models\User;
use Illuminate\Http\Request;

class MyShiftController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $currentAssignment = ShiftAssignment::with('shift')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNull('effective_to')
            ->latest('effective_from')
            ->first();

        $changeRequests = ShiftChangeRequest::with(['currentShift', 'requestedShift', 'swapWithUser', 'reviewedBy'])
            ->where('requester_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        $availableShifts = Shift::where('is_active', true)->orderBy('name')->get();
        $swapCandidates  = User::active()->excludeAdmin()->where('id', '!=', $user->id)->orderBy('name')->get();

        return view('staff.shifts.index', compact('currentAssignment', 'changeRequests', 'availableShifts', 'swapCandidates'));
    }

    public function requestChange(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'request_type'        => 'required|in:change,swap',
            'requested_shift_id'  => 'required_if:request_type,change|nullable|exists:shifts,id',
            'swap_with_user_id'   => 'required_if:request_type,swap|nullable|exists:users,id',
            'effective_date'      => 'required|date|after_or_equal:today',
            'reason'              => 'required|string|max:1000',
        ]);

        $current = ShiftAssignment::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('effective_from')
            ->first();

        ShiftChangeRequest::create([
            'requester_id'       => $user->id,
            'current_shift_id'   => $current?->shift_id,
            'requested_shift_id' => $validated['request_type'] === 'change' ? $validated['requested_shift_id'] : null,
            'swap_with_user_id'  => $validated['request_type'] === 'swap'   ? $validated['swap_with_user_id']  : null,
            'effective_date'     => $validated['effective_date'],
            'reason'             => $validated['reason'],
            'status'             => 'pending',
        ]);

        return back()->with('success', 'Shift change request submitted.');
    }

    public function cancelRequest(ShiftChangeRequest $changeRequest)
    {
        abort_if($changeRequest->requester_id !== auth()->id(), 403);
        abort_if(!$changeRequest->isPending(), 422, 'Only pending requests can be cancelled.');

        $changeRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'Request cancelled.');
    }
}
