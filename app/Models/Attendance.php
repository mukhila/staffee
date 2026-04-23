<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'shift_id', 'date', 'check_in', 'check_out', 'status',
        'worked_minutes', 'overtime_minutes', 'is_shift_day', 'validated_at',
    ];

    protected $casts = [
        'date'         => 'date',
        'is_shift_day' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(\App\Models\Shift\Shift::class);
    }

    public function exceptions()
    {
        return $this->hasMany(\App\Models\Shift\AttendanceException::class);
    }

    public function getWorkedHoursAttribute(): float
    {
        return round(($this->worked_minutes ?? 0) / 60, 2);
    }

    public function isValidated(): bool
    {
        return $this->validated_at !== null;
    }

    public function scopeForDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('date', $date->toDateString());
    }

    public function scopeUnvalidated($query)
    {
        return $query->whereNull('validated_at');
    }

    public function scopeForShift($query, \App\Models\Shift\Shift $shift)
    {
        return $query->where('shift_id', $shift->id);
    }

    public function scopeViolations($query)
    {
        return $query->whereHas('exceptions', fn ($q) => $q->where('status', 'pending'));
    }
}
