<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\HR\EmployeeProfile;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function workforce(Request $request): View
    {
        // Headcount by department
        $activeCountByDept = User::where('is_active', true)
            ->whereNotNull('department_id')
            ->select('department_id', DB::raw('count(*) as active_count'))
            ->groupBy('department_id')
            ->pluck('active_count', 'department_id');

        $headcountByDept = Department::orderBy('name')->get()->map(function ($dept) use ($activeCountByDept) {
            $dept->active_count = $activeCountByDept->get($dept->id, 0);
            return $dept;
        });

        // Overall active / inactive
        $activeCount   = User::where('is_active', true)->count();
        $inactiveCount = User::where('is_active', false)->count();

        // Average tenure (months) for active employees
        $avgTenureMonths = EmployeeProfile::whereNotNull('joining_date')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get()
            ->avg(fn ($p) => $p->joining_date->diffInMonths(now()));

        // Gender split
        $genderSplit = EmployeeProfile::select('gender', DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->pluck('total', 'gender');

        // New hires this month
        $newHiresThisMonth = EmployeeProfile::whereYear('joining_date', now()->year)
            ->whereMonth('joining_date', now()->month)
            ->count();

        // Terminations this month (active=false, from lifecycle events or termination table)
        $terminationsThisMonth = User::where('is_active', false)
            ->whereYear('updated_at', now()->year)
            ->whereMonth('updated_at', now()->month)
            ->count();

        return view('admin.analytics.workforce', compact(
            'headcountByDept',
            'activeCount',
            'inactiveCount',
            'avgTenureMonths',
            'genderSplit',
            'newHiresThisMonth',
            'terminationsThisMonth',
        ));
    }

    public function attendance(Request $request): View
    {
        $year  = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();
        $workDays  = $startDate->diffInWeekdays($endDate) + 1;

        // Per-department attendance stats
        $departments = Department::orderBy('name')->get();
        $deptStats   = [];

        foreach ($departments as $dept) {
            $userIds = User::where('department_id', $dept->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($userIds->isEmpty()) {
                continue;
            }

            $totalExpected = $userIds->count() * $workDays;

            $records = Attendance::whereIn('user_id', $userIds)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $presentDays    = $records->count();
            $lateCheckins   = $records->where('status', 'late')->count();
            $overtimeHours  = round($records->sum('overtime_minutes') / 60, 1);
            $attendanceRate = $totalExpected > 0
                ? round(($presentDays / $totalExpected) * 100, 1)
                : 0;
            $absentDays = max(0, $totalExpected - $presentDays);

            $deptStats[] = [
                'department'      => $dept->name,
                'employees'       => $userIds->count(),
                'expected_days'   => $totalExpected,
                'present_days'    => $presentDays,
                'absent_days'     => $absentDays,
                'late_checkins'   => $lateCheckins,
                'overtime_hours'  => $overtimeHours,
                'attendance_rate' => $attendanceRate,
            ];
        }

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = \Carbon\Carbon::createFromDate($year, $m, 1)->format('F');
        }

        return view('admin.analytics.attendance', compact(
            'deptStats',
            'year',
            'month',
            'months',
        ));
    }

    public function leaves(Request $request): View
    {
        $year = (int) $request->get('year', now()->year);

        // Leave utilisation by type
        $leaveTypes   = LeaveType::orderBy('name')->get();
        $departments  = Department::orderBy('name')->get();

        // By leave type
        $byType = LeaveRequest::select('leave_type_id', DB::raw('sum(days) as total_days'), DB::raw('count(*) as requests'))
            ->whereYear('from_date', $year)
            ->whereIn('status', ['approved', 'auto_approved'])
            ->groupBy('leave_type_id')
            ->with('leaveType')
            ->get()
            ->keyBy('leave_type_id');

        // By department
        $byDept = LeaveRequest::select(
            'users.department_id',
            DB::raw('sum(leave_requests.days) as total_days'),
            DB::raw('count(*) as requests')
        )
            ->join('users', 'users.id', '=', 'leave_requests.user_id')
            ->whereYear('leave_requests.from_date', $year)
            ->whereIn('leave_requests.status', ['approved', 'auto_approved'])
            ->groupBy('users.department_id')
            ->get()
            ->keyBy('department_id');

        return view('admin.analytics.leaves', compact(
            'leaveTypes',
            'departments',
            'byType',
            'byDept',
            'year',
        ));
    }

    public function turnover(Request $request): View
    {
        // Monthly hire vs exit trend — last 12 months
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $months[] = [
                'label' => $date->format('M Y'),
                'year'  => $date->year,
                'month' => $date->month,
                'hires' => EmployeeProfile::whereYear('joining_date', $date->year)
                    ->whereMonth('joining_date', $date->month)
                    ->count(),
                'exits' => User::where('is_active', false)
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->count(),
            ];
        }

        // Tenure distribution buckets (in years)
        $buckets = [
            '< 1 year'   => 0,
            '1–2 years'  => 0,
            '2–5 years'  => 0,
            '5–10 years' => 0,
            '10+ years'  => 0,
        ];

        $profiles = EmployeeProfile::whereNotNull('joining_date')
            ->whereHas('user', fn ($q) => $q->where('is_active', true))
            ->get();

        foreach ($profiles as $profile) {
            $years = $profile->joining_date->diffInYears(now());
            if ($years < 1) {
                $buckets['< 1 year']++;
            } elseif ($years < 2) {
                $buckets['1–2 years']++;
            } elseif ($years < 5) {
                $buckets['2–5 years']++;
            } elseif ($years < 10) {
                $buckets['5–10 years']++;
            } else {
                $buckets['10+ years']++;
            }
        }

        return view('admin.analytics.turnover', compact('months', 'buckets'));
    }
}
