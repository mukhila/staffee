@php
    use App\Models\Monitoring\MonitoringAppRule;
    use App\Services\Monitoring\ProductivityService;
@endphp
<x-app-layout>
@php
    $active = max(1, $summary['active_seconds']);
@endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Activity</h1>
            <span>{{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }} · only you and authorised managers can see this</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('staff.my-activity.index', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-angle-left"></i> Previous week
            </a>
            @if($weekEnd->isPast())
            <a href="{{ route('staff.my-activity.index', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">
                Next week <i class="fi fi-rr-angle-right"></i>
            </a>
            @endif
        </div>
    </div>

    @unless($monitoringEnabled)
    <div class="alert alert-info">Activity monitoring is currently switched off for your account, so nothing new is being recorded.</div>
    @endunless

    @if($summary['active_seconds'] === 0)
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fi fi-rr-chart-pie-alt fs-2 d-block mb-2"></i>
            No activity was recorded for you this week.
        </div>
    </div>
    @else
    <div class="row g-3 mb-3">
        @php
        $cards = [
            ['label' => 'Active time',  'value' => ProductivityService::formatDuration($summary['active_seconds']),          'color' => 'primary',   'icon' => 'fi-rr-clock'],
            ['label' => 'Productive',   'value' => $summary['productive_pct'] . '%',                                         'color' => 'success',   'icon' => 'fi-rr-check-circle'],
            ['label' => 'Unproductive', 'value' => ProductivityService::formatDuration($summary['seconds']['unproductive']), 'color' => 'danger',    'icon' => 'fi-rr-cross-circle'],
            ['label' => 'Idle',         'value' => ProductivityService::formatDuration($summary['idle_seconds']),            'color' => 'secondary', 'icon' => 'fi-rr-pause'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-{{ $card['color'] }}-subtle" style="width:44px;height:44px;flex-shrink:0;">
                        <i class="fi {{ $card['icon'] }} text-{{ $card['color'] }}"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">{{ $card['value'] }}</div>
                        <div class="text-muted small">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Day by day</h6></div>
                <div class="card-body">
                    @foreach($days as $day)
                    @php $d = $day['summary']; $dayActive = max(1, $d['active_seconds']); @endphp
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="small text-muted" style="width: 70px">{{ $day['date']->format('D d') }}</div>
                        <div class="flex-grow-1">
                            @if($d['active_seconds'] > 0)
                            <div class="progress" style="height: 14px" role="img"
                                 aria-label="{{ $day['date']->format('l') }}: productive {{ ProductivityService::formatDuration($d['seconds']['productive']) }}, neutral {{ ProductivityService::formatDuration($d['seconds']['neutral']) }}, unproductive {{ ProductivityService::formatDuration($d['seconds']['unproductive']) }}">
                                @foreach(MonitoringAppRule::CATEGORIES as $category)
                                <div class="progress-bar bg-{{ MonitoringAppRule::CATEGORY_COLORS[$category] }}"
                                     style="width: {{ round($d['seconds'][$category] / $dayActive * 100, 1) }}%"
                                     title="{{ MonitoringAppRule::CATEGORY_LABELS[$category] }}: {{ ProductivityService::formatDuration($d['seconds'][$category]) }}"></div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-muted small">No activity</div>
                            @endif
                        </div>
                        <div class="small fw-medium text-end" style="width: 70px">
                            {{ $d['active_seconds'] > 0 ? ProductivityService::formatDuration($d['active_seconds']) : '—' }}
                        </div>
                    </div>
                    @endforeach
                    <div class="small mt-3">
                        @foreach(MonitoringAppRule::CATEGORY_LABELS as $value => $label)
                        <span class="badge bg-{{ MonitoringAppRule::CATEGORY_COLORS[$value] }} me-1">{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0">Most used applications</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach($summary['top_apps'] as $app)
                    @php $color = MonitoringAppRule::CATEGORY_COLORS[$app['category']]; @endphp
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-truncate me-2">{{ $app['app'] }}</span>
                            <span class="text-nowrap">
                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} me-1">{{ MonitoringAppRule::CATEGORY_LABELS[$app['category']] }}</span>
                                <strong>{{ ProductivityService::formatDuration($app['seconds']) }}</strong>
                            </span>
                        </div>
                        <div class="progress mt-1" style="height: 4px">
                            <div class="progress-bar bg-{{ $color }}" style="width: {{ round($app['seconds'] / $active * 100, 1) }}%"></div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-0">
        Applications with no rule count as neutral. If a tool you use for work shows as unproductive, tell your manager or HR so the rule can be corrected.
    </p>
    @endif
</div>
</x-app-layout>
