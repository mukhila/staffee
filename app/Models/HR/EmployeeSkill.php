<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeSkill extends Model
{
    protected $table = 'employee_skills';

    protected $fillable = [
        'user_id', 'name', 'category', 'proficiency',
        'years_of_experience', 'is_verified', 'verified_by',
    ];

    protected function casts(): array
    {
        return ['is_verified' => 'boolean'];
    }

    public function employee() { return $this->belongsTo(User::class, 'user_id'); }

    public function getProficiencyColorAttribute(): string
    {
        return match ($this->proficiency) {
            'beginner'     => 'secondary',
            'intermediate' => 'info',
            'advanced'     => 'primary',
            'expert'       => 'success',
            default        => 'secondary',
        };
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeVerified($query)       { return $query->where('is_verified', true); }
    public function scopeByCategory($query, $c) { return $query->where('category', $c); }
}
