<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MonitoringSession extends Model
{
    protected $fillable = [
        'user_id', 'started_at', 'ended_at', 'last_heartbeat_at',
        'ip_address', 'hostname', 'os_info', 'agent_version', 'status',
    ];

    protected $casts = [
        'started_at'         => 'datetime',
        'ended_at'           => 'datetime',
        'last_heartbeat_at'  => 'datetime',
    ];

    public function user()       { return $this->belongsTo(User::class); }
    public function screenshots() { return $this->hasMany(MonitoringScreenshot::class, 'session_id'); }
    public function activityLogs() { return $this->hasMany(MonitoringActivityLog::class, 'session_id'); }
    public function idlePeriods() { return $this->hasMany(MonitoringIdlePeriod::class, 'session_id'); }

    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function getDurationMinutesAttribute(): int
    {
        $end = $this->ended_at ?? now();
        return (int) $this->started_at->diffInMinutes($end);
    }

    public function isStale(): bool
    {
        // No heartbeat in 3 minutes = stale
        return $this->last_heartbeat_at
            && $this->last_heartbeat_at->lt(now()->subMinutes(3));
    }
}
