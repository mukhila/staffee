<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Loan #{{ $loan->id }}</h1>
            <span>{{ $loan->user?->name }} &mdash; {{ $loan->loan_type }}</span>
        </div>
        <div class="d-flex gap-2">
            @if(in_array($loan->status, ['active', 'hold']))
            <form action="{{ route('admin.payroll.loans.cancel', $loan) }}" method="POST"
                  onsubmit="return confirm('Cancel this loan? This cannot be undone.')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fi fi-rr-ban me-1"></i> Cancel Loan
                </button>
            </form>
            @endif
            <a href="{{ route('admin.payroll.loans.index') }}" class="btn btn-sm btn-outline-secondary">
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
    $statusColors = ['active'=>'success','completed'=>'info','cancelled'=>'secondary','hold'=>'warning'];
    $sc = $statusColors[$loan->status] ?? 'secondary';
    @endphp

    {{-- Loan summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Principal Amount</div>
                    <div class="fw-bold fs-5">{{ number_format($loan->principal_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Remaining Balance</div>
                    <div class="fw-bold fs-5">{{ number_format($loan->remaining_balance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Installment Amount</div>
                    <div class="fw-bold fs-5">{{ number_format($loan->installment_amount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small mb-1">Status</div>
                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }} fs-6">{{ ucfirst($loan->status) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Loan details --}}
    <div class="card mb-4">
        <div class="card-header fw-semibold">Loan Details</div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3 text-muted small">Employee</div>
                <div class="col-md-9">{{ $loan->user?->name }}</div>
                <div class="col-md-3 text-muted small">Loan Type</div>
                <div class="col-md-9">{{ $loan->loan_type }}</div>
                <div class="col-md-3 text-muted small">Issued Date</div>
                <div class="col-md-9">{{ $loan->issued_date?->format('d M Y') }}</div>
                <div class="col-md-3 text-muted small">Recovery Start</div>
                <div class="col-md-9">{{ $loan->recovery_start_period }}</div>
                <div class="col-md-3 text-muted small">Total Installments</div>
                <div class="col-md-9">{{ $loan->total_installments }}</div>
                @if($loan->notes)
                <div class="col-md-3 text-muted small">Notes</div>
                <div class="col-md-9">{{ $loan->notes }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Installment schedule --}}
    <div class="card">
        <div class="card-header fw-semibold">Installment Schedule</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Period</th>
                            <th>Scheduled Amount</th>
                            <th>Recovered Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loan->installments as $index => $inst)
                        @php
                        $instColors = ['pending'=>'warning','processed'=>'success','skipped'=>'secondary','waived'=>'info'];
                        $ic = $instColors[$inst->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="text-muted small">{{ $index + 1 }}</td>
                            <td>{{ $inst->due_period }}</td>
                            <td>{{ number_format($inst->scheduled_amount, 2) }}</td>
                            <td>{{ number_format($inst->recovered_amount, 2) }}</td>
                            <td><span class="badge bg-{{ $ic }}-subtle text-{{ $ic }}">{{ ucfirst($inst->status) }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No installments found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
