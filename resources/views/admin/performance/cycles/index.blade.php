<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Performance Cycles</h1>
            <span>Manage review cycles for performance evaluations</span>
        </div>
        <a href="{{ route('admin.performance.cycles.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Cycle
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = [
        'draft'    => 'secondary',
        'active'   => 'success',
        'closed'   => 'danger',
        'archived' => 'dark',
    ];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Fiscal Year</th>
                            <th>Review Period</th>
                            <th>Submission Deadline</th>
                            <th>Status</th>
                            <th>Reviews</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $cycle)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $cycle->name }}</div>
                                <div class="text-muted small">Created by {{ $cycle->createdBy?->name ?? '—' }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $cycle->cycle_type)) }}</span></td>
                            <td>{{ $cycle->fiscal_year ?? '—' }}</td>
                            <td class="small">
                                {{ $cycle->review_period_start->format('d M Y') }}
                                → {{ $cycle->review_period_end->format('d M Y') }}
                            </td>
                            <td class="small">{{ $cycle->submission_deadline->format('d M Y') }}</td>
                            <td>
                                @php $c = $statusColors[$cycle->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                    {{ ucfirst($cycle->status) }}
                                </span>
                            </td>
                            <td>{{ $cycle->reviews_count ?? $cycle->reviews()->count() }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.performance.cycles.show', $cycle) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-star fs-3 d-block mb-2 opacity-25"></i>
                                No performance cycles found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($cycles->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $cycles->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
