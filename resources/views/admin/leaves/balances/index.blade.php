<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Balances</h1>
            <span>Employee leave entitlements for {{ $year }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#accrualModal">
                <i class="fi fi-rr-refresh me-1"></i> Run Accrual
            </button>
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
            <div class="col-auto">
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2020" max="{{ now()->year + 1 }}" style="width:90px">
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="leave_type" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ request('leave_type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                <a href="{{ route('admin.leaves.balances.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th class="text-end">Opening</th>
                            <th class="text-end">Accrued</th>
                            <th class="text-end">Carry Fwd</th>
                            <th class="text-end text-danger">Used</th>
                            <th class="text-end text-warning">Pending</th>
                            <th class="text-end text-success">Available</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balances as $balance)
                        <tr>
                            <td><strong>{{ $balance->user->name }}</strong></td>
                            <td><small class="text-muted">{{ $balance->user->department?->name ?? '—' }}</small></td>
                            <td>
                                <span class="badge" style="background-color: {{ $balance->leaveType->color }}">
                                    {{ $balance->leaveType->name }}
                                </span>
                            </td>
                            <td class="text-end">{{ $balance->opening_balance }}</td>
                            <td class="text-end">{{ $balance->accrued_days }}</td>
                            <td class="text-end">{{ $balance->carry_forward_days }}</td>
                            <td class="text-end text-danger">{{ $balance->used_days }}</td>
                            <td class="text-end text-warning">{{ $balance->pending_days }}</td>
                            <td class="text-end fw-bold text-success">{{ $balance->effective_available }}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#adjustModal"
                                        data-user="{{ $balance->user_id }}"
                                        data-type="{{ $balance->leave_type_id }}"
                                        data-year="{{ $balance->year }}"
                                        data-name="{{ $balance->user->name }}"
                                        data-typename="{{ $balance->leaveType->name }}">
                                    <i class="fi fi-rr-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-5 text-muted">No balance records for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($balances->hasPages())
        <div class="card-footer">{{ $balances->links() }}</div>
        @endif
    </div>
</div>

{{-- Adjust Balance Modal --}}
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.leaves.balances.adjust') }}">
            @csrf
            <input type="hidden" name="user_id" id="adj_user_id">
            <input type="hidden" name="leave_type_id" id="adj_type_id">
            <input type="hidden" name="year" id="adj_year">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3" id="adj_label"></p>
                    <div class="mb-3">
                        <label class="form-label">Field to Adjust</label>
                        <select name="field" class="form-select" required>
                            <option value="opening_balance">Opening Balance</option>
                            <option value="accrued_days">Accrued Days</option>
                            <option value="used_days">Used Days</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Value</label>
                        <input type="number" name="value" class="form-control" min="0" step="0.5" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Save Adjustment</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Run Accrual Modal --}}
<div class="modal fade" id="accrualModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.leaves.balances.run-accrual') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Run Manual Accrual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">This will credit accrual entries for all eligible employees as of the selected date. Duplicate entries for the same period are skipped automatically.</p>
                    <div class="mb-3">
                        <label class="form-label">Accrual Date</label>
                        <input type="date" name="date" class="form-control" value="{{ now()->toDateString() }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Run Accrual</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('adjustModal').addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('adj_user_id').value = btn.dataset.user;
    document.getElementById('adj_type_id').value = btn.dataset.type;
    document.getElementById('adj_year').value     = btn.dataset.year;
    document.getElementById('adj_label').textContent = btn.dataset.name + ' — ' + btn.dataset.typename + ' (' + btn.dataset.year + ')';
});
</script>
</x-app-layout>
