<?php

namespace App\Models\Shift;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'name', 'code', 'shift_type', 'start_time', 'end_time', 'crosses_midnight',
        'break_duration_minutes', 'grace_in_minutes', 'grace_out_minutes',
        'overtime_threshold_minutes', 'min_hours_for_full_day', 'half_day_threshold_hours',
        'flexible_window_start', 'flexible_window_end', 'flexible_duration_hours',
        'working_days', 'color', 'timezone', 'description', 'is_active', 'created_by',
    ];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'is_active'        => 'boolean',
        'working_days'     => 'array',
    ];

    const TYPES = [
        'fixed'    => 'Fixed',
        'rotating' => 'Rotating',
        'flexible' => 'Flexible',
        'night'    => 'Night',
        'hybrid'   => 'Hybrid',
    ];

    const TYPE_COLORS = [
        'fixed'    => 'primary',
        'rotating' => 'warning',
        'flexible' => 'info',
        'night'    => 'dark',
        'hybrid'   => 'secondary',
    ];

    public function assignments()
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function patterns()
    {
        return $this->hasMany(ShiftPattern::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isNightShift(): bool
    {
        return $this->shift_type === 'night' || $this->crosses_midnight;
    }

    public function isFlexible(): bool
    {
        return $this->shift_type === 'flexible';
    }

    public function isRotating(): bool
    {
        return $this->shift_type === 'rotating';
    }

    /**
     * Expected check-in as a Carbon datetime anchored to the given date.
     */
    public function expectedStartForDate(Carbon $date): Carbon
    {
        return $date->copy()->setTimeFromTimeString($this->start_time);
    }

    /**
     * Expected check-out as a Carbon datetime.
     * Night shifts: end_time on the following calendar day.
     */
    public function expectedEndForDate(Carbon $date): Carbon
    {
        $end = $date->copy()->setTimeFromTimeString($this->end_time);
        if ($this->crosses_midnight) {
            $start = $date->copy()->setTimeFromTimeString($this->start_time);
            if ($end->lte($start)) {
                $end->addDay();
            }
        }
        return $end;
    }

    public function shiftDurationMinutes(): int
    {
        $start = Carbon::createFromTimeString($this->start_time);
        $end   = Carbon::createFromTimeString($this->end_time);
        if ($this->crosses_midnight) {
            $end->addDay();
        }
        return max(0, (int) $start->diffInMinutes($end));
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->shift_type] ?? 'secondary';
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->shift_type] ?? ucfirst($this->shift_type);
    }
}
