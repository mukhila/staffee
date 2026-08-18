<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Performance Cycle</h1>
            <span>Define a new review cycle</span>
        </div>
        <a href="{{ route('admin.performance.cycles.index') }}" class="btn btn-secondary btn-sm">Back</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('admin.performance.cycles.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Cycle Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. FY2026 Annual Review" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach(['draft','active','closed','archived'] as $s)
                            <option value="{{ $s }}" @selected(old('status', 'draft') === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Cycle Type <span class="text-danger">*</span></label>
                        <select name="cycle_type" class="form-select @error('cycle_type') is-invalid @enderror" required>
                            @foreach(['annual','half_yearly','quarterly','probation','adhoc'] as $t)
                            <option value="{{ $t }}" @selected(old('cycle_type') === $t)>{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                            @endforeach
                        </select>
                        @error('cycle_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Fiscal Year</label>
                        <input type="text" name="fiscal_year" class="form-control @error('fiscal_year') is-invalid @enderror"
                               value="{{ old('fiscal_year') }}" placeholder="e.g. 2026-2027" maxlength="9">
                        @error('fiscal_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Review Period Start <span class="text-danger">*</span></label>
                        <input type="date" name="review_period_start" class="form-control @error('review_period_start') is-invalid @enderror"
                               value="{{ old('review_period_start') }}" required>
                        @error('review_period_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Review Period End <span class="text-danger">*</span></label>
                        <input type="date" name="review_period_end" class="form-control @error('review_period_end') is-invalid @enderror"
                               value="{{ old('review_period_end') }}" required>
                        @error('review_period_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Submission Deadline <span class="text-danger">*</span></label>
                        <input type="date" name="submission_deadline" class="form-control @error('submission_deadline') is-invalid @enderror"
                               value="{{ old('submission_deadline') }}" required>
                        @error('submission_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Calibration Deadline</label>
                        <input type="date" name="calibration_deadline" class="form-control @error('calibration_deadline') is-invalid @enderror"
                               value="{{ old('calibration_deadline') }}">
                        @error('calibration_deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Cycle</button>
                    <a href="{{ route('admin.performance.cycles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
