<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Monitoring Settings</h1>
            <span>Global defaults, per-employee overrides, and agent token management</span>
        </div>
        <a href="{{ route('admin.monitoring.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Live Board
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fi fi-rr-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Show newly generated token once --}}
    @if(session('new_token'))
    <div class="alert alert-warning">
        <strong>Copy this token now — it will not be shown again:</strong>
        <div class="mt-2 font-monospace bg-dark text-white p-2 rounded user-select-all">{{ session('new_token') }}</div>
    </div>
    @endif

    <div class="row g-4">
        {{-- Global settings form --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Global Defaults</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.monitoring.settings.store') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="">
                        @include('admin.monitoring._settings_form', ['settings' => $global])
                        <button class="btn btn-primary mt-3">Save Global Defaults</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Per-user override --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Add Per-Employee Override</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.monitoring.settings.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">— Select employee —</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @include('admin.monitoring._settings_form', ['settings' => null])
                        <button class="btn btn-primary mt-3">Save Override</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Per-user overrides list --}}
        @if($overrides->isNotEmpty())
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Per-Employee Overrides</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Screenshots</th>
                                <th>Interval</th>
                                <th>Activity</th>
                                <th>Idle Threshold</th>
                                <th>Enabled</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($overrides as $s)
                            <tr>
                                <td>{{ $s->user?->name }}</td>
                                <td>{{ $s->screenshot_enabled ? 'Yes' : 'No' }}</td>
                                <td>{{ $s->screenshot_interval_seconds }}s</td>
                                <td>{{ $s->activity_tracking_enabled ? 'Yes' : 'No' }}</td>
                                <td>{{ $s->idle_threshold_seconds }}s</td>
                                <td>
                                    <span class="badge bg-{{ $s->enabled ? 'success' : 'secondary' }}">
                                        {{ $s->enabled ? 'On' : 'Off' }}
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.monitoring.settings.destroy', $s) }}"
                                          onsubmit="return confirm('Remove this override?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fi fi-rr-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Token management --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Agent Token Management</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Token Status</th>
                                <th>Online</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td class="text-muted small">{{ $u->department?->name ?? '—' }}</td>
                                <td>
                                    @if($u->agent_token)
                                    <span class="badge bg-success">Token issued</span>
                                    @else
                                    <span class="badge bg-secondary">No token</span>
                                    @endif
                                </td>
                                <td>
                                    @if($u->isOnline())
                                    <span class="badge bg-success"><i class="fi fi-rr-signal-alt me-1"></i>Online</span>
                                    @else
                                    <span class="text-muted small">Offline</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <form method="POST" action="{{ route('admin.monitoring.tokens.generate', $u) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="return confirm('Generate new token? The old token will stop working.')">
                                            <i class="fi fi-rr-refresh me-1"></i>{{ $u->agent_token ? 'Regenerate' : 'Generate' }}
                                        </button>
                                    </form>
                                    @if($u->agent_token)
                                    <form method="POST" action="{{ route('admin.monitoring.tokens.revoke', $u) }}"
                                          onsubmit="return confirm('Revoke token? The agent will disconnect.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fi fi-rr-ban me-1"></i>Revoke
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
