<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Live Monitoring</h1>
            <span>Real-time employee activity — auto-refreshes every 30 s</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.settings.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-settings me-1"></i> Settings & Tokens
            </a>
        </div>
    </div>

    {{-- KPI row --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Online Now</div>
                <div class="fw-bold fs-3 text-success">{{ $onlineCount }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Offline</div>
                <div class="fw-bold fs-3 text-secondary">{{ $offlineUsers->count() }}</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Sessions Today</div>
                <div class="fw-bold fs-3 text-primary">{{ $todayTotal }}</div>
            </div>
        </div>
    </div>

    {{-- Online employees --}}
    @if($onlineUsers->isNotEmpty())
    <h6 class="fw-bold mb-2 text-success"><i class="fi fi-rr-signal-alt me-1"></i> Online</h6>
    <div class="row g-3 mb-4">
        @foreach($onlineUsers as $entry)
        @php $user = $entry['user']; $session = $entry['session']; $act = $entry['latest_activity']; @endphp
        <div class="col-sm-6 col-xl-4">
            <div class="card h-100 border-success border-opacity-25">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="position-relative">
                            <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('assets/images/avatar.png') }}"
                                 class="rounded-circle" width="44" height="44" style="object-fit:cover">
                            <span class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white"
                                  style="width:12px;height:12px"></span>
                        </span>
                        <div class="flex-fill min-w-0">
                            <div class="fw-bold text-truncate">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->department?->name ?? '—' }}</div>
                        </div>
                        <a href="{{ route('admin.monitoring.show', $user) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fi fi-rr-eye"></i>
                        </a>
                    </div>
                    <div class="text-muted small mb-1">
                        <i class="fi fi-rr-desktop me-1"></i>{{ $session->hostname ?? 'Unknown host' }}
                        &nbsp;·&nbsp;
                        <i class="fi fi-rr-time-past me-1"></i>Online {{ $session->started_at->diffForHumans(null, true) }}
                    </div>
                    @if($act)
                    <div class="text-truncate small" title="{{ $act->active_window_title }}">
                        <i class="fi fi-rr-window me-1 text-muted"></i>{{ $act->active_window_title ?? 'No window data' }}
                    </div>
                    @endif
                    <div class="mt-2 d-flex gap-2">
                        <a href="{{ route('admin.monitoring.screenshots.index', $user) }}" class="btn btn-xs btn-outline-secondary btn-sm">
                            <i class="fi fi-rr-camera me-1"></i>Screenshots
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Offline employees --}}
    <h6 class="fw-bold mb-2 text-secondary"><i class="fi fi-rr-signal-alt-slash me-1"></i> Offline</h6>
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Last Seen</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($offlineUsers as $user)
                    @php
                        $lastSession = \App\Models\Monitoring\MonitoringSession::where('user_id', $user->id)
                            ->latest('ended_at')->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="position-relative">
                                    <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : asset('assets/images/avatar.png') }}"
                                         class="rounded-circle" width="32" height="32" style="object-fit:cover">
                                    <span class="position-absolute bottom-0 end-0 bg-secondary rounded-circle border border-white"
                                          style="width:9px;height:9px"></span>
                                </span>
                                <span>{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted small">{{ $user->department?->name ?? '—' }}</td>
                        <td class="text-muted small">
                            {{ $lastSession?->ended_at?->diffForHumans() ?? 'Never connected' }}
                        </td>
                        <td>
                            <a href="{{ route('admin.monitoring.show', $user) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fi fi-rr-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">All employees are online.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Auto-refresh every 30 seconds
setTimeout(() => location.reload(), 30000);
</script>
</x-app-layout>
