<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Compliance</h1>
            <span>Employees using less than {{ $threshold }}% of entitlement in {{ $year }}</span>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" style="width:90px">
                <input type="number" name="threshold" class="form-control form-control-sm" value="{{ $threshold }}" min="1" max="100" style="width:90px" placeholder="% threshold">
                <button class="btn btn-sm btn-secondary">Filter</button>
            </form>
            <a href="{{ route('admin.leaves.reports.index') }}?year={{ $year }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Leave Type</th>
                            <th class="text-end">Entitlement</th>
                            <th class="text-end">Used</th>
                            <th class="text-end">Available</th>
                            <th style="width:180px">Usage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($balances as $balance)
                        @php
                            $max = $balance->leaveType->max_days_per_year ?? 0;
                            $pct = $max > 0 ? round($balance->used_days / $max * 100) : 0;
                        @endphp
                        <tr>
                            <td><strong>{{ $balance->user->name }}</strong></td>
                            <td><small class="text-muted">{{ $balance->user->department?->name ?? '—' }}</small></td>
                            <td>
                                <span class="badge" style="background-color: {{ $balance->leaveType->color }}">
                                    {{ $balance->leaveType->name }}
                                </span>
                            </td>
                            <td class="text-end">{{ $max }}</td>
                            <td class="text-end text-danger">{{ $balance->used_days }}</td>
                            <td class="text-end text-success fw-bold">{{ $balance->effective_available }}</td>
                            <td>
                                <div class="progress" style="height:8px">
                                    <div class="progress-bar bg-{{ $pct < 25 ? 'danger' : ($pct < 50 ? 'warning' : 'success') }}"
                                         style="width:{{ $pct }}%"></div>
                                </div>
                                <small class="text-muted">{{ $pct }}%</small>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted">
                            All employees have used at least {{ $threshold }}% of their entitlement.
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
