<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $cycle->name }}</h1>
            <span>{{ ucwords(str_replace('_', ' ', $cycle->cycle_type)) }}
                @if($cycle->fiscal_year) · {{ $cycle->fiscal_year }} @endif
            </span>
        </div>
        <div class="d-flex gap-2">
            @if($cycle->status === 'active')
            <form action="{{ route('admin.performance.cycles.close', $cycle) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('Close this cycle? Managers will no longer be able to submit reviews.')">
                    Close Cycle
                </button>
            </form>
            @endif
            <a href="{{ route('admin.performance.cycles.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Review Period</div>
                    <div class="fw-medium">{{ $cycle->review_period_start->format('d M Y') }}</div>
                    <div class="text-muted">to {{ $cycle->review_period_end->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Submission Deadline</div>
                    <div class="fw-medium">{{ $cycle->submission_deadline->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Status</div>
                    @php $sc = ['draft'=>'secondary','active'=>'success','closed'=>'danger','archived'=>'dark'][$cycle->status] ?? 'secondary'; @endphp
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fs-6">{{ ucfirst($cycle->status) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Reviews</div>
                    <div class="fw-medium fs-4">{{ $cycle->reviews->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Review Form --}}
    @if(in_array($cycle->status, ['draft', 'active']))
    <div class="card mb-4">
        <div class="card-header fw-medium">Add Review</div>
        <div class="card-body">
            <form action="{{ route('admin.performance.reviews.store', $cycle) }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Employee (Reviewee) <span class="text-danger">*</span></label>
                        <select name="reviewee_id" class="form-select @error('reviewee_id') is-invalid @enderror" required>
                            <option value="">Select employee…</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('reviewee_id') == $emp->id)>{{ $emp->name }}</option>
                            @endforeach
                        </select>
                        @error('reviewee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reviewer (Manager) <span class="text-danger">*</span></label>
                        <select name="reviewer_id" class="form-select @error('reviewer_id') is-invalid @enderror" required>
                            <option value="">Select reviewer…</option>
                            @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}" @selected(old('reviewer_id') == $mgr->id)>{{ $mgr->name }}</option>
                            @endforeach
                        </select>
                        @error('reviewer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Add Review</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Reviews list --}}
    <div class="card">
        <div class="card-header fw-medium">Reviews</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Reviewer</th>
                            <th>Status</th>
                            <th>Overall Rating</th>
                            <th>Self Rating</th>
                            <th>Acknowledged</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycle->reviews as $review)
                        @php
                        $rColors = ['pending'=>'secondary','self_submitted'=>'info','manager_reviewing'=>'warning','hr_calibrated'=>'primary','completed'=>'success','cancelled'=>'danger'];
                        $rc = $rColors[$review->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $review->reviewee?->name }}</td>
                            <td class="text-muted">{{ $review->reviewer?->name }}</td>
                            <td><span class="badge bg-{{ $rc }}-subtle text-{{ $rc }}">{{ ucwords(str_replace('_', ' ', $review->status)) }}</span></td>
                            <td>{{ $review->overall_rating ?? '—' }}</td>
                            <td>{{ $review->self_rating ?? '—' }}</td>
                            <td>{{ $review->acknowledged_by_employee ? '<span class="text-success">Yes</span>' : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.performance.reviews.show', $review) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No reviews yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
