<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Bug Report</h1>
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
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-3">
            @foreach(['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'] as $sev => $color)
            <div class="col-6 col-md-3">
                <div class="card border-0 bg-{{ $color }} bg-opacity-10 shadow-none text-center">
                    <div class="card-body">
                        <h3 class="text-{{ $color }}">{{ $bySeverity[$sev] ?? 0 }}</h3>
                        <div class="small text-muted">{{ ucfirst($sev) }}</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Reported By</th>
                                <th>Assigned To</th>
                                <th>Severity</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bugs as $bug)
                            <tr>
                                <td>{{ Str::limit($bug->title, 40) }}</td>
                                <td>{{ $bug->project->name ?? '-' }}</td>
                                <td>{{ $bug->reportedByUser->name ?? '-' }}</td>
                                <td>{{ $bug->assignedUser->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'success'][$bug->severity] ?? 'secondary' }}">
                                        {{ ucfirst($bug->severity) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $bug->status === 'open' ? 'danger' : ($bug->status === 'resolved' || $bug->status === 'closed' ? 'success' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $bug->status)) }}
                                    </span>
                                </td>
                                <td>{{ $bug->created_at->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No bugs in this range.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($bugs->hasPages())
            <div class="card-footer">{{ $bugs->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
