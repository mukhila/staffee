<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Issue Warning</h1>
            <span>Create a disciplinary or performance warning record</span>
        </div>
        <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('admin.hr.warnings.store') }}" method="POST">
                @csrf
                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">Employee <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">— Select Employee —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}"
                                {{ (old('user_id', $preselect?->id)) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}@if($emp->employee_id) ({{ $emp->employee_id }})@endif — {{ $emp->department?->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Warning Type <span class="text-danger">*</span></label>
                        <select name="warning_type" class="form-select" required>
                            <option value="">— Select Type —</option>
                            <option value="verbal"       {{ old('warning_type') === 'verbal'        ? 'selected' : '' }}>Verbal Warning</option>
                            <option value="written"      {{ old('warning_type') === 'written'       ? 'selected' : '' }}>Written Warning</option>
                            <option value="final_written"{{ old('warning_type') === 'final_written' ? 'selected' : '' }}>Final Written Warning</option>
                            <option value="suspension"   {{ old('warning_type') === 'suspension'    ? 'selected' : '' }}>Suspension</option>
                            <option value="pip"          {{ old('warning_type') === 'pip'           ? 'selected' : '' }}>Performance Improvement Plan (PIP)</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Incident Date <span class="text-danger">*</span></label>
                        <input type="date" name="incident_date" class="form-control"
                               max="{{ today()->format('Y-m-d') }}"
                               value="{{ old('incident_date', today()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Response Deadline</label>
                        <input type="date" name="response_deadline" class="form-control"
                               value="{{ old('response_deadline') }}">
                        <div class="form-text">Optional. Date by which the employee must respond or improve.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Incident Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required
                                  placeholder="Describe the incident, behaviour, or performance issue in detail...">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Improvement Plan / Expected Actions</label>
                        <textarea name="improvement_plan" class="form-control" rows="4"
                                  placeholder="List specific actions the employee must take to resolve the issue (optional for verbal warnings)...">{{ old('improvement_plan') }}</textarea>
                    </div>

                    <div class="col-12">
                        <div class="alert alert-warning mb-0 py-2">
                            <i class="fi fi-rr-triangle-warning me-2"></i>
                            This warning will be permanently stored on the employee's HR record. Ensure the details are accurate before submitting.
                        </div>
                    </div>

                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <a href="{{ route('admin.hr.warnings.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-danger">
                            <i class="fi fi-rr-triangle-warning me-1"></i> Issue Warning
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
