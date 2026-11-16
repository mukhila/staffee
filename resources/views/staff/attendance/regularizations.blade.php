<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Attendance Regularisation</h1>
            <span>Request a correction for a missed or wrong punch (last {{ $maxAgeDays }} days)</span>
        </div>
        <a href="{{ route('staff.attendance.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> My Attendance
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
                <div class="card-header"><h6 class="mb-0">New request</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('staff.attendance.regularizations.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="date">Date</label>
                            <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror"
                                   value="{{ old('date') }}" max="{{ now()->toDateString() }}"
                                   min="{{ now()->subDays($maxAgeDays)->toDateString() }}" required>
                            @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="requested_check_in">Check in</label>
                                <input type="time" id="requested_check_in" name="requested_check_in"
                                       class="form-control @error('requested_check_in') is-invalid @enderror" value="{{ old('requested_check_in') }}">
                                @error('requested_check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="requested_check_out">Check out</label>
                                <input type="time" id="requested_check_out" name="requested_check_out"
                                       class="form-control @error('requested_check_out') is-invalid @enderror" value="{{ old('requested_check_out') }}">
                                @error('requested_check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12"><small class="text-muted">Leave a time blank to keep what is already recorded.</small></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">Reason</label>
                            <textarea id="reason" name="reason" rows="3" maxlength="500"
                                      class="form-control @error('reason') is-invalid @enderror" required>{{ old('reason') }}</textarea>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit request</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">My requests</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Reviewed by</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $r)
                                @php $color = \App\Models\AttendanceRegularization::STATUS_COLORS[$r->status] ?? 'secondary'; @endphp
                                <tr>
                                    <td class="fw-medium">{{ $r->date->format('d M Y') }}</td>
                                    <td>{{ $r->requested_check_in ? \Carbon\Carbon::parse($r->requested_check_in)->format('H:i') : '—' }}</td>
                                    <td>{{ $r->requested_check_out ? \Carbon\Carbon::parse($r->requested_check_out)->format('H:i') : '—' }}</td>
                                    <td class="small">{{ $r->reason }}</td>
                                    <td><span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst($r->status) }}</span></td>
                                    <td class="small">
                                        {{ $r->reviewer?->name ?? '—' }}
                                        @if($r->review_notes)<div class="text-muted">{{ $r->review_notes }}</div>@endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-5">No regularisation requests yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($requests->hasPages())
                <div class="card-footer">{{ $requests->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
