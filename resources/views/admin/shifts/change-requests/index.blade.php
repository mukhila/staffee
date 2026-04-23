<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Shift Change Requests</h1>
            <span>Review and act on employee shift change / swap requests</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $v => $l)
                    <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Current Shift</th>
                            <th>Requested Shift</th>
                            <th>Effective Date</th>
                            <th>Swap With</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $cr)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $cr->requester->name }}</div>
                                <div class="text-muted small">{{ $cr->requester->department?->name }}</div>
                            </td>
                            <td>{{ $cr->currentShift->name }}</td>
                            <td class="text-success fw-medium">{{ $cr->requestedShift->name }}</td>
                            <td>{{ $cr->effective_date->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $cr->swapWithUser?->name ?? '—' }}</td>
                            <td class="small text-muted" style="max-width:200px;">{{ Str::limit($cr->reason, 60) }}</td>
                            <td>
                                <span class="badge bg-{{ $cr->status_color }}-subtle text-{{ $cr->status_color }}">{{ ucfirst($cr->status) }}</span>
                            </td>
                            <td class="text-end">
                                @if($cr->isPending())
                                <div class="d-flex gap-1 justify-content-end">
                                    <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal{{ $cr->id }}">Approve</button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $cr->id }}">Reject</button>
                                </div>
                                @else
                                <span class="text-muted small">{{ $cr->reviewedBy?->name }}</span>
                                @endif
                            </td>
                        </tr>

                        @if($cr->isPending())
                        {{-- Approve Modal --}}
                        <div class="modal fade" id="approveModal{{ $cr->id }}" tabindex="-1">
                            <div class="modal-dialog modal-sm"><div class="modal-content">
                                <form action="{{ route('admin.shifts.change-requests.approve', $cr) }}" method="POST">
                                    @csrf
                                    <div class="modal-header"><h6 class="modal-title">Approve Request</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <p class="text-muted small mb-2">{{ $cr->requester->name }}: {{ $cr->currentShift->name }} → {{ $cr->requestedShift->name }} from {{ $cr->effective_date->format('d M Y') }}</p>
                                        <textarea name="manager_notes" class="form-control form-control-sm" rows="2" placeholder="Approval notes (optional)"></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">Approve & Apply</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>

                        {{-- Reject Modal --}}
                        <div class="modal fade" id="rejectModal{{ $cr->id }}" tabindex="-1">
                            <div class="modal-dialog modal-sm"><div class="modal-content">
                                <form action="{{ route('admin.shifts.change-requests.reject', $cr) }}" method="POST">
                                    @csrf
                                    <div class="modal-header"><h6 class="modal-title">Reject Request</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <textarea name="manager_notes" class="form-control form-control-sm" rows="3" placeholder="Reason for rejection…" required></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>
                        @endif
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">No shift change requests found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($requests->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
