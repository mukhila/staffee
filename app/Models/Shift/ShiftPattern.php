<?php

namespace App\Models\Shift;

use Illuminate\Database\Eloquent\Model;

class ShiftPattern extends Model
{
    protected $fillable = ['shift_id', 'name', 'cycle_length_days', 'description'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function days()
    {
        return $this->hasMany(ShiftPatternDay::class, 'pattern_id')->orderBy('day_number');
    }

    public function workingDaysCount(): int
    {
        return $this->days()->where('is_working_day', true)->count();
    }
}
