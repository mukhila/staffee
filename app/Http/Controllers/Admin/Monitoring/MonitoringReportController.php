<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();

        $employees = User::excludeAdmin()->with('department')->orderBy('name')->get();

        $data = $employees->map(function (User $user) use ($date) {
            $sessions = MonitoringSession::where('user_id', $user->id)
                ->whereDate('started_at', $date)
                ->get();

            $activeMinutes = $sessions->sum(function ($s) use ($date) {
                $end = $s->ended_at ?? now();
                $end = $end->isToday() ? $end : $end->endOfDay();
                return max(0, $s->started_at->diffInMinutes($end));
            });

            $idleMinutes = MonitoringActivityLog::where('user_id', $user->id)
                ->whereDate('recorded_at', $date)
                ->where('is_idle', true)
                ->sum('duration_seconds') / 60;

            $productiveMinutes = max(0, $activeMinutes - $idleMinutes);

            return [
                'user'               => $user,
                'sessions'           => $sessions->count(),
                'active_minutes'     => (int) $activeMinutes,
                'idle_minutes'       => (int) $idleMinutes,
                'productive_minutes' => (int) $productiveMinutes,
            ];
        });

        $totalActiveMinutes     = $data->sum('active_minutes');
        $totalProductiveMinutes = $data->sum('productive_minutes');

        return view('admin.monitoring.reports.daily', compact(
            'date', 'data', 'totalActiveMinutes', 'totalProductiveMinutes'
        ));
    }

    public function weekly(Request $request)
    {
        $weekStart = $request->week
            ? Carbon::parse($request->week)->startOfWeek()
            : now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();
        $days    = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));

        $employees = User::excludeAdmin()->with('department')->orderBy('name')->get();

        $matrix = $employees->map(function (User $user) use ($days) {
            $byDay = $days->map(function ($day) use ($user) {
                $sessions = MonitoringSession::where('user_id', $user->id)
                    ->whereDate('started_at', $day)
                    ->get();

                $minutes = $sessions->sum(function ($s) use ($day) {
                    $end = $s->ended_at ?? now();
                    return max(0, $s->started_at->diffInMinutes($end));
                });

                return ['date' => $day, 'minutes' => (int) $minutes];
            });

            return [
                'user'        => $user,
                'days'        => $byDay,
                'total'       => $byDay->sum('minutes'),
            ];
        })->sortByDesc('total')->values();

        return view('admin.monitoring.reports.weekly', compact('matrix', 'days', 'weekStart', 'weekEnd'));
    }

    public function department(Request $request)
    {
        $from = $request->from ? Carbon::parse($request->from) : now()->startOfMonth();
        $to   = $request->to   ? Carbon::parse($request->to)   : now();

        $departments = Department::with(['users' => fn ($q) => $q->where('role', '!=', 'admin')])->get();

        $deptData = $departments->map(function (Department $dept) use ($from, $to) {
            $userIds = $dept->users->pluck('id');
            if ($userIds->isEmpty()) return null;

            $totalSessions = MonitoringSession::whereIn('user_id', $userIds)
                ->whereBetween('started_at', [$from, $to])
                ->count();

            $totalMinutes = MonitoringSession::whereIn('user_id', $userIds)
                ->whereBetween('started_at', [$from, $to])
                ->get()
                ->sum(function ($s) use ($to) {
                    $end = $s->ended_at ?? now();
                    $end = $end->gt($to) ? $to : $end;
                    return max(0, $s->started_at->diffInMinutes($end));
                });

            $idleMinutes = MonitoringActivityLog::whereIn('user_id', $userIds)
                ->whereBetween('recorded_at', [$from, $to])
                ->where('is_idle', true)
                ->sum('duration_seconds') / 60;

            $headCount         = $dept->users->count();
            $avgMinutesPerHead = $headCount > 0 ? round($totalMinutes / $headCount) : 0;

            return [
                'department'       => $dept,
                'head_count'       => $headCount,
                'total_sessions'   => $totalSessions,
                'total_minutes'    => (int) $totalMinutes,
                'idle_minutes'     => (int) $idleMinutes,
                'productive_minutes' => max(0, (int) ($totalMinutes - $idleMinutes)),
                'avg_minutes_per_head' => $avgMinutesPerHead,
            ];
        })->filter()->sortByDesc('productive_minutes')->values();

        $maxMinutes = $deptData->max('total_minutes') ?: 1;

        return view('admin.monitoring.reports.department', compact(
            'deptData', 'from', 'to', 'maxMinutes'
        ));
    }
}
