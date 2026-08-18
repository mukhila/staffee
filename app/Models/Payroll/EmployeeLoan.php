<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $table = 'employee_loans';

    protected $fillable = [
        'user_id',
        'loan_type',
        'principal_amount',
        'issued_date',
        'recovery_start_period',
        'installment_amount',
        'total_installments',
        'remaining_balance',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_date'        => 'date',
            'principal_amount'   => 'decimal:6',
            'installment_amount' => 'decimal:6',
            'remaining_balance'  => 'decimal:6',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function installments()
    {
        return $this->hasMany(EmployeeLoanInstallment::class, 'loan_id')->orderBy('due_period');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // ── Domain helpers ───────────────────────────────────────────────────────

    /**
     * Return the sum of all pending installment scheduled_amounts as a BCMath string.
     */
    public function outstandingBalance(): string
    {
        $sum = '0.000000';
        foreach ($this->installments()->where('status', 'pending')->get() as $installment) {
            $sum = bcadd($sum, (string) $installment->scheduled_amount, 6);
        }

        return $sum;
    }
}
