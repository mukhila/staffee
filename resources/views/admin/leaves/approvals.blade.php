<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Approval Dashboard</h1>
            <span>Leave requests awaiting your action</span>
        </div>
        <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-list me-1"></i> All Requests
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @if($pending->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fi fi-rr-check-circle text-success" style="font-size:3rem"></i>
            <h5 class="mt-3 text-muted">All caught up!</h5>
            <p class="text-muted">No leave requests are pending your approval.</p>
        </div>
    </div>
    @else
    <div class="row g-3">
        @foreach($pending as $leave)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="fw-semibold">{{ $leave->user->name }}</span>
                        <small class="text-muted d-block">{{ $leave->user->department?->name }}</small>
                    </div>
                    <span class="badge bg-{{ $leave->status_color }}">{{ $leave->status_label }}</span>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-2 align-items-center">
                        @if($leave->leaveType)
                        <span class="badge" style="background-color: {{ $leave->leaveType->color }}">{{ $leave->leaveType->name }}</span>
                        @else
                        <span class="badge bg-secondary">{{ ucfirst($leave->type) }}</span>
                        @endif
                        <span class="text-muted small">{{ $leave->days }} {{ $leave->half_day ? '½ day' : Str::plural('day', $leave->days) }}</span>
                    </div>
                    <div class="text-muted small mb-2">
                        <i class="fi fi-rr-calendar me-1"></i>
                        {{ $leave->from_date->format('d M Y') }}
                        @if(!$leave->from_date->eq($leave->to_date))
                        → {{ $leave->to_date->format('d M Y') }}
                        @endif
                    </div>
                    <p class="small mb-3 text-truncate" title="{{ $leave->reason }}">{{ $leave->reason }}</p>

                    <div class="d-flex gap-2">
                        @if($leave->status === 'pending')
                        <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fi fi-rr-check me-1"></i> Approve
                            </button>
                        </form>
                        @elseif($leave->status === 'manager_approved')
                        <form method="POST" action="{{ route('admin.leaves.hr-approve', $leave) }}" class="flex-fill">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="fi fi-rr-check me-1"></i> HR Approve
                            </button>
                        </form>
                        @endif

                        <button type="button" class="btn btn-outline-danger btn-sm"
                                data-bs-toggle="modal" data-bs-target="#rejectModal{{ $leave->id }}">
                            <i class="fi fi-rr-cross"></i>
                        </button>
                        <a href="{{ route('admin.leaves.show', $leave) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fi fi-rr-eye"></i>
                        </a>
                    </div>
                </div>
                <div class="card-footer text-muted small">
                    Submitted {{ $leave->created_at->diffForHumans() }}
                </div>
            </div>

            {{-- Reject Modal --}}
            <div class="modal fade" id="rejectModal{{ $leave->id }}" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('admin.leaves.reject', $leave) }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Reject — {{ $leave->user->name }}</h5>
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
        </div>
        @endforeach
    </div>
    @endif
</div>
</x-app-layout>
