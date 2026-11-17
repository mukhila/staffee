<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll\PayrollSlip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    /** GET /api/v1/payslips — the user's published payslips */
    public function index(Request $request): JsonResponse
    {
        $slips = PayrollSlip::where('user_id', $request->user()->id)
            ->where('status', 'published')
            ->orderByDesc('period_start')
            ->limit(36)
            ->get();

        return response()->json(['data' => $slips->map(fn (PayrollSlip $s) => [
            'id'               => $s->id,
            'slip_number'      => $s->slip_number,
            'period_start'     => $s->period_start?->toDateString(),
            'period_end'       => $s->period_end?->toDateString(),
            'currency'         => $s->currency_code,
            // Money as strings, exactly as stored, so no precision is lost.
            'gross_earnings'   => (string) $s->gross_earnings,
            'total_deductions' => (string) $s->total_deductions,
            'net_pay'          => (string) $s->net_pay,
            'pdf_url'          => route('api.v1.payslips.pdf', $s),
        ])]);
    }

    /** GET /api/v1/payslips/{payslip}/pdf */
    public function pdf(Request $request, PayrollSlip $payslip)
    {
        // Only your own, published slips; anything else looks like it does not exist.
        if ($payslip->user_id !== $request->user()->id || $payslip->status !== 'published') {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $payrollSlip = $payslip->load('lines.definition', 'employee');

        return Pdf::loadView('payroll.slips.download', compact('payrollSlip'))
            ->download(($payslip->slip_number ?? 'payslip') . '.pdf');
    }
}
