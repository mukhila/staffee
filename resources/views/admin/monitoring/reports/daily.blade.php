<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Daily Active Hours</h1>
            <span>{{ $date->format('l, d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.reports.weekly') }}" class="btn btn-outline-secondary btn-sm">Weekly View</a>
            <a href="{{ route('admin.monitoring.reports.department') }}" class="btn btn-outline-secondary btn-sm">By Department</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->format('Y-m-d') }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Load</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card text-center py-3">
                <div class="fs-5 fw-bold">{{ gmdate('H:i', $totalActiveMinutes * 60) }}</div>
                <div class="text-muted small">Total Active Time</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-center py-3">
                <div class="fs-5 fw-bold text-success">{{ gmdate('H:i', $totalProductiveMinutes * 60) }}</div>
                <div class="text-muted small">Total Productive Time</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Sessions</th>
                            <th>Active Time</th>
                            <th>Idle Time</th>
                            <th>Productive Time</th>
                            <th style="width:180px">Productivity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $row)
                        @php
                            $pct = $row['active_minutes'] > 0
                                ? round($row['productive_minutes'] / $row['active_minutes'] * 100)
                                : 0;
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ $row['user']->name }}</td>
                            <td class="text-muted small">{{ $row['user']->department?->name ?? '—' }}</td>
                            <td>{{ $row['sessions'] }}</td>
                            <td>{{ gmdate('H:i', $row['active_minutes'] * 60) }}</td>
                            <td class="text-warning">{{ gmdate('H:i', $row['idle_minutes'] * 60) }}</td>
                            <td class="text-success fw-medium">{{ gmdate('H:i', $row['productive_minutes'] * 60) }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height:6px;">
                                        <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="small">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No monitoring data for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
