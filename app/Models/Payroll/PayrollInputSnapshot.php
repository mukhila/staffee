<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollInputSnapshot extends Model
{
    protected $table = 'payroll_input_snapshots';

    protected $fillable = [
        'payroll_run_id', 'user_id', 'attendance_summary', 'leave_summary',
        'time_summary', 'deduction_summary', 'salary_structure_snapshot',
        'tax_context_snapshot', 'snapshot_hash',
    ];

    protected function casts(): array
    {
        return [
            'attendance_summary' => 'array',
            'leave_summary' => 'array',
            'time_summary' => 'array',
            'deduction_summary' => 'array',
            'salary_structure_snapshot' => 'array',
            'tax_context_snapshot' => 'array',
        ];
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
