<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip {{ $payrollSlip->slip_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; }
        .page { padding: 32px 40px; }

        /* Header */
        .slip-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 2px solid #1e293b; padding-bottom: 16px; }
        .company-name { font-size: 18px; font-weight: 700; color: #1e293b; }
        .slip-title { font-size: 13px; color: #64748b; margin-top: 2px; }
        .slip-meta { text-align: right; }
        .slip-number { font-size: 12px; font-weight: 600; color: #475569; }
        .slip-period { font-size: 11px; color: #64748b; margin-top: 2px; }

        /* Employee info */
        .employee-section { display: flex; justify-content: space-between; margin-bottom: 20px; background: #f8fafc; border-radius: 6px; padding: 12px 16px; }
        .info-group { flex: 1; }
        .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 2px; }
        .info-value { font-size: 11px; font-weight: 600; color: #1e293b; }

        /* Attendance row */
        .attendance-row { display: flex; gap: 8px; margin-bottom: 20px; }
        .att-box { flex: 1; text-align: center; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 4px; }
        .att-num { font-size: 14px; font-weight: 700; color: #1e293b; }
        .att-label { font-size: 8px; text-transform: uppercase; color: #94a3b8; margin-top: 2px; }

        /* Tables */
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; margin-bottom: 6px; margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #f1f5f9; font-size: 9px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; padding: 6px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        table th.text-right { text-align: right; }
        table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        table td.text-right { text-align: right; }
        table td.muted { color: #94a3b8; font-size: 9px; }
        table tfoot td { font-weight: 700; background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: none; padding: 8px 10px; }
        .text-danger { color: #dc2626; }

        /* Two-column layout */
        .two-col { display: flex; gap: 20px; margin-bottom: 4px; }
        .two-col > div { flex: 1; }

        /* Net pay */
        .net-box { margin-top: 20px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; }
        .net-label { font-size: 13px; font-weight: 700; color: #15803d; }
        .net-amount { font-size: 22px; font-weight: 800; color: #15803d; }
        .net-currency { font-size: 10px; color: #4ade80; margin-right: 4px; }

        /* Employer section */
        .employer-note { font-size: 9px; color: #94a3b8; margin-top: 2px; }

        /* Footer */
        .slip-footer { margin-top: 32px; padding-top: 12px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; color: #94a3b8; font-size: 9px; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="slip-header">
        <div>
            <div class="company-name">Staffee</div>
            <div class="slip-title">Salary Slip</div>
        </div>
        <div class="slip-meta">
            <div class="slip-number">{{ $payrollSlip->slip_number }}</div>
            <div class="slip-period">{{ $payrollSlip->period_start->format('d M Y') }} &ndash; {{ $payrollSlip->period_end->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Employee details --}}
    <div class="employee-section">
        <div class="info-group">
            <div class="info-label">Employee</div>
            <div class="info-value">{{ $payrollSlip->employee?->name }}</div>
        </div>
        @if($payrollSlip->employee?->employee_id)
        <div class="info-group">
            <div class="info-label">Employee ID</div>
            <div class="info-value">{{ $payrollSlip->employee->employee_id }}</div>
        </div>
        @endif
        <div class="info-group">
            <div class="info-label">Pay Period</div>
            <div class="info-value">{{ $payrollSlip->period_start->format('F Y') }}</div>
        </div>
        <div class="info-group">
            <div class="info-label">Currency</div>
            <div class="info-value">{{ $payrollSlip->currency_code }}</div>
        </div>
    </div>

    {{-- Attendance --}}
    <div class="attendance-row">
        <div class="att-box">
            <div class="att-num">{{ number_format($payrollSlip->payable_days, 1) }}</div>
            <div class="att-label">Payable Days</div>
        </div>
        <div class="att-box">
            <div class="att-num">{{ number_format($payrollSlip->worked_days, 1) }}</div>
            <div class="att-label">Worked Days</div>
        </div>
        <div class="att-box">
            <div class="att-num">{{ number_format($payrollSlip->paid_leave_days, 1) }}</div>
            <div class="att-label">Paid Leave</div>
        </div>
        <div class="att-box">
            <div class="att-num">{{ number_format($payrollSlip->unpaid_leave_days, 1) }}</div>
            <div class="att-label">Unpaid Leave</div>
        </div>
        @if($payrollSlip->overtime_hours > 0)
        <div class="att-box">
            <div class="att-num">{{ number_format($payrollSlip->overtime_hours, 1) }}h</div>
            <div class="att-label">Overtime</div>
        </div>
        @endif
    </div>

    @php
        $earningLines   = $payrollSlip->lines->where('line_category', 'earning');
        $deductionLines = $payrollSlip->lines->where('line_category', 'deduction');
        $employerLines  = $payrollSlip->lines->where('line_category', 'employer_contribution');
    @endphp

    {{-- Earnings & Deductions side by side --}}
    <div class="two-col">
        <div>
            <div class="section-title">Earnings</div>
            <table>
                <thead>
                    <tr>
                        <th>Component</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($earningLines as $line)
                    <tr>
                        <td>
                            {{ $line->line_name }}
                            @if($line->calculation_basis)
                            <br><span class="muted">{{ $line->calculation_basis }}</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($line->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:16px 0;">—</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Gross Earnings</td>
                        <td class="text-right">{{ number_format($payrollSlip->gross_earnings, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div>
            <div class="section-title">Deductions</div>
            <table>
                <thead>
                    <tr>
                        <th>Component</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deductionLines as $line)
                    <tr>
                        <td>
                            {{ $line->line_name }}
                            @if($line->calculation_basis)
                            <br><span class="muted">{{ $line->calculation_basis }}</span>
                            @endif
                        </td>
                        <td class="text-right text-danger">-{{ number_format($line->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" style="text-align:center;color:#94a3b8;padding:16px 0;">—</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Deductions</td>
                        <td class="text-right text-danger">-{{ number_format($payrollSlip->total_deductions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Net Pay --}}
    <div class="net-box">
        <div class="net-label">Net Pay</div>
        <div>
            <span class="net-currency">{{ $payrollSlip->currency_code }}</span>
            <span class="net-amount">{{ number_format($payrollSlip->net_pay, 2) }}</span>
        </div>
    </div>

    {{-- Employer contributions --}}
    @if($employerLines->isNotEmpty())
    <div class="section-title" style="margin-top:20px;">Employer Contributions <span class="employer-note">(informational — not deducted from your salary)</span></div>
    <table>
        <thead>
            <tr>
                <th>Component</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employerLines as $line)
            <tr>
                <td>{{ $line->line_name }}</td>
                <td class="text-right">{{ number_format($line->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="text-right">{{ number_format($payrollSlip->employer_contributions, 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Footer --}}
    <div class="slip-footer">
        <div>This is a system-generated payslip. No signature required.</div>
        <div>Generated: {{ now()->format('d M Y, H:i') }}</div>
    </div>

</div>
</body>
</html>
