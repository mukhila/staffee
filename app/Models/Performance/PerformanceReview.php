<?php

namespace App\Models\Performance;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PerformanceReview extends Model
{
    protected $fillable = [
        'cycle_id',
        'reviewee_id',
        'reviewer_id',
        'department_id',
        'overall_rating',
        'overall_comments',
        'self_rating',
        'self_comments',
        'status',
        'submitted_at',
        'completed_at',
        'acknowledged_by_employee',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating'           => 'decimal:2',
            'self_rating'              => 'decimal:2',
            'submitted_at'             => 'datetime',
            'completed_at'             => 'datetime',
            'acknowledged_by_employee' => 'boolean',
            'acknowledged_at'          => 'datetime',
        ];
    }

    public function cycle()
    {
        return $this->belongsTo(PerformanceCycle::class, 'cycle_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function goals()
    {
        return $this->hasMany(PerformanceGoal::class, 'review_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('reviewee_id', $userId)
              ->orWhere('reviewer_id', $userId);
        });
    }
}
