<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Payroll Adjustment</h1>
            <span>Submit a salary addition or deduction for approval</span>
        </div>
        <a href="{{ route('admin.payroll.adjustments.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('admin.payroll.adjustments.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('user_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }} @if($emp->employee_id)({{ $emp->employee_id }})@endif — {{ $emp->department?->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Component <span class="text-danger">*</span></label>
                        <select name="component_definition_id" class="form-select" required>
                            <option value="">— Select Component —</option>
                            @foreach($definitions as $def)
                            <option value="{{ $def->id }}" {{ old('component_definition_id') == $def->id ? 'selected' : '' }}>
                                {{ $def->name }} ({{ ucfirst($def->category) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="addition" {{ old('adjustment_type','addition') === 'addition' ? 'selected' : '' }}>Addition (Earning)</option>
                            <option value="deduction" {{ old('adjustment_type') === 'deduction' ? 'selected' : '' }}>Deduction</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount') }}" min="0.01" step="0.01" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Recurrence <span class="text-danger">*</span></label>
                        <select name="recurrence" class="form-select" required id="recurrenceSelect">
                            <option value="one-time" {{ old('recurrence','one-time') === 'one-time' ? 'selected' : '' }}>One-Time</option>
                            <option value="monthly" {{ old('recurrence') === 'monthly' ? 'selected' : '' }}>Monthly (Ongoing)</option>
                            <option value="fixed_installments" {{ old('recurrence') === 'fixed_installments' ? 'selected' : '' }}>Fixed Installments</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Period <span class="text-danger">*</span></label>
                        <input type="month" name="start_period" class="form-control" value="{{ old('start_period', now()->format('Y-m')) }}" required>
                    </div>

                    <div class="col-md-4" id="endPeriodField">
                        <label class="form-label">End Period</label>
                        <input type="month" name="end_period" class="form-control" value="{{ old('end_period') }}">
                    </div>

                    <div class="col-md-4 d-none" id="installmentsField">
                        <label class="form-label">Number of Installments</label>
                        <input type="number" name="remaining_installments" class="form-control" value="{{ old('remaining_installments') }}" min="1">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Reason / Justification <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain the business reason for this adjustment...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-info py-2 mb-0">
                            <i class="fi fi-rr-info me-2"></i>
                            This adjustment will be submitted as <strong>pending</strong> and must be approved before it affects the next payroll run.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.payroll.adjustments.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="fi fi-rr-paper-plane me-1"></i> Submit for Approval</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('recurrenceSelect').addEventListener('change', function() {
    const isInstallments = this.value === 'fixed_installments';
    const isOngoing      = this.value === 'monthly';
    document.getElementById('installmentsField').classList.toggle('d-none', !isInstallments);
    document.getElementById('endPeriodField').classList.toggle('d-none', isOngoing);
});
</script>
</x-app-layout>
