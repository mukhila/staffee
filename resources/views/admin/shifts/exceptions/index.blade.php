<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Attendance Exceptions</h1>
            <span>Review and approve shift-attendance violations</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Employee name…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($types as $val => $label)
                    <option value="{{ $val }}" {{ request('type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','auto_approved'=>'Auto-Approved'] as $v => $l)
                    <option value="{{ $v }}" {{ request('status') == $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date" class="form-control" value="{{ request('date') }}" placeholder="Date">
            </div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Filter</button></div>
            <div class="col-md-2">
                <form action="{{ route('admin.shifts.exceptions.bulk-approve') }}" method="POST" id="bulkForm">
                    @csrf
                    <button type="submit" class="btn btn-outline-success w-100" onclick="return confirm('Approve all selected?')">
                        Bulk Approve
                    </button>
                </form>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Shift</th>
                            <th>Exception</th>
                            <th>Deviation</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($exceptions as $ex)
                        <tr>
                            <td>
                                @if($ex->isPending())
                                <input type="checkbox" class="form-check-input row-check" name="ids[]" value="{{ $ex->id }}" form="bulkForm">
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium">{{ $ex->user->name }}</div>
                                <div class="text-muted small">{{ $ex->user->department?->name }}</div>
                            </td>
                            <td>{{ $ex->date->format('d M Y') }}</td>
                            <td class="text-muted small">{{ $ex->shift->name }}</td>
                            <td>
                                <span class="badge bg-{{ $ex->type_color }}-subtle text-{{ $ex->type_color }}">
                                    {{ $ex->type_label }}
                                </span>
                            </td>
                            <td>
                                @if($ex->exception_type === 'overtime')
                                <span class="text-info">+{{ $ex->overtime_minutes }}m OT</span>
                                @elseif($ex->deviation_minutes)
                                <span class="{{ $ex->exception_type === 'late_arrival' ? 'text-warning' : 'text-muted' }}">
                                    {{ $ex->deviation_minutes }}m
                                </span>
                                @else
                                —
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ex->status_color }}-subtle text-{{ $ex->status_color }}">
                                    {{ ucwords(str_replace('_',' ',$ex->status)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($ex->isPending())
                                <div class="d-flex gap-1 justify-content-end">
                                    <form action="{{ route('admin.shifts.exceptions.approve', $ex) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Approve</button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $ex->id }}">Reject</button>
                                </div>
                                @else
                                <span class="text-muted small">{{ $ex->reviewedBy?->name }}</span>
                                @endif
                            </td>
                        </tr>

                        {{-- Reject modal --}}
                        @if($ex->isPending())
                        <div class="modal fade" id="rejectModal{{ $ex->id }}" tabindex="-1">
                            <div class="modal-dialog modal-sm"><div class="modal-content">
                                <form action="{{ route('admin.shifts.exceptions.reject', $ex) }}" method="POST">
                                    @csrf
                                    <div class="modal-header"><h6 class="modal-title">Reject Exception</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                    <div class="modal-body">
                                        <textarea name="manager_notes" class="form-control" rows="3" placeholder="Reason for rejection…" required></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="submit" class="btn btn-danger">Reject</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>
                        @endif
                        @empty
                        <tr><td colspan="8" class="text-center text-muted py-5">
                            <i class="fi fi-rr-check-circle fs-3 d-block mb-2 opacity-25"></i>
                            No exceptions found.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($exceptions->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $exceptions->links() }}</div>
        @endif
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
});
</script>
</x-app-layout>
