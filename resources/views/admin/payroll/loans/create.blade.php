<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Employee Loan</h1>
            <span>Disburse a loan and auto-generate the recovery schedule</span>
        </div>
        <a href="{{ route('admin.payroll.loans.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.payroll.loans.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                            <option value="">Select employee…</option>
                            @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Loan Type <span class="text-danger">*</span></label>
                        <input type="text" name="loan_type" class="form-control @error('loan_type') is-invalid @enderror"
                               value="{{ old('loan_type') }}" placeholder="e.g. Personal, Home, Vehicle" required maxlength="80">
                        @error('loan_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Principal Amount <span class="text-danger">*</span></label>
                        <input type="number" name="principal_amount" class="form-control @error('principal_amount') is-invalid @enderror"
                               value="{{ old('principal_amount') }}" step="0.01" min="0.01" required>
                        @error('principal_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Installment Amount <span class="text-danger">*</span></label>
                        <input type="number" name="installment_amount" class="form-control @error('installment_amount') is-invalid @enderror"
                               value="{{ old('installment_amount') }}" step="0.01" min="0.01" required>
                        @error('installment_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Total Installments <span class="text-danger">*</span></label>
                        <input type="number" name="total_installments" class="form-control @error('total_installments') is-invalid @enderror"
                               value="{{ old('total_installments') }}" min="1" required>
                        @error('total_installments')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Issued Date <span class="text-danger">*</span></label>
                        <input type="date" name="issued_date" class="form-control @error('issued_date') is-invalid @enderror"
                               value="{{ old('issued_date') }}" required>
                        @error('issued_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Recovery Start Period <span class="text-danger">*</span></label>
                        <input type="month" name="recovery_start_period" class="form-control @error('recovery_start_period') is-invalid @enderror"
                               value="{{ old('recovery_start_period') }}" required>
                        <div class="form-text">First payroll month for EMI deduction</div>
                        @error('recovery_start_period')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.payroll.loans.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-check me-1"></i> Create Loan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
