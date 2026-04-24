<?php

namespace App\Models\Payroll;

class PayrollDetail extends PayrollSlipLine
{
    protected $table = 'payroll_slip_lines';

    public function slip()
    {
        return $this->belongsTo(PayrollSlip::class, 'payroll_slip_id');
    }
}
