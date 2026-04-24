<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BillableRate extends Model
{
    protected $fillable = [
        'user_id', 'project_id', 'rate_type',
        'hourly_rate', 'currency',
        'effective_from', 'effective_to',
        'created_by', 'notes',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
        'hourly_rate'    => 'decimal:2',
    ];

    const RATE_TYPES = [
        'global'       => 'Global Default',
        'user'         => 'Per Employee',
        'project'      => 'Per Project',
        'user_project' => 'Employee + Project',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()    { return $this->belongsTo(User::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /** Rates active on a specific date. */
    public function scopeActiveOn($query, Carbon $date)
    {
        return $query
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($q) use ($date) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $date->toDateString());
            });
    }

    /** Shorthand: rates active today. */
    public function scopeActive($query)
    {
        return $this->scopeActiveOn($query, today());
    }

    // ── Rate resolution ───────────────────────────────────────────────────────

    /**
     * Resolve the most specific active rate for a user/project combination on a date.
     *
     * Priority: user_project > project > user > global
     */
    public static function resolve(User $user, ?Project $project, Carbon $date): ?self
    {
        $candidates = self::activeOn($date)
            ->where(function ($q) use ($user, $project) {
                // user_project
                $q->when($project, fn ($q2) =>
                    $q2->orWhere(fn ($q3) =>
                        $q3->where('rate_type', 'user_project')
                           ->where('user_id', $user->id)
                           ->where('project_id', $project->id)
                    )
                );
                // project
                if ($project) {
                    $q->orWhere(fn ($q2) =>
                        $q2->where('rate_type', 'project')
                           ->where('project_id', $project->id)
                           ->whereNull('user_id')
                    );
                }
                // user
                $q->orWhere(fn ($q2) =>
                    $q2->where('rate_type', 'user')
                       ->where('user_id', $user->id)
                       ->whereNull('project_id')
                );
                // global
                $q->orWhere(fn ($q2) =>
                    $q2->where('rate_type', 'global')
                       ->whereNull('user_id')
                       ->whereNull('project_id')
                );
            })
            ->orderByRaw("FIELD(rate_type, 'user_project', 'project', 'user', 'global')")
            ->get();

        return $candidates->first();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        $today = today();
        return $this->effective_from->lte($today)
            && ($this->effective_to === null || $this->effective_to->gte($today));
    }

    public function getRateTypeLabelAttribute(): string
    {
        return self::RATE_TYPES[$this->rate_type] ?? ucfirst($this->rate_type);
    }
}
