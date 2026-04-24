<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">New Leave Type</h1>
            <span>Define a new leave category</span>
        </div>
        <a href="{{ route('admin.leaves.types.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.leaves.types.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" placeholder="e.g. Annual Leave" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}" placeholder="e.g. AL" required maxlength="20"
                               style="text-transform:uppercase" oninput="this.value=this.value.toUpperCase()">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color <span class="text-danger">*</span></label>
                        <input type="color" name="color" class="form-control form-control-color @error('color') is-invalid @enderror"
                               value="{{ old('color', '#3b82f6') }}" style="height:38px;width:100%" required>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">— Select category —</option>
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Max Days / Year</label>
                        <input type="number" name="max_days_per_year" class="form-control @error('max_days_per_year') is-invalid @enderror"
                               value="{{ old('max_days_per_year') }}" min="1" max="365" placeholder="Unlimited">
                        <div class="form-text">Leave blank for unlimited.</div>
                        @error('max_days_per_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">Options</label>
                        <div class="form-check form-check-inline mt-1">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid" value="1" {{ old('is_paid', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_paid">Paid</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="requires_approval" id="requires_approval" value="1" {{ old('requires_approval', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_approval">Requires Approval</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="allow_half_day" id="allow_half_day" value="1" {{ old('allow_half_day') ? 'checked' : '' }}>
                            <label class="form-check-label" for="allow_half_day">Allow Half Day</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="requires_document" id="requires_document" value="1" {{ old('requires_document') ? 'checked' : '' }}>
                            <label class="form-check-label" for="requires_document">Requires Document</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                                  rows="2" placeholder="Optional notes about this leave type">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fi fi-rr-check me-1"></i> Create Leave Type
                    </button>
                    <a href="{{ route('admin.leaves.types.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
