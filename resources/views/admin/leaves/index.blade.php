<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Requests</h1>
            <span>Manage employee leave applications</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.leaves.approvals') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-check me-1"></i> Approvals
            </a>
            <a href="{{ route('admin.leaves.calendar') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-calendar me-1"></i> Calendar
            </a>
            <a href="{{ route('admin.leaves.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-chart-line me-1"></i> Reports
            </a>
            <a href="{{ route('admin.leaves.types.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-settings me-1"></i> Types
            </a>
            <a href="{{ route('admin.leaves.policies.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-document me-1"></i> Policies
            </a>
            <a href="{{ route('admin.leaves.balances.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-calculator me-1"></i> Balances
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\LeaveRequest::STATUS_LABELS as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Period</th>
                            <th>Days</th>
                            <th>Status</th>
                            <th>Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaves as $leave)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $leave->user->name }}</div>
                                <small class="text-muted">{{ $leave->user->department?->name }}</small>
                            </td>
                            <td>
                                @if($leave->leaveType)
                                <span class="badge" style="background-color: {{ $leave->leaveType->color }}">{{ $leave->leaveType->name }}</span>
                                @else
                                <span class="badge bg-secondary">{{ ucfirst($leave->type) }}</span>
                                @endif
                            </td>
                            <td>
                                <span>{{ $leave->from_date->format('d M Y') }}</span>
                                @if(!$leave->from_date->eq($leave->to_date))
                                <span class="text-muted"> → {{ $leave->to_date->format('d M Y') }}</span>
                                @endif
                                @if($leave->half_day)
                                <small class="badge bg-light text-dark">½ {{ $leave->half_day_period }}</small>
                                @endif
                            </td>
                            <td><strong>{{ $leave->days }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $leave->status_color }}">{{ $leave->status_label }}</span>
                            </td>
                            <td>{{ $leave->created_at->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.leaves.show', $leave) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fi fi-rr-eye"></i>
                                </a>
                                @if($leave->status === 'pending')
                                <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}">Reject</button>
                                @elseif($leave->status === 'manager_approved')
                                <form method="POST" action="{{ route('admin.leaves.hr-approve', $leave) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success">HR Approve</button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}">Reject</button>
                                @endif
                            </td>
                        </tr>

                        {{-- Reject Modal --}}
                        <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
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
                                            <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">No leave requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($leaves->hasPages())
        <div class="card-footer">{{ $leaves->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
