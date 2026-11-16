<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Attendance Regularisation</h1>
            <span>Review requests to correct missed or wrong punches</span>
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
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th style="min-width: 280px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $r)
                        @php $color = \App\Models\AttendanceRegularization::STATUS_COLORS[$r->status] ?? 'secondary'; @endphp
                        <tr>
                            <td>
                                <strong>{{ $r->user?->name }}</strong>
                                <div class="text-muted small">{{ $r->user?->department?->name }}</div>
                            </td>
                            <td>{{ $r->date->format('d M Y') }}</td>
                            <td>{{ $r->requested_check_in ? \Carbon\Carbon::parse($r->requested_check_in)->format('H:i') : '—' }}</td>
                            <td>{{ $r->requested_check_out ? \Carbon\Carbon::parse($r->requested_check_out)->format('H:i') : '—' }}</td>
                            <td class="small">{{ $r->reason }}</td>
                            <td><span class="badge bg-{{ $color }}-subtle text-{{ $color }}">{{ ucfirst($r->status) }}</span></td>
                            <td>
                                @if($r->isPending() && $r->user_id !== auth()->id())
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('admin.attendances.regularizations.approve', $r) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.attendances.regularizations.reject', $r) }}" class="d-flex gap-1 flex-grow-1">
                                        @csrf
                                        <input type="text" name="review_notes" class="form-control form-control-sm" placeholder="Reason for rejecting" maxlength="500" required>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                </div>
                                @else
                                <span class="small text-muted">
                                    {{ $r->reviewer?->name ?? '—' }}
                                    @if($r->review_notes) · {{ $r->review_notes }}@endif
                                </span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No requests found.</td></tr>
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
</x-app-layout>
