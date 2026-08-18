<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">{{ $client->name }}</h1>
                <span>Client Details</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-outline-primary">
                    <i class="fi fi-rr-pencil me-1"></i> Edit
                </a>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">
                    <i class="fi fi-rr-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Client Info</h5></div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Name</dt><dd class="col-sm-7">{{ $client->name }}</dd>
                            <dt class="col-sm-5">Contact Person</dt><dd class="col-sm-7">{{ $client->contact_person ?? '—' }}</dd>
                            <dt class="col-sm-5">Email</dt><dd class="col-sm-7">{{ $client->email ?? '—' }}</dd>
                            <dt class="col-sm-5">Phone</dt><dd class="col-sm-7">{{ $client->phone ?? '—' }}</dd>
                            <dt class="col-sm-5">Country</dt><dd class="col-sm-7">{{ $client->country ?? '—' }}</dd>
                            <dt class="col-sm-5">Currency</dt><dd class="col-sm-7">{{ $client->currency }}</dd>
                            <dt class="col-sm-5">GST Number</dt><dd class="col-sm-7">{{ $client->gst_number ?? '—' }}</dd>
                            <dt class="col-sm-5">Status</dt>
                            <dd class="col-sm-7">
                                <span class="badge bg-{{ $client->is_active ? 'success' : 'secondary' }}">
                                    {{ $client->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </dd>
                            @if($client->address)
                            <dt class="col-sm-5">Address</dt><dd class="col-sm-7">{{ $client->address }}</dd>
                            @endif
                            @if($client->notes)
                            <dt class="col-sm-5">Notes</dt><dd class="col-sm-7">{{ $client->notes }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Invoices</h5>
                        <a href="{{ route('admin.invoices.create') }}?client_id={{ $client->id }}" class="btn btn-sm btn-primary">
                            <i class="fi fi-rr-plus"></i> New Invoice
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($client->invoices as $inv)
                                <tr>
                                    <td><a href="{{ route('admin.invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
                                    <td>{{ $inv->invoice_date->format('d M Y') }}</td>
                                    <td>{{ $inv->currency }} {{ number_format($inv->total_amount, 2) }}</td>
                                    <td><span class="badge bg-secondary">{{ ucfirst($inv->status) }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-3">No invoices yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
