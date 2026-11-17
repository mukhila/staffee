<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Reports by email</h1>
            <span>Download a report now, or have it emailed to you every week or month</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        {{-- My subscriptions --}}
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">My subscriptions</h6></div>
                <div class="card-body p-0">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Report</th><th>When</th><th>Last sent</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                            @php $def = $all[$sub->report] ?? null; @endphp
                            <tr class="{{ $sub->is_active ? '' : 'text-muted' }}">
                                <td>
                                    <div class="fw-medium">{{ $def?->label() ?? $sub->report }}</div>
                                    @if($sub->department)<div class="small text-muted">{{ $sub->department->name }}</div>@endif
                                    @if($sub->last_error)<div class="small text-danger">{{ $sub->last_error }}</div>@endif
                                </td>
                                <td class="small">
                                    {{ ucfirst($sub->frequency) }}
                                    <div class="text-muted">
                                        @if($sub->is_active) next {{ $sub->nextSendDate()->format('D j M') }} @else paused @endif
                                    </div>
                                </td>
                                <td class="small">{{ $sub->last_sent_at?->diffForHumans() ?? 'Not yet' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        @if($sub->is_active)
                                        <form method="POST" action="{{ route('reports.scheduled.send', $sub) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-primary" title="Email the latest report to me now">Send now</button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('reports.scheduled.toggle', $sub) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary">{{ $sub->is_active ? 'Pause' : 'Resume' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('reports.scheduled.destroy', $sub) }}" onsubmit="return confirm('Remove this subscription?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" aria-label="Remove subscription"><i class="fi fi-rr-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">You have no report subscriptions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="mb-0">Reports you can use</h6></div>
                <ul class="list-group list-group-flush">
                    @foreach($available as $key => $report)
                    <li class="list-group-item">
                        <div class="fw-medium">{{ $report->label() }}</div>
                        <div class="small text-muted">{{ $report->description() }}</div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="col-lg-5">
            {{-- Subscribe --}}
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Get a report by email</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('reports.scheduled.store') }}" class="js-report-form">
                        @csrf
                        @include('reports._report-fields', ['prefix' => 'sub'])
                        <div class="mb-3">
                            <label class="form-label" for="sub-frequency">How often</label>
                            <select id="sub-frequency" name="frequency" class="form-select">
                                @foreach($frequencies as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-primary w-100">Subscribe</button>
                        <div class="form-text">Sent as a CSV file to {{ auth()->user()->email }}, which opens in Excel.</div>
                    </form>
                </div>
            </div>

            {{-- Download now --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Download now</h6></div>
                <div class="card-body">
                    <form method="GET" action="{{ route('reports.scheduled.download') }}" class="js-report-form">
                        @include('reports._report-fields', ['prefix' => 'dl'])
                        <div class="mb-3">
                            <label class="form-label" for="dl-period">Period</label>
                            <select id="dl-period" name="period" class="form-select">
                                @foreach($periods as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="btn btn-outline-primary w-100"><i class="fi fi-rr-download me-1"></i> Download CSV</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Only show the department filter for reports that use it.
document.querySelectorAll('.js-report-form').forEach(function (form) {
    const report = form.querySelector('[name="report"]');
    const dept   = form.querySelector('.js-dept');
    const sync   = () => {
        const supports = report.selectedOptions[0]?.dataset.department === '1';
        dept.classList.toggle('d-none', !supports);
        if (!supports) dept.querySelector('select').value = '';
    };
    report.addEventListener('change', sync);
    sync();
});
</script>
@endpush
</x-app-layout>
