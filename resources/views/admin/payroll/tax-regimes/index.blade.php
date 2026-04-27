<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Tax Regimes</h1>
            <span>Manage tax slabs and brackets for payroll calculation</span>
        </div>
        <a href="{{ route('admin.payroll.tax-regimes.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Tax Regime
        </a>
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
                            <th>Regime</th>
                            <th>Country</th>
                            <th>Fiscal Year</th>
                            <th>Effective Period</th>
                            <th class="text-center">Brackets</th>
                            <th>Cess %</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($regimes as $regime)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $regime->name }}</div>
                                <div class="text-muted small">{{ $regime->regime_code }}</div>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($regime->country_code) }}</span></td>
                            <td>{{ $regime->fiscal_year }}</td>
                            <td class="text-muted small">
                                {{ $regime->effective_from?->format('d M Y') }} →
                                {{ $regime->effective_to?->format('d M Y') ?? 'Ongoing' }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary">{{ $regime->brackets_count }}</span>
                            </td>
                            <td>{{ $regime->cess_percent ? number_format($regime->cess_percent, 2).'%' : '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $regime->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $regime->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($regime->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.payroll.tax-regimes.show', $regime) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <a href="{{ route('admin.payroll.tax-regimes.edit', $regime) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.payroll.tax-regimes.destroy', $regime) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this tax regime and all its brackets?')">
                                            <i class="fi fi-rr-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-percent fs-3 d-block mb-2 opacity-25"></i>
                                No tax regimes configured.
                                <a href="{{ route('admin.payroll.tax-regimes.create') }}">Create one now.</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($regimes->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $regimes->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
