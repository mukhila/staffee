<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Payroll Adjustment</h1>
            <span>{{ $adjustment->employee?->name }} — {{ $adjustment->definition?->name }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @php
            $sc = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','applied'=>'info','cancelled'=>'secondary'][$adjustment->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $sc }} fs-6">{{ ucfirst($adjustment->status) }}</span>
            <a href="{{ route('admin.payroll.adjustments.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal ps-0 small">Employee</th><td class="fw-medium">{{ $adjustment->employee?->name }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Department</th><td>{{ $adjustment->employee?->department?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Component</th><td>{{ $adjustment->definition?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Type</th>
                            <td><span class="badge bg-{{ $adjustment->adjustment_type === 'addition' ? 'success' : 'danger' }}-subtle text-{{ $adjustment->adjustment_type === 'addition' ? 'success' : 'danger' }}">{{ ucfirst($adjustment->adjustment_type) }}</span></td>
                        </tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Amount</th><td class="fw-bold">{{ number_format($adjustment->amount, 2) }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Recurrence</th><td>{{ ucwords(str_replace('-',' ',$adjustment->recurrence)) }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Start Period</th><td>{{ \Carbon\Carbon::parse($adjustment->start_period)->format('M Y') }}</td></tr>
                        @if($adjustment->end_period)
                        <tr><th class="text-muted fw-normal ps-0 small">End Period</th><td>{{ \Carbon\Carbon::parse($adjustment->end_period)->format('M Y') }}</td></tr>
                        @endif
                        @if($adjustment->remaining_installments)
                        <tr><th class="text-muted fw-normal ps-0 small">Installments</th><td>{{ $adjustment->remaining_installments }}</td></tr>
                        @endif
                        <tr><th class="text-muted fw-normal ps-0 small">Submitted By</th><td>{{ $adjustment->creator?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Submitted On</th><td>{{ $adjustment->created_at->format('d M Y') }}</td></tr>
                        @if($adjustment->approver)
                        <tr><th class="text-muted fw-normal ps-0 small">Reviewed By</th><td>{{ $adjustment->approver?->name }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0 small">Reviewed On</th><td>{{ $adjustment->approved_at?->format('d M Y') }}</td></tr>
                        @endif
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Reason</h6></div>
                <div class="card-body"><p class="small mb-0">{{ $adjustment->reason }}</p></div>
            </div>

            @if($adjustment->status === 'pending')
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Decision</h6></div>
                <div class="card-body d-flex flex-column gap-2">
                    <form action="{{ route('admin.payroll.adjustments.approve', $adjustment) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success w-100 mb-2" onclick="return confirm('Approve this payroll adjustment?')">
                            <i class="fi fi-rr-check me-1"></i> Approve
                        </button>
                    </form>
                    <form action="{{ route('admin.payroll.adjustments.reject', $adjustment) }}" method="POST" id="rejectForm">
                        @csrf
                        <div class="mb-2">
                            <textarea name="notes" class="form-control form-control-sm" rows="2" required placeholder="Rejection reason (required)..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Reject this adjustment?')">
                            <i class="fi fi-rr-cross me-1"></i> Reject
                        </button>
                    </form>
                    <form action="{{ route('admin.payroll.adjustments.cancel', $adjustment) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100 btn-sm" onclick="return confirm('Cancel this adjustment?')">Cancel</button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Approval Timeline</h6></div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-4">
                        <div class="d-flex flex-column align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#316AFF20;">
                                <i class="fi fi-rr-paper-plane text-primary small"></i>
                            </div>
                            <div style="width:2px;flex:1;min-height:24px;background:#dee2e6;margin-top:4px;"></div>
                        </div>
                        <div class="pb-3">
                            <div class="fw-medium">Submitted</div>
                            <div class="text-muted small">{{ $adjustment->creator?->name }} · {{ $adjustment->created_at->format('d M Y, H:i') }}</div>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:{{ $adjustment->status === 'rejected' ? '#dc354520' : ($adjustment->status === 'approved' || $adjustment->status === 'applied' ? '#19875420' : '#6c757d20') }}">
                            @if($adjustment->status === 'approved' || $adjustment->status === 'applied')
                            <i class="fi fi-rr-check text-success small"></i>
                            @elseif($adjustment->status === 'rejected')
                            <i class="fi fi-rr-cross text-danger small"></i>
                            @else
                            <i class="fi fi-rr-clock text-muted small"></i>
                            @endif
                        </div>
                        <div>
                            <div class="fw-medium {{ $adjustment->status === 'rejected' ? 'text-danger' : ($adjustment->status === 'pending' ? 'text-muted' : '') }}">
                                @if($adjustment->status === 'pending') Awaiting Review
                                @elseif($adjustment->status === 'approved') Approved
                                @elseif($adjustment->status === 'applied') Approved & Applied to Payroll
                                @elseif($adjustment->status === 'rejected') Rejected
                                @else {{ ucfirst($adjustment->status) }}
                                @endif
                            </div>
                            @if($adjustment->approver)
                            <div class="text-muted small">{{ $adjustment->approver->name }} · {{ $adjustment->approved_at?->format('d M Y, H:i') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
