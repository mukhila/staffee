<?php

namespace App\Http\Controllers\Admin\Time;

use App\Http\Controllers\Controller;
use App\Models\TimeTracker;
use Illuminate\Http\Request;

class TimeEntryApprovalController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view-staff');

        $entries = TimeTracker::with(['user.department', 'trackable', 'project'])
            ->where('approval_status', 'pending')
            ->when($request->user_id, fn ($q) => $q->where('user_id', $request->user_id))
            ->orderBy('start_time')
            ->paginate(25)
            ->withQueryString();

        return view('admin.time.approvals.index', compact('entries'));
    }

    public function approve(Request $request, TimeTracker $entry)
    {
        $this->authorize('edit-staff');
        abort_if($entry->approval_status !== 'pending', 422, 'Not pending approval.');

        $request->validate(['approval_notes' => 'nullable|string|max:500']);

        $entry->update([
            'approval_status' => 'approved',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'approval_notes'  => $request->approval_notes,
        ]);

        return back()->with('success', 'Time entry approved.');
    }

    public function reject(Request $request, TimeTracker $entry)
    {
        $this->authorize('edit-staff');
        abort_if($entry->approval_status !== 'pending', 422, 'Not pending approval.');

        $request->validate(['approval_notes' => 'required|string|max:500']);

        $entry->update([
            'approval_status' => 'rejected',
            'approved_by'     => auth()->id(),
            'approved_at'     => now(),
            'approval_notes'  => $request->approval_notes,
        ]);

        return back()->with('success', 'Time entry rejected.');
    }
}
