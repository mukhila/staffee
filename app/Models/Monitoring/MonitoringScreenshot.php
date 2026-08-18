<?php

namespace App\Models\Monitoring;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MonitoringScreenshot extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'captured_at', 'file_path', 'thumbnail_path',
        'file_size_bytes', 'width', 'height', 'active_window_title',
        'is_flagged', 'flag_reason',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'is_flagged'  => 'boolean',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function session() { return $this->belongsTo(MonitoringSession::class, 'session_id'); }

    public function scopeFlagged($query)  { return $query->where('is_flagged', true); }
    public function scopeForDate($query, string $date) { return $query->whereDate('captured_at', $date); }

    public function getUrlAttribute(): string
    {
        return route('admin.monitoring.screenshots.serve', $this);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $suffix = $this->thumbnail_path ? '?thumb=1' : '';
        return route('admin.monitoring.screenshots.serve', $this) . $suffix;
    }
}
