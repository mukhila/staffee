<?php

namespace App\Models\Shift;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ShiftAssignment extends Model
{
    protected $fillable = [
        'user_id', 'shift_id', 'effective_from', 'effective_to',
        'assigned_by', 'status', 'pattern_anchor_date', 'notes',
    ];

    protected $casts = [
        'effective_from'      => 'date',
        'effective_to'        => 'date',
        'pattern_anchor_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Assignments covering a specific date.
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->active()
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date->toDateString());
            });
    }

    public function isActiveOnDate(Carbon $date): bool
    {
        return $this->status === 'active'
            && $this->effective_from->lte($date)
            && ($this->effective_to === null || $this->effective_to->gte($date));
    }

    /**
     * For rotating shifts: determine if the given date falls on a working day in the cycle.
     */
    public function isWorkingDay(Carbon $date): bool
    {
        if (!$this->shift->isRotating()) {
            return true;
        }

        $pattern = $this->shift->patterns()->with('days')->first();
        if (!$pattern || !$this->pattern_anchor_date) {
            return true;
        }

        $dayOffset  = (int) $this->pattern_anchor_date->diffInDays($date) % $pattern->cycle_length_days;
        $patternDay = $pattern->days->firstWhere('day_number', $dayOffset + 1);

        return $patternDay ? $patternDay->is_working_day : true;
    }

    public function scopeForUser($query, \App\Models\User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'     => 'success',
            'superseded' => 'secondary',
            'cancelled'  => 'danger',
            default      => 'secondary',
        };
    }
}
