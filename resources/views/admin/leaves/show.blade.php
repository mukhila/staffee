<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Request #{{ $leave->id }}</h1>
            <span>{{ $leave->user->name }} — {{ $leave->type_label }}</span>
        </div>
        <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-3">
        {{-- Request Details --}}
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Request Details</h6>
                    <span class="badge bg-{{ $leave->status_color }}">{{ $leave->status_label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Employee</small>
                            <strong>{{ $leave->user->name }}</strong>
                            <small class="text-muted d-block">{{ $leave->user->department?->name }}</small>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Leave Type</small>
                            @if($leave->leaveType)
                            <span class="badge" style="background-color: {{ $leave->leaveType->color }}; font-size: 0.9rem">{{ $leave->leaveType->name }}</span>
                            <small class="d-block text-muted">{{ $leave->leaveType->category_label }} — {{ $leave->leaveType->is_paid ? 'Paid' : 'Unpaid' }}</small>
                            @else
                            <strong>{{ ucfirst($leave->type) }}</strong>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">From Date</small>
                            <strong>{{ $leave->from_date->format('d M Y, D') }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">To Date</small>
                            <strong>{{ $leave->to_date->format('d M Y, D') }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Duration</small>
                            <strong>{{ $leave->days }} {{ $leave->half_day ? '½ day' : Str::plural('day', $leave->days) }}</strong>
                            @if($leave->half_day)
                            <small class="text-muted d-block">{{ ucfirst($leave->half_day_period) }}</small>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Submitted</small>
                            <strong>{{ $leave->created_at->format('d M Y, H:i') }}</strong>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Reason</small>
                            <p class="mb-0">{{ $leave->reason }}</p>
                        </div>
                        @if($leave->rejection_reason)
                        <div class="col-12">
                            <small class="text-muted d-block">Rejection Reason</small>
                            <p class="mb-0 text-danger">{{ $leave->rejection_reason }}</p>
                        </div>
                        @endif
                        @if($leave->cancelled_reason)
                        <div class="col-12">
                            <small class="text-muted d-block">Cancellation Reason</small>
                            <p class="mb-0 text-secondary">{{ $leave->cancelled_reason }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Approval Timeline --}}
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Approval Timeline</h6></div>
                <div class="card-body">
                    @if($leave->auto_approved)
                    <div class="d-flex gap-3 align-items-start">
                        <div class="rounded-circle bg-success d-flex align-items-center justify-content-center" style="width:36px;height:36px;flex-shrink:0">
                            <i class="fi fi-rr-check text-white"></i>
                        </div>
                        <div>
                            <div class="fw-medium">Auto-approved</div>
                            <small class="text-muted">Request was auto-approved per policy (short leave)</small>
                        </div>
                    </div>
                    @else
                    <div class="timeline">
                        {{-- Manager step --}}
                        @php $managerApproval = $leave->approvals->firstWhere('level', 1); @endphp
                        <div class="d-flex gap-3 align-items-start mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                bg-{{ $managerApproval ? ($managerApproval->action === 'approved' ? 'success' : 'danger') : ($leave->status === 'pending' ? 'warning' : 'light') }}"
                                style="width:36px;height:36px">
                                <i class="fi fi-rr-{{ $managerApproval ? ($managerApproval->action === 'approved' ? 'check' : 'cross') : 'clock' }} text-{{ $managerApproval ? 'white' : 'muted' }}"></i>
                            </div>
                            <div>
                                <div class="fw-medium">Manager Review</div>
                                @if($managerApproval)
                                <small class="text-muted">{{ $managerApproval->approver->name }} — {{ $managerApproval->acted_at->format('d M Y, H:i') }}</small>
                                @if($managerApproval->notes)<small class="d-block text-muted">{{ $managerApproval->notes }}</small>@endif
                                @elseif($leave->status === 'pending')
                                <small class="text-muted">Awaiting manager review</small>
                                @endif
                            </div>
                        </div>

                        {{-- HR step --}}
                        @php $hrApproval = $leave->approvals->firstWhere('level', 2); @endphp
                        <div class="d-flex gap-3 align-items-start">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                bg-{{ $hrApproval ? ($hrApproval->action === 'approved' ? 'success' : 'danger') : ($leave->status === 'manager_approved' ? 'warning' : 'light') }}"
                                style="width:36px;height:36px">
                                <i class="fi fi-rr-{{ $hrApproval ? ($hrApproval->action === 'approved' ? 'check' : 'cross') : 'clock' }} text-{{ $hrApproval ? 'white' : 'muted' }}"></i>
                            </div>
                            <div>
                                <div class="fw-medium">HR Review</div>
                                @if($hrApproval)
                                <small class="text-muted">{{ $hrApproval->approver->name }} — {{ $hrApproval->acted_at->format('d M Y, H:i') }}</small>
                                @if($hrApproval->notes)<small class="d-block text-muted">{{ $hrApproval->notes }}</small>@endif
                                @elseif($leave->status === 'manager_approved')
                                <small class="text-muted">Awaiting HR review</small>
                                @else
                                <small class="text-muted">Not yet reached</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Actions sidebar --}}
        <div class="col-md-4">
            @if(in_array($leave->status, ['pending', 'manager_approved']))
            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Actions</h6></div>
                <div class="card-body d-grid gap-2">
                    @if($leave->status === 'pending')
                    <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}">
                        @csrf
                        <div class="mb-2">
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fi fi-rr-check me-1"></i> Approve (Manager)
                        </button>
                    </form>
                    @elseif($leave->status === 'manager_approved')
                    <form method="POST" action="{{ route('admin.leaves.hr-approve', $leave) }}">
                        @csrf
                        <div class="mb-2">
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fi fi-rr-check me-1"></i> HR Approve
                        </button>
                    </form>
                    @endif

                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fi fi-rr-cross me-1"></i> Reject
                    </button>
                </div>
            </div>
            @endif

            {{-- Employee Balance Card --}}
            @if($leave->leaveType)
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Employee Balance ({{ now()->year }})</h6></div>
                <div class="card-body">
                    @php
                    $balance = \App\Models\Leave\LeaveBalance::where('user_id', $leave->user_id)
                        ->where('leave_type_id', $leave->leave_type_id)
                        ->where('year', now()->year)
                        ->first();
                    @endphp
                    @if($balance)
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">Opening</td><td class="text-end fw-medium">{{ $balance->opening_balance }}</td></tr>
                        <tr><td class="text-muted">Accrued</td><td class="text-end fw-medium">{{ $balance->accrued_days }}</td></tr>
                        <tr><td class="text-muted">Carry Fwd</td><td class="text-end fw-medium">{{ $balance->carry_forward_days }}</td></tr>
                        <tr><td class="text-muted">Used</td><td class="text-end fw-medium text-danger">{{ $balance->used_days }}</td></tr>
                        <tr><td class="text-muted">Pending</td><td class="text-end fw-medium text-warning">{{ $balance->pending_days }}</td></tr>
                        <tr class="border-top"><td class="fw-bold">Available</td><td class="text-end fw-bold text-success">{{ $balance->effective_available }}</td></tr>
                    </table>
                    @else
                    <p class="text-muted mb-0 small">No balance record found.</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.leaves.reject', $leave) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea name="rejection_reason" class="form-control" rows="3" required placeholder="Provide a reason for rejection..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
