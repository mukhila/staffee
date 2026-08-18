<?php

namespace App\Models\Payroll;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeBenefitDeduction extends Model
{
    protected $table = 'employee_benefit_deductions';

    protected $fillable = [
        'user_id',
        'benefit_name',
        'benefit_type',
        'amount',
        'effective_from',
        'effective_to',
        'frequency',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to'   => 'date',
            'amount'         => 'decimal:6',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to deductions effective during a given period (format "YYYY-MM").
     *
     * A deduction is effective in a period when:
     *   - effective_from <= last day of the period month
     *   - AND (effective_to IS NULL OR effective_to >= first day of the period month)
     */
    public function scopeEffectiveForPeriod(Builder $query, string $period): Builder
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();

        return $query
            ->where('effective_from', '<=', $end)
            ->where(function (Builder $q) use ($start) {
                $q->whereNull('effective_to')
                  ->orWhere('effective_to', '>=', $start);
            });
    }

    // ── Domain helpers ───────────────────────────────────────────────────────

    /**
     * Check whether this deduction is active (status = 'active') and effective
     * within the given period (format "YYYY-MM").
     */
    public function isActiveForPeriod(string $period): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end   = Carbon::createFromFormat('Y-m', $period)->endOfMonth();

        if ($this->effective_from->gt($end)) {
            return false;
        }

        if ($this->effective_to !== null && $this->effective_to->lt($start)) {
            return false;
        }

        return true;
    }
}
