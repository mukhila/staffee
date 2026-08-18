<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">My Expense Claims</h1>
                <span>Track your expense reimbursements</span>
            </div>
            <a href="{{ route('staff.expenses.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> New Claim
            </a>
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($claims as $claim)
                            <tr>
                                <td>{{ $claim->title }}</td>
                                <td>{{ $claim->category?->name ?? '—' }}</td>
                                <td>{{ $claim->currency }} {{ number_format($claim->amount, 2) }}</td>
                                <td>{{ $claim->expense_date->format('d M Y') }}</td>
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
                                <td class="text-end">
                                    <a href="{{ route('staff.expenses.show', $claim) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                    @if($claim->status === 'draft')
                                    <form action="{{ route('staff.expenses.destroy', $claim) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this claim?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fi fi-rr-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No expense claims yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($claims->hasPages())
            <div class="card-footer">
                {{ $claims->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
