<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollSlip;

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

        $content = view('payroll.slips.download', compact('payrollSlip'))->render();

        return response()->streamDownload(
            static function () use ($content) {
                echo $content;
            },
            ($payrollSlip->slip_number ?? 'payroll-slip') . '.html',
            ['Content-Type' => 'text/html']
        );
    }
}
