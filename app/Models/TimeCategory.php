<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeCategory extends Model
{
    protected $fillable = ['name', 'is_billable', 'color', 'sort_order', 'is_active'];

    protected $casts = [
        'is_billable' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function timeTrackers()
    {
        return $this->hasMany(TimeTracker::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Total hours logged under this category for optional date range.
     */
    public function totalHours(?string $from = null, ?string $to = null): float
    {
        return (float) $this->timeTrackers()
            ->completed()
            ->when($from, fn ($q) => $q->where('start_time', '>=', $from))
            ->when($to,   fn ($q) => $q->where('start_time', '<=', $to))
            ->sum('hours_decimal');
    }
}
