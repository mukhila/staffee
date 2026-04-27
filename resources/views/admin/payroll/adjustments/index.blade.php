<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Payroll Adjustments</h1>
            <span>One-time and recurring salary additions or deductions</span>
        </div>
        <a href="{{ route('admin.payroll.adjustments.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Adjustment
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Employee</label>
                    <select name="employee" class="form-select form-select-sm">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee') == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['pending','approved','rejected','applied','cancelled'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('admin.payroll.adjustments.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
                            <th>Component</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Recurrence</th>
                            <th>Start Period</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjustments as $adj)
                        @php
                        $statusColors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','applied'=>'info','cancelled'=>'secondary'];
                        $sc = $statusColors[$adj->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $adj->employee?->name }}</div>
                                <div class="text-muted small">{{ $adj->employee?->employee_id }}</div>
                            </td>
                            <td class="small">{{ $adj->definition?->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $adj->adjustment_type === 'addition' ? 'success' : 'danger' }}-subtle text-{{ $adj->adjustment_type === 'addition' ? 'success' : 'danger' }}">
                                    {{ ucfirst($adj->adjustment_type) }}
                                </span>
                            </td>
                            <td class="fw-medium">{{ number_format($adj->amount, 2) }}</td>
                            <td class="text-muted small">{{ ucwords(str_replace('-',' ',$adj->recurrence)) }}</td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($adj->start_period)->format('M Y') }}</td>
                            <td><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($adj->status) }}</span></td>
                            <td class="text-muted small">{{ $adj->creator?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.payroll.adjustments.show', $adj) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ $adj->status === 'pending' ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fi fi-rr-sack-dollar fs-3 d-block mb-2 opacity-25"></i>
                                No payroll adjustments found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($adjustments->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $adjustments->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
