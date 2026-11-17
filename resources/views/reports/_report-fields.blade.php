<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}-report">Report</label>
    <select id="{{ $prefix }}-report" name="report" class="form-select" required>
        @foreach($available as $key => $report)
        <option value="{{ $key }}" data-department="{{ $report->supportsDepartment() ? 1 : 0 }}">{{ $report->label() }}</option>
        @endforeach
    </select>
</div>
<div class="mb-3 js-dept">
    <label class="form-label" for="{{ $prefix }}-department">Department</label>
    <select id="{{ $prefix }}-department" name="department_id" class="form-select">
        <option value="">All departments</option>
        @foreach($departments as $d)
        <option value="{{ $d->id }}">{{ $d->name }}</option>
        @endforeach
    </select>
</div>
