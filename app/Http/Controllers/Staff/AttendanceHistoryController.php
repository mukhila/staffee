<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Shift\ShiftAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $month = $request->month ? Carbon::parse($request->month . '-01') : now()->startOfMonth();

        $attendance = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->orderBy('date')
            ->get();

        $months = Attendance::where('user_id', $user->id)
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as ym, DATE_FORMAT(date, "%M %Y") as label')
            ->groupBy('ym', 'label')
            ->orderByDesc('ym')
            ->limit(12)
            ->get();

        $summary = [
            'present'  => $attendance->where('status', 'present')->count(),
            'absent'   => $attendance->where('status', 'absent')->count(),
            'late'     => $attendance->where('status', 'late')->count(),
            'halfday'  => $attendance->where('status', 'halfday')->count(),
            'total_hours' => round($attendance->sum('worked_minutes') / 60, 1),
        ];

        return view('staff.attendance.index', compact('attendance', 'month', 'months', 'summary'));
    }
}
