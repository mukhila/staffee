<x-app-layout>
@php
    $show = function ($value) {
        if ($value === null) return '—';
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        return (string) $value;
    };
@endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Audit Entry #{{ $auditLog->id }}</h1>
            <span>{{ $auditLog->event_label }}{{ $auditLog->record_label ? ' · ' . $auditLog->record_label . ' #' . $auditLog->auditable_id : '' }}</span>
        </div>
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">What changed</h6></div>
                <div class="card-body p-0">
                    @php $changes = $auditLog->changes(); @endphp
                    @if($changes)
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%">Field</th>
                                    @if($auditLog->event !== 'created')<th>Before</th>@endif
                                    @if($auditLog->event !== 'deleted')<th>After</th>@endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($changes as $field => [$old, $new])
                                <tr>
                                    <td class="fw-medium">{{ Str::headline($field) }}</td>
                                    @if($auditLog->event !== 'created')
                                    <td class="small {{ $auditLog->event === 'updated' ? 'text-danger' : '' }}" style="word-break: break-word">{{ $show($old) }}</td>
                                    @endif
                                    @if($auditLog->event !== 'deleted')
                                    <td class="small {{ $auditLog->event === 'updated' ? 'text-success' : '' }}" style="word-break: break-word">{{ $show($new) }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted p-3 mb-0">{{ $auditLog->description ?? 'No field changes recorded for this action.' }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Details</h6></div>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">When</span><span>{{ $auditLog->created_at->format('d M Y H:i:s') }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Who</span><span>{{ $auditLog->user?->name ?? 'System' }}</span></li>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Action</span><span class="badge bg-{{ $auditLog->event_color }}">{{ $auditLog->event_label }}</span></li>
                    @if($auditLog->description)
                    <li class="list-group-item"><span class="text-muted d-block">Note</span>{{ $auditLog->description }}</li>
                    @endif
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">IP address</span><span>{{ $auditLog->ip_address ?? '—' }}</span></li>
                    @if($auditLog->user_agent)
                    <li class="list-group-item"><span class="text-muted d-block">Browser</span><span style="word-break: break-word">{{ $auditLog->user_agent }}</span></li>
                    @endif
                    @if($auditLog->url)
                    <li class="list-group-item"><span class="text-muted d-block">Page</span><span style="word-break: break-all">{{ $auditLog->url }}</span></li>
                    @endif
                </ul>
            </div>

            @if($history->count() > 1)
            <div class="card">
                <div class="card-header"><h6 class="mb-0">History of this record</h6></div>
                <ul class="list-group list-group-flush small">
                    @foreach($history as $entry)
                    <li class="list-group-item {{ $entry->id === $auditLog->id ? 'bg-primary-subtle' : '' }}">
                        <a href="{{ route('admin.audit-logs.show', $entry) }}" class="text-decoration-none">
                            <span class="badge bg-{{ $entry->event_color }}-subtle text-{{ $entry->event_color }}">{{ $entry->event_label }}</span>
                            {{ $entry->user?->name ?? 'System' }}
                        </a>
                        <div class="text-muted">{{ $entry->created_at->format('d M Y H:i') }}</div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
