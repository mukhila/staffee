<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Department Productivity</h1>
            <span>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.reports.daily') }}" class="btn btn-outline-secondary btn-sm">Daily View</a>
            <a href="{{ route('admin.monitoring.reports.weekly') }}" class="btn btn-outline-secondary btn-sm">Weekly View</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        @forelse($deptData as $d)
        @php
            $pct = $maxMinutes > 0 ? round($d['total_minutes'] / $maxMinutes * 100) : 0;
            $prodPct = $d['total_minutes'] > 0 ? round($d['productive_minutes'] / $d['total_minutes'] * 100) : 0;
        @endphp
        <div class="col-md-6 col-xl-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $d['department']->name }}</h6>
                            <div class="text-muted small">{{ $d['head_count'] }} member{{ $d['head_count'] !== 1 ? 's' : '' }}</div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">{{ $prodPct }}% productive</span>
                    </div>

                    <div class="mb-3">
                        <div class="progress mb-1" style="height:8px;">
                            <div class="progress-bar bg-success" style="width:{{ $prodPct }}%" title="{{ $prodPct }}% productive"></div>
                            <div class="progress-bar bg-warning opacity-50" style="width:{{ 100 - $prodPct }}%" title="{{ 100 - $prodPct }}% idle"></div>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.72rem;color:#6c757d;">
                            <span>Productive: {{ gmdate('H:i', $d['productive_minutes'] * 60) }}</span>
                            <span>Idle: {{ gmdate('H:i', $d['idle_minutes'] * 60) }}</span>
                        </div>
                    </div>

                    <div class="row g-2 text-center">
                        <div class="col-4">
                            <div class="fw-bold">{{ $d['total_sessions'] }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Sessions</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ gmdate('H:i', $d['total_minutes'] * 60) }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Total Active</div>
                        </div>
                        <div class="col-4">
                            <div class="fw-bold">{{ gmdate('H:i', $d['avg_minutes_per_head'] * 60) }}</div>
                            <div class="text-muted" style="font-size:.72rem;">Avg/Person</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col"><div class="text-center text-muted py-5">No monitoring data for this period.</div></div>
        @endforelse
    </div>
</div>
</x-app-layout>
