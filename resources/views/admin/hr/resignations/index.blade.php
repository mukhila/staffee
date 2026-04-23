<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Resignations</h1>
            <span>Track employee resignation requests and notice periods</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = [
        'manager_reviewing' => 'warning',
        'manager_accepted'  => 'info',
        'manager_rejected'  => 'danger',
        'hr_approved'       => 'success',
        'notice_period'     => 'primary',
        'completed'         => 'success',
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
                            <th>Submitted</th>
                            <th>Type</th>
                            <th>Requested Last Date</th>
                            <th>Notice Period</th>
                            <th>Status</th>
                            <th>Days Left</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resignations as $r)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($r->employee->avatar)
                                    <img src="{{ asset('storage/' . $r->employee->avatar) }}" class="rounded-circle" width="34" height="34">
                                    @else
                                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:.75rem;">
                                        {{ strtoupper(substr($r->employee->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $r->employee->name }}</div>
                                        <div class="text-muted small">{{ $r->employee->department?->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $r->submitted_date->format('d M Y') }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $r->resignation_type)) }}</td>
                            <td>{{ $r->requested_last_date->format('d M Y') }}</td>
                            <td>
                                {{ $r->notice_period_days }}d
                                @if($r->notice_waived)
                                <span class="badge bg-secondary-subtle text-secondary ms-1">Waived</span>
                                @endif
                            </td>
                            <td>
                                @php $c = $statusColors[$r->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                    {{ ucwords(str_replace('_', ' ', $r->status)) }}
                                </span>
                            </td>
                            <td>
                                @php $days = $r->daysRemainingInNotice(); @endphp
                                @if($days !== null)
                                <span class="badge bg-{{ $days <= 7 ? 'danger' : 'info' }}-subtle text-{{ $days <= 7 ? 'danger' : 'info' }}">
                                    {{ $days }}d
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.resignations.show', $r) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-file-invoice fs-3 d-block mb-2 opacity-25"></i>
                                No resignation records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($resignations->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $resignations->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
