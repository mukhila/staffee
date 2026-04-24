<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Employee Utilization</h1>
            <span>{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.time.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Overview
            </a>
            <a href="{{ route('admin.time.reports.revenue') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-dollar me-1"></i> Revenue
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-3">
        <div class="d-flex flex-wrap gap-2">
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}" style="width:160px">
            <input type="date" name="to"   class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}"   style="width:160px">
            <select name="department" class="form-select form-select-sm" style="width:200px">
                <option value="">All Departments</option>
                @foreach($departments as $d)
                <option value="{{ $d->id }}" {{ $deptId == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-secondary">Filter</button>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th class="text-end">Total Hours</th>
                        <th class="text-end">Billable</th>
                        <th class="text-end">Non-Billable</th>
                        <th style="width:200px">Utilization</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($metrics as $m)
                    <tr class="{{ $m['underutilized'] ? 'table-warning' : '' }}">
                        <td>
                            <strong>{{ $m['user']->name }}</strong>
                            @if($m['underutilized'])
                            <span class="badge bg-warning text-dark ms-1" title="Below 80% utilization">
                                <i class="fi fi-rr-exclamation"></i>
                            </span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $m['user']->department?->name ?? '—' }}</td>
                        <td class="text-end">{{ number_format($m['total_hours'], 1) }}h</td>
                        <td class="text-end text-success">{{ number_format($m['billable_hours'], 1) }}h</td>
                        <td class="text-end text-muted">{{ number_format($m['non_billable_hours'], 1) }}h</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-fill" style="height:8px">
                                    <div class="progress-bar bg-{{ $m['utilization_pct'] >= 80 ? 'success' : ($m['utilization_pct'] >= 60 ? 'warning' : 'danger') }}"
                                         style="width:{{ min(100, $m['utilization_pct']) }}%"></div>
                                </div>
                                <span class="fw-bold small" style="width:40px;text-align:right">{{ $m['utilization_pct'] }}%</span>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-primary">
                            {{ $m['revenue'] > 0 ? '$' . number_format($m['revenue'], 2) : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No time data for this period.</td></tr>
                    @endforelse
                </tbody>
                @if($metrics->isNotEmpty())
                <tfoot class="table-light">
                    <tr>
                        <td colspan="2"><strong>Totals</strong></td>
                        <td class="text-end fw-bold">{{ number_format($metrics->sum('total_hours'), 1) }}h</td>
                        <td class="text-end fw-bold text-success">{{ number_format($metrics->sum('billable_hours'), 1) }}h</td>
                        <td class="text-end fw-bold text-muted">{{ number_format($metrics->sum('non_billable_hours'), 1) }}h</td>
                        <td>
                            @php $avgUtil = $metrics->where('total_hours', '>', 0)->avg('utilization_pct'); @endphp
                            <span class="fw-bold">Avg: {{ round($avgUtil, 1) }}%</span>
                        </td>
                        <td class="text-end fw-bold text-primary">${{ number_format($metrics->sum('revenue'), 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
</x-app-layout>
