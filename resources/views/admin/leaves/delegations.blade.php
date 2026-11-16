<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Approval Delegation</h1>
            <span>Hand your leave approvals to a colleague while you are away</span>
        </div>
        <a href="{{ route('admin.leaves.approvals') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Approvals
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
                <div class="card-header"><h6 class="mb-0">New delegation</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.leaves.delegations.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="delegate_id">Delegate to</label>
                            <select id="delegate_id" name="delegate_id" class="form-select @error('delegate_id') is-invalid @enderror" required>
                                <option value="">Select a colleague</option>
                                @foreach($candidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ old('delegate_id') == $candidate->id ? 'selected' : '' }}>{{ $candidate->name }}</option>
                                @endforeach
                            </select>
                            @error('delegate_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label" for="from_date">From</label>
                                <input type="date" id="from_date" name="from_date" min="{{ now()->toDateString() }}"
                                       class="form-control @error('from_date') is-invalid @enderror" value="{{ old('from_date') }}" required>
                                @error('from_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="to_date">To</label>
                                <input type="date" id="to_date" name="to_date" min="{{ now()->toDateString() }}"
                                       class="form-control @error('to_date') is-invalid @enderror" value="{{ old('to_date') }}" required>
                                @error('to_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="reason">Reason (optional)</label>
                            <input type="text" id="reason" name="reason" maxlength="255" class="form-control" value="{{ old('reason') }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Delegate</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">My delegations</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Delegate</th><th>From</th><th>To</th><th>Reason</th><th></th><th></th></tr>
                        </thead>
                        <tbody>
                            @forelse($mine as $d)
                            <tr>
                                <td class="fw-medium">{{ $d->delegate?->name }}</td>
                                <td>{{ $d->from_date->format('d M Y') }}</td>
                                <td>{{ $d->to_date->format('d M Y') }}</td>
                                <td class="small">{{ $d->reason ?? '—' }}</td>
                                <td>
                                    @if($d->isActive())
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                    @elseif($d->to_date->isPast())
                                    <span class="badge bg-secondary-subtle text-secondary">Ended</span>
                                    @else
                                    <span class="badge bg-info-subtle text-info">Upcoming</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.leaves.delegations.destroy', $d) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">You have not delegated your approvals.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">Delegated to me</h6></div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>From manager</th><th>From</th><th>To</th><th>Reason</th></tr>
                        </thead>
                        <tbody>
                            @forelse($toMe as $d)
                            <tr>
                                <td class="fw-medium">{{ $d->delegator?->name }}</td>
                                <td>{{ $d->from_date->format('d M Y') }}</td>
                                <td>{{ $d->to_date->format('d M Y') }}</td>
                                <td class="small">{{ $d->reason ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nobody is delegating approvals to you.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
