<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Time Reports</h1>
            <span>{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
                <input type="date" name="to"   class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
                <button class="btn btn-sm btn-secondary">Filter</button>
            </form>
            <a href="{{ route('admin.time.reports.utilization') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-users me-1"></i> Utilization
            </a>
            <a href="{{ route('admin.time.reports.revenue') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-dollar me-1"></i> Revenue
            </a>
            <a href="{{ route('admin.time.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-list me-1"></i> Time Log
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Total Hours</div>
                    <div class="display-6 fw-bold">{{ number_format($totalHours, 1) }}h</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Billable Hours</div>
                    <div class="display-6 fw-bold text-success">{{ number_format($billableHours, 1) }}h</div>
                    <div class="text-muted small">{{ $utilization }}% utilization</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Revenue</div>
                    <div class="display-6 fw-bold text-primary">${{ number_format($totalRevenue, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Underutilized Employees</div>
                    <div class="display-6 fw-bold text-{{ $underutilized->count() > 0 ? 'warning' : 'success' }}">
                        {{ $underutilized->count() }}
                    </div>
                    <div class="text-muted small">below 80%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Monthly trend chart --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Monthly Trend ({{ now()->year }})</h6>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="120"></canvas>
                </div>
            </div>
        </div>

        {{-- Department breakdown --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">By Department</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Dept</th><th class="text-end">Hours</th><th class="text-end">Util %</th><th class="text-end">Revenue</th></tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $d)
                            <tr>
                                <td>{{ $d['department']->name }}</td>
                                <td class="text-end">{{ number_format($d['billable_hours'], 1) }}</td>
                                <td class="text-end">
                                    <span class="badge bg-{{ $d['avg_utilization'] >= 80 ? 'success' : ($d['avg_utilization'] >= 60 ? 'warning' : 'danger') }}">
                                        {{ $d['avg_utilization'] }}%
                                    </span>
                                </td>
                                <td class="text-end">${{ number_format($d['revenue'], 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-muted text-center small py-3">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Underutilized --}}
        @if($underutilized->isNotEmpty())
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning-subtle d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 text-warning"><i class="fi fi-rr-exclamation me-1"></i> Underutilized Employees</h6>
                    <a href="{{ route('admin.time.reports.utilization') }}" class="btn btn-sm btn-outline-warning">View All</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Employee</th><th>Department</th><th class="text-end">Total Hours</th><th class="text-end">Billable</th><th class="text-end">Utilization</th></tr>
                        </thead>
                        <tbody>
                            @foreach($underutilized->take(5) as $m)
                            <tr>
                                <td>{{ $m['user']->name }}</td>
                                <td class="text-muted small">{{ $m['user']->department?->name }}</td>
                                <td class="text-end">{{ number_format($m['total_hours'], 1) }}h</td>
                                <td class="text-end">{{ number_format($m['billable_hours'], 1) }}h</td>
                                <td class="text-end">
                                    <div class="progress" style="height:6px;width:80px;display:inline-flex;vertical-align:middle">
                                        <div class="progress-bar bg-warning" style="width:{{ $m['utilization_pct'] }}%"></div>
                                    </div>
                                    <span class="ms-1 text-warning fw-bold small">{{ $m['utilization_pct'] }}%</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const months = @json($trend->pluck('month'));
const totalH = @json($trend->pluck('total_hours'));
const billH  = @json($trend->pluck('billable_hours'));
const utils  = @json($trend->pluck('utilization'));

new Chart(document.getElementById('trendChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Billable', data: billH,  backgroundColor: 'rgba(16,185,129,0.7)', borderRadius: 3 },
            { label: 'Non-Billable', data: totalH.map((t, i) => Math.max(0, t - billH[i])), backgroundColor: 'rgba(107,114,128,0.4)', borderRadius: 3 },
        ]
    },
    options: {
        responsive: true, plugins: { legend: { position: 'bottom' } },
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } }
    }
});
</script>
</x-app-layout>
