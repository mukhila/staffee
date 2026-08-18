<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LearningCourse extends Model
{
    protected $fillable = [
        'title', 'description', 'provider', 'category',
        'duration_hours', 'cost', 'is_mandatory', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cost'         => 'decimal:6',
            'duration_hours' => 'decimal:2',
            'is_mandatory' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function enrollments()
    {
        return $this->hasMany(EmployeeEnrollment::class, 'course_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
