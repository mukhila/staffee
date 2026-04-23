<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Edit Profile: {{ $employee->name }}</h1>
            <span>{{ $employee->employee_id }} · {{ $employee->designation ?? ucfirst($employee->role) }}</span>
        </div>
        <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back to Profile
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('admin.hr.employees.update', $employee) }}" method="POST">
        @csrf @method('PUT')

        {{-- ── Personal Information ───────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Personal Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="{{ old('date_of_birth', $profile->date_of_birth?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(['male'=>'Male','female'=>'Female','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'] as $val => $label)
                            <option value="{{ $val }}" {{ old('gender', $profile->gender) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(['single'=>'Single','married'=>'Married','divorced'=>'Divorced','widowed'=>'Widowed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('marital_status', $profile->marital_status) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Blood Group</label>
                        <input type="text" name="blood_group" class="form-control"
                               value="{{ old('blood_group', $profile->blood_group) }}" placeholder="e.g. O+">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control"
                               value="{{ old('nationality', $profile->nationality) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">National ID Type</label>
                        <input type="text" name="national_id_type" class="form-control"
                               value="{{ old('national_id_type', $profile->national_id_type) }}" placeholder="e.g. Aadhar, Passport">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">National ID Number</label>
                        <input type="text" name="national_id" class="form-control"
                               value="{{ old('national_id', $profile->national_id) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control"
                               value="{{ old('phone', $employee->phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea name="bio" class="form-control" rows="3">{{ old('bio', $profile->bio) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control"
                               value="{{ old('linkedin_url', $profile->linkedin_url) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GitHub URL</label>
                        <input type="url" name="github_url" class="form-control"
                               value="{{ old('github_url', $profile->github_url) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Employment Details ─────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Employment Details</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Designation</label>
                        <input type="text" name="designation" class="form-control"
                               value="{{ old('designation', $employee->designation) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Work Location</label>
                        <input type="text" name="work_location" class="form-control"
                               value="{{ old('work_location', $profile->work_location) }}" placeholder="e.g. Office, Remote, Hybrid">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control"
                               value="{{ old('joining_date', $profile->joining_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Probation End Date</label>
                        <input type="date" name="probation_end_date" class="form-control"
                               value="{{ old('probation_end_date', $profile->probation_end_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contract Type</label>
                        <select name="contract_type" class="form-select">
                            <option value="">— Select —</option>
                            @foreach(['permanent'=>'Permanent','fixed_term'=>'Fixed Term','internship'=>'Internship','part_time'=>'Part Time','consultant'=>'Consultant'] as $val => $label)
                            <option value="{{ $val }}" {{ old('contract_type', $profile->contract_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control"
                               value="{{ old('contract_end_date', $profile->contract_end_date?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notice Period (days)</label>
                        <input type="number" name="notice_period_days" class="form-control" min="0" max="365"
                               value="{{ old('notice_period_days', $profile->notice_period_days) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Current Salary</label>
                        <input type="number" name="current_salary" class="form-control" min="0" step="0.01"
                               value="{{ old('current_salary', $profile->current_salary) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Salary Currency</label>
                        <input type="text" name="salary_currency" class="form-control" maxlength="3"
                               value="{{ old('salary_currency', $profile->salary_currency ?? 'USD') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Permanent Address ──────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Permanent Address</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="perm_address_line1" class="form-control"
                               value="{{ old('perm_address_line1', $profile->perm_address_line1) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="perm_city" class="form-control"
                               value="{{ old('perm_city', $profile->perm_city) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="perm_state" class="form-control"
                               value="{{ old('perm_state', $profile->perm_state) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="perm_postal_code" class="form-control"
                               value="{{ old('perm_postal_code', $profile->perm_postal_code) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Country</label>
                        <input type="text" name="perm_country" class="form-control"
                               value="{{ old('perm_country', $profile->perm_country) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-4">
            <a href="{{ route('admin.hr.employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fi fi-rr-disk me-1"></i> Save Changes
            </button>
        </div>

    </form>
</div>
</x-app-layout>
