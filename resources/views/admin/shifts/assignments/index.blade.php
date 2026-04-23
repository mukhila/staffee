<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Shift Assignments</h1>
            <span>Employee–shift mapping history</span>
        </div>
        <a href="{{ route('admin.shifts.assignments.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-user-add me-1"></i> Assign Shift
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Employee name…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="shift_id" class="form-select">
                    <option value="">All Shifts</option>
                    @foreach($shifts as $s)
                    <option value="{{ $s->id }}" {{ request('shift_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['active'=>'Active','superseded'=>'Superseded','cancelled'=>'Cancelled'] as $v => $l)
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
                            <th>Shift</th>
                            <th>Effective From</th>
                            <th>Effective To</th>
                            <th>Assigned By</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assignments as $a)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $a->user->name }}</div>
                                <div class="text-muted small">{{ $a->user->department?->name }}</div>
                            </td>
                            <td>
                                <span class="me-1" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $a->shift->color }};"></span>
                                {{ $a->shift->name }}
                            </td>
                            <td>{{ $a->effective_from->format('d M Y') }}</td>
                            <td>{{ $a->effective_to ? $a->effective_to->format('d M Y') : '<span class="text-success small">Ongoing</span>' }}</td>
                            <td class="text-muted small">{{ $a->assignedBy?->name }}</td>
                            <td>
                                <span class="badge bg-{{ $a->status_color }}-subtle text-{{ $a->status_color }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td>
                                @if($a->status === 'active')
                                <form action="{{ route('admin.shifts.assignments.destroy', $a) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this assignment?')">Cancel</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">No assignments found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($assignments->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $assignments->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
