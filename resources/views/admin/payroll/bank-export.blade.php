<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Payroll Bank Export</h1>
            <span>Generate bank transfer files for salary payments</span>
        </div>
        <a href="{{ route('admin.reports.payroll') }}" class="btn btn-secondary btn-sm">Back to Reports</a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Generate Export File</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.payroll.bank-export.generate') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payroll Run <span class="text-danger">*</span></label>
                            <select name="payroll_run_id" class="form-select @error('payroll_run_id') is-invalid @enderror" required>
                                <option value="">— Select a completed payroll run —</option>
                                @foreach($runs as $run)
                                <option value="{{ $run->id }}" {{ old('payroll_run_id') == $run->id ? 'selected' : '' }}>
                                    {{ date('F Y', mktime(0,0,0,$run->for_month,1,$run->for_year)) }} — {{ ucfirst($run->status) }}
                                    ({{ $run->currency_code }})
                                </option>
                                @endforeach
                            </select>
                            @error('payroll_run_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Export Format <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-sm-4">
                                    <label class="card p-3 cursor-pointer border {{ old('format','csv') === 'csv' ? 'border-primary' : '' }}" style="cursor:pointer;">
                                        <input type="radio" name="format" value="csv" class="d-none format-radio" {{ old('format','csv') === 'csv' ? 'checked' : '' }}>
                                        <div class="fw-semibold mb-1">Generic CSV</div>
                                        <div class="small text-muted">Universal format — works with any bank portal</div>
                                    </label>
                                </div>
                                <div class="col-sm-4">
                                    <label class="card p-3 border {{ old('format') === 'nacha' ? 'border-primary' : '' }}" style="cursor:pointer;">
                                        <input type="radio" name="format" value="nacha" class="d-none format-radio" {{ old('format') === 'nacha' ? 'checked' : '' }}>
                                        <div class="fw-semibold mb-1">NACHA / ACH</div>
                                        <div class="small text-muted">US bank ACH format (PPD batch)</div>
                                    </label>
                                </div>
                                <div class="col-sm-4">
                                    <label class="card p-3 border {{ old('format') === 'sepa' ? 'border-primary' : '' }}" style="cursor:pointer;">
                                        <input type="radio" name="format" value="sepa" class="d-none format-radio" {{ old('format') === 'sepa' ? 'checked' : '' }}>
                                        <div class="fw-semibold mb-1">SEPA XML</div>
                                        <div class="small text-muted">European SEPA Credit Transfer (pain.001)</div>
                                    </label>
                                </div>
                            </div>
                            @error('format')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Effective/Value Date <span class="text-danger">*</span></label>
                            <input type="date" name="effective_date" class="form-control @error('effective_date') is-invalid @enderror"
                                   value="{{ old('effective_date', now()->addDays(1)->format('Y-m-d')) }}" required>
                            @error('effective_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-download me-1"></i> Generate & Download
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Format Guide</h6></div>
                <div class="card-body small">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light"><tr><th>Format</th><th>Use For</th><th>Employee Data Needed</th></tr></thead>
                        <tbody>
                            <tr><td><strong>CSV</strong></td><td>Any bank that accepts CSV upload</td><td>bank_account, bank_ifsc</td></tr>
                            <tr><td><strong>NACHA</strong></td><td>US ACH transfers</td><td>bank_account, routing number</td></tr>
                            <tr><td><strong>SEPA</strong></td><td>European EUR transfers</td><td>bank_iban, bank_bic</td></tr>
                        </tbody>
                    </table>
                    <div class="alert alert-warning py-2 mb-0 small">
                        <strong>Note:</strong> Bank account details (IBAN, account number, IFSC) must be set on each employee's profile for the export to include correct data.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.format-radio').forEach(radio => {
    radio.closest('label').addEventListener('click', function() {
        document.querySelectorAll('.format-radio').forEach(r => r.closest('label').classList.remove('border-primary'));
        this.classList.add('border-primary');
    });
});
</script>
</x-app-layout>
