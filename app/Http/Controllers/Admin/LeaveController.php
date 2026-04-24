<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveApprovalService;
use App\Services\Leave\LeaveRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveSvc,
        private readonly LeaveApprovalService $approvalSvc,
    ) {}

    public function index(Request $request)
    {
        $leaves = LeaveRequest::with(['user.department', 'leaveType'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->type, fn ($q) => $q->where('leave_type_id', $request->type))
            ->when($request->department, fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('department_id', $request->department)))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $leaveTypes = LeaveType::active()->orderBy('name')->get();

        return view('admin.leaves.index', compact('leaves', 'leaveTypes'));
    }

    public function show(LeaveRequest $leave)
    {
        $leave->load(['user.department', 'leaveType', 'approvals.approver', 'managerApprover', 'hrApprover']);
        return view('admin.leaves.show', compact('leave'));
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        $this->leaveSvc->managerApprove($leave, auth()->user(), $request->notes);
        return back()->with('success', 'Leave approved successfully.');
    }

    public function hrApprove(Request $request, LeaveRequest $leave)
    {
        $this->leaveSvc->hrApprove($leave, auth()->user(), $request->notes);
        return back()->with('success', 'Leave HR-approved successfully.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $this->leaveSvc->reject($leave, auth()->user(), $request->rejection_reason);
        return back()->with('success', 'Leave rejected.');
    }

    public function approvalDashboard()
    {
        $pending = $this->approvalSvc->getPendingForApprover(auth()->user());
        return view('admin.leaves.approvals', compact('pending'));
    }

    public function calendar(Request $request)
    {
        $year  = (int) ($request->year ?? now()->year);
        $month = (int) ($request->month ?? now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $leaves = LeaveRequest::approved()
            ->forDateRange($start->toDateString(), $end->toDateString())
            ->with(['user.department', 'leaveType'])
            ->orderBy('from_date')
            ->get();

        return view('admin.leaves.calendar', compact('leaves', 'year', 'month', 'start', 'end'));
    }
}
