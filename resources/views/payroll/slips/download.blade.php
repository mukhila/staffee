<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Slip {{ $payrollSlip->slip_number }}</title>
</head>
<body>
    <h1>Payroll Slip</h1>
    <p>Employee: {{ $payrollSlip->employee?->name }}</p>
    <p>Period: {{ $payrollSlip->period_start->format('M Y') }}</p>
    <p>Gross: {{ $payrollSlip->gross_earnings }}</p>
    <p>Deductions: {{ $payrollSlip->total_deductions }}</p>
    <p>Net: {{ $payrollSlip->net_pay }}</p>
    <hr>
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>Component</th>
                <th>Type</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payrollSlip->lines as $line)
                <tr>
                    <td>{{ $line->line_name }}</td>
                    <td>{{ $line->line_category }}</td>
                    <td>{{ $line->amount }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
