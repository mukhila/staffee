<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Audit Log</h1>
            <span>Who changed what, and when. Entries cannot be edited or deleted here.</span>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="user_id">Person</label>
                    <select id="user_id" name="user_id" class="form-select form-select-sm">
                        <option value="">Anyone</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="event">Action</label>
                    <select id="event" name="event" class="form-select form-select-sm">
                        <option value="">Any action</option>
                        @foreach($events as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['event'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="type">Record type</label>
                    <select id="type" name="type" class="form-select form-select-sm">
                        <option value="">Any record</option>
                        @foreach($types as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="from">From</label>
                    <input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="to">To</label>
                    <input type="date" id="to" name="to" class="form-control form-control-sm" value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="q">Search</label>
                    <input type="search" id="q" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}" placeholder="Record #, IP, text">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
            @if($errors->any())
            <div class="text-danger small mt-2">{{ $errors->first() }}</div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 160px">When</th>
                            <th>Who</th>
                            <th>Action</th>
                            <th>Record</th>
                            <th>Details</th>
                            <th>IP address</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="small text-nowrap" title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                {{ $log->created_at->format('d M Y H:i') }}
                                <div class="text-muted">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="badge bg-{{ $log->event_color }}-subtle text-{{ $log->event_color }}">{{ $log->event_label }}</span></td>
                            <td class="small">
                                @if($log->record_label)
                                {{ $log->record_label }} <span class="text-muted">#{{ $log->auditable_id }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small">
                                @if($log->description)
                                {{ $log->description }}
                                @elseif($log->event === 'updated')
                                @php $fields = array_keys($log->changes()); @endphp
                                Changed {{ Str::limit(implode(', ', array_map(fn ($f) => Str::headline($f), $fields)), 70) }}
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $log->ip_address ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.audit-logs.show', $log) }}" class="btn btn-sm btn-outline-primary" aria-label="View entry {{ $log->id }}">
                                    <i class="fi fi-rr-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No audit entries match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
        <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
