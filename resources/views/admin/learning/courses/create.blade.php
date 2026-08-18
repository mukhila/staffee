<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.learning.courses.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="fi fi-rr-arrow-left"></i>
            </a>
            <h4 class="mb-0">Create Learning Course</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.learning.courses.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" class="form-control" value="{{ old('provider') }}" placeholder="e.g. Udemy, Coursera">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category') }}" placeholder="e.g. Technical, Leadership">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                                <option value="draft" @selected(old('status') === 'draft')>Draft</option>
                                <option value="archived" @selected(old('status') === 'archived')>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration (hours)</label>
                            <input type="number" name="duration_hours" class="form-control" step="0.5" min="0" value="{{ old('duration_hours') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cost</label>
                            <input type="number" name="cost" class="form-control" step="0.01" min="0" value="{{ old('cost', '0') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="hidden" name="is_mandatory" value="0">
                                <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="isMandatory"
                                    @checked(old('is_mandatory'))>
                                <label class="form-check-label" for="isMandatory">Mandatory for all employees</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Create Course</button>
                        <a href="{{ route('admin.learning.courses.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
