<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeTracker extends Model
{
    protected $fillable = [
        'user_id', 'trackable_id', 'trackable_type',
        'category_id', 'project_id',
        'start_time', 'end_time',
        'is_billable', 'hours_decimal', 'rate_snapshot',
        'description', 'notes',
    ];

    protected $casts = [
        'start_time'    => 'datetime',
        'end_time'      => 'datetime',
        'is_billable'   => 'boolean',
        'hours_decimal' => 'decimal:4',
        'rate_snapshot' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function trackable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(TimeCategory::class, 'category_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('end_time');
    }

    public function scopeRunning($query)
    {
        return $query->whereNull('end_time');
    }

    public function scopeBillable($query)
    {
        return $query->where('is_billable', true);
    }

    public function scopeNonBillable($query)
    {
        return $query->where('is_billable', false);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeForDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('start_time', [$from . ' 00:00:00', $to . ' 23:59:59']);
    }

    public function scopeForPeriod($query, string $period)
    {
        return match ($period) {
            'today'      => $query->whereDate('start_time', today()),
            'this_week'  => $query->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth('start_time', now()->month)->whereYear('start_time', now()->year),
            'this_year'  => $query->whereYear('start_time', now()->year),
            default      => $query,
        };
    }

    // ── Scopes (query-level revenue) ──────────────────────────────────────────

    /**
     * Add a SQL-computed `computed_revenue` column to the result set.
     * Use for aggregate queries where you need revenue per row without
     * loading the collection into PHP.
     */
    public function scopeWithRevenue($query)
    {
        return $query->addSelect(\DB::raw(
            'CASE WHEN is_billable = 1
                   AND hours_decimal IS NOT NULL
                   AND rate_snapshot IS NOT NULL
             THEN ROUND(hours_decimal * rate_snapshot, 2)
             ELSE 0 END AS computed_revenue'
        ));
    }

    // ── Computed attributes ───────────────────────────────────────────────────

    /**
     * Duration in hours (4 decimal places).
     * Uses stored hours_decimal if available, otherwise calculates live.
     */
    public function getDurationHoursAttribute(): float
    {
        if ($this->hours_decimal !== null) {
            return (float) $this->hours_decimal;
        }

        if ($this->end_time === null) {
            return round($this->start_time->diffInSeconds(now()) / 3600, 4);
        }

        return round($this->start_time->diffInSeconds($this->end_time) / 3600, 4);
    }

    /**
     * Duration formatted as "Xh Ym".
     */
    public function getDurationFormattedAttribute(): string
    {
        $totalMinutes = (int) round($this->duration_hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    /**
     * Revenue = hours × snapshot rate — uses BCMath to avoid float drift.
     * Returns 0.0 if non-billable, no rate, or not yet completed.
     */
    public function getRevenueAttribute(): float
    {
        if (!$this->is_billable || !$this->rate_snapshot || !$this->hours_decimal) {
            return 0.0;
        }

        return (float) bcmul(
            (string) $this->hours_decimal,
            (string) $this->rate_snapshot,
            2
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isRunning(): bool
    {
        return $this->end_time === null;
    }

    public function isBillable(): bool
    {
        return (bool) $this->is_billable;
    }

    /**
     * Resolve the effective billable rate for this entry's user, project,
     * and start date. Useful for previewing rates before stopping a timer.
     */
    public function getEffectiveRate(): ?BillableRate
    {
        return BillableRate::resolve(
            $this->user,
            $this->project,
            ($this->start_time ?? now())->toCarbon()
        );
    }

    /**
     * Compute and store hours_decimal from start/end timestamps.
     */
    public function computeAndStoreHours(): void
    {
        if ($this->end_time) {
            $this->hours_decimal = round(
                $this->start_time->diffInSeconds($this->end_time) / 3600,
                4
            );
            $this->saveQuietly();
        }
    }
}
