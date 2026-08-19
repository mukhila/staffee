<x-app-layout>
<div class="container">

    {{-- Page header --}}
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between mb-4">
        <div>
            <h1 class="app-page-title">Payslip</h1>
            <span class="text-muted">{{ $payrollSlip->slip_number }} &mdash; {{ $payrollSlip->period_start->format('F Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()?->role === 'admin')
            <a href="{{ route('admin.payroll.runs.status', $payrollSlip->payroll_run_id) }}" class="btn btn-secondary btn-sm">Back to Run</a>
            @else
            <a href="{{ route('staff.payslips.index') }}" class="btn btn-secondary btn-sm">Back</a>
            @endif
            <a href="{{ route('payroll.slips.download', $payrollSlip) }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-download me-1"></i> Download PDF
            </a>
        </div>
    </div>

    {{-- Employee & period summary --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">Employee</small>
                    <div class="fw-semibold">{{ $payrollSlip->employee?->name }}</div>
                    @if($payrollSlip->employee?->employee_id)
                    <div class="text-muted small">ID: {{ $payrollSlip->employee->employee_id }}</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Pay Period</small>
                    <div class="fw-semibold">
                        {{ $payrollSlip->period_start->format('d M Y') }} &mdash; {{ $payrollSlip->period_end->format('d M Y') }}
                    </div>
                    <div class="text-muted small">{{ ucfirst($payrollSlip->pay_frequency) }}</div>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">Status</small>
                    <div>
                        <span class="badge bg-{{ $payrollSlip->status === 'published' ? 'success' : 'secondary' }}-subtle text-{{ $payrollSlip->status === 'published' ? 'success' : 'secondary' }}">
                            {{ ucfirst($payrollSlip->status) }}
                        </span>
                    </div>
                    @if($payrollSlip->published_at)
                    <div class="text-muted small">Published {{ $payrollSlip->published_at->format('d M Y') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ number_format($payrollSlip->payable_days, 1) }}</div>
                    <div class="text-muted small">Payable Days</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ number_format($payrollSlip->worked_days, 1) }}</div>
                    <div class="text-muted small">Worked Days</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5">{{ number_format($payrollSlip->paid_leave_days, 1) }}</div>
                    <div class="text-muted small">Paid Leave</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center">
                <div class="card-body py-3">
                    <div class="fw-bold fs-5 {{ $payrollSlip->unpaid_leave_days > 0 ? 'text-danger' : '' }}">{{ number_format($payrollSlip->unpaid_leave_days, 1) }}</div>
                    <div class="text-muted small">Unpaid Leave</div>
                </div>
            </div>
        </div>
    </div>

    @php
        $earningLines      = $payrollSlip->lines->where('line_category', 'earning');
        $deductionLines    = $payrollSlip->lines->where('line_category', 'deduction');
        $employerLines     = $payrollSlip->lines->where('line_category', 'employer_contribution');
    @endphp

    <div class="row g-4 mb-4">
        {{-- Earnings --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">Earnings</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Component</th>
                                <th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($earningLines as $line)
                            <tr>
                                <td class="ps-3">
                                    <div>{{ $line->line_name }}</div>
                                    @if($line->calculation_basis)
                                    <div class="text-muted small">{{ $line->calculation_basis }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-3">{{ number_format($line->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3 ps-3">No earning components</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Gross Earnings</td>
                                <td class="text-end pe-3">{{ number_format($payrollSlip->gross_earnings, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Deductions --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h6 class="card-title mb-0">Deductions</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Component</th>
                                <th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($deductionLines as $line)
                            <tr>
                                <td class="ps-3">
                                    <div>{{ $line->line_name }}</div>
                                    @if($line->calculation_basis)
                                    <div class="text-muted small">{{ $line->calculation_basis }}</div>
                                    @endif
                                </td>
                                <td class="text-end pe-3 text-danger">-{{ number_format($line->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3 ps-3">No deductions</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td class="ps-3">Total Deductions</td>
                                <td class="text-end pe-3 text-danger">-{{ number_format($payrollSlip->total_deductions, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Net Pay --}}
    <div class="card mb-4 border-success">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">Net Pay</div>
                <div class="text-muted small">{{ $payrollSlip->currency_code }}</div>
            </div>
            <div class="fs-3 fw-bold text-success">{{ number_format($payrollSlip->net_pay, 2) }}</div>
        </div>
    </div>

    {{-- Employer contributions (informational) --}}
    @if($employerLines->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header border-0 pb-0">
            <h6 class="card-title mb-0">Employer Contributions <small class="text-muted fw-normal">(for reference — not deducted from your pay)</small></h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Component</th>
                        <th class="text-end pe-3">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employerLines as $line)
                    <tr>
                        <td class="ps-3">{{ $line->line_name }}</td>
                        <td class="text-end pe-3">{{ number_format($line->amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="ps-3">Total</td>
                        <td class="text-end pe-3">{{ number_format($payrollSlip->employer_contributions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

</div>
</x-app-layout>
