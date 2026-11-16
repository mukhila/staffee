@php
    use App\Models\Monitoring\MonitoringAppRule;
    use App\Services\Monitoring\ProductivityService;
@endphp
<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Productivity Rules</h1>
            <span>Decide which applications and websites count as productive, neutral or unproductive</span>
        </div>
        <a href="{{ route('admin.monitoring.reports.productivity') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-chart-line-up me-1"></i> Productivity Report
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Add a rule</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.monitoring.app-rules.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="match_type">Look in</label>
                            <select id="match_type" name="match_type" class="form-select @error('match_type') is-invalid @enderror">
                                <option value="app" {{ old('match_type', 'app') === 'app' ? 'selected' : '' }}>Application name (e.g. Excel, Code)</option>
                                <option value="title" {{ old('match_type') === 'title' ? 'selected' : '' }}>Window title (for websites, e.g. YouTube)</option>
                            </select>
                            @error('match_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="pattern">Name contains</label>
                            <input type="text" id="pattern" name="pattern" maxlength="255" value="{{ old('pattern') }}"
                                   class="form-control @error('pattern') is-invalid @enderror" placeholder="e.g. Visual Studio" required>
                            @error('pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Not case-sensitive. A longer, more specific name wins over a shorter one.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="category">Count as</label>
                            <select id="category" name="category" class="form-select @error('category') is-invalid @enderror">
                                @foreach(MonitoringAppRule::CATEGORY_LABELS as $value => $label)
                                <option value="{{ $value }}" {{ old('category', 'productive') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add rule</button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Not categorised yet</h6>
                    <small class="text-muted">Most-used applications in the last 14 days with no rule. They count as neutral until you choose.</small>
                </div>
                <ul class="list-group list-group-flush">
                    @forelse($uncategorised as $item)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-truncate me-2">{{ $item['app'] }}</strong>
                            <span class="text-muted small text-nowrap">{{ ProductivityService::formatDuration($item['seconds']) }}</span>
                        </div>
                        <div class="d-flex gap-1">
                            @foreach(MonitoringAppRule::CATEGORY_LABELS as $value => $label)
                            <form method="POST" action="{{ route('admin.monitoring.app-rules.store') }}" class="flex-fill">
                                @csrf
                                <input type="hidden" name="match_type" value="app">
                                <input type="hidden" name="pattern" value="{{ $item['app'] }}">
                                <input type="hidden" name="category" value="{{ $value }}">
                                <button type="submit" class="btn btn-sm btn-outline-{{ MonitoringAppRule::CATEGORY_COLORS[$value] }} w-100">{{ $label }}</button>
                            </form>
                            @endforeach
                        </div>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small">Nothing to categorise. Every application used recently has a rule.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            @foreach(MonitoringAppRule::CATEGORY_LABELS as $category => $label)
            @php $color = MonitoringAppRule::CATEGORY_COLORS[$category]; $list = $rules->get($category, collect()); @endphp
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><span class="badge bg-{{ $color }} me-2">{{ $label }}</span>{{ $list->count() }} {{ Str::plural('rule', $list->count()) }}</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            @forelse($list as $rule)
                            <tr>
                                <td class="fw-medium">{{ $rule->pattern }}</td>
                                <td class="text-muted small">{{ $rule->match_type === 'title' ? 'Window title' : 'Application' }}</td>
                                <td style="width: 190px">
                                    <form method="POST" action="{{ route('admin.monitoring.app-rules.update', $rule) }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="category" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Change category for {{ $rule->pattern }}">
                                            @foreach(MonitoringAppRule::CATEGORY_LABELS as $value => $optionLabel)
                                            <option value="{{ $value }}" {{ $rule->category === $value ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="text-end" style="width: 90px">
                                    <form method="POST" action="{{ route('admin.monitoring.app-rules.destroy', $rule) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td class="text-center text-muted py-4">No {{ strtolower($label) }} rules yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
</x-app-layout>
