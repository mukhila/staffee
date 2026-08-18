<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Benefit Deduction #{{ $deduction->id }}</h1>
            <span>{{ $deduction->user?->name }} &mdash; {{ $deduction->benefit_name }}</span>
        </div>
        <div class="d-flex gap-2">
            @if($deduction->status === 'active')
            <form action="{{ route('admin.payroll.benefit-deductions.pause', $deduction) }}" method="POST"
                  onsubmit="return confirm('Pause this deduction?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-warning">
                    <i class="fi fi-rr-pause me-1"></i> Pause
                </button>
            </form>
            @endif

            @if($deduction->status === 'paused')
            <form action="{{ route('admin.payroll.benefit-deductions.resume', $deduction) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success">
                    <i class="fi fi-rr-play me-1"></i> Resume
                </button>
            </form>
            @endif

            @if($deduction->status !== 'terminated')
            <form action="{{ route('admin.payroll.benefit-deductions.terminate', $deduction) }}" method="POST"
                  onsubmit="return confirm('Terminate this deduction? This will set effective_to to today.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fi fi-rr-ban me-1"></i> Terminate
                </button>
            </form>
            @endif

            <a href="{{ route('admin.payroll.benefit-deductions.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    @php
    $statusColors = ['active'=>'success','paused'=>'warning','terminated'=>'secondary'];
    $sc = $statusColors[$deduction->status] ?? 'secondary';
    @endphp

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Monthly Amount</div>
                    <div class="fw-bold fs-5">{{ number_format($deduction->amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Frequency</div>
                    <div class="fw-bold fs-5">{{ ucfirst($deduction->frequency) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Effective Period</div>
                    <div class="fw-medium">
                        {{ $deduction->effective_from?->format('d M Y') }}
                        &mdash;
                        {{ $deduction->effective_to?->format('d M Y') ?? 'Ongoing' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Status</div>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fs-6">{{ ucfirst($deduction->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail card --}}
    <div class="card">
        <div class="card-header fw-semibold">Deduction Details</div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3 text-muted small">Employee</div>
                <div class="col-md-9">{{ $deduction->user?->name }}</div>

                <div class="col-md-3 text-muted small">Benefit Name</div>
                <div class="col-md-9">{{ $deduction->benefit_name }}</div>

                <div class="col-md-3 text-muted small">Benefit Type</div>
                <div class="col-md-9">{{ ucfirst(str_replace('_', ' ', $deduction->benefit_type)) }}</div>

                <div class="col-md-3 text-muted small">Amount</div>
                <div class="col-md-9">{{ number_format($deduction->amount, 2) }}</div>

                <div class="col-md-3 text-muted small">Frequency</div>
                <div class="col-md-9">{{ ucfirst($deduction->frequency) }}</div>

                <div class="col-md-3 text-muted small">Effective From</div>
                <div class="col-md-9">{{ $deduction->effective_from?->format('d M Y') }}</div>

                <div class="col-md-3 text-muted small">Effective To</div>
                <div class="col-md-9">{{ $deduction->effective_to?->format('d M Y') ?? '— (Ongoing)' }}</div>

                <div class="col-md-3 text-muted small">Created By</div>
                <div class="col-md-9">{{ $deduction->createdBy?->name ?? '—' }}</div>

                <div class="col-md-3 text-muted small">Created At</div>
                <div class="col-md-9">{{ $deduction->created_at?->format('d M Y, H:i') }}</div>

                @if($deduction->notes)
                <div class="col-md-3 text-muted small">Notes</div>
                <div class="col-md-9">{{ $deduction->notes }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
</x-app-layout>
