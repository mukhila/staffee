<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">New Invoice</h1>
                <span>Create a client invoice with line items</span>
            </div>
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form action="{{ route('admin.invoices.store') }}" method="POST" id="invoiceForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header"><h5 class="mb-0">Invoice Details</h5></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Client <span class="text-danger">*</span></label>
                                    <select name="client_id" class="form-select" required>
                                        <option value="">— Select Client —</option>
                                        @foreach($clients as $c)
                                        <option value="{{ $c->id }}" data-currency="{{ $c->currency }}"
                                                {{ old('client_id', request('client_id')) == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Project</label>
                                    <select name="project_id" class="form-select">
                                        <option value="">— None —</option>
                                        @foreach($projects as $p)
                                        <option value="{{ $p->id }}" {{ old('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                                    <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Due Date <span class="text-danger">*</span></label>
                                    <input type="date" name="due_date" class="form-control" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Currency</label>
                                    <input type="text" name="currency" id="currency" class="form-control" value="{{ old('currency', 'INR') }}" maxlength="3">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Discount Amount</label>
                                    <input type="number" step="0.01" name="discount_amount" class="form-control" value="{{ old('discount_amount', 0) }}" min="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Line Items</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                                <i class="fi fi-rr-plus me-1"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width:200px">Description</th>
                                            <th style="width:90px">Qty</th>
                                            <th style="width:120px">Unit Price</th>
                                            <th style="width:90px">Tax %</th>
                                            <th style="width:120px">Line Total</th>
                                            <th style="width:40px"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr class="item-row">
                                            <td><input type="text" name="items[0][description]" class="form-control form-control-sm" required maxlength="300"></td>
                                            <td><input type="number" step="0.0001" name="items[0][quantity]" class="form-control form-control-sm item-qty" value="1" min="0.0001" required></td>
                                            <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm item-price" value="0" min="0" required></td>
                                            <td><input type="number" step="0.01" name="items[0][tax_rate]" class="form-control form-control-sm item-tax" value="0" min="0" max="100"></td>
                                            <td><span class="item-total form-control-plaintext form-control-sm fw-bold">0.00</span></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="fi fi-rr-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card sticky-top" style="top:1rem">
                        <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-7">Subtotal</dt>
                                <dd class="col-5 text-end" id="summarySubtotal">0.00</dd>
                                <dt class="col-7">Tax</dt>
                                <dd class="col-5 text-end" id="summaryTax">0.00</dd>
                                <dt class="col-7">Discount</dt>
                                <dd class="col-5 text-end" id="summaryDiscount">0.00</dd>
                                <dt class="col-7 fw-bold">Total</dt>
                                <dd class="col-5 text-end fw-bold" id="summaryTotal">0.00</dd>
                            </dl>
                            <hr>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fi fi-rr-file-invoice me-1"></i> Create Invoice
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
    let itemIndex = 1;

    function calcRow(row) {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const tax = parseFloat(row.querySelector('.item-tax').value) || 0;
        const lineTotal = qty * price;
        row.querySelector('.item-total').textContent = lineTotal.toFixed(2);
        return { lineTotal, itemTax: lineTotal * tax / 100 };
    }

    function updateSummary() {
        let subtotal = 0, totalTax = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const r = calcRow(row);
            subtotal += r.lineTotal;
            totalTax += r.itemTax;
        });
        const discount = parseFloat(document.querySelector('[name=discount_amount]').value) || 0;
        const total = subtotal + totalTax - discount;
        document.getElementById('summarySubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('summaryTax').textContent = totalTax.toFixed(2);
        document.getElementById('summaryDiscount').textContent = discount.toFixed(2);
        document.getElementById('summaryTotal').textContent = total.toFixed(2);
    }

    document.getElementById('addItem').addEventListener('click', function () {
        const row = document.querySelector('.item-row').cloneNode(true);
        row.querySelectorAll('input').forEach(inp => {
            inp.name = inp.name.replace(/\[\d+\]/, '[' + itemIndex + ']');
            if (inp.classList.contains('item-qty')) inp.value = 1;
            if (inp.classList.contains('item-price')) inp.value = 0;
            if (inp.classList.contains('item-tax')) inp.value = 0;
            if (!inp.classList.contains('item-qty') && !inp.classList.contains('item-price') && !inp.classList.contains('item-tax')) inp.value = '';
        });
        row.querySelector('.item-total').textContent = '0.00';
        document.getElementById('itemsBody').appendChild(row);
        itemIndex++;
        updateSummary();
    });

    document.getElementById('itemsBody').addEventListener('input', updateSummary);
    document.querySelector('[name=discount_amount]').addEventListener('input', updateSummary);

    document.getElementById('itemsBody').addEventListener('click', function (e) {
        if (e.target.closest('.remove-item')) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length > 1) {
                e.target.closest('.item-row').remove();
                updateSummary();
            }
        }
    });

    // Auto-fill currency from client selection
    document.querySelector('[name=client_id]').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const cur = opt.dataset.currency;
        if (cur) document.getElementById('currency').value = cur;
    });

    updateSummary();
    </script>
</x-app-layout>
