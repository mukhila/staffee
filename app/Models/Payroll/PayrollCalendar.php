<?php

namespace App\Models\Payroll;

use Illuminate\Database\Eloquent\Model;

class PayrollCalendar extends Model
{
    protected $table = 'payroll_calendars';

    protected $fillable = [
        'company_code', 'pay_frequency', 'period_code', 'period_start',
        'period_end', 'pay_date', 'attendance_cutoff_date', 'timesheet_cutoff_date',
        'leave_cutoff_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'pay_date' => 'date',
            'attendance_cutoff_date' => 'date',
            'timesheet_cutoff_date' => 'date',
            'leave_cutoff_date' => 'date',
        ];
    }

    public function runs()
    {
        return $this->hasMany(PayrollRun::class, 'payroll_calendar_id');
    }

    public function slips()
    {
        return $this->hasMany(PayrollSlip::class, 'payroll_calendar_id');
    }
}
