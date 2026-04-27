<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Transfer: {{ $transfer->employee->name }}</h1>
            <span>{{ $transfer->fromDepartment?->name ?? '—' }} → {{ $transfer->toDepartment?->name ?? '—' }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            @php
            $colors = ['pending'=>'warning','approved'=>'success','rejected'=>'danger'];
            $c = $colors[$transfer->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $c }} fs-6">{{ ucfirst($transfer->status) }}</span>
            <a href="{{ route('admin.hr.transfers.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        {{-- Left panel --}}
        <div class="col-lg-4">

            {{-- Employee card --}}
            <div class="card mb-3">
                <div class="card-body text-center py-4">
                    @if($transfer->employee->avatar)
                    <img src="{{ asset('storage/' . $transfer->employee->avatar) }}" class="rounded-circle mb-3" width="72" height="72" style="object-fit:cover;">
                    @else
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold mx-auto mb-3" style="width:72px;height:72px;font-size:1.25rem;">
                        {{ strtoupper(substr($transfer->employee->name, 0, 2)) }}
                    </div>
                    @endif
                    <h6 class="mb-1 fw-bold">{{ $transfer->employee->name }}</h6>
                    <div class="text-muted small">{{ $transfer->employee->employee_id }}</div>
                    <div class="text-muted small">{{ $transfer->employee->email }}</div>
                </div>
            </div>

            {{-- Transfer details --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Transfer Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th class="text-muted fw-normal ps-0">Effective Date</th>
                            <td>{{ \Carbon\Carbon::parse($transfer->effective_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0">Requested By</th>
                            <td>{{ $transfer->requestedBy?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0">Submitted</th>
                            <td>{{ $transfer->created_at->format('d M Y') }}</td>
                        </tr>
                        @if($transfer->approver)
                        <tr>
                            <th class="text-muted fw-normal ps-0">{{ ucfirst($transfer->status) }} By</th>
                            <td>{{ $transfer->approver->name }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted fw-normal ps-0">Decision At</th>
                            <td>{{ $transfer->approved_at?->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            {{-- Reason --}}
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Reason</h6></div>
                <div class="card-body">
                    <p class="small mb-0">{{ $transfer->reason }}</p>
                </div>
            </div>

            @if($transfer->notes)
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Notes</h6></div>
                <div class="card-body">
                    <p class="small mb-0 fst-italic text-muted">"{{ $transfer->notes }}"</p>
                </div>
            </div>
            @endif

            {{-- Action buttons --}}
            @if($transfer->status === 'pending')
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Decision</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.hr.transfers.approve', $transfer) }}" method="POST" class="mb-2">
                        @csrf
                        <div class="mb-2">
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Notes (optional)"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('Approve this transfer and update the employee record?')">
                            <i class="fi fi-rr-check me-1"></i> Approve & Apply
                        </button>
                    </form>
                    <form action="{{ route('admin.hr.transfers.reject', $transfer) }}" method="POST">
                        @csrf
                        <div class="mb-2">
                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Rejection reason (required)" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger w-100"
                                onclick="return confirm('Reject this transfer request?')">
                            <i class="fi fi-rr-cross me-1"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>

        {{-- Right panel: before/after comparison --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Department &amp; Role Change</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border" style="background:#f8f9fa;">
                                <div class="text-muted small fw-semibold text-uppercase mb-3">Before</div>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Department</th>
                                        <td class="fw-medium">{{ $transfer->fromDepartment?->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Role</th>
                                        <td>{{ $transfer->from_role ? ucfirst($transfer->from_role) : '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Designation</th>
                                        <td>{{ $transfer->from_designation ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Reporting To</th>
                                        <td>{{ $transfer->fromReportingTo?->name ?? '—' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border border-primary" style="background:#f0f4ff;">
                                <div class="text-primary small fw-semibold text-uppercase mb-3">After Transfer</div>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Department</th>
                                        <td class="fw-medium text-primary">{{ $transfer->toDepartment?->name ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Role</th>
                                        <td class="fw-medium text-primary">{{ $transfer->to_role ? ucfirst($transfer->to_role) : '(same)' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Designation</th>
                                        <td class="fw-medium text-primary">{{ $transfer->to_designation ?? '(same)' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal ps-0 small">Reporting To</th>
                                        <td class="fw-medium text-primary">{{ $transfer->toReportingTo?->name ?? '(same)' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Status timeline --}}
                    <div class="mt-4">
                        <h6 class="text-muted small fw-semibold text-uppercase mb-3">Timeline</h6>
                        <div class="d-flex gap-3 mb-3">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:#316AFF20;">
                                    <i class="fi fi-rr-paper-plane text-primary small"></i>
                                </div>
                                <div style="width:2px;flex:1;min-height:24px;background:#dee2e6;margin-top:4px;"></div>
                            </div>
                            <div class="pb-3">
                                <div class="fw-medium">Request Submitted</div>
                                <div class="text-muted small">{{ $transfer->requestedBy?->name }} · {{ $transfer->created_at->format('d M Y, H:i') }}</div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <div class="d-flex flex-column align-items-center">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:32px;height:32px;background:{{ $transfer->status === 'rejected' ? '#dc354520' : ($transfer->status === 'approved' ? '#19875420' : '#6c757d20') }}">
                                    @if($transfer->status === 'approved')
                                    <i class="fi fi-rr-check text-success small"></i>
                                    @elseif($transfer->status === 'rejected')
                                    <i class="fi fi-rr-cross text-danger small"></i>
                                    @else
                                    <i class="fi fi-rr-clock text-muted small"></i>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <div class="fw-medium {{ $transfer->status === 'approved' ? '' : ($transfer->status === 'rejected' ? 'text-danger' : 'text-muted') }}">
                                    @if($transfer->status === 'approved') Transfer Approved &amp; Applied
                                    @elseif($transfer->status === 'rejected') Request Rejected
                                    @else Awaiting Review
                                    @endif
                                </div>
                                @if($transfer->approver)
                                <div class="text-muted small">{{ $transfer->approver->name }} · {{ $transfer->approved_at?->format('d M Y, H:i') }}</div>
                                @endif
                                @if($transfer->notes)
                                <div class="text-muted small fst-italic mt-1">"{{ $transfer->notes }}"</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
