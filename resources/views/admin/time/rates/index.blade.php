<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Billable Rates</h1>
            <span>Hourly rate configuration with history</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.time.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Time Log
            </a>
            <a href="{{ route('admin.time.rates.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> Add Rate
            </a>
        </div>
    </div>

    <div class="alert alert-info alert-dismissible fade show small">
        <strong>Priority order:</strong> Employee + Project &gt; Project &gt; Employee &gt; Global default. The most specific active rate wins at the time of logging.
        <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filter by type --}}
    <form method="GET" class="mb-3">
        <div class="d-flex gap-2">
            @foreach(\App\Models\BillableRate::RATE_TYPES as $val => $label)
            <a href="?type={{ $val }}" class="btn btn-sm {{ request('type') === $val ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
            @endforeach
            @if(request('type'))
            <a href="{{ route('admin.time.rates.index') }}" class="btn btn-sm btn-outline-secondary">All</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Employee</th>
                            <th>Project</th>
                            <th class="text-end">Rate / hr</th>
                            <th>Currency</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rates as $rate)
                        <tr>
                            <td><span class="badge bg-secondary">{{ $rate->rate_type_label }}</span></td>
                            <td>{{ $rate->user?->name ?? '—' }}</td>
                            <td>{{ $rate->project?->name ?? '—' }}</td>
                            <td class="text-end fw-bold">${{ number_format($rate->hourly_rate, 2) }}</td>
                            <td>{{ $rate->currency }}</td>
                            <td>{{ $rate->effective_from->format('d M Y') }}</td>
                            <td>{{ $rate->effective_to ? $rate->effective_to->format('d M Y') : '<span class="badge bg-success">Current</span>' }}</td>
                            <td>
                                @if($rate->isActive())
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-secondary">Historical</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.time.rates.destroy', $rate) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this rate?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">No rates configured. <a href="{{ route('admin.time.rates.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($rates->hasPages())
        <div class="card-footer">{{ $rates->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
