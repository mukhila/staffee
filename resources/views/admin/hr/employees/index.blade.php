<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Employees</h1>
            <span>HR profiles for all active employees</span>
        </div>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-users me-1"></i> Staff Management
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Name, ID or email…" value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    @foreach(['active'=>'Active','probation'=>'Probation','notice_period'=>'Notice Period','terminated'=>'Terminated'] as $val => $label)
                    <option value="{{ $val }}" {{ request('status') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    @php
    $statusColors = [
        'active'        => 'success',
        'probation'     => 'warning',
        'notice_period' => 'danger',
        'terminated'    => 'secondary',
        'inactive'      => 'secondary',
    ];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>ID</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Joining Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($emp->avatar)
                                    <img src="{{ asset('storage/' . $emp->avatar) }}" class="rounded-circle" width="36" height="36">
                                    @else
                                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:.8rem;">
                                        {{ strtoupper(substr($emp->name, 0, 2)) }}
                                    </div>
                                    @endif
                                    <div>
                                        <div class="fw-medium">{{ $emp->name }}</div>
                                        <div class="text-muted small">{{ $emp->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted small font-monospace">{{ $emp->employee_id ?? '—' }}</td>
                            <td>{{ $emp->department?->name ?? '—' }}</td>
                            <td>{{ $emp->designation ?? ucfirst($emp->role) }}</td>
                            <td>
                                @if($emp->profile?->joining_date)
                                <span title="{{ $emp->profile->joining_date->format('d M Y') }}">
                                    {{ $emp->profile->joining_date->format('d M Y') }}
                                </span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @php $s = $emp->employment_status ?? 'active'; $c = $statusColors[$s] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">
                                    {{ ucwords(str_replace('_', ' ', $s)) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hr.employees.show', $emp) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fi fi-rr-user me-1"></i> HR Profile
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fi fi-rr-users fs-3 d-block mb-2 opacity-25"></i>
                                No employees found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($employees->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $employees->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
