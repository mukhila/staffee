<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Payroll Report</h1>
            <span>Salary disbursement summary</span>
        </div>
        <a href="{{ route('admin.reports.payroll.export', ['month' => $month]) }}" class="btn btn-success btn-sm">
            <i class="fi fi-rr-file-csv me-1"></i> Export CSV
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Month</label>
                    <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
            </form>
        </div>
    </div>

    @if($total)
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card text-center py-3">
                <div class="fs-5 fw-bold">{{ number_format($total->gross, 2) }}</div>
                <div class="text-muted small">Gross Pay</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <div class="fs-5 fw-bold text-danger">{{ number_format($total->deductions, 2) }}</div>
                <div class="text-muted small">Total Deductions</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center py-3">
                <div class="fs-5 fw-bold text-success">{{ number_format($total->net, 2) }}</div>
                <div class="text-muted small">Net Pay</div>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th class="text-end">Gross Pay</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Net Pay</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slips as $slip)
                        <tr>
                            <td class="fw-medium">{{ $slip->employee?->name }}</td>
                            <td class="text-muted small">{{ $slip->employee?->department?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($slip->gross_pay, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($slip->total_deductions, 2) }}</td>
                            <td class="text-end text-muted">{{ number_format($slip->total_tax ?? 0, 2) }}</td>
                            <td class="text-end fw-bold text-success">{{ number_format($slip->net_pay, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No payroll data for this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($slips->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $slips->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
