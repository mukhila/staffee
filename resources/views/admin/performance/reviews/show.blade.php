<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Performance Review</h1>
            <span>{{ $review->cycle?->name }} · {{ $review->reviewee?->name }}</span>
        </div>
        <a href="{{ route('admin.performance.cycles.show', $review->cycle) }}" class="btn btn-secondary btn-sm">Back to Cycle</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        {{-- Review meta --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header fw-medium">Review Details</div>
                <div class="card-body">
                    @php $rColors = ['pending'=>'secondary','self_submitted'=>'info','manager_reviewing'=>'warning','hr_calibrated'=>'primary','completed'=>'success','cancelled'=>'danger']; @endphp
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $rColors[$review->status] ?? 'secondary' }}-subtle text-{{ $rColors[$review->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $review->status)) }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted">Reviewee</dt>
                        <dd class="col-7">{{ $review->reviewee?->name }}</dd>
                        <dt class="col-5 text-muted">Reviewer</dt>
                        <dd class="col-7">{{ $review->reviewer?->name }}</dd>
                        <dt class="col-5 text-muted">Self Rating</dt>
                        <dd class="col-7">{{ $review->self_rating ?? '—' }} / 5</dd>
                        <dt class="col-5 text-muted">Overall Rating</dt>
                        <dd class="col-7">{{ $review->overall_rating ?? '—' }} / 5</dd>
                        <dt class="col-5 text-muted">Completed</dt>
                        <dd class="col-7">{{ $review->completed_at?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-5 text-muted">Acknowledged</dt>
                        <dd class="col-7">{{ $review->acknowledged_by_employee ? 'Yes ('.$review->acknowledged_at?->format('d M Y').')' : 'No' }}</dd>
                    </dl>
                </div>
            </div>

            @if($review->self_comments)
            <div class="card mt-3">
                <div class="card-header fw-medium">Employee Comments</div>
                <div class="card-body small text-muted">{{ $review->self_comments }}</div>
            </div>
            @endif
        </div>

        {{-- Manager submit form / completed view --}}
        <div class="col-md-8">
            @if(in_array($review->status, ['self_submitted', 'manager_reviewing']))
            <div class="card">
                <div class="card-header fw-medium">Submit Manager Review</div>
                <div class="card-body">
                    <form action="{{ route('admin.performance.reviews.submit', $review) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Overall Rating (0–5) <span class="text-danger">*</span></label>
                                <input type="number" name="overall_rating" step="0.25" min="0" max="5"
                                    class="form-control @error('overall_rating') is-invalid @enderror"
                                    value="{{ old('overall_rating', $review->overall_rating) }}" required>
                                @error('overall_rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Overall Comments</label>
                                <textarea name="overall_comments" rows="3" class="form-control">{{ old('overall_comments', $review->overall_comments) }}</textarea>
                            </div>
                        </div>

                        @if($review->goals->count())
                        <h6 class="mt-4 mb-3">Goals</h6>
                        @foreach($review->goals as $i => $goal)
                        <div class="border rounded p-3 mb-3">
                            <input type="hidden" name="goals[{{ $i }}][id]" value="{{ $goal->id }}">
                            <div class="fw-medium mb-1">{{ $goal->title }}</div>
                            @if($goal->description)<div class="text-muted small mb-2">{{ $goal->description }}</div>@endif
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small">Self Rating</label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $goal->self_rating ?? '—' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Reviewer Rating</label>
                                    <input type="number" name="goals[{{ $i }}][reviewer_rating]" step="0.25" min="0" max="5"
                                        class="form-control form-control-sm" value="{{ old("goals.$i.reviewer_rating", $goal->reviewer_rating) }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Status</label>
                                    <select name="goals[{{ $i }}][status]" class="form-select form-select-sm">
                                        @foreach(['not_started','in_progress','achieved','partially_achieved','not_achieved'] as $s)
                                        <option value="{{ $s }}" @selected(old("goals.$i.status", $goal->status) === $s)>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Achievement Notes</label>
                                    <input type="text" name="goals[{{ $i }}][achievement_notes]" class="form-control form-control-sm"
                                        value="{{ old("goals.$i.achievement_notes", $goal->achievement_notes) }}">
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @endif

                        <button type="submit" class="btn btn-primary mt-2">Submit Review</button>
                    </form>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-header fw-medium">Review Summary</div>
                <div class="card-body">
                    @if($review->overall_comments)
                    <p class="text-muted">{{ $review->overall_comments }}</p>
                    @endif
                    @if($review->goals->count())
                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr><th>Goal</th><th>Self</th><th>Reviewer</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($review->goals as $goal)
                            <tr>
                                <td>{{ $goal->title }}</td>
                                <td>{{ $goal->self_rating ?? '—' }}</td>
                                <td>{{ $goal->reviewer_rating ?? '—' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $goal->status)) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @else
                    <p class="text-muted">No goals recorded.</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
