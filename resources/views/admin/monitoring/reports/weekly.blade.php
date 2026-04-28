<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Weekly Active Hours</h1>
            <span>{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.reports.daily') }}" class="btn btn-outline-secondary btn-sm">Daily View</a>
            <a href="{{ route('admin.monitoring.reports.department') }}" class="btn btn-outline-secondary btn-sm">By Department</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Week of</label>
                    <input type="date" name="week" class="form-control form-control-sm" value="{{ $weekStart->format('Y-m-d') }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Load</button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:160px;">Employee</th>
                            @foreach($days as $day)
                            <th class="text-center small {{ $day->isToday() ? 'text-primary fw-bold' : '' }}">
                                {{ $day->format('D') }}<br>{{ $day->format('d') }}
                            </th>
                            @endforeach
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($matrix as $row)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $row['user']->name }}</div>
                                <div class="text-muted small">{{ $row['user']->department?->name }}</div>
                            </td>
                            @foreach($row['days'] as $day)
                            <td class="text-center small {{ $day['minutes'] > 0 ? 'text-success' : 'text-muted' }}">
                                {{ $day['minutes'] > 0 ? gmdate('H:i', $day['minutes'] * 60) : '—' }}
                            </td>
                            @endforeach
                            <td class="text-end fw-bold">{{ gmdate('H:i', $row['total'] * 60) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($days) + 2 }}" class="text-center text-muted py-5">No data for this week.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
