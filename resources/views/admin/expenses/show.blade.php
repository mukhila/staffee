<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Expense Claim</h1>
                <span>{{ $claim->title }}</span>
            </div>
            <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
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

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Details</h5></div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Staff</dt>
                            <dd class="col-sm-8">{{ $claim->user?->name }}</dd>

                            <dt class="col-sm-4">Title</dt>
                            <dd class="col-sm-8">{{ $claim->title }}</dd>

                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8">{{ $claim->currency }} {{ number_format($claim->amount, 2) }}</dd>

                            <dt class="col-sm-4">Expense Date</dt>
                            <dd class="col-sm-8">{{ $claim->expense_date->format('d M Y') }}</dd>

                            <dt class="col-sm-4">Category</dt>
                            <dd class="col-sm-8">{{ $claim->category?->name ?? '—' }}</dd>

                            <dt class="col-sm-4">Project</dt>
                            <dd class="col-sm-8">{{ $claim->project?->name ?? '—' }}</dd>

                            <dt class="col-sm-4">Description</dt>
                            <dd class="col-sm-8">{{ $claim->description ?: '—' }}</dd>

                            <dt class="col-sm-4">Receipt</dt>
                            <dd class="col-sm-8">{{ $claim->receipt_path ?: '—' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Review</h5></div>
                    <div class="card-body">
                        @php
                            $colors = ['draft'=>'secondary','submitted'=>'warning','approved'=>'info','rejected'=>'danger','paid'=>'success'];
                        @endphp
                        <span class="badge bg-{{ $colors[$claim->status] ?? 'secondary' }} fs-6 mb-3">
                            {{ ucfirst($claim->status) }}
                        </span>

                        @if($claim->submitted_at)
                        <p class="small text-muted mb-1">Submitted: {{ $claim->submitted_at->format('d M Y H:i') }}</p>
                        @endif

                        @if($claim->reviewed_at)
                        <p class="small text-muted mb-1">Reviewed: {{ $claim->reviewed_at->format('d M Y H:i') }} by {{ $claim->reviewer?->name }}</p>
                        @endif

                        @if($claim->review_notes)
                        <div class="alert alert-light"><small><strong>Notes:</strong> {{ $claim->review_notes }}</small></div>
                        @endif

                        @if($claim->status === 'submitted')
                        <form action="{{ route('admin.expenses.approve', $claim) }}" method="POST" class="mb-2">
                            @csrf
                            <textarea name="review_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Optional notes"></textarea>
                            <button class="btn btn-success w-100 btn-sm"><i class="fi fi-rr-check me-1"></i> Approve</button>
                        </form>
                        <form action="{{ route('admin.expenses.reject', $claim) }}" method="POST">
                            @csrf
                            <textarea name="review_notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Rejection reason (required)" required></textarea>
                            <button class="btn btn-danger w-100 btn-sm"><i class="fi fi-rr-cross me-1"></i> Reject</button>
                        </form>
                        @endif

                        @if($claim->status === 'approved')
                        <form action="{{ route('admin.expenses.pay', $claim) }}" method="POST">
                            @csrf
                            <button class="btn btn-primary w-100"><i class="fi fi-rr-money me-1"></i> Mark as Paid</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
