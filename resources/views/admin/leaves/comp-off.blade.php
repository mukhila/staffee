<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Comp-Off Claims</h1>
            <span>Approving a claim credits the days to the employee's Compensatory Off balance</span>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $value => $label)
                <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @error('review_notes')
    <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Date worked</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th style="min-width: 280px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($credits as $c)
                        @php $color = \App\Models\Leave\CompOffCredit::STATUS_COLORS[$c->status] ?? 'secondary'; @endphp
                        <tr>
                            <td>
                                <strong>{{ $c->user?->name }}</strong>
                                <div class="text-muted small">{{ $c->user?->department?->name }}</div>
                            </td>
                            <td>{{ $c->worked_date->format('D, d M Y') }}</td>
                            <td>{{ rtrim(rtrim((string) $c->days, '0'), '.') }}</td>
                            <td class="small">{{ $c->reason }}</td>
                            <td><span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst($c->status) }}</span></td>
                            <td>
                                @if($c->isPending() && $c->user_id !== auth()->id())
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('admin.leaves.comp-off.approve', $c) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.leaves.comp-off.reject', $c) }}" class="d-flex gap-1 flex-grow-1">
                                        @csrf
                                        <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Reason for rejecting" maxlength="500" required>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                                @else
                                <span class="small text-muted">
                                    {{ $c->reviewer?->name ?? '—' }}
                                    @if($c->review_notes) · {{ $c->review_notes }}@endif
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No claims found.</td></tr>
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
</x-app-layout>
