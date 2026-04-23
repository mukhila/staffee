<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Promotions</h1>
            <span>Track and approve employee promotion proposals</span>
        </div>
        <a href="{{ route('admin.hr.promotions.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-arrow-up me-1"></i> New Promotion
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = [
        'pending_manager'   => 'warning',
        'manager_approved'  => 'info',
        'manager_rejected'  => 'danger',
        'hr_approved'       => 'primary',
        'hr_rejected'       => 'danger',
        'finance_approved'  => 'success',
        'finance_rejected'  => 'danger',
        'applied'           => 'success',
        'withdrawn'         => 'secondary',
    ];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Salary Change</th>
                            <th>Effective Date</th>
                            <th>Status</th>
                            <th>Proposed By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $p)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($p->employee->avatar)
                                    <img src="{{ asset('storage/' . $p->employee->avatar) }}" class="rounded-circle" width="34" height="34">
                                    @else
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:.75rem;">
                                        {{ strtoupper(substr($p->employee->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $p->employee->name }}</div>
                                        <div class="text-muted small">{{ $p->employee->employee_id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $p->current_designation ?? ucfirst($p->current_role) }}</div>
                                <div class="text-muted small">{{ $p->currentDepartment?->name }}</div>
                            </td>
                            <td>
                                <div class="fw-medium text-success">{{ $p->proposed_designation ?? ucfirst($p->proposed_role) }}</div>
                                <div class="text-muted small">{{ $p->proposedDepartment?->name }}</div>
                            </td>
                            <td>
                                @if($p->proposed_salary && $p->current_salary)
                                @php $pct = $p->salary_increase_percent; @endphp
                                <span class="text-{{ $pct >= 0 ? 'success' : 'danger' }} fw-medium">
                                    {{ $pct >= 0 ? '+' : '' }}{{ number_format($pct, 1) }}%
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $p->effective_date->format('d M Y') }}</td>
                            <td>
                                @php $c = $statusColors[$p->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                    {{ ucwords(str_replace('_', ' ', $p->status)) }}
                                </span>
                            </td>
                            <td>{{ $p->proposedBy?->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.promotions.show', $p) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ $p->isPending() ? 'Review' : 'View' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-arrow-up fs-3 d-block mb-2 opacity-25"></i>
                                No promotion proposals found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($promotions->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $promotions->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
