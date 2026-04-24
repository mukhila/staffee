<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $table = 'payroll_runs';

    protected $fillable = [
        'payroll_calendar_id', 'run_type', 'currency_code', 'employee_scope_type',
        'employee_scope', 'for_month', 'for_year', 'status', 'generated_at', 'input_snapshot_hash',
        'totals_json', 'error_log', 'locked_by', 'locked_at',
        'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_scope' => 'array',
            'for_month' => 'integer',
            'for_year' => 'integer',
            'generated_at' => 'datetime',
            'totals_json' => 'array',
            'error_log' => 'array',
            'locked_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function calendar()
    {
        return $this->belongsTo(PayrollCalendar::class, 'payroll_calendar_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function runEmployees()
    {
        return $this->hasMany(PayrollRunEmployee::class, 'payroll_run_id');
    }

    public function slips()
    {
        return $this->hasMany(PayrollSlip::class, 'payroll_run_id');
    }

    public function snapshots()
    {
        return $this->hasMany(PayrollInputSnapshot::class, 'payroll_run_id');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'approved', 'posted', 'paid']);
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('for_month', $month)->where('for_year', $year);
    }
}
