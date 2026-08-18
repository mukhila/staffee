<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollSlip;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollSlipController extends Controller
{
    public function showSlip(PayrollSlip $payrollSlip)
    {
        abort_unless(
            auth()->id() === $payrollSlip->user_id || auth()->user()?->role === 'admin',
            403
        );

        $payrollSlip->load('lines.definition', 'employee', 'payrollRun');

        return view('payroll.slips.show', compact('payrollSlip'));
    }

    public function downloadSlip(PayrollSlip $payrollSlip)
    {
        abort_unless(
            auth()->id() === $payrollSlip->user_id || auth()->user()?->role === 'admin',
            403
        );

        $payrollSlip->load('lines.definition', 'employee');

        $pdf = Pdf::loadView('payroll.slips.download', compact('payrollSlip'));

        $filename = ($payrollSlip->slip_number ?? 'payroll-slip') . '.pdf';

        return $pdf->download($filename);
    }
}
