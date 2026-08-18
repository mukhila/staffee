<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Invoices</h1>
                <span>Manage client invoices</span>
            </div>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> New Invoice
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label mb-1">Client</label>
                        <select name="client_id" class="form-select form-select-sm">
                            <option value="">All Clients</option>
                            @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Due</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                            @php
                                $colors = ['draft'=>'secondary','sent'=>'info','partial'=>'warning','paid'=>'success','overdue'=>'danger','cancelled'=>'dark'];
                            @endphp
                            <tr>
                                <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->client?->name }}</td>
                                <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                                <td>{{ $invoice->due_date->format('d M Y') }}</td>
                                <td>{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                                <td>{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</td>
                                <td>{{ $invoice->currency }} {{ number_format($invoice->balanceDue(), 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $colors[$invoice->status] ?? 'secondary' }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No invoices found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($invoices->hasPages())
            <div class="card-footer">{{ $invoices->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
