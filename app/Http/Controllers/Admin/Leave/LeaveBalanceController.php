<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\User;
use App\Services\Leave\LeaveAccrualService;
use App\Services\Leave\LeaveBalanceService;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    public function __construct(
        private readonly LeaveBalanceService $balanceSvc,
        private readonly LeaveAccrualService $accrualSvc,
    ) {}

    public function index(Request $request)
    {
        $year        = (int) ($request->year ?? now()->year);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $leaveTypes  = LeaveType::active()->orderBy('name')->get();

        $balances = LeaveBalance::with(['user.department', 'leaveType'])
            ->forYear($year)
            ->when($request->department, fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('department_id', $request->department)))
            ->when($request->leave_type, fn ($q) => $q->where('leave_type_id', $request->leave_type))
            ->orderBy('user_id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.leaves.balances.index', compact('balances', 'year', 'departments', 'leaveTypes'));
    }

    /**
     * Manually adjust a balance (admin override).
     */
    public function adjust(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'leave_type_id' => 'required|exists:leave_types,id',
            'year'          => 'required|integer|min:2020',
            'field'         => 'required|in:opening_balance,accrued_days,used_days',
            'value'         => 'required|numeric|min:0',
        ]);

        $user    = User::findOrFail($request->user_id);
        $type    = LeaveType::findOrFail($request->leave_type_id);
        $balance = $this->balanceSvc->getBalance($user, $type, $request->year);

        $balance->update([$request->field => $request->value]);

        return back()->with('success', 'Balance adjusted.');
    }

    /**
     * Trigger accrual manually for a specific date (admin tool).
     */
    public function runAccrual(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $count = $this->accrualSvc->runAccrual(\Carbon\Carbon::parse($request->date));
        return back()->with('success', "{$count} accrual entries created.");
    }
}
