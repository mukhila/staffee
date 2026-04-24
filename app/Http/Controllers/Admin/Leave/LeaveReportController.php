<?php

namespace App\Http\Controllers\Admin\Leave;

use App\Http\Controllers\Controller;
use App\Models\Leave\LeaveBalance;
use App\Models\Leave\LeaveType;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveReportController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) ($request->year ?? now()->year);

        $totalRequests = LeaveRequest::forYear($year)->count();
        $approved      = LeaveRequest::approved()->forYear($year)->count();
        $rejected      = LeaveRequest::forYear($year)->where('status', 'rejected')->count();
        $pending       = LeaveRequest::pending()->count(); // current, not year-scoped
        $totalDays     = LeaveRequest::approved()->forYear($year)->sum('days');

        $byType = LeaveType::withCount([
            'requests as approved_count' => fn ($q) => $q->approved()->forYear($year),
        ])->withSum(
            ['requests as approved_days' => fn ($q) => $q->approved()->forYear($year)],
            'days'
        )->get();

        // Monthly distribution (approved)
        $monthly = LeaveRequest::approved()
            ->forYear($year)
            ->selectRaw('MONTH(from_date) as month, SUM(days) as total_days, COUNT(*) as total_count')
            ->groupByRaw('MONTH(from_date)')
            ->orderByRaw('MONTH(from_date)')
            ->pluck('total_days', 'month')
            ->all();

        // Ensure all 12 months present
        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyData[$m] = $monthly[$m] ?? 0;
        }

        return view('admin.leaves.reports.index', compact(
            'year', 'totalRequests', 'approved', 'rejected', 'pending', 'totalDays', 'byType', 'monthlyData'
        ));
    }

    /**
     * Compliance: employees who have used less than a given % of their entitlement.
     */
    public function compliance(Request $request)
    {
        $year      = (int) ($request->year ?? now()->year);
        $threshold = (int) ($request->threshold ?? 50);

        $balances = LeaveBalance::with(['user.department', 'leaveType'])
            ->forYear($year)
            ->get()
            ->filter(function (LeaveBalance $b) use ($threshold) {
                $max = $b->leaveType->max_days_per_year ?? 0;
                if ($max <= 0) {
                    return false;
                }
                $pct = ($b->used_days / $max) * 100;
                return $pct < $threshold;
            })
            ->sortBy('user.name');

        return view('admin.leaves.reports.compliance', compact('balances', 'year', 'threshold'));
    }

    /**
     * Trends: month-by-month breakdown per leave type.
     */
    public function trends(Request $request)
    {
        $year  = (int) ($request->year ?? now()->year);
        $types = LeaveType::active()->orderBy('name')->get();

        // [month][leave_type_id] => total_days
        $raw = LeaveRequest::approved()
            ->forYear($year)
            ->selectRaw('MONTH(from_date) as month, leave_type_id, SUM(days) as total_days')
            ->groupByRaw('MONTH(from_date), leave_type_id')
            ->get();

        $grid = [];
        for ($m = 1; $m <= 12; $m++) {
            $grid[$m] = [];
            foreach ($types as $t) {
                $grid[$m][$t->id] = 0;
            }
        }
        foreach ($raw as $row) {
            $grid[$row->month][$row->leave_type_id] = (float) $row->total_days;
        }

        return view('admin.leaves.reports.trends', compact('grid', 'types', 'year'));
    }
}
