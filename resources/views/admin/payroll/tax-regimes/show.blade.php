<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $taxRegime->name }}</h1>
            <span>{{ strtoupper($taxRegime->country_code) }} · {{ $taxRegime->fiscal_year }} · {{ $taxRegime->regime_code }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-{{ $taxRegime->status === 'active' ? 'success' : 'secondary' }} fs-6">{{ ucfirst($taxRegime->status) }}</span>
            <a href="{{ route('admin.payroll.tax-regimes.edit', $taxRegime) }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-edit me-1"></i> Edit
            </a>
            <a href="{{ route('admin.payroll.tax-regimes.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Regime Parameters</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal ps-0 small">Country</th><td><span class="badge bg-secondary">{{ strtoupper($taxRegime->country_code) }}</span></td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Fiscal Year</th><td>{{ $taxRegime->fiscal_year }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Effective From</th><td>{{ $taxRegime->effective_from?->format('d M Y') }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Effective To</th><td>{{ $taxRegime->effective_to?->format('d M Y') ?? 'Ongoing' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Standard Deduction</th><td>{{ number_format($taxRegime->standard_deduction ?? 0, 2) }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Rebate Amount</th><td>{{ number_format($taxRegime->rebate_amount ?? 0, 2) }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Cess %</th><td>{{ number_format($taxRegime->cess_percent ?? 0, 2) }}%</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Tax Brackets ({{ $taxRegime->brackets->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Slab</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Fixed Tax</th>
                                    <th class="text-center">Rebate Eligible</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($taxRegime->brackets as $bracket)
                                <tr>
                                    <td class="fw-medium">
                                        {{ number_format($bracket->income_from, 0) }}
                                        → {{ $bracket->income_to ? number_format($bracket->income_to, 0) : '∞' }}
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-primary-subtle text-primary">{{ number_format($bracket->rate_percent, 2) }}%</span>
                                    </td>
                                    <td class="text-end text-muted">
                                        {{ $bracket->fixed_tax_amount > 0 ? number_format($bracket->fixed_tax_amount, 2) : '—' }}
                                    </td>
                                    <td class="text-center">
                                        @if($bracket->rebate_eligible)
                                        <i class="fi fi-rr-check-circle text-success"></i>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No brackets defined.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
