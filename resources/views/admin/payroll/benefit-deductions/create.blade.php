<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Benefit Deduction</h1>
            <span>Set up a recurring benefit deduction for an employee</span>
        </div>
        <a href="{{ route('admin.payroll.benefit-deductions.index') }}" class="btn btn-outline-secondary btn-sm">
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
            <form action="{{ route('admin.payroll.benefit-deductions.store') }}" method="POST">
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
                        <label class="form-label">Benefit Name <span class="text-danger">*</span></label>
                        <input type="text" name="benefit_name" class="form-control @error('benefit_name') is-invalid @enderror"
                               value="{{ old('benefit_name') }}" placeholder="e.g. Health Insurance Premium" required maxlength="150">
                        @error('benefit_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Benefit Type <span class="text-danger">*</span></label>
                        <select name="benefit_type" class="form-select @error('benefit_type') is-invalid @enderror" required>
                            <option value="">Select type…</option>
                            @foreach(['insurance'=>'Insurance','transport'=>'Transport','food_voucher'=>'Food Voucher','provident_fund'=>'Provident Fund','other'=>'Other'] as $val => $label)
                            <option value="{{ $val }}" {{ old('benefit_type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('benefit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount (monthly deduction) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount') }}" step="0.01" min="0.01" required>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Frequency <span class="text-danger">*</span></label>
                        <select name="frequency" class="form-select @error('frequency') is-invalid @enderror" required>
                            @foreach(['monthly'=>'Monthly','quarterly'=>'Quarterly','annual'=>'Annual'] as $val => $label)
                            <option value="{{ $val }}" {{ old('frequency', 'monthly') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('frequency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control @error('effective_from') is-invalid @enderror"
                               value="{{ old('effective_from') }}" required>
                        @error('effective_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control @error('effective_to') is-invalid @enderror"
                               value="{{ old('effective_to') }}">
                        <div class="form-text">Leave blank for an ongoing (open-ended) deduction.</div>
                        @error('effective_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.payroll.benefit-deductions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-check me-1"></i> Create Deduction
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
