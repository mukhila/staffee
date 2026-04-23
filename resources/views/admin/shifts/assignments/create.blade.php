<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Assign Shift</h1>
            <span>Assign one shift to one or more employees</span>
        </div>
        <a href="{{ route('admin.shifts.assignments.index') }}" class="btn btn-secondary btn-sm">
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
            <form action="{{ route('admin.shifts.assignments.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Shift <span class="text-danger">*</span></label>
                        <select name="shift_id" class="form-select" required>
                            <option value="">— Select Shift —</option>
                            @foreach($shifts as $shift)
                            <option value="{{ $shift->id }}" {{ (old('shift_id', request('shift_id')) == $shift->id) ? 'selected' : '' }}>
                                {{ $shift->name }} ({{ $shift->code }}) — {{ substr($shift->start_time,0,5) }}–{{ substr($shift->end_time,0,5) }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Employees <span class="text-danger">*</span></label>
                        <div class="border rounded p-3" style="max-height:300px;overflow-y:auto;">
                            <div class="mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label fw-semibold" for="selectAll">Select All</label>
                                </div>
                            </div>
                            @foreach($employees->groupBy(fn($e) => $e->department?->name ?? 'No Department') as $dept => $emps)
                            <div class="mb-2">
                                <div class="text-muted small fw-semibold text-uppercase mb-1">{{ $dept }}</div>
                                @foreach($emps as $emp)
                                <div class="form-check">
                                    <input class="form-check-input emp-check" type="checkbox" name="user_ids[]"
                                           value="{{ $emp->id }}" id="emp_{{ $emp->id }}"
                                           {{ in_array($emp->id, old('user_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="emp_{{ $emp->id }}">
                                        {{ $emp->name }}
                                        <span class="text-muted small">{{ $emp->employee_id }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective From <span class="text-danger">*</span></label>
                        <input type="date" name="effective_from" class="form-control"
                               value="{{ old('effective_from', today()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Effective To</label>
                        <input type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                        <div class="form-text">Leave blank for an open-ended assignment.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this assignment">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.shifts.assignments.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fi fi-rr-user-add me-1"></i> Assign Shift
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.emp-check').forEach(cb => cb.checked = this.checked);
});
</script>
</x-app-layout>
