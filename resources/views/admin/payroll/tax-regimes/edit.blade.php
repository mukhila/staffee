<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Edit Tax Regime</h1>
            <span>{{ $taxRegime->name }}</span>
        </div>
        <a href="{{ route('admin.payroll.tax-regimes.show', $taxRegime) }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <form action="{{ route('admin.payroll.tax-regimes.update', $taxRegime) }}" method="POST" id="taxForm">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-3">
        <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Regime Details</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Regime Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $taxRegime->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Regime Code <span class="text-danger">*</span></label>
                    <input type="text" name="regime_code" class="form-control" value="{{ old('regime_code', $taxRegime->regime_code) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country Code <span class="text-danger">*</span></label>
                    <input type="text" name="country_code" class="form-control" maxlength="2" value="{{ old('country_code', $taxRegime->country_code) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fiscal Year <span class="text-danger">*</span></label>
                    <input type="text" name="fiscal_year" class="form-control" value="{{ old('fiscal_year', $taxRegime->fiscal_year) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective From <span class="text-danger">*</span></label>
                    <input type="date" name="effective_from" class="form-control" value="{{ old('effective_from', $taxRegime->effective_from?->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Effective To</label>
                    <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to', $taxRegime->effective_to?->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $taxRegime->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $taxRegime->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Standard Deduction</label>
                    <input type="number" name="standard_deduction" class="form-control" value="{{ old('standard_deduction', $taxRegime->standard_deduction) }}" min="0" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Rebate Amount</label>
                    <input type="number" name="rebate_amount" class="form-control" value="{{ old('rebate_amount', $taxRegime->rebate_amount) }}" min="0" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cess %</label>
                    <input type="number" name="cess_percent" class="form-control" value="{{ old('cess_percent', $taxRegime->cess_percent) }}" min="0" max="100" step="0.01">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">Tax Brackets</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addBracket">
                <i class="fi fi-rr-plus me-1"></i> Add Bracket
            </button>
        </div>
        <div class="card-body">
            <div class="alert alert-warning py-2 small mb-3">
                <i class="fi fi-rr-triangle-warning me-1"></i>
                Saving will replace all existing brackets with the ones below.
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Income From</th>
                            <th>Income To <span class="text-muted small">(blank = no limit)</span></th>
                            <th>Rate %</th>
                            <th>Fixed Tax Amount</th>
                            <th>Rebate Eligible</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bracketsBody">
                        @foreach($taxRegime->brackets as $i => $bracket)
                        <tr class="bracket-row">
                            <td><input type="number" name="brackets[{{ $i }}][income_from]" class="form-control form-control-sm" min="0" step="0.01" value="{{ $bracket->income_from }}"></td>
                            <td><input type="number" name="brackets[{{ $i }}][income_to]" class="form-control form-control-sm" min="0" step="0.01" placeholder="No limit" value="{{ $bracket->income_to }}"></td>
                            <td><input type="number" name="brackets[{{ $i }}][rate_percent]" class="form-control form-control-sm" min="0" max="100" step="0.01" value="{{ $bracket->rate_percent }}"></td>
                            <td><input type="number" name="brackets[{{ $i }}][fixed_tax_amount]" class="form-control form-control-sm" min="0" step="0.01" value="{{ $bracket->fixed_tax_amount }}"></td>
                            <td class="text-center"><input type="checkbox" name="brackets[{{ $i }}][rebate_eligible]" value="1" class="form-check-input" {{ $bracket->rebate_eligible ? 'checked' : '' }}></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-bracket"><i class="fi fi-rr-trash"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="{{ route('admin.payroll.tax-regimes.show', $taxRegime) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fi fi-rr-check me-1"></i> Save Changes</button>
    </div>
    </form>
</div>

<script>
let bracketIndex = {{ $taxRegime->brackets->count() }};

document.getElementById('addBracket').addEventListener('click', function() {
    const tbody = document.getElementById('bracketsBody');
    const i = bracketIndex++;
    const row = document.createElement('tr');
    row.className = 'bracket-row';
    row.innerHTML = `
        <td><input type="number" name="brackets[${i}][income_from]" class="form-control form-control-sm" min="0" step="0.01"></td>
        <td><input type="number" name="brackets[${i}][income_to]" class="form-control form-control-sm" min="0" step="0.01" placeholder="No limit"></td>
        <td><input type="number" name="brackets[${i}][rate_percent]" class="form-control form-control-sm" min="0" max="100" step="0.01" value="0"></td>
        <td><input type="number" name="brackets[${i}][fixed_tax_amount]" class="form-control form-control-sm" min="0" step="0.01" value="0"></td>
        <td class="text-center"><input type="checkbox" name="brackets[${i}][rebate_eligible]" value="1" class="form-check-input"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-bracket"><i class="fi fi-rr-trash"></i></button></td>
    `;
    tbody.appendChild(row);
});

document.getElementById('bracketsBody').addEventListener('click', function(e) {
    if (e.target.closest('.remove-bracket')) {
        const rows = document.querySelectorAll('.bracket-row');
        if (rows.length > 1) e.target.closest('tr').remove();
    }
});
</script>
</x-app-layout>
