<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Employee Loans</h1>
            <span>Manage staff loan disbursements and recovery schedules</span>
        </div>
        <a href="{{ route('admin.payroll.loans.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Loan
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Employee</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Employees</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach(['active','completed','cancelled','hold'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('admin.payroll.loans.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Loan Type</th>
                            <th>Principal</th>
                            <th>Installment</th>
                            <th>Installments</th>
                            <th>Balance</th>
                            <th>Start Period</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($loans as $loan)
                        @php
                        $statusColors = ['active'=>'success','completed'=>'info','cancelled'=>'secondary','hold'=>'warning'];
                        $sc = $statusColors[$loan->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $loan->user?->name }}</div>
                            </td>
                            <td class="small">{{ $loan->loan_type }}</td>
                            <td class="fw-medium">{{ number_format($loan->principal_amount, 2) }}</td>
                            <td class="small">{{ number_format($loan->installment_amount, 2) }}</td>
                            <td class="small text-center">{{ $loan->total_installments }}</td>
                            <td class="fw-medium">{{ number_format($loan->remaining_balance, 2) }}</td>
                            <td class="text-muted small">{{ $loan->recovery_start_period }}</td>
                            <td><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($loan->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.payroll.loans.show', $loan) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fi fi-rr-money-bill-wave fs-3 d-block mb-2 opacity-25"></i>
                                No loans found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($loans->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $loans->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
