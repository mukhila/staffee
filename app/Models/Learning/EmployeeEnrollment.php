<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeEnrollment extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'enrolled_at', 'completed_at',
        'completion_score', 'status', 'certificate_path', 'notes', 'enrolled_by',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at'      => 'datetime',
            'completed_at'     => 'datetime',
            'completion_score' => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(LearningCourse::class, 'course_id');
    }

    public function enrolledBy()
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
