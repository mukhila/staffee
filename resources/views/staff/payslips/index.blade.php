<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Payslips</h1>
            <span>View and download your published payslips</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Gross Earnings</th>
                            <th>Deductions</th>
                            <th>Tax</th>
                            <th>Net Pay</th>
                            <th>Published</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $slip)
                        <tr>
                            <td>
                                <div class="fw-medium">
                                    {{ \Carbon\Carbon::parse($slip->period_start)->format('d M') }} –
                                    {{ \Carbon\Carbon::parse($slip->period_end)->format('d M Y') }}
                                </div>
                                @if($slip->payrollRun)
                                <div class="text-muted small">{{ $slip->payrollRun->name ?? '' }}</div>
                                @endif
                            </td>
                            <td class="fw-medium">{{ number_format($slip->gross_earnings, 2) }}</td>
                            <td class="text-danger">-{{ number_format($slip->total_deductions, 2) }}</td>
                            <td class="text-warning">-{{ number_format($slip->tax_amount, 2) }}</td>
                            <td class="fw-bold text-success">{{ number_format($slip->net_pay, 2) }}</td>
                            <td class="text-muted small">
                                {{ $slip->published_at ? \Carbon\Carbon::parse($slip->published_at)->format('d M Y') : '—' }}
                            </td>
                            <td class="text-end">
                                <span class="badge bg-success-subtle text-success">Published</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-money fs-3 d-block mb-2 opacity-25"></i>
                                No payslips available yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payslips->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $payslips->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
