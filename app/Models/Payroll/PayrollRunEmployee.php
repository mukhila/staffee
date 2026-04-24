<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollRunEmployee extends Model
{
    protected $table = 'payroll_run_employees';

    protected $fillable = [
        'payroll_run_id', 'user_id', 'salary_structure_id',
        'employment_status_snapshot', 'inclusion_status',
        'exclusion_reason', 'source_summary',
    ];

    protected function casts(): array
    {
        return [
            'source_summary' => 'array',
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

    public function salaryStructure()
    {
        return $this->belongsTo(EmployeeSalaryStructure::class, 'salary_structure_id');
    }
}
