<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.assets.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="fi fi-rr-arrow-left"></i>
            </a>
            <h4 class="mb-0">Add New Asset</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.assets.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                            <input type="text" name="asset_tag" class="form-control @error('asset_tag') is-invalid @enderror"
                                value="{{ old('asset_tag') }}" placeholder="e.g. LAP-001" required>
                            @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select category</option>
                                @foreach(['laptop','desktop','phone','tablet','monitor','peripheral','vehicle','furniture','software_license','other'] as $cat)
                                    <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                                @endforeach
                            </select>
                            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ old('brand') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model Number</label>
                            <input type="text" name="model_number" class="form-control" value="{{ old('model_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Cost</label>
                            <input type="number" name="purchase_cost" class="form-control" step="0.01" min="0" value="{{ old('purchase_cost') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="form-control" value="{{ old('warranty_expiry') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Create Asset</button>
                        <a href="{{ route('admin.assets.index') }}" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
