<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $user->name }}</h1>
            <span>Activity detail — {{ $date->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Live Board
            </a>
            <a href="{{ route('admin.monitoring.screenshots.index', $user) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-camera me-1"></i> Screenshots
            </a>
        </div>
    </div>

    {{-- Date picker --}}
    <form method="GET" class="mb-3 d-flex gap-2 align-items-end">
        <div>
            <label class="form-label form-label-sm mb-1">Date</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->format('Y-m-d') }}">
        </div>
        <button class="btn btn-sm btn-secondary">Go</button>
    </form>

    {{-- KPI row --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Sessions</div>
                <div class="fw-bold fs-4">{{ $sessions->count() }}</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Active Time</div>
                <div class="fw-bold fs-4 text-success">{{ gmdate('H:i', $totalActive) }}</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Idle Time</div>
                <div class="fw-bold fs-4 text-warning">{{ gmdate('H:i', $totalIdle) }}</div>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Top App</div>
                <div class="fw-bold fs-6 text-truncate px-2">{{ $topApps->keys()->first() ?? '—' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Sessions timeline --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Sessions</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Start</th><th>End</th><th>Duration</th><th>Host</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                            <tr>
                                <td class="small">{{ $session->started_at->format('H:i:s') }}</td>
                                <td class="small">{{ $session->ended_at?->format('H:i:s') ?? '—' }}</td>
                                <td class="small">{{ $session->duration_minutes }}m</td>
                                <td class="small text-muted text-truncate" style="max-width:120px">{{ $session->hostname ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $session->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($session->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-muted text-center py-4">No sessions on this date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Top apps --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Top Applications</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>App</th><th class="text-end">Time</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topApps as $app => $seconds)
                            <tr>
                                <td class="small">{{ $app ?: 'Unknown' }}</td>
                                <td class="text-end small">{{ gmdate('H:i', $seconds) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-muted text-center small py-3">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Activity log --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Activity Log</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>App</th>
                                    <th>Window</th>
                                    <th class="text-end">Keyboard</th>
                                    <th class="text-end">Mouse</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activityLogs as $log)
                                <tr class="{{ !$log->is_active ? 'table-secondary' : '' }}">
                                    <td class="small text-nowrap">{{ $log->recorded_at->format('H:i:s') }}</td>
                                    <td class="small">{{ $log->active_app_name ?? '—' }}</td>
                                    <td class="small text-truncate" style="max-width:200px" title="{{ $log->active_window_title }}">
                                        {{ $log->active_window_title ?? '—' }}
                                    </td>
                                    <td class="text-end small">{{ number_format($log->keyboard_events) }}</td>
                                    <td class="text-end small">{{ number_format($log->mouse_events) }}</td>
                                    <td>
                                        @if($log->is_active)
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary">Idle</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-muted text-center py-4">No activity logged.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
