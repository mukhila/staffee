<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Performance Reviews</h1>
            <span>View and respond to your performance reviews</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = ['pending'=>'secondary','self_submitted'=>'info','manager_reviewing'=>'warning','hr_calibrated'=>'primary','completed'=>'success','cancelled'=>'danger'];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Cycle</th>
                            <th>Reviewer</th>
                            <th>Status</th>
                            <th>Self Rating</th>
                            <th>Overall Rating</th>
                            <th>Acknowledged</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $review)
                        @php $rc = $statusColors[$review->status] ?? 'secondary'; @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $review->cycle?->name }}</div>
                                <div class="text-muted small">{{ $review->cycle ? ucwords(str_replace('_', ' ', $review->cycle->cycle_type)) : '' }}</div>
                            </td>
                            <td class="text-muted">{{ $review->reviewer?->name }}</td>
                            <td><span class="badge bg-{{ $rc }}-subtle text-{{ $rc }}">{{ ucwords(str_replace('_', ' ', $review->status)) }}</span></td>
                            <td>{{ $review->self_rating ? number_format($review->self_rating, 2) . ' / 5' : '—' }}</td>
                            <td>{{ $review->overall_rating ? number_format($review->overall_rating, 2) . ' / 5' : '—' }}</td>
                            <td>
                                @if($review->acknowledged_by_employee)
                                <span class="text-success small"><i class="fi fi-rr-check me-1"></i>Yes</span>
                                @else
                                <span class="text-muted small">No</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('staff.performance.show', $review) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-star fs-3 d-block mb-2 opacity-25"></i>
                                No performance reviews yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reviews->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $reviews->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
