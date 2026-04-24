<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MonitoringIdlePeriod extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'idle_start', 'idle_end', 'duration_seconds',
    ];

    protected $casts = [
        'idle_start' => 'datetime',
        'idle_end'   => 'datetime',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function session() { return $this->belongsTo(MonitoringSession::class, 'session_id'); }

    public function getFormattedDurationAttribute(): string
    {
        $s = $this->duration_seconds ?? 0;
        $m = intdiv($s, 60);
        $s = $s % 60;
        return $m > 0 ? "{$m}m {$s}s" : "{$s}s";
    }
}
