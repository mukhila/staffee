<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeExperience extends Model
{
    protected $table = 'employee_experience';

    protected $fillable = [
        'user_id', 'company_name', 'position', 'department', 'location',
        'employment_type', 'start_date', 'end_date', 'is_current', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function employee() { return $this->belongsTo(User::class, 'user_id'); }

    public function getDurationMonthsAttribute(): int
    {
        $end = $this->is_current ? now() : ($this->end_date ?? now());
        return (int) $this->start_date->diffInMonths($end);
    }

    public function getDurationLabelAttribute(): string
    {
        $months = $this->duration_months;
        $years  = intdiv($months, 12);
        $rem    = $months % 12;
        $parts  = [];
        if ($years > 0)  $parts[] = "{$years} yr" . ($years > 1 ? 's' : '');
        if ($rem > 0)    $parts[] = "{$rem} mo";
        return implode(' ', $parts) ?: '< 1 mo';
    }
}
