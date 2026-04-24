<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollAdjustment extends Model
{
    protected $table = 'payroll_adjustments';

    protected $fillable = [
        'user_id', 'payroll_calendar_id', 'component_definition_id', 'adjustment_type',
        'amount', 'quantity', 'reason', 'recurrence', 'start_period', 'end_period',
        'remaining_installments', 'source_type', 'source_id', 'status',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
            'quantity' => 'decimal:4',
            'approved_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function calendar()
    {
        return $this->belongsTo(PayrollCalendar::class, 'payroll_calendar_id');
    }

    public function definition()
    {
        return $this->belongsTo(ComponentDefinition::class, 'component_definition_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
