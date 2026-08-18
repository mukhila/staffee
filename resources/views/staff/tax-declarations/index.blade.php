<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Tax Declarations</h1>
            <span>Your income tax declarations for each fiscal year</span>
        </div>
        <a href="{{ route('staff.tax-declarations.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New / Edit Declaration
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ $errors->first('general') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fiscal Year</th>
                            <th>Tax Regime</th>
                            <th>Total Declared</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($declarations as $decl)
                        @php
                        $statusColors = [
                            'draft'     => 'secondary',
                            'submitted' => 'info',
                            'verified'  => 'success',
                            'locked'    => 'dark',
                        ];
                        $sc = $statusColors[$decl->declaration_status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $decl->fiscal_year }}</td>
                            <td class="small">{{ $decl->taxRegime?->name ?? '—' }}</td>
                            <td class="fw-medium">{{ number_format((float) $decl->totalDeclared(), 2) }}</td>
                            <td><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($decl->declaration_status) }}</span></td>
                            <td class="text-muted small">{{ $decl->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td class="text-end">
                                @if($decl->isDraft())
                                <a href="{{ route('staff.tax-declarations.create') }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                <form method="POST" action="{{ route('staff.tax-declarations.submit', $decl) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                        onclick="return confirm('Submit this declaration for verification?')">Submit</button>
                                </form>
                                @else
                                <span class="text-muted small">{{ ucfirst($decl->declaration_status) }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fi fi-rr-file-invoice fs-3 d-block mb-2 opacity-25"></i>
                                No tax declarations found. <a href="{{ route('staff.tax-declarations.create') }}">Create one</a>.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($declarations->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $declarations->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
