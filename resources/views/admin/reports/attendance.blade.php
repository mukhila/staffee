<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Attendance Report</h1>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="fi fi-rr-arrow-left me-1"></i> Reports
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-1 small">From</label>
                        <input type="date" name="from" class="form-control form-control-sm" value="{{ $from }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-1 small">To</label>
                        <input type="date" name="to" class="form-control form-control-sm" value="{{ $to }}">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary waves-effect">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 bg-success bg-opacity-10 shadow-none">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ $summary['present'] ?? 0 }}</h3>
                        <div class="small text-muted">Present records</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-danger bg-opacity-10 shadow-none">
                    <div class="card-body text-center">
                        <h3 class="text-danger">{{ $summary['absent'] ?? 0 }}</h3>
                        <div class="small text-muted">Absent records</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-warning bg-opacity-10 shadow-none">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ $summary['leave'] ?? 0 }}</h3>
                        <div class="small text-muted">On leave records</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                            <tr>
                                <td>{{ $record->user->name ?? 'N/A' }}</td>
                                <td>{{ $record->date }}</td>
                                <td>{{ $record->check_in ?? '-' }}</td>
                                <td>{{ $record->check_out ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $record->status === 'present' ? 'success' : ($record->status === 'absent' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No records in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($records->hasPages())
            <div class="card-footer">{{ $records->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
