<?php

namespace App\Models\Payroll;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PayrollCalculationLog extends Model
{
    protected $table = 'payroll_calculation_logs';

    protected $fillable = [
        'payroll_slip_id', 'payroll_run_id', 'user_id', 'stage', 'action',
        'input_payload', 'output_payload', 'formula_used', 'performed_by', 'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'input_payload' => 'array',
            'output_payload' => 'array',
            'performed_at' => 'datetime',
        ];
    }

    public function slip()
    {
        return $this->belongsTo(PayrollSlip::class, 'payroll_slip_id');
    }

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
