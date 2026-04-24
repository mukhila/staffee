<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Policies</h1>
            <span>Accrual rules and entitlements per leave type</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
            <a href="{{ route('admin.leaves.policies.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> Add Policy
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Policy Name</th>
                            <th>Leave Type</th>
                            <th>Department</th>
                            <th>Max Days/Year</th>
                            <th>Accrual</th>
                            <th>Carry Fwd</th>
                            <th>Approval</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policies as $policy)
                        <tr>
                            <td><strong>{{ $policy->name }}</strong></td>
                            <td>
                                @if($policy->leaveType)
                                <span class="badge" style="background-color: {{ $policy->leaveType->color }}">{{ $policy->leaveType->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $policy->department?->name ?? 'All' }}</td>
                            <td>{{ $policy->max_days_per_year ?: '—' }}</td>
                            <td>
                                <span class="badge bg-light text-dark">{{ ucfirst($policy->accrual_method) }}</span>
                                <small class="text-muted d-block">{{ $policy->accrual_amount }} days</small>
                            </td>
                            <td>{{ $policy->carry_forward_days ?: '—' }}</td>
                            <td>
                                @if($policy->requires_manager_approval && $policy->requires_hr_approval)
                                <span class="badge bg-warning-subtle text-warning">Manager + HR</span>
                                @elseif($policy->requires_manager_approval)
                                <span class="badge bg-info-subtle text-info">Manager</span>
                                @elseif($policy->auto_approve_days)
                                <span class="badge bg-success-subtle text-success">Auto (≤{{ $policy->auto_approve_days }}d)</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary">None</span>
                                @endif
                            </td>
                            <td>
                                @if($policy->is_active)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.leaves.policies.destroy', $policy) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this policy?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">No policies defined. <a href="{{ route('admin.leaves.policies.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
