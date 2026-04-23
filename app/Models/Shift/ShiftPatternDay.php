<?php

namespace App\Models\Shift;

use Illuminate\Database\Eloquent\Model;

class ShiftPatternDay extends Model
{
    protected $fillable = [
        'pattern_id', 'day_number', 'is_working_day',
        'override_start_time', 'override_end_time',
    ];

    protected $casts = [
        'is_working_day' => 'boolean',
    ];

    public function pattern()
    {
        return $this->belongsTo(ShiftPattern::class, 'pattern_id');
    }
}
