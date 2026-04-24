<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <h1 class="app-page-title">New Time Category</h1>
        <a href="{{ route('admin.time.categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card" style="max-width:480px">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.time.categories.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="row g-3 mb-3">
                    <div class="col">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color"
                               value="{{ old('color', '#6366f1') }}" style="height:38px;width:100%">
                    </div>
                    <div class="col">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control"
                               value="{{ old('sort_order', 99) }}" min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_billable" id="is_billable"
                               value="1" {{ old('is_billable', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_billable">Billable category</label>
                    </div>
                    <div class="form-text">Time logged under billable categories counts toward utilization and revenue.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('admin.time.categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
