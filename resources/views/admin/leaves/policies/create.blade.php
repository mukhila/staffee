<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Leave Policy</h1>
            <span>Set entitlement rules for a leave type</span>
        </div>
        <a href="{{ route('admin.leaves.policies.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leaves.policies.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Policy Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Standard Annual Leave Policy" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select @error('leave_type_id') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach($types as $t)
                            <option value="{{ $t->id }}" {{ old('leave_type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        @error('leave_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">All departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Leave blank to apply to all departments.</div>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Entitlement</small></div>

                    <div class="col-md-3">
                        <label class="form-label">Max Days / Year <span class="text-danger">*</span></label>
                        <input type="number" name="max_days_per_year" class="form-control @error('max_days_per_year') is-invalid @enderror"
                               value="{{ old('max_days_per_year', 0) }}" min="0" step="0.5" required>
                        <div class="form-text">0 = unlimited.</div>
                        @error('max_days_per_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Carry Forward Days <span class="text-danger">*</span></label>
                        <input type="number" name="carry_forward_days" class="form-control @error('carry_forward_days') is-invalid @enderror"
                               value="{{ old('carry_forward_days', 0) }}" min="0" step="0.5" required>
                        @error('carry_forward_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Carry Fwd Expiry (months) <span class="text-danger">*</span></label>
                        <input type="number" name="carry_forward_expiry_months" class="form-control @error('carry_forward_expiry_months') is-invalid @enderror"
                               value="{{ old('carry_forward_expiry_months', 3) }}" min="0" max="12" required>
                        <div class="form-text">0 = never expires.</div>
                        @error('carry_forward_expiry_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Accrual</small></div>

                    <div class="col-md-3">
                        <label class="form-label">Accrual Method <span class="text-danger">*</span></label>
                        <select name="accrual_method" class="form-select @error('accrual_method') is-invalid @enderror" required>
                            @foreach(['immediate' => 'Immediate (on join)', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'annual' => 'Annual'] as $val => $label)
                            <option value="{{ $val }}" {{ old('accrual_method') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('accrual_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Accrual Amount (days) <span class="text-danger">*</span></label>
                        <input type="number" name="accrual_amount" class="form-control @error('accrual_amount') is-invalid @enderror"
                               value="{{ old('accrual_amount', 0) }}" min="0" step="0.5" required>
                        @error('accrual_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vesting Period (months) <span class="text-danger">*</span></label>
                        <input type="number" name="vesting_period_months" class="form-control @error('vesting_period_months') is-invalid @enderror"
                               value="{{ old('vesting_period_months', 0) }}" min="0" required>
                        <div class="form-text">Months before accrual begins. 0 = immediate.</div>
                        @error('vesting_period_months')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-12"><hr class="my-1"><small class="text-muted fw-semibold text-uppercase">Approval & Restrictions</small></div>

                    <div class="col-md-3">
                        <label class="form-label">Min Notice Days <span class="text-danger">*</span></label>
                        <input type="number" name="min_notice_days" class="form-control @error('min_notice_days') is-invalid @enderror"
                               value="{{ old('min_notice_days', 0) }}" min="0" required>
                        @error('min_notice_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Consecutive Days</label>
                        <input type="number" name="max_consecutive_days" class="form-control @error('max_consecutive_days') is-invalid @enderror"
                               value="{{ old('max_consecutive_days') }}" min="1" placeholder="No limit">
                        @error('max_consecutive_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Auto-Approve ≤ (days)</label>
                        <input type="number" name="auto_approve_days" class="form-control @error('auto_approve_days') is-invalid @enderror"
                               value="{{ old('auto_approve_days') }}" min="1" placeholder="Disabled">
                        <div class="form-text">Requests up to this many days are auto-approved.</div>
                        @error('auto_approve_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">Approval Flow</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_manager_approval" id="req_mgr" value="1"
                                   {{ old('requires_manager_approval', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="req_mgr">Manager Approval</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_hr_approval" id="req_hr" value="1"
                                   {{ old('requires_hr_approval') ? 'checked' : '' }}>
                            <label class="form-check-label" for="req_hr">HR Approval (2nd level)</label>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-check me-1"></i> Create Policy
                    </button>
                    <a href="{{ route('admin.leaves.policies.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
