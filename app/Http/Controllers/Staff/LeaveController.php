<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = LeaveRequest::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('staff.leaves.index', compact('leaves'));
    }

    public function create()
    {
        return view('staff.leaves.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'      => 'required|in:sick,casual,annual,unpaid',
            'from_date' => 'required|date|after_or_equal:today',
            'to_date'   => 'required|date|after_or_equal:from_date',
            'reason'    => 'required|string|max:1000',
        ]);

        $from = \Carbon\Carbon::parse($request->from_date);
        $to   = \Carbon\Carbon::parse($request->to_date);
        $days = $from->diffInDays($to) + 1;

        $leave = LeaveRequest::create([
            'user_id'   => auth()->id(),
            'type'      => $request->type,
            'from_date' => $request->from_date,
            'to_date'   => $request->to_date,
            'days'      => $days,
            'reason'    => $request->reason,
            'status'    => 'pending',
        ]);

        // Notify admin users
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type'    => 'leave_request',
                'title'   => 'New Leave Request',
                'message' => auth()->user()->name . ' has submitted a ' . $request->type . ' leave request for ' . $days . ' day(s).',
                'url'     => route('admin.leaves.index'),
            ]);
        }

        return redirect()->route('staff.leaves.index')->with('success', 'Leave request submitted successfully.');
    }

    public function destroy(LeaveRequest $leave)
    {
        if ($leave->user_id !== auth()->id() || $leave->status !== 'pending') {
            abort(403);
        }
        $leave->delete();

        return redirect()->route('staff.leaves.index')->with('success', 'Leave request cancelled.');
    }
}
