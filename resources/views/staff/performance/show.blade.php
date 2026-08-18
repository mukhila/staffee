<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $review->cycle?->name }}</h1>
            <span>Your performance review</span>
        </div>
        <a href="{{ route('staff.performance.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        {{-- Status card --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header fw-medium">Review Status</div>
                <div class="card-body">
                    @php $rColors = ['pending'=>'secondary','self_submitted'=>'info','manager_reviewing'=>'warning','hr_calibrated'=>'primary','completed'=>'success','cancelled'=>'danger']; @endphp
                    <dl class="row small mb-0">
                        <dt class="col-5 text-muted">Status</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $rColors[$review->status] ?? 'secondary' }}-subtle text-{{ $rColors[$review->status] ?? 'secondary' }}">
                                {{ ucwords(str_replace('_', ' ', $review->status)) }}
                            </span>
                        </dd>
                        <dt class="col-5 text-muted">Reviewer</dt>
                        <dd class="col-7">{{ $review->reviewer?->name }}</dd>
                        <dt class="col-5 text-muted">Self Rating</dt>
                        <dd class="col-7">{{ $review->self_rating ? number_format($review->self_rating, 2).' / 5' : '—' }}</dd>
                        <dt class="col-5 text-muted">Overall Rating</dt>
                        <dd class="col-7">{{ $review->overall_rating ? number_format($review->overall_rating, 2).' / 5' : '—' }}</dd>
                        <dt class="col-5 text-muted">Acknowledged</dt>
                        <dd class="col-7">{{ $review->acknowledged_by_employee ? 'Yes' : 'No' }}</dd>
                    </dl>
                </div>
            </div>

            @if($review->status === 'completed' && !$review->acknowledged_by_employee)
            <div class="card mt-3">
                <div class="card-body">
                    <p class="text-muted small">Your review is complete. Please acknowledge it.</p>
                    <form action="{{ route('staff.performance.acknowledge', $review) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">Acknowledge Review</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Main content --}}
        <div class="col-md-8">
            {{-- Self-assessment form --}}
            @if($review->status === 'pending')
            <div class="card">
                <div class="card-header fw-medium">Submit Self-Assessment</div>
                <div class="card-body">
                    <form action="{{ route('staff.performance.self-assessment', $review) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Self Rating (0–5) <span class="text-danger">*</span></label>
                                <input type="number" name="self_rating" step="0.25" min="0" max="5"
                                    class="form-control @error('self_rating') is-invalid @enderror"
                                    value="{{ old('self_rating') }}" required>
                                @error('self_rating')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Comments</label>
                                <textarea name="self_comments" rows="4" class="form-control"
                                    placeholder="Describe your achievements, challenges, and growth areas…">{{ old('self_comments') }}</textarea>
                            </div>

                            @if($review->goals->count())
                            <div class="col-12">
                                <h6 class="mb-3">Goal Self-Ratings</h6>
                                @foreach($review->goals as $i => $goal)
                                <div class="border rounded p-3 mb-2">
                                    <input type="hidden" name="goals[{{ $i }}][id]" value="{{ $goal->id }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium">{{ $goal->title }}</div>
                                            @if($goal->description)<div class="text-muted small">{{ $goal->description }}</div>@endif
                                        </div>
                                        <div style="width:100px">
                                            <input type="number" name="goals[{{ $i }}][self_rating]" step="0.25" min="0" max="5"
                                                class="form-control form-control-sm" placeholder="0–5">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Submit Self-Assessment</button>
                    </form>
                </div>
            </div>
            @else
            {{-- Read-only view of completed/in-progress review --}}
            @if($review->overall_comments)
            <div class="card mb-3">
                <div class="card-header fw-medium">Reviewer's Comments</div>
                <div class="card-body text-muted">{{ $review->overall_comments }}</div>
            </div>
            @endif

            @if($review->goals->count())
            <div class="card">
                <div class="card-header fw-medium">Goals</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Goal</th><th>Self</th><th>Reviewer</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($review->goals as $goal)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $goal->title }}</div>
                                    @if($goal->achievement_notes)<div class="text-muted small">{{ $goal->achievement_notes }}</div>@endif
                                </td>
                                <td>{{ $goal->self_rating ?? '—' }}</td>
                                <td>{{ $goal->reviewer_rating ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ ucwords(str_replace('_', ' ', $goal->status)) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
</div>
</x-app-layout>
