<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Transfer Requests</h1>
            <span>Manage interdepartmental employee transfers</span>
        </div>
        <a href="{{ route('admin.hr.transfers.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-arrows-alt-h me-1"></i> New Transfer
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
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach(['pending','approved','rejected'] as $s)
                        <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">To Department</label>
                    <select name="department" class="form-select form-select-sm">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('admin.hr.transfers.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
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
                            <th>From</th>
                            <th>To</th>
                            <th>Effective Date</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $t)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($t->employee->avatar)
                                    <img src="{{ asset('storage/' . $t->employee->avatar) }}" class="rounded-circle" width="34" height="34" style="object-fit:cover;">
                                    @else
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:.75rem;">
                                        {{ strtoupper(substr($t->employee->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $t->employee->name }}</div>
                                        <div class="text-muted small">{{ $t->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $t->fromDepartment?->name ?? '—' }}</div>
                                @if($t->from_designation)
                                <div class="text-muted small">{{ $t->from_designation }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium text-primary">{{ $t->toDepartment?->name ?? '—' }}</div>
                                @if($t->to_designation)
                                <div class="text-muted small">{{ $t->to_designation }}</div>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($t->effective_date)->format('d M Y') }}</td>
                            <td>{{ $t->requestedBy?->name ?? '—' }}</td>
                            <td>
                                @php
                                $colors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
                                $c = $colors[$t->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucfirst($t->status) }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.transfers.show', $t) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ $t->status === 'pending' ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-arrows-alt-h fs-3 d-block mb-2 opacity-25"></i>
                                No transfer requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transfers->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
