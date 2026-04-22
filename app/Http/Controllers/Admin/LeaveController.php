<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Notification;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leaves = $query->paginate(20);

        return view('admin.leaves.index', compact('leaves'));
    }

    public function approve(LeaveRequest $leave)
    {
        $leave->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
        ]);

        Notification::create([
            'user_id' => $leave->user_id,
            'type'    => 'leave_approved',
            'title'   => 'Leave Request Approved',
            'message' => 'Your ' . $leave->type . ' leave request from ' . $leave->from_date . ' to ' . $leave->to_date . ' has been approved.',
            'url'     => route('staff.leaves.index'),
        ]);

        return redirect()->back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $leave->update([
            'status'           => 'rejected',
            'reviewed_by'      => auth()->id(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        Notification::create([
            'user_id' => $leave->user_id,
            'type'    => 'leave_rejected',
            'title'   => 'Leave Request Rejected',
            'message' => 'Your ' . $leave->type . ' leave request has been rejected. Reason: ' . $request->rejection_reason,
            'url'     => route('staff.leaves.index'),
        ]);

        return redirect()->back()->with('success', 'Leave rejected.');
    }
}
