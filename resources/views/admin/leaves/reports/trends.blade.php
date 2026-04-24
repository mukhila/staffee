<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Trends</h1>
            <span>Month-by-month breakdown for {{ $year }}</span>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" style="width:90px">
                <button class="btn btn-sm btn-secondary">Go</button>
            </form>
            <a href="{{ route('admin.leaves.reports.index') }}?year={{ $year }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Stacked chart --}}
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0">Days Taken per Month by Type</h6></div>
        <div class="card-body">
            <canvas id="trendsChart" height="100"></canvas>
        </div>
    </div>

    {{-- Data table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            @foreach($types as $type)
                            <th class="text-end">
                                <span class="rounded-circle d-inline-block me-1" style="width:8px;height:8px;background:{{ $type->color }}"></span>
                                {{ $type->name }}
                            </th>
                            @endforeach
                            <th class="text-end fw-bold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']; @endphp
                        @for($m = 1; $m <= 12; $m++)
                        @php $rowTotal = array_sum($grid[$m]); @endphp
                        <tr>
                            <td>{{ $monthNames[$m-1] }}</td>
                            @foreach($types as $type)
                            <td class="text-end">{{ $grid[$m][$type->id] > 0 ? $grid[$m][$type->id] : '—' }}</td>
                            @endforeach
                            <td class="text-end fw-bold">{{ $rowTotal > 0 ? $rowTotal : '—' }}</td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td><strong>Total</strong></td>
                            @foreach($types as $type)
                            @php $colTotal = 0; for($m=1;$m<=12;$m++) $colTotal += $grid[$m][$type->id]; @endphp
                            <td class="text-end fw-bold">{{ $colTotal > 0 ? $colTotal : '—' }}</td>
                            @endforeach
                            @php $grandTotal = 0; foreach($types as $t) for($m=1;$m<=12;$m++) $grandTotal += $grid[$m][$t->id]; @endphp
                            <td class="text-end fw-bold text-primary">{{ $grandTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const months   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const rawTypes = @json($types->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color]));
const rawGrid  = @json($grid);

const datasets = rawTypes.map(type => ({
    label: type.name,
    data: months.map((_, i) => rawGrid[i + 1][type.id] || 0),
    backgroundColor: type.color + 'cc',
    borderColor: type.color,
    borderWidth: 1,
}));

new Chart(document.getElementById('trendsChart'), {
    type: 'bar',
    data: { labels: months, datasets },
    options: {
        responsive: true,
        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
        plugins: { legend: { position: 'bottom' } },
    }
});
</script>
</x-app-layout>
