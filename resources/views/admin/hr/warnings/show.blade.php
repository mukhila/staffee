<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Warning Record</h1>
            <span>{{ $warning->employee->name }} — {{ ucwords(str_replace('_', ' ', $warning->warning_type)) }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @if($warning->isResolved())
            <span class="badge bg-success fs-6">Resolved</span>
            @elseif($warning->isOverdue())
            <span class="badge bg-danger fs-6">Overdue</span>
            @else
            <span class="badge bg-warning fs-6">Active</span>
            @endif
            <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        {{-- Left: meta + actions --}}
        <div class="col-lg-4">

            {{-- Employee --}}
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    @if($warning->employee->avatar)
                    <img src="{{ asset('storage/' . $warning->employee->avatar) }}" class="rounded-circle mb-3" width="72" height="72" style="object-fit:cover;">
                    @else
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold mx-auto mb-3" style="width:72px;height:72px;font-size:1.25rem;">
                        {{ strtoupper(substr($warning->employee->name, 0, 2)) }}
                    </div>
                    @endif
                    <h6 class="mb-1 fw-bold">{{ $warning->employee->name }}</h6>
                    <div class="text-muted small">{{ $warning->employee->department?->name }}</div>
                    <div class="text-muted small">{{ $warning->employee->employee_id }}</div>
                </div>
            </div>

            {{-- Warning details --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Type</th>
                            <td><span class="badge bg-warning-subtle text-warning">{{ ucwords(str_replace('_',' ',$warning->warning_type)) }}</span></td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Incident Date</th>
                            <td>{{ \Carbon\Carbon::parse($warning->incident_date)->format('d M Y') }}</td>
                        </tr>
                        @if($warning->response_deadline)
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Response By</th>
                            <td class="{{ $warning->isOverdue() ? 'text-danger fw-medium' : '' }}">
                                {{ \Carbon\Carbon::parse($warning->response_deadline)->format('d M Y') }}
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Issued By</th>
                            <td>{{ $warning->issuedBy?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Issued On</th>
                            <td>{{ $warning->created_at->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Acknowledged</th>
                            <td>
                                @if($warning->is_acknowledged)
                                <i class="fi fi-rr-check-circle text-success me-1"></i>
                                {{ $warning->acknowledged_at ? \Carbon\Carbon::parse($warning->acknowledged_at)->format('d M Y') : 'Yes' }}
                                @else
                                <span class="text-warning">Pending</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            {{-- Actions --}}
            @if(!$warning->isResolved())
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Actions</h6></div>
                <div class="card-body d-flex flex-column gap-2">
                    @if(!$warning->is_acknowledged)
                    <form action="{{ route('admin.hr.warnings.acknowledge', $warning) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary w-100 btn-sm"
                                onclick="return confirm('Mark this warning as acknowledged by the employee?')">
                            <i class="fi fi-rr-check me-1"></i> Mark Acknowledged
                        </button>
                    </form>
                    @endif

                    <button type="button" class="btn btn-success w-100 btn-sm" data-bs-toggle="collapse" data-bs-target="#resolveForm">
                        <i class="fi fi-rr-check-circle me-1"></i> Resolve Warning
                    </button>
                    <div class="collapse" id="resolveForm">
                        <form action="{{ route('admin.hr.warnings.resolve', $warning) }}" method="POST" class="mt-2">
                            @csrf
                            <textarea name="resolution_notes" class="form-control form-control-sm mb-2" rows="3"
                                      placeholder="Resolution notes (optional)..."></textarea>
                            <button type="submit" class="btn btn-success btn-sm w-100">Confirm Resolve</button>
                        </form>
                    </div>
                </div>
            </div>
            @else
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Resolution</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Resolved By</th>
                            <td>{{ $warning->resolvedBy?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0 small">Resolved On</th>
                            <td>{{ $warning->resolved_at?->format('d M Y') }}</td>
                        </tr>
                    </table>
                    @if($warning->resolution_notes)
                    <p class="small text-muted fst-italic mt-2 mb-0">"{{ $warning->resolution_notes }}"</p>
                    @endif
                </div>
            </div>
            @endif

            <form action="{{ route('admin.hr.warnings.destroy', $warning) }}" method="POST">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                        onclick="return confirm('Permanently delete this warning record?')">
                    <i class="fi fi-rr-trash me-1"></i> Delete Record
                </button>
            </form>
        </div>

        {{-- Right: description + improvement plan --}}
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Incident Description</h6></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap;">{{ $warning->description }}</p>
                </div>
            </div>

            @if($warning->improvement_plan)
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Improvement Plan / Expected Actions</h6></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap;">{{ $warning->improvement_plan }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
