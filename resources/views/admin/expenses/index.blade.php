<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Expense Claims</h1>
                <span>Review and manage all expense claims</span>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Staff</label>
                        <select name="user_id" class="form-select form-select-sm">
                            <option value="">All Staff</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('admin.expenses.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Staff</th>
                                <th>Title</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($claims as $claim)
                            <tr>
                                <td>{{ $claim->user?->name }}</td>
                                <td>{{ $claim->title }}</td>
                                <td>{{ $claim->currency }} {{ number_format($claim->amount, 2) }}</td>
                                <td>{{ $claim->expense_date->format('d M Y') }}</td>
                                <td>{{ $claim->category?->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $colors = [
                                            'draft'     => 'secondary',
                                            'submitted' => 'warning',
                                            'approved'  => 'info',
                                            'rejected'  => 'danger',
                                            'paid'      => 'success',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $colors[$claim->status] ?? 'secondary' }}">
                                        {{ ucfirst($claim->status) }}
                                    </span>
                                </td>
                                <td>{{ $claim->submitted_at ? $claim->submitted_at->format('d M Y') : '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.expenses.show', $claim) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No expense claims found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($claims->hasPages())
            <div class="card-footer">{{ $claims->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
