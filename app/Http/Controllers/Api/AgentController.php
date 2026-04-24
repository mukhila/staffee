<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringActivityLog;
use App\Models\Monitoring\MonitoringIdlePeriod;
use App\Models\Monitoring\MonitoringScreenshot;
use App\Models\Monitoring\MonitoringSession;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AgentController extends Controller
{
    // ── Session management ────────────────────────────────────────────────────

    /**
     * POST /api/agent/session/start
     * Agent calls this at startup after token auth succeeds.
     */
    public function sessionStart(Request $request): JsonResponse
    {
        $user = $request->agentUser();

        // Expire any stale active sessions for this user
        MonitoringSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'expired', 'ended_at' => now()]);

        $session = MonitoringSession::create([
            'user_id'            => $user->id,
            'started_at'         => now(),
            'last_heartbeat_at'  => now(),
            'ip_address'         => $request->ip(),
            'hostname'           => $request->input('hostname'),
            'os_info'            => $request->input('os_info'),
            'agent_version'      => $request->input('agent_version'),
            'status'             => 'active',
        ]);

        $settings = MonitoringSetting::forUser($user);

        return response()->json([
            'session_id' => $session->id,
            'config'     => $this->buildConfig($settings),
        ]);
    }

    /**
     * POST /api/agent/session/end
     */
    public function sessionEnd(Request $request): JsonResponse
    {
        $user = $request->agentUser();

        MonitoringSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'ended', 'ended_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ── Heartbeat ─────────────────────────────────────────────────────────────

    /**
     * POST /api/agent/heartbeat
     * Called every 30 s. Marks session as alive.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user    = $request->agentUser();
        $session = $this->resolveSession($user, $request->input('session_id'));

        if ($session) {
            $session->update(['last_heartbeat_at' => now()]);
        }

        return response()->json(['ok' => true, 'server_time' => now()->toIso8601String()]);
    }

    // ── Config ────────────────────────────────────────────────────────────────

    /**
     * GET /api/agent/config
     * Agent fetches settings on startup and after each session start.
     */
    public function config(Request $request): JsonResponse
    {
        $user     = $request->agentUser();
        $settings = MonitoringSetting::forUser($user);
        return response()->json($this->buildConfig($settings));
    }

    // ── Screenshot ────────────────────────────────────────────────────────────

    /**
     * POST /api/agent/screenshot
     * Accepts multipart/form-data with file=screenshot image.
     */
    public function screenshot(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'          => 'required|integer',
            'captured_at'         => 'required|date',
            'file'                => 'required|image|max:5120',
            'active_window_title' => 'nullable|string|max:500',
        ]);

        $user    = $request->agentUser();
        $session = $this->resolveSession($user, $request->input('session_id'));
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 422);
        }

        $dir  = "monitoring/screenshots/{$user->id}/" . now()->format('Y/m/d');
        $path = $request->file('file')->store($dir, 'public');

        MonitoringScreenshot::create([
            'user_id'             => $user->id,
            'session_id'          => $session->id,
            'captured_at'         => $request->input('captured_at'),
            'file_path'           => $path,
            'file_size_bytes'     => $request->file('file')->getSize(),
            'active_window_title' => $request->input('active_window_title'),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Activity ──────────────────────────────────────────────────────────────

    /**
     * POST /api/agent/activity
     * Agent posts a 60-second activity summary every minute.
     */
    public function activity(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'          => 'required|integer',
            'recorded_at'         => 'required|date',
            'duration_seconds'    => 'nullable|integer|min:1|max:300',
            'active_app_name'     => 'nullable|string|max:255',
            'active_window_title' => 'nullable|string|max:500',
            'keyboard_events'     => 'nullable|integer|min:0',
            'mouse_events'        => 'nullable|integer|min:0',
            'mouse_distance_px'   => 'nullable|integer|min:0',
            'is_active'           => 'nullable|boolean',
        ]);

        $user    = $request->agentUser();
        $session = $this->resolveSession($user, $request->input('session_id'));
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 422);
        }

        MonitoringActivityLog::create([
            'user_id'             => $user->id,
            'session_id'          => $session->id,
            'recorded_at'         => $request->input('recorded_at'),
            'duration_seconds'    => $request->input('duration_seconds', 60),
            'active_app_name'     => $request->input('active_app_name'),
            'active_window_title' => $request->input('active_window_title'),
            'keyboard_events'     => $request->input('keyboard_events', 0),
            'mouse_events'        => $request->input('mouse_events', 0),
            'mouse_distance_px'   => $request->input('mouse_distance_px', 0),
            'is_active'           => $request->boolean('is_active', true),
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Idle ──────────────────────────────────────────────────────────────────

    /**
     * POST /api/agent/idle
     * Agent posts when it detects an idle period has ended.
     */
    public function idle(Request $request): JsonResponse
    {
        $request->validate([
            'session_id'       => 'required|integer',
            'idle_start'       => 'required|date',
            'idle_end'         => 'required|date|after:idle_start',
            'duration_seconds' => 'nullable|integer|min:1',
        ]);

        $user    = $request->agentUser();
        $session = $this->resolveSession($user, $request->input('session_id'));
        if (!$session) {
            return response()->json(['error' => 'Session not found.'], 422);
        }

        $start    = \Carbon\Carbon::parse($request->input('idle_start'));
        $end      = \Carbon\Carbon::parse($request->input('idle_end'));
        $duration = $request->input('duration_seconds', $start->diffInSeconds($end));

        MonitoringIdlePeriod::create([
            'user_id'          => $user->id,
            'session_id'       => $session->id,
            'idle_start'       => $start,
            'idle_end'         => $end,
            'duration_seconds' => $duration,
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveSession(User $user, mixed $sessionId): ?MonitoringSession
    {
        return MonitoringSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
    }

    private function buildConfig(MonitoringSetting $settings): array
    {
        return [
            'enabled'                     => $settings->enabled,
            'screenshot_enabled'          => $settings->screenshot_enabled,
            'screenshot_interval_seconds' => $settings->screenshot_interval_seconds,
            'activity_tracking_enabled'   => $settings->activity_tracking_enabled,
            'idle_threshold_seconds'      => $settings->idle_threshold_seconds,
            'working_hours_only'          => $settings->working_hours_only,
            'work_start_time'             => $settings->work_start_time,
            'work_end_time'               => $settings->work_end_time,
            'notify_employee'             => $settings->notify_employee,
        ];
    }
}
