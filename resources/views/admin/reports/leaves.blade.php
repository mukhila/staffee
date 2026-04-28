<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Report</h1>
            <span>Leave requests by date range</span>
        </div>
        <a href="{{ route('admin.reports.leaves.export', ['from' => $from, 'to' => $to]) }}" class="btn btn-success btn-sm">
            <i class="fi fi-rr-file-csv me-1"></i> Export CSV
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @php $colors = ['approved'=>'success','pending'=>'warning','rejected'=>'danger']; @endphp
        @foreach(['approved','pending','rejected'] as $st)
        <div class="col-auto">
            <div class="card text-center px-4 py-3">
                <div class="fs-4 fw-bold text-{{ $colors[$st] }}">{{ $byStatus[$st] ?? 0 }}</div>
                <div class="text-muted small">{{ ucfirst($st) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $r)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $r->user?->name }}</div>
                                <div class="text-muted small">{{ $r->user?->department?->name }}</div>
                            </td>
                            <td class="text-muted small">{{ $r->leaveType?->name ?? $r->leave_type }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->start_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->end_date)->format('d M Y') }}</td>
                            <td>{{ $r->total_days ?? '—' }}</td>
                            <td>
                                @php $c = $colors[$r->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucfirst($r->status) }}</span>
                            </td>
                            <td class="text-muted small">{{ Str::limit($r->reason, 50) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No leave records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($records->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $records->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
