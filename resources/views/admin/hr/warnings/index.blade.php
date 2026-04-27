<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Warning Records</h1>
            <span>Disciplinary and performance improvement records</span>
        </div>
        <a href="{{ route('admin.hr.warnings.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-triangle-warning me-1"></i> New Warning
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
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        @foreach(['verbal','written','final_written','suspension','pip'] as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @php
    $typeColors = ['verbal'=>'warning','written'=>'info','final_written'=>'orange','suspension'=>'danger','pip'=>'primary'];
    $typeLabels = ['verbal'=>'Verbal','written'=>'Written','final_written'=>'Final Written','suspension'=>'Suspension','pip'=>'PIP'];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Incident Date</th>
                            <th>Issued By</th>
                            <th>Deadline</th>
                            <th>Acknowledged</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warnings as $w)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($w->employee->avatar)
                                    <img src="{{ asset('storage/' . $w->employee->avatar) }}" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                    @else
                                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:.7rem;">
                                        {{ strtoupper(substr($w->employee->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $w->employee->name }}</div>
                                        <div class="text-muted small">{{ $w->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php $tc = $typeColors[$w->warning_type] ?? 'secondary'; @endphp
                                <span class="badge" style="background:{{ $tc === 'orange' ? '#fd7e1420' : '' }};color:{{ $tc === 'orange' ? '#e55a00' : '' }}"
                                    @if($tc !== 'orange') class="badge bg-{{ $tc }}-subtle text-{{ $tc }}" @endif>
                                    {{ $typeLabels[$w->warning_type] ?? ucfirst($w->warning_type) }}
                                </span>
                            </td>
                            <td>{{ \Carbon\Carbon::parse($w->incident_date)->format('d M Y') }}</td>
                            <td>{{ $w->issuedBy?->name ?? '—' }}</td>
                            <td>
                                @if($w->response_deadline)
                                @php $overdue = $w->isOverdue(); @endphp
                                <span class="{{ $overdue ? 'text-danger fw-medium' : 'text-muted' }}">
                                    {{ \Carbon\Carbon::parse($w->response_deadline)->format('d M Y') }}
                                    @if($overdue) <i class="fi fi-rr-exclamation ms-1"></i> @endif
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($w->is_acknowledged)
                                <i class="fi fi-rr-check-circle text-success"></i>
                                @else
                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if($w->isResolved())
                                <span class="badge bg-success-subtle text-success">Resolved</span>
                                @elseif($w->isOverdue())
                                <span class="badge bg-danger-subtle text-danger">Overdue</span>
                                @else
                                <span class="badge bg-warning-subtle text-warning">Active</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.warnings.show', $w) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-triangle-warning fs-3 d-block mb-2 opacity-25"></i>
                                No warning records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($warnings->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $warnings->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
