<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollSlip;

class PayslipController extends Controller
{
    public function index()
    {
        $payslips = PayrollSlip::with('payrollRun')
            ->where('user_id', auth()->id())
            ->where('status', 'published')
            ->orderByDesc('period_start')
            ->paginate(20);

        return view('staff.payslips.index', compact('payslips'));
    }
}
