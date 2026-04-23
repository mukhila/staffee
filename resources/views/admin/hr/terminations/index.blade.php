<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Terminations</h1>
            <span>Manage employee offboarding and termination workflows</span>
        </div>
        <a href="{{ route('admin.hr.terminations.create') }}" class="btn btn-danger btn-sm">
            <i class="fi fi-rr-user-minus me-1"></i> Initiate Termination
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = [
        'pending_approval'    => 'warning',
        'approved'            => 'info',
        'processing'          => 'primary',
        'settlement_pending'  => 'secondary',
        'settlement_approved' => 'success',
        'completed'           => 'success',
        'cancelled'           => 'danger',
    ];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Last Working Date</th>
                            <th>Initiated By</th>
                            <th>Status</th>
                            <th>Settlement</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($terminations as $t)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($t->employee->avatar)
                                    <img src="{{ asset('storage/' . $t->employee->avatar) }}" class="rounded-circle" width="34" height="34">
                                    @else
                                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:.75rem;">
                                        {{ strtoupper(substr($t->employee->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $t->employee->name }}</div>
                                        <div class="text-muted small">{{ $t->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ ucwords(str_replace('_', ' ', $t->termination_type)) }}</td>
                            <td>{{ $t->last_working_date->format('d M Y') }}</td>
                            <td>{{ $t->initiatedBy->name }}</td>
                            <td>
                                @php $c = $statusColors[$t->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                    {{ ucwords(str_replace('_', ' ', $t->status)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $t->settlement_status === 'paid' ? 'success' : 'warning' }}-subtle text-{{ $t->settlement_status === 'paid' ? 'success' : 'warning' }}">
                                    {{ ucwords(str_replace('_', ' ', $t->settlement_status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.terminations.show', $t) }}" class="btn btn-sm btn-outline-secondary">
                                    View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-user-minus fs-3 d-block mb-2 opacity-25"></i>
                                No termination records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($terminations->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $terminations->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
