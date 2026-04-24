<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MonitoringSetting extends Model
{
    protected $fillable = [
        'user_id', 'enabled', 'screenshot_enabled', 'screenshot_interval_seconds',
        'activity_tracking_enabled', 'idle_threshold_seconds',
        'working_hours_only', 'work_start_time', 'work_end_time', 'notify_employee',
    ];

    protected $casts = [
        'enabled'                    => 'boolean',
        'screenshot_enabled'         => 'boolean',
        'activity_tracking_enabled'  => 'boolean',
        'working_hours_only'         => 'boolean',
        'notify_employee'            => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve effective settings for a user: per-user override first, then global default.
     */
    public static function forUser(User $user): self
    {
        return static::where('user_id', $user->id)->first()
            ?? static::whereNull('user_id')->first()
            ?? new self([
                'enabled'                   => true,
                'screenshot_enabled'        => true,
                'screenshot_interval_seconds' => 300,
                'activity_tracking_enabled' => true,
                'idle_threshold_seconds'    => 300,
                'working_hours_only'        => false,
                'work_start_time'           => '09:00:00',
                'work_end_time'             => '18:00:00',
                'notify_employee'           => false,
            ]);
    }
}
