<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Job Posting</h1>
            <span>Create a new recruitment posting</span>
        </div>
        <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-outline-secondary btn-sm">
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
            <form method="POST" action="{{ route('admin.recruitment.postings.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Job Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}" required>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Employment Type <span class="text-danger">*</span></label>
                        <select name="employment_type" class="form-select @error('employment_type') is-invalid @enderror" required>
                            @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','internship'=>'Internship'] as $val=>$label)
                            <option value="{{ $val }}" @selected(old('employment_type') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Openings</label>
                        <input type="number" name="openings" class="form-control" value="{{ old('openings', 1) }}" min="1">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="draft" @selected(old('status','draft')==='draft')>Draft</option>
                            <option value="open" @selected(old('status')==='open')>Open</option>
                            <option value="on_hold" @selected(old('status')==='on_hold')>On Hold</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Salary Min</label>
                        <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min') }}" step="0.01">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Salary Max</label>
                        <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max') }}" step="0.01">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Closes At</label>
                        <input type="date" name="closes_at" class="form-control" value="{{ old('closes_at') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="5">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Requirements</label>
                        <textarea name="requirements" class="form-control" rows="5">{{ old('requirements') }}</textarea>
                    </div>

                    <div class="col-12 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Posting</button>
                        <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
