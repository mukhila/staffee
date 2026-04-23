<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Initiate Termination</h1>
            <span>Start the termination process for an employee</span>
        </div>
        <a href="{{ route('admin.hr.terminations.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('admin.hr.terminations.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                                @if($emp->employee_id) ({{ $emp->employee_id }}) @endif
                                — {{ $emp->department?->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Termination Type <span class="text-danger">*</span></label>
                        <select name="termination_type" class="form-select" required>
                            <option value="">— Select Type —</option>
                            @foreach([
                                'voluntary_resignation' => 'Voluntary Resignation',
                                'involuntary_dismissal' => 'Involuntary Dismissal',
                                'layoff'                => 'Layoff / Retrenchment',
                                'end_of_contract'       => 'End of Contract',
                                'retirement'            => 'Retirement',
                                'mutual_separation'     => 'Mutual Separation',
                                'abandonment'           => 'Job Abandonment / Absconding',
                            ] as $val => $label)
                            <option value="{{ $val }}" {{ old('termination_type') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Last Working Date <span class="text-danger">*</span></label>
                        <input type="date" name="last_working_date" class="form-control"
                               min="{{ today()->format('Y-m-d') }}"
                               value="{{ old('last_working_date') }}" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Reason / Justification <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="5"
                                  placeholder="Provide a detailed reason for this termination. This will be included in HR records and the employee notice." required>{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-warning mb-0">
                            <i class="fi fi-rr-triangle-warning me-2"></i>
                            <strong>Important:</strong> This action will notify the employee and all HR admins.
                            The employee's employment status will be set to "Notice Period" immediately.
                            Termination requires HR approval before it is processed.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.hr.terminations.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Initiate termination for this employee? This action notifies HR and the employee immediately.')">
                            <i class="fi fi-rr-user-minus me-1"></i> Initiate Termination
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
