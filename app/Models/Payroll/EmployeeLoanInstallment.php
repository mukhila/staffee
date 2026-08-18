<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoanInstallment extends Model
{
    protected $table = 'employee_loan_installments';

    protected $fillable = [
        'loan_id',
        'payroll_calendar_id',
        'due_period',
        'scheduled_amount',
        'recovered_amount',
        'status',
        'payroll_slip_line_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_amount' => 'decimal:6',
            'recovered_amount' => 'decimal:6',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function loan()
    {
        return $this->belongsTo(EmployeeLoan::class, 'loan_id');
    }

    public function payrollSlipLine()
    {
        return $this->belongsTo(PayrollSlipLine::class, 'payroll_slip_line_id');
    }
}
