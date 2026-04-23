<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class FinalSettlement extends Model
{
    protected $table = 'final_settlements';

    protected $fillable = [
        'termination_id', 'user_id', 'last_working_date',
        'basic_salary', 'pending_salary_days', 'pending_salary_amount',
        'leave_encashment_days', 'leave_encashment_amount',
        'bonus', 'gratuity', 'other_earnings', 'total_earnings',
        'pending_advances', 'notice_shortfall_days', 'notice_shortfall_deduction',
        'other_deductions', 'total_deductions', 'net_payable', 'currency',
        'status', 'calculated_by', 'approved_by', 'approved_at',
        'paid_at', 'payment_mode', 'payment_reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_working_date'          => 'date',
            'basic_salary'               => 'decimal:2',
            'pending_salary_amount'      => 'decimal:2',
            'leave_encashment_days'      => 'decimal:2',
            'leave_encashment_amount'    => 'decimal:2',
            'bonus'                      => 'decimal:2',
            'gratuity'                   => 'decimal:2',
            'other_earnings'             => 'array',
            'total_earnings'             => 'decimal:2',
            'pending_advances'           => 'decimal:2',
            'notice_shortfall_deduction' => 'decimal:2',
            'other_deductions'           => 'array',
            'total_deductions'           => 'decimal:2',
            'net_payable'                => 'decimal:2',
            'approved_at'                => 'datetime',
            'paid_at'                    => 'datetime',
        ];
    }

    public function termination()
    {
        return $this->belongsTo(TerminationRequest::class, 'termination_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recalculate(): void
    {
        $earnings = collect($this->other_earnings ?? [])
            ->sum(fn ($item) => $item['amount'] ?? 0);

        $this->total_earnings = $this->pending_salary_amount
            + $this->leave_encashment_amount
            + $this->bonus
            + $this->gratuity
            + $earnings;

        $deductions = collect($this->other_deductions ?? [])
            ->sum(fn ($item) => $item['amount'] ?? 0);

        $this->total_deductions = $this->pending_advances
            + $this->notice_shortfall_deduction
            + $deductions;

        $this->net_payable = $this->total_earnings - $this->total_deductions;
    }
}
