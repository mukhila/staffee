<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollSlip extends Model
{
    protected $table = 'payroll_slips';

    protected $fillable = [
        'payroll_run_id', 'payroll_calendar_id', 'user_id', 'salary_structure_id',
        'slip_number', 'currency_code', 'pay_frequency', 'period_start', 'period_end',
        'payable_days', 'worked_days', 'paid_leave_days', 'unpaid_leave_days',
        'overtime_hours', 'gross_earnings', 'total_deductions',
        'employer_contributions', 'taxable_income', 'tax_amount', 'net_pay',
        'ytd_gross', 'ytd_tax', 'ytd_net', 'status', 'pdf_path', 'emailed_at',
        'published_at', 'paid_at', 'payment_mode', 'payment_reference',
        'calculation_version', 'snapshot_json',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'payable_days' => 'decimal:4',
            'worked_days' => 'decimal:4',
            'paid_leave_days' => 'decimal:4',
            'unpaid_leave_days' => 'decimal:4',
            'overtime_hours' => 'decimal:4',
            'gross_earnings' => 'decimal:6',
            'total_deductions' => 'decimal:6',
            'employer_contributions' => 'decimal:6',
            'taxable_income' => 'decimal:6',
            'tax_amount' => 'decimal:6',
            'net_pay' => 'decimal:6',
            'ytd_gross' => 'decimal:6',
            'ytd_tax' => 'decimal:6',
            'ytd_net' => 'decimal:6',
            'emailed_at' => 'datetime',
            'published_at' => 'datetime',
            'paid_at' => 'datetime',
            'snapshot_json' => 'array',
        ];
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function calendar()
    {
        return $this->belongsTo(PayrollCalendar::class, 'payroll_calendar_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salaryStructure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'salary_structure_id');
    }

    public function lines()
    {
        return $this->hasMany(PayrollSlipLine::class, 'payroll_slip_id')->orderBy('display_order');
    }

    public function calculationLogs()
    {
        return $this->hasMany(PayrollCalculationLog::class, 'payroll_slip_id');
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->whereMonth('period_start', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('period_start', $year);
    }
}
