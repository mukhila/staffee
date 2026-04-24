<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Time Log</h1>
            <span>All completed time entries</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.time.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-chart-line me-1"></i> Reports
            </a>
            <a href="{{ route('admin.time.rates.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-dollar me-1"></i> Rates
            </a>
            <a href="{{ route('admin.time.categories.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-tags me-1"></i> Categories
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-2">
                <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Employees</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="project_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="billable" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Billable & Non-Billable</option>
                    <option value="1" {{ request('billable') === '1' ? 'selected' : '' }}>Billable only</option>
                    <option value="0" {{ request('billable') === '0' ? 'selected' : '' }}>Non-billable only</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}" placeholder="To">
                <button type="submit" class="d-none">Filter</button>
            </div>
        </div>
    </form>

    {{-- Summary bar --}}
    <div class="row g-3 mb-3">
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Total Hours (page)</div>
                <div class="fw-bold fs-5">{{ number_format($totalHours, 1) }}h</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Billable Hours</div>
                <div class="fw-bold fs-5 text-success">{{ number_format($billableHours, 1) }}h
                    <small class="text-muted fw-normal">
                        ({{ $totalHours > 0 ? round($billableHours / $totalHours * 100) : 0 }}%)
                    </small>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-2">
                <div class="text-muted small">Revenue (page)</div>
                <div class="fw-bold fs-5 text-primary">${{ number_format($totalRevenue, 2) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Task / Bug</th>
                            <th>Project</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Hours</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end">Revenue</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="text-muted small">{{ $entry->start_time->format('d M Y') }}<br>{{ $entry->start_time->format('H:i') }}–{{ $entry->end_time->format('H:i') }}</td>
                            <td>
                                <div class="fw-medium">{{ $entry->user->name }}</div>
                                <small class="text-muted">{{ $entry->user->department?->name }}</small>
                            </td>
                            <td>
                                @if($entry->trackable)
                                <small class="badge bg-light text-dark">{{ class_basename($entry->trackable_type) }}</small>
                                <span class="small">{{ Str::limit($entry->trackable->title, 30) }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><small>{{ $entry->project?->name ?? '—' }}</small></td>
                            <td>
                                @if($entry->category)
                                <span class="badge" style="background-color: {{ $entry->category->color }}">
                                    {{ $entry->category->name }}
                                </span>
                                @if(!$entry->is_billable)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" title="Non-billable">NB</span>
                                @endif
                                @else
                                <span class="text-muted small">Uncategorised</span>
                                @endif
                            </td>
                            <td class="small">{{ Str::limit($entry->description, 50) }}</td>
                            <td class="text-end fw-bold">{{ number_format($entry->hours_decimal, 2) }}h</td>
                            <td class="text-end small text-muted">
                                {{ $entry->rate_snapshot ? '$' . number_format($entry->rate_snapshot, 2) : '—' }}
                            </td>
                            <td class="text-end fw-bold text-{{ $entry->revenue > 0 ? 'success' : 'muted' }}">
                                {{ $entry->revenue > 0 ? '$' . number_format($entry->revenue, 2) : '—' }}
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.time.destroy', $entry) }}"
                                      onsubmit="return confirm('Delete this time entry?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-5 text-muted">No time entries match the current filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
