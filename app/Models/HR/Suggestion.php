<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    protected $fillable = [
        'user_id',
        'is_anonymous',
        'title',
        'body',
        'category',
        'status',
        'admin_response',
        'responded_by',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    const STATUS_COLORS = [
        'new'          => 'secondary',
        'under_review' => 'info',
        'implemented'  => 'success',
        'rejected'     => 'danger',
    ];

    const STATUS_LABELS = [
        'new'          => 'New',
        'under_review' => 'Under Review',
        'implemented'  => 'Implemented',
        'rejected'     => 'Rejected',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getStatusColorAttribute(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'secondary';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function getAuthorNameAttribute(): string
    {
        if ($this->is_anonymous || !$this->user) {
            return 'Anonymous';
        }

        return $this->user->name;
    }
}
