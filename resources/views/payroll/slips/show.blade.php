<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="app-page-title">Payroll Slip</h1>
                <span>{{ $payrollSlip->period_start->format('M Y') }}</span>
            </div>
            <a href="{{ route('payroll.slips.download', $payrollSlip) }}" class="btn btn-primary">Download</a>
        </div>

        <div class="card mb-4">
            <div class="card-body row g-3">
                <div class="col-md-3"><small class="text-muted">Employee</small><div>{{ $payrollSlip->employee?->name }}</div></div>
                <div class="col-md-3"><small class="text-muted">Gross</small><div>{{ $payrollSlip->gross_earnings }}</div></div>
                <div class="col-md-3"><small class="text-muted">Deductions</small><div>{{ $payrollSlip->total_deductions }}</div></div>
                <div class="col-md-3"><small class="text-muted">Net</small><div>{{ $payrollSlip->net_pay }}</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table">
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
                                <td>{{ ucfirst(str_replace('_', ' ', $line->line_category)) }}</td>
                                <td>{{ $line->amount }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
