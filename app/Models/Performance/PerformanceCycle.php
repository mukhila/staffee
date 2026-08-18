<?php

namespace App\Models\Performance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PerformanceCycle extends Model
{
    protected $fillable = [
        'name',
        'cycle_type',
        'fiscal_year',
        'review_period_start',
        'review_period_end',
        'submission_deadline',
        'calibration_deadline',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'review_period_start'  => 'date',
            'review_period_end'    => 'date',
            'submission_deadline'  => 'date',
            'calibration_deadline' => 'date',
        ];
    }

    public function reviews()
    {
        return $this->hasMany(PerformanceReview::class, 'cycle_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
