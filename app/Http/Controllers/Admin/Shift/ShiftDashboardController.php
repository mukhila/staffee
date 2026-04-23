<?php

namespace App\Http\Controllers\Admin\Shift;

use App\Http\Controllers\Controller;
use App\Models\Shift\AttendanceException;
use App\Models\Shift\Shift;
use App\Models\Shift\ShiftAssignment;
use App\Models\Shift\ShiftChangeRequest;
use App\Models\Shift\ShiftHoliday;
use App\Models\User;
use App\Services\Shift\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftDashboardController extends Controller
{
    public function __construct(private readonly ShiftService $shiftSvc) {}

    public function index(Request $request)
    {
        $today = Carbon::today();

        // ── Stat cards ─────────────────────────────────────────────────────────
        $stats = [
            'total_shifts'       => Shift::active()->count(),
            'assigned_employees' => ShiftAssignment::forDate($today)->distinct('user_id')->count('user_id'),
            'unassigned'         => User::active()->excludeAdmin()->count()
                                   - ShiftAssignment::forDate($today)->distinct('user_id')->count('user_id'),
            'pending_exceptions' => AttendanceException::pending()->whereDate('date', '>=', $today->copy()->subDays(7))->count(),
            'pending_changes'    => ShiftChangeRequest::pending()->count(),
            'upcoming_holidays'  => ShiftHoliday::upcoming()->count(),
        ];

        // ── Shift utilisation today ────────────────────────────────────────────
        $utilisation = $this->shiftSvc->utilisationReport($today, $today);

        // ── Recent exceptions (last 7 days) ────────────────────────────────────
        $recentExceptions = AttendanceException::with('user', 'shift')
            ->pending()
            ->where('date', '>=', $today->copy()->subDays(7))
            ->orderByDesc('date')
            ->take(10)
            ->get();

        // ── Pending shift change requests ──────────────────────────────────────
        $pendingChanges = ShiftChangeRequest::with('requester', 'currentShift', 'requestedShift')
            ->pending()
            ->orderBy('effective_date')
            ->take(5)
            ->get();

        // ── Calendar data: shift assignments for current month ─────────────────
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd   = $today->copy()->endOfMonth();

        $calendarAssignments = ShiftAssignment::with('user', 'shift')
            ->active()
            ->where('effective_from', '<=', $monthEnd->toDateString())
            ->where(function ($q) use ($monthStart) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $monthStart->toDateString());
            })
            ->get();

        // ── Upcoming holidays ──────────────────────────────────────────────────
        $upcomingHolidays = ShiftHoliday::upcoming()->take(5)->orderBy('date')->get();

        return view('admin.shifts.dashboard', compact(
            'stats', 'utilisation', 'recentExceptions',
            'pendingChanges', 'calendarAssignments', 'upcomingHolidays', 'today'
        ));
    }
}
