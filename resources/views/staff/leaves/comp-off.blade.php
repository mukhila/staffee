<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Comp-Off</h1>
            <span>Claim a day off for working on a weekend or public holiday</span>
        </div>
        <a href="{{ route('staff.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> My Leaves
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">New claim</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.comp-off.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="worked_date">Date worked</label>
                            <input type="date" id="worked_date" name="worked_date" max="{{ now()->toDateString() }}"
                                   class="form-control @error('worked_date') is-invalid @enderror" value="{{ old('worked_date') }}" required>
                            @error('worked_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Must be a weekend or holiday on which you checked in.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">What you worked on</label>
                            <textarea id="reason" name="reason" rows="3" maxlength="500"
                                      class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit claim</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">My claims</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date worked</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Reviewed by</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($credits as $c)
                                @php $color = \App\Models\Leave\CompOffCredit::STATUS_COLORS[$c->status] ?? 'secondary'; @endphp
                                <tr>
                                    <td class="fw-medium">{{ $c->worked_date->format('D, d M Y') }}</td>
                                    <td>{{ rtrim(rtrim((string) $c->days, '0'), '.') }}</td>
                                    <td class="small">{{ $c->reason }}</td>
                                    <td><span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst($c->status) }}</span></td>
                                    <td class="small">
                                        {{ $c->reviewer?->name ?? '—' }}
                                        @if($c->review_notes)<div class="text-muted">{{ $c->review_notes }}</div>@endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-5">No comp-off claims yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($credits->hasPages())
                <div class="card-footer">{{ $credits->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
