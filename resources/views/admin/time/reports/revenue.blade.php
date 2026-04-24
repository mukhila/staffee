<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Revenue Report</h1>
            <span>Billable hours × rate — {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.time.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Overview
            </a>
            <a href="{{ route('admin.time.reports.utilization') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-users me-1"></i> Utilization
            </a>
        </div>
    </div>

    {{-- Filters + Export --}}
    <form method="GET" class="mb-3 d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label form-label-sm mb-1">From</label>
            <input type="date" name="from" class="form-control form-control-sm" value="{{ $from->format('Y-m-d') }}">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">To</label>
            <input type="date" name="to"   class="form-control form-control-sm" value="{{ $to->format('Y-m-d') }}">
        </div>
        <div>
            <label class="form-label form-label-sm mb-1">Project</label>
            <select name="project" class="form-select form-select-sm" style="width:200px">
                <option value="">All Projects</option>
                @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-secondary">Filter</button>

        {{-- Export button --}}
        @if($projectId)
        <a href="{{ route('admin.time.reports.export', ['project_id' => $projectId, 'from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d')]) }}"
           class="btn btn-sm btn-outline-success ms-auto">
            <i class="fi fi-rr-file-csv me-1"></i> Export CSV
        </a>
        @endif
    </form>

    {{-- KPI cards --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-md-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Total Revenue</div>
                <div class="fw-bold fs-4 text-primary">${{ number_format($grandTotal, 2) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Billable Hours</div>
                <div class="fw-bold fs-4">{{ number_format($totalHours, 1) }}h</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Avg Rate / hr</div>
                <div class="fw-bold fs-4">${{ $totalHours > 0 ? number_format($grandTotal / $totalHours, 2) : '—' }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="card text-center py-2">
                <div class="text-muted small">Projects</div>
                <div class="fw-bold fs-4">{{ $byProject->pluck('project_id')->unique()->count() }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Project</th>
                            <th>Employee</th>
                            <th class="text-end">Entries</th>
                            <th class="text-end">Hours</th>
                            <th class="text-end">Revenue</th>
                            <th class="text-end">Avg Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lastProject = null; @endphp
                        @forelse($byProject as $row)
                        @if($row->project_name !== $lastProject)
                        <tr class="table-light">
                            <td colspan="6" class="fw-bold py-1">
                                <i class="fi fi-rr-folder me-1 text-muted"></i>{{ $row->project_name }}
                            </td>
                        </tr>
                        @php $lastProject = $row->project_name; @endphp
                        @endif
                        <tr>
                            <td></td>
                            <td>{{ $row->user_name }}</td>
                            <td class="text-end">{{ $row->entry_count }}</td>
                            <td class="text-end">{{ number_format($row->hours, 2) }}h</td>
                            <td class="text-end fw-bold text-success">${{ number_format($row->revenue, 2) }}</td>
                            <td class="text-end text-muted small">
                                {{ $row->hours > 0 ? '$' . number_format($row->revenue / $row->hours, 2) : '—' }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">No billable time logged for this period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($byProject->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3"><strong>Grand Total</strong></td>
                            <td class="text-end fw-bold">{{ number_format($totalHours, 2) }}h</td>
                            <td class="text-end fw-bold text-primary">${{ number_format($grandTotal, 2) }}</td>
                            <td class="text-end text-muted small">
                                {{ $totalHours > 0 ? '$' . number_format($grandTotal / $totalHours, 2) : '—' }}/hr
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
