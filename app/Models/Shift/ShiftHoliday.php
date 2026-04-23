<?php

namespace App\Models\Shift;

use Illuminate\Database\Eloquent\Model;

class ShiftHoliday extends Model
{
    protected $fillable = [
        'name', 'date', 'holiday_type', 'is_recurring', 'description', 'is_active',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_recurring' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->active()->where('date', '>=', today());
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->holiday_type) {
            'national' => 'danger',
            'regional' => 'warning',
            'company'  => 'info',
            default    => 'secondary',
        };
    }
}
