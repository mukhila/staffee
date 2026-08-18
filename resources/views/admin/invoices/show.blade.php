<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">{{ $invoice->invoice_number }}</h1>
                <span>Invoice for {{ $invoice->client?->name }}</span>
            </div>
            <div class="d-flex gap-2">
                @if($invoice->status === 'draft')
                <form action="{{ route('admin.invoices.send', $invoice) }}" method="POST">
                    @csrf
                    <button class="btn btn-info"><i class="fi fi-rr-paper-plane me-1"></i> Mark as Sent</button>
                </form>
                @endif
                @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST"
                      onsubmit="return confirm('Cancel this invoice?')">
                    @csrf
                    <button class="btn btn-outline-danger">Cancel</button>
                </form>
                @endif
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">
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
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Invoice Info</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Invoice #</dt><dd class="col-sm-7">{{ $invoice->invoice_number }}</dd>
                                    <dt class="col-sm-5">Client</dt><dd class="col-sm-7">{{ $invoice->client?->name }}</dd>
                                    <dt class="col-sm-5">Project</dt><dd class="col-sm-7">{{ $invoice->project?->name ?? '—' }}</dd>
                                    <dt class="col-sm-5">Issued By</dt><dd class="col-sm-7">{{ $invoice->issuedBy?->name }}</dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">Invoice Date</dt><dd class="col-sm-7">{{ $invoice->invoice_date->format('d M Y') }}</dd>
                                    <dt class="col-sm-5">Due Date</dt><dd class="col-sm-7">{{ $invoice->due_date->format('d M Y') }}</dd>
                                    <dt class="col-sm-5">Currency</dt><dd class="col-sm-7">{{ $invoice->currency }}</dd>
                                    <dt class="col-sm-5">Status</dt>
                                    <dd class="col-sm-7">
                                        @php $colors = ['draft'=>'secondary','sent'=>'info','partial'=>'warning','paid'=>'success','overdue'=>'danger','cancelled'=>'dark']; @endphp
                                        <span class="badge bg-{{ $colors[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        @if($invoice->notes)
                        <p class="text-muted mb-0"><strong>Notes:</strong> {{ $invoice->notes }}</p>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Line Items</h5></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Tax %</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                    <tr>
                                        <td>{{ $item->description }}</td>
                                        <td class="text-end">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end">{{ $item->tax_rate }}%</td>
                                        <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="4" class="text-end">Subtotal</td>
                                        <td class="text-end">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="text-end">Tax</td>
                                        <td class="text-end">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                                    </tr>
                                    @if(bccomp((string)$invoice->discount_amount, '0', 6) > 0)
                                    <tr>
                                        <td colspan="4" class="text-end">Discount</td>
                                        <td class="text-end text-danger">- {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
                                    </tr>
                                    @endif
                                    <tr class="fw-bold">
                                        <td colspan="4" class="text-end">Total</td>
                                        <td class="text-end">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="mb-0">Payment Summary</h5></div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-7">Total</dt>
                            <dd class="col-5 text-end">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</dd>
                            <dt class="col-7">Paid</dt>
                            <dd class="col-5 text-end text-success">{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</dd>
                            <dt class="col-7 fw-bold">Balance Due</dt>
                            <dd class="col-5 text-end fw-bold text-danger">{{ $invoice->currency }} {{ number_format($invoice->balanceDue(), 2) }}</dd>
                        </dl>
                    </div>
                </div>

                @if(!in_array($invoice->status, ['paid', 'cancelled']))
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Record Payment</h5></div>
                    <div class="card-body">
                        <form action="{{ route('admin.invoices.payment', $invoice) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">{{ $invoice->currency }}</span>
                                    <input type="number" step="0.01" name="amount" class="form-control" required min="0.01"
                                           max="{{ $invoice->balanceDue() }}" placeholder="0.00">
                                </div>
                            </div>
                            <button class="btn btn-success w-100">
                                <i class="fi fi-rr-check me-1"></i> Record Payment
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
