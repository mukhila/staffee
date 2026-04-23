<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    protected $table = 'employee_education';

    protected $fillable = [
        'user_id', 'institution_name', 'degree', 'field_of_study',
        'start_year', 'end_year', 'is_current', 'grade_gpa', 'activities',
    ];

    protected function casts(): array
    {
        return ['is_current' => 'boolean'];
    }

    public function employee() { return $this->belongsTo(User::class, 'user_id'); }

    public function getDurationAttribute(): string
    {
        $end = $this->is_current ? 'Present' : ($this->end_year ?? '—');
        return ($this->start_year ?? '—') . ' – ' . $end;
    }
}
