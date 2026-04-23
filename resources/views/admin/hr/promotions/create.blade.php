<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Promotion Proposal</h1>
            <span>Propose a role, designation, or salary change for an employee</span>
        </div>
        <a href="{{ route('admin.hr.promotions.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('admin.hr.promotions.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    {{-- Employee --}}
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" id="employeeSelect" class="form-select" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                    data-role="{{ $emp->role }}"
                                    data-designation="{{ $emp->designation }}"
                                    data-dept="{{ $emp->department_id }}"
                                    data-salary="{{ $emp->currentSalary() }}"
                                    {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                                @if($emp->employee_id) ({{ $emp->employee_id }}) @endif
                                — {{ $emp->department?->name }}
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

                    {{-- Current (read-only snapshot) --}}
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Current Details (auto-filled on selection)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Role</label>
                                        <input type="text" id="currentRole" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Designation</label>
                                        <input type="text" id="currentDesignation" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label text-muted small">Current Salary</label>
                                        <input type="text" id="currentSalary" class="form-control form-control-sm" readonly placeholder="—">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Proposed fields --}}
                    <div class="col-md-6">
                        <label class="form-label">Proposed Role <span class="text-danger">*</span></label>
                        <select name="proposed_role" class="form-select" required>
                            <option value="">— Select Role —</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ old('proposed_role') == $role->slug ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Proposed Department <span class="text-danger">*</span></label>
                        <select name="proposed_department_id" class="form-select" required>
                            <option value="">— Select Department —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('proposed_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Proposed Designation</label>
                        <input type="text" name="proposed_designation" class="form-control"
                               value="{{ old('proposed_designation') }}" placeholder="e.g. Senior Software Engineer">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Proposed Salary</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="proposed_salary" class="form-control"
                                   value="{{ old('proposed_salary') }}" min="0" step="0.01" placeholder="0.00"
                                   id="proposedSalary">
                        </div>
                        <div class="form-text" id="salaryDiff"></div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Reason / Justification <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4"
                                  placeholder="Describe the reason for this promotion — performance highlights, scope changes, business needs..." required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="fi fi-rr-info me-2"></i>
                            This proposal will go through a 3-step approval: <strong>Manager → HR → Finance</strong>.
                            The promotion will be applied automatically after all 3 approvals.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.hr.promotions.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-paper-plane me-1"></i> Submit Proposal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('employeeSelect').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('currentRole').value        = opt.dataset.role || '';
    document.getElementById('currentDesignation').value = opt.dataset.designation || '';
    document.getElementById('currentSalary').value      = opt.dataset.salary ? parseFloat(opt.dataset.salary).toLocaleString('en-US', {style:'currency',currency:'USD'}) : '';
    updateDiff();
});

document.getElementById('proposedSalary').addEventListener('input', updateDiff);

function updateDiff() {
    const emp     = document.getElementById('employeeSelect');
    const opt     = emp.options[emp.selectedIndex];
    const current = parseFloat(opt?.dataset?.salary);
    const proposed= parseFloat(document.getElementById('proposedSalary').value);
    const el      = document.getElementById('salaryDiff');

    if (!isNaN(current) && !isNaN(proposed) && current > 0) {
        const pct  = ((proposed - current) / current * 100).toFixed(1);
        const sign = pct >= 0 ? '+' : '';
        el.innerHTML = `<span class="text-${pct >= 0 ? 'success' : 'danger'}">${sign}${pct}% from current (${current.toLocaleString('en-US', {style:'currency',currency:'USD'})})</span>`;
    } else {
        el.innerHTML = '';
    }
}
</script>
</x-app-layout>
