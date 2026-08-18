<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Expense\ExpenseClaim;
use App\Models\LeaveRequest;
use App\Models\Payroll\PayrollSlip;
use App\Models\Task;
use App\Models\User;

class EssController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Quick stats
        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $currentTasksCount = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        $upcomingPayslip = PayrollSlip::where('user_id', $user->id)
            ->where('status', 'published')
            ->orderByDesc('period_end')
            ->first();

        // Recent announcements (latest 3)
        $announcements = Announcement::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        // My team (colleagues in same department, excluding self)
        $teammates = $user->department_id
            ? User::where('department_id', $user->department_id)
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->limit(10)
                ->get()
            : collect();

        // Pending action items
        $pendingLeaveRequests = LeaveRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'manager_approved'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $pendingExpenses = ExpenseClaim::where('user_id', $user->id)
            ->whereIn('status', ['draft', 'submitted'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('staff.ess.index', compact(
            'user',
            'pendingLeaves',
            'todayAttendance',
            'currentTasksCount',
            'upcomingPayslip',
            'announcements',
            'teammates',
            'pendingLeaveRequests',
            'pendingExpenses',
        ));
    }
}
