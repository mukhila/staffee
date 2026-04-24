<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MonitoringActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'recorded_at', 'duration_seconds',
        'active_app_name', 'active_window_title',
        'keyboard_events', 'mouse_events', 'mouse_distance_px', 'is_active',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'is_active'   => 'boolean',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function session() { return $this->belongsTo(MonitoringSession::class, 'session_id'); }

    public function scopeActive($query)  { return $query->where('is_active', true); }
    public function scopeForDate($query, string $date) { return $query->whereDate('recorded_at', $date); }

    /** Activity score 0–100: fraction of interval with keyboard or mouse input. */
    public function getActivityScoreAttribute(): int
    {
        if ($this->duration_seconds === 0) {
            return 0;
        }
        $events = $this->keyboard_events + $this->mouse_events;
        return min(100, (int) round($events / max(1, $this->duration_seconds) * 10));
    }
}
