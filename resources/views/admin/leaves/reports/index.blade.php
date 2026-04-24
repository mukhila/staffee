<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Reports</h1>
            <span>Overview for {{ $year }}</span>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" style="width:90px">
                <button class="btn btn-sm btn-secondary">Go</button>
            </form>
            <a href="{{ route('admin.leaves.reports.compliance') }}?year={{ $year }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-shield-check me-1"></i> Compliance
            </a>
            <a href="{{ route('admin.leaves.reports.trends') }}?year={{ $year }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-chart-line me-1"></i> Trends
            </a>
            <a href="{{ route('admin.leaves.calendar') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-calendar me-1"></i> Calendar
            </a>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Pending Now</div>
                    <div class="display-6 fw-bold text-warning">{{ $pending }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Approved ({{ $year }})</div>
                    <div class="display-6 fw-bold text-success">{{ $approved }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Rejected ({{ $year }})</div>
                    <div class="display-6 fw-bold text-danger">{{ $rejected }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="text-muted small">Total Days Taken ({{ $year }})</div>
                    <div class="display-6 fw-bold text-primary">{{ $totalDays }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Monthly bar chart (canvas) --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Monthly Days Taken ({{ $year }})</h6></div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="120"></canvas>
                </div>
            </div>
        </div>

        {{-- By type --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">By Leave Type</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Type</th><th class="text-end">Requests</th><th class="text-end">Days</th></tr>
                        </thead>
                        <tbody>
                            @forelse($byType as $type)
                            <tr>
                                <td>
                                    <span class="rounded-circle d-inline-block me-1" style="width:8px;height:8px;background:{{ $type->color }}"></span>
                                    {{ $type->name }}
                                </td>
                                <td class="text-end">{{ $type->approved_count ?? 0 }}</td>
                                <td class="text-end fw-bold">{{ $type->approved_days ?? 0 }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center small py-3">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const data   = {!! json_encode(array_values($monthlyData)) !!};

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Days taken',
            data: data,
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderColor: 'rgba(99,102,241,1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
</x-app-layout>
