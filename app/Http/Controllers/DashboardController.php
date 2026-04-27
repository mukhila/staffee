<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Bug;
use App\Models\LeaveBalance;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeTracker;
use App\Models\User;
use App\Models\Payroll\PayrollAdjustment;
use App\Models\Payroll\PayrollRun;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $today = now()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $user->id)->where('date', $today)->first();

        $pendingTasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        $assignedBugs = Bug::where('assigned_to', $user->id)
            ->where('status', '!=', 'closed')
            ->orderBy('severity', 'desc')
            ->limit(5)
            ->get();

        $workingHours = null;
        if ($attendance && $attendance->check_out) {
            $diff = \Carbon\Carbon::parse($attendance->check_in)->diff(\Carbon\Carbon::parse($attendance->check_out));
            $workingHours = $diff->format('%H:%I:%S');
        }

        $announcements = Announcement::where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('audience', 'all')->orWhere('audience', $user->role);
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // ── Staff widgets ─────────────────────────────────────────────────────
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('user_id', $user->id)
            ->where('year', now()->year)
            ->get();

        $todayLoggedMinutes = TimeTracker::where('user_id', $user->id)
            ->whereDate('start_time', today())
            ->whereNotNull('end_time')
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)'));

        $runningTimer = TimeTracker::with('trackable')
            ->where('user_id', $user->id)
            ->whereNull('end_time')
            ->first();

        // ── Admin-only stats ──────────────────────────────────────────────────
        $adminStats   = null;
        $adminAlerts  = null;
        if ($user->isAdmin()) {
            $adminStats = [
                'total_staff'        => User::where('role', '!=', 'admin')->count(),
                'active_projects'    => Project::where('status', 'active')->count(),
                'on_leave'           => Attendance::where('date', $today)->where('status', 'leave')->count(),
                'open_bugs'          => Bug::where('status', 'open')->count(),
                'pending_tasks'      => Task::where('status', 'pending')->count(),
                'pending_adjustments'=> PayrollAdjustment::where('status', 'pending')->count(),
            ];

            // Probation ending within 30 days
            $probationAlerts = DB::table('employee_profiles')
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->where('users.employment_status', 'probation')
                ->whereNotNull('employee_profiles.probation_end_date')
                ->whereBetween('employee_profiles.probation_end_date', [today(), today()->addDays(30)])
                ->select('users.id', 'users.name', 'employee_profiles.probation_end_date')
                ->orderBy('employee_profiles.probation_end_date')
                ->limit(5)
                ->get();

            // Contracts expiring within 30 days
            $contractAlerts = DB::table('employee_profiles')
                ->join('users', 'users.id', '=', 'employee_profiles.user_id')
                ->whereNotNull('employee_profiles.contract_end_date')
                ->whereBetween('employee_profiles.contract_end_date', [today(), today()->addDays(30)])
                ->select('users.id', 'users.name', 'employee_profiles.contract_end_date', 'employee_profiles.contract_type')
                ->orderBy('employee_profiles.contract_end_date')
                ->limit(5)
                ->get();

            $adminAlerts = compact('probationAlerts', 'contractAlerts');
        }

        return view('dashboard', compact(
            'attendance', 'pendingTasks', 'assignedBugs',
            'workingHours', 'announcements', 'adminStats', 'adminAlerts',
            'leaveBalances', 'todayLoggedMinutes', 'runningTimer'
        ));
    }
}

