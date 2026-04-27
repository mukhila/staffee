<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Transfer Request</h1>
            <span>Initiate an interdepartmental transfer for an employee</span>
        </div>
        <a href="{{ route('admin.hr.transfers.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('admin.hr.transfers.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    {{-- Employee --}}
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" id="employeeSelect" class="form-select" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                    data-dept="{{ $emp->department_id }}"
                                    data-dept-name="{{ $emp->department?->name }}"
                                    data-role="{{ $emp->role }}"
                                    data-designation="{{ $emp->designation }}"
                                    data-reporting="{{ $emp->reporting_to }}"
                                    {{ (old('user_id', $preselect?->id)) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}@if($emp->employee_id) ({{ $emp->employee_id }})@endif — {{ $emp->department?->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Effective Date --}}
                    <div class="col-md-6">
                        <label class="form-label">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control"
                               min="{{ today()->format('Y-m-d') }}"
                               value="{{ old('effective_date') }}" required>
                    </div>

                    {{-- Current snapshot --}}
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-muted mb-3 small fw-semibold text-uppercase letter-spacing">Current Assignment (auto-filled)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Department</label>
                                        <input type="text" id="currentDept" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Role</label>
                                        <input type="text" id="currentRole" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Designation</label>
                                        <input type="text" id="currentDesignation" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Transfer destination --}}
                    <div class="col-12">
                        <h6 class="text-muted small fw-semibold text-uppercase mb-3">Transfer To</h6>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Target Department <span class="text-danger">*</span></label>
                        <select name="to_department_id" class="form-select" required>
                            <option value="">— Select Department —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('to_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">New Reporting Manager</label>
                        <select name="to_reporting_to" class="form-select">
                            <option value="">— Keep current / None —</option>
                            @foreach($managers as $mgr)
                            <option value="{{ $mgr->id }}" {{ old('to_reporting_to') == $mgr->id ? 'selected' : '' }}>
                                {{ $mgr->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">New Role</label>
                        <select name="to_role" class="form-select">
                            <option value="">— Keep current role —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ old('to_role') == $role->slug ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">New Designation</label>
                        <input type="text" name="to_designation" class="form-control"
                               value="{{ old('to_designation') }}" placeholder="Leave blank to keep current">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Reason / Business Justification <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required
                                  placeholder="Explain the business reason for this transfer...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info mb-0 py-2">
                            <i class="fi fi-rr-info me-2"></i>
                            The transfer request will be reviewed and approved by an HR admin. Once approved, the employee record will be updated automatically on the effective date.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.hr.transfers.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-paper-plane me-1"></i> Submit Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('employeeSelect').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    document.getElementById('currentDept').value        = opt.dataset.deptName || '';
    document.getElementById('currentRole').value        = opt.dataset.role || '';
    document.getElementById('currentDesignation').value = opt.dataset.designation || '';
});

// Auto-fill if preselected
(function () {
    const sel = document.getElementById('employeeSelect');
    if (sel.value) sel.dispatchEvent(new Event('change'));
})();
</script>
</x-app-layout>
