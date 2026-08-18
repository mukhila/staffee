<?php

namespace App\Models\Performance;

use Illuminate\Database\Eloquent\Model;

class PerformanceGoal extends Model
{
    protected $fillable = [
        'review_id',
        'title',
        'description',
        'category',
        'target_metric',
        'achievement_notes',
        'weightage',
        'self_rating',
        'reviewer_rating',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'weightage'       => 'decimal:2',
            'self_rating'     => 'decimal:2',
            'reviewer_rating' => 'decimal:2',
        ];
    }

    public function review()
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }
}
