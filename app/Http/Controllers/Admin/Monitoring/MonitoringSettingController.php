<?php

namespace App\Http\Controllers\Admin\Monitoring;

use App\Http\Controllers\Controller;
use App\Models\Monitoring\MonitoringSetting;
use App\Models\User;
use Illuminate\Http\Request;

class MonitoringSettingController extends Controller
{
    /** Settings page: global defaults + per-user overrides + token management. */
    public function index()
    {
        $global   = MonitoringSetting::whereNull('user_id')->first();
        $overrides = MonitoringSetting::whereNotNull('user_id')->with('user')->get();
        $users    = User::active()->excludeAdmin()->orderBy('name')->get();

        return view('admin.monitoring.settings', compact('global', 'overrides', 'users'));
    }

    /** Save global or per-user monitoring settings. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'                     => 'nullable|exists:users,id',
            'enabled'                     => 'boolean',
            'screenshot_enabled'          => 'boolean',
            'screenshot_interval_seconds' => 'required|integer|min:60|max:3600',
            'activity_tracking_enabled'   => 'boolean',
            'idle_threshold_seconds'      => 'required|integer|min:30|max:3600',
            'working_hours_only'          => 'boolean',
            'work_start_time'             => 'required|date_format:H:i',
            'work_end_time'               => 'required|date_format:H:i|after:work_start_time',
            'notify_employee'             => 'boolean',
        ]);

        // Checkboxes not submitted = false
        foreach (['enabled', 'screenshot_enabled', 'activity_tracking_enabled', 'working_hours_only', 'notify_employee'] as $bool) {
            $data[$bool] = $request->boolean($bool);
        }

        MonitoringSetting::updateOrCreate(
            ['user_id' => $data['user_id'] ?? null],
            $data
        );

        return redirect()->route('admin.monitoring.settings.index')
            ->with('success', 'Monitoring settings saved.');
    }

    /** Delete a per-user override (falls back to global). */
    public function destroy(MonitoringSetting $setting)
    {
        abort_if($setting->user_id === null, 403, 'Cannot delete global settings.');
        $setting->delete();
        return back()->with('success', 'Per-user override removed.');
    }

    /** Generate a new agent token for a user. */
    public function generateToken(User $user)
    {
        $token = $user->generateAgentToken();
        return back()->with([
            'success'     => "New token generated for {$user->name}.",
            'new_token'   => $token,
            'token_user'  => $user->id,
        ]);
    }

    /** Revoke a user's agent token — desktop agent will be blocked immediately. */
    public function revokeToken(User $user)
    {
        $user->revokeAgentToken();

        // End any active monitoring sessions for this user
        \App\Models\Monitoring\MonitoringSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->update(['status' => 'ended', 'ended_at' => now()]);

        return back()->with('success', "Agent token revoked for {$user->name}.");
    }
}
