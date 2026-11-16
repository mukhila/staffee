@php
    use App\Models\Monitoring\MonitoringAppRule;
    use App\Services\Monitoring\ProductivityService;
@endphp
<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Productivity Report</h1>
            <span>{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.reports.daily') }}" class="btn btn-outline-secondary btn-sm">Daily View</a>
            <a href="{{ route('admin.monitoring.reports.weekly') }}" class="btn btn-outline-secondary btn-sm">Weekly Hours</a>
            <a href="{{ route('admin.monitoring.reports.department') }}" class="btn btn-outline-secondary btn-sm">By Department</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-end justify-content-between">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1" for="week">Week of</label>
                    <input type="date" id="week" name="week" class="form-control form-control-sm" value="{{ $weekStart->format('Y-m-d') }}">
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Load</button></div>
            </form>
            <div class="small">
                @foreach(MonitoringAppRule::CATEGORY_LABELS as $value => $label)
                <span class="badge bg-{{ MonitoringAppRule::CATEGORY_COLORS[$value] }} me-1">{{ $label }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:170px;">Employee</th>
                            <th>Active</th>
                            <th style="min-width:260px;">Split</th>
                            <th>Productive</th>
                            <th>Idle</th>
                            <th style="min-width:220px;">Most used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        @php $s = $row['summary']; $active = max(1, $s['active_seconds']); @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $row['user']->name }}</div>
                                <div class="text-muted small">{{ $row['user']->department?->name }}</div>
                            </td>
                            <td>{{ ProductivityService::formatDuration($s['active_seconds']) }}</td>
                            <td>
                                <div class="progress" style="height: 14px" role="img"
                                     aria-label="Productive {{ ProductivityService::formatDuration($s['seconds']['productive']) }}, neutral {{ ProductivityService::formatDuration($s['seconds']['neutral']) }}, unproductive {{ ProductivityService::formatDuration($s['seconds']['unproductive']) }}">
                                    @foreach(MonitoringAppRule::CATEGORIES as $category)
                                    <div class="progress-bar bg-{{ MonitoringAppRule::CATEGORY_COLORS[$category] }}"
                                         style="width: {{ round($s['seconds'][$category] / $active * 100, 1) }}%"
                                         title="{{ MonitoringAppRule::CATEGORY_LABELS[$category] }}: {{ ProductivityService::formatDuration($s['seconds'][$category]) }}"></div>
                                    @endforeach
                                </div>
                            </td>
                            <td class="fw-bold">{{ $s['productive_pct'] }}%</td>
                            <td class="text-muted">{{ ProductivityService::formatDuration($s['idle_seconds']) }}</td>
                            <td class="small">
                                @foreach($s['top_apps'] as $app)
                                <span class="badge bg-{{ MonitoringAppRule::CATEGORY_COLORS[$app['category']] }}-subtle text-{{ MonitoringAppRule::CATEGORY_COLORS[$app['category']] }} me-1 mb-1">
                                    {{ Str::limit($app['app'], 22) }} · {{ ProductivityService::formatDuration($app['seconds']) }}
                                </span>
                                @endforeach
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">No activity was recorded for this week.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="text-muted small mt-2 mb-0">
        Time in applications with no rule counts as neutral.
        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.monitoring.app-rules.index') }}">Manage productivity rules</a>
        @endif
    </p>
</div>
</x-app-layout>
