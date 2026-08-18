<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Tax Declarations</h1>
            <span>Review and verify employee tax declarations</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if($errors->has('general'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ $errors->first('general') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Employee</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Employees</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Fiscal Year</label>
                    <select name="fiscal_year" class="form-select form-select-sm">
                        <option value="">All Years</option>
                        @foreach($fiscalYears as $fy)
                        <option value="{{ $fy }}" {{ request('fiscal_year') === $fy ? 'selected' : '' }}>{{ $fy }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('admin.payroll.tax-declarations.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
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
                            <td>
                                <div class="fw-medium">{{ $decl->user?->name }}</div>
                                <div class="text-muted small">{{ $decl->user?->email }}</div>
                            </td>
                            <td class="fw-medium">{{ $decl->fiscal_year }}</td>
                            <td class="small">{{ $decl->taxRegime?->name ?? '—' }}</td>
                            <td class="fw-medium">{{ number_format((float) $decl->totalDeclared(), 2) }}</td>
                            <td><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($decl->declaration_status) }}</span></td>
                            <td class="text-muted small">{{ $decl->submitted_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.payroll.tax-declarations.show', $decl) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @if($decl->isSubmitted())
                                <form method="POST" action="{{ route('admin.payroll.tax-declarations.verify', $decl) }}" class="d-inline ms-1">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('Mark this declaration as verified?')">Verify</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-file-invoice fs-3 d-block mb-2 opacity-25"></i>
                                No tax declarations found.
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
