<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Services\Leave\LeaveBalanceService;
use App\Services\Leave\LeaveRequestService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(
        private readonly LeaveRequestService $leaveSvc,
        private readonly LeaveBalanceService $balanceSvc,
    ) {}

    public function index()
    {
        $user    = auth()->user();
        $year    = now()->year;
        $leaves  = LeaveRequest::with('leaveType')
            ->forUser($user->id)
            ->orderByDesc('created_at')
            ->get();

        $types   = LeaveType::active()->get();
        $balances = $this->balanceSvc->getBalanceSummary($user, $year);

        return view('staff.leaves.index', compact('leaves', 'types', 'balances', 'year'));
    }

    public function create()
    {
        $user    = auth()->user();
        $types   = LeaveType::active()->orderBy('name')->get();
        $year    = now()->year;
        $balances = $this->balanceSvc->getBalanceSummary($user, $year);

        return view('staff.leaves.create', compact('types', 'balances'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id'  => 'required|exists:leave_types,id',
            'from_date'      => 'required|date|after_or_equal:today',
            'to_date'        => 'required|date|after_or_equal:from_date',
            'half_day'       => 'boolean',
            'half_day_period'=> 'nullable|in:morning,afternoon',
            'reason'         => 'required|string|max:1000',
        ]);

        try {
            $this->leaveSvc->submit(auth()->user(), $request->only([
                'leave_type_id', 'from_date', 'to_date',
                'half_day', 'half_day_period', 'reason',
            ]));
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('staff.leaves.index')->with('success', 'Leave request submitted successfully.');
    }

    public function show(LeaveRequest $leave)
    {
        abort_if($leave->user_id !== auth()->id(), 403);
        $leave->load(['leaveType', 'approvals.approver', 'managerApprover', 'hrApprover']);
        return view('staff.leaves.show', compact('leave'));
    }

    public function destroy(LeaveRequest $leave)
    {
        abort_if($leave->user_id !== auth()->id(), 403);

        try {
            $this->leaveSvc->cancel($leave, 'Cancelled by employee');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('staff.leaves.index')->with('success', 'Leave request cancelled.');
    }

    public function teamCalendar()
    {
        $user       = auth()->user();
        $month      = (int) request('month', now()->month);
        $year       = (int) request('year', now()->year);
        $start      = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $end        = $start->copy()->endOfMonth();

        // Staff see their department teammates' approved leaves
        $deptId = $user->department_id;
        $leaves = LeaveRequest::with(['user', 'leaveType'])
            ->approved()
            ->whereHas('user', fn ($q) => $q->where('department_id', $deptId))
            ->forDateRange($start->toDateString(), $end->toDateString())
            ->orderBy('from_date')
            ->get();

        return view('staff.leaves.team-calendar', compact('leaves', 'month', 'year', 'start', 'end'));
    }
}
