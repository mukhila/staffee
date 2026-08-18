<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="fi fi-rr-arrow-left"></i>
            </a>
            <h4 class="mb-0">Edit Asset — {{ $asset->asset_tag }}</h4>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.assets.update', $asset) }}">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Asset Tag <span class="text-danger">*</span></label>
                            <input type="text" name="asset_tag" class="form-control @error('asset_tag') is-invalid @enderror"
                                value="{{ old('asset_tag', $asset->asset_tag) }}" required>
                            @error('asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $asset->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                @foreach(['laptop','desktop','phone','tablet','monitor','peripheral','vehicle','furniture','software_license','other'] as $cat)
                                    <option value="{{ $cat }}" @selected(old('category', $asset->category) === $cat)>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                @foreach(['available','assigned','in_repair','retired','lost'] as $st)
                                    <option value="{{ $st }}" @selected(old('status', $asset->status) === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="{{ old('brand', $asset->brand) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model Number</label>
                            <input type="text" name="model_number" class="form-control" value="{{ old('model_number', $asset->model_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $asset->serial_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ old('location', $asset->location) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', $asset->purchase_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Cost</label>
                            <input type="number" name="purchase_cost" class="form-control" step="0.01" min="0" value="{{ old('purchase_cost', $asset->purchase_cost) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Warranty Expiry</label>
                            <input type="date" name="warranty_expiry" class="form-control" value="{{ old('warranty_expiry', $asset->warranty_expiry?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3">{{ old('notes', $asset->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
