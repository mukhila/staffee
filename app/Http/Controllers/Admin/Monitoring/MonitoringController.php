<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    /** Live status board — who's online, recent activity. */
    public function index()
    {
        // Mark stale sessions as expired
        MonitoringSession::where('status', 'active')
            ->where('last_heartbeat_at', '<', now()->subMinutes(3))
            ->update(['status' => 'expired', 'ended_at' => now()]);

        $onlineUsers = User::excludeAdmin()
            ->with(['department', 'monitoringSessions' => fn ($q) =>
                $q->where('status', 'active')->latest('last_heartbeat_at')
            ])
            ->get()
            ->filter(fn ($u) => $u->monitoringSessions->isNotEmpty())
            ->map(function (User $user) {
                $session = $user->monitoringSessions->first();
                $latest  = MonitoringActivityLog::where('user_id', $user->id)
                    ->where('session_id', $session->id)
                    ->latest('recorded_at')
                    ->first();
                return [
                    'user'            => $user,
                    'session'         => $session,
                    'latest_activity' => $latest,
                ];
            })
            ->values();

        $offlineUsers = User::excludeAdmin()
            ->with('department')
            ->whereDoesntHave('monitoringSessions', fn ($q) => $q->where('status', 'active'))
            ->orderBy('name')
            ->get();

        $todayTotal   = MonitoringSession::whereDate('started_at', today())->count();
        $onlineCount  = $onlineUsers->count();

        return view('admin.monitoring.index', compact(
            'onlineUsers', 'offlineUsers', 'todayTotal', 'onlineCount'
        ));
    }

    /** Per-employee detail: today's sessions, activity breakdown, idle time. */
    public function show(User $user, Request $request)
    {
        $date = $request->date ? Carbon::parse($request->date) : today();

        $sessions = MonitoringSession::where('user_id', $user->id)
            ->whereDate('started_at', $date)
            ->orderBy('started_at')
            ->get();

        $activityLogs = MonitoringActivityLog::where('user_id', $user->id)
            ->whereDate('recorded_at', $date)
            ->orderBy('recorded_at')
            ->get();

        $idlePeriods = \App\Models\Monitoring\MonitoringIdlePeriod::where('user_id', $user->id)
            ->whereDate('idle_start', $date)
            ->orderBy('idle_start')
            ->get();

        $totalActive  = $activityLogs->where('is_active', true)->sum('duration_seconds');
        $totalIdle    = $idlePeriods->sum('duration_seconds');
        $topApps      = $activityLogs->groupBy('active_app_name')
            ->map(fn ($g) => $g->sum('duration_seconds'))
            ->sortDesc()
            ->take(5);

        return view('admin.monitoring.show', compact(
            'user', 'date', 'sessions', 'activityLogs', 'idlePeriods',
            'totalActive', 'totalIdle', 'topApps'
        ));
    }
}
