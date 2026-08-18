<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Assets & Equipment</h4>
            <a href="{{ route('admin.assets.create') }}" class="btn btn-primary">
                <i class="fi fi-rr-plus me-1"></i> Add Asset
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            @foreach(['laptop','desktop','phone','tablet','monitor','peripheral','vehicle','furniture','software_license','other'] as $cat)
                                <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ ucfirst(str_replace('_', ' ', $cat)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All Statuses</option>
                            @foreach(['available','assigned','in_repair','retired','lost'] as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_', ' ', $st)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary btn-sm w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tag</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Location</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                            <tr>
                                <td><code>{{ $asset->asset_tag }}</code></td>
                                <td>{{ $asset->name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $asset->category)) }}</td>
                                <td>
                                    @php
                                        $badgeClass = match($asset->status) {
                                            'available' => 'success',
                                            'assigned'  => 'primary',
                                            'in_repair' => 'warning',
                                            'retired'   => 'secondary',
                                            'lost'      => 'danger',
                                            default     => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $asset->status)) }}</span>
                                </td>
                                <td>{{ $asset->currentAssignment?->user?->name ?? '—' }}</td>
                                <td>{{ $asset->location ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.assets.show', $asset) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No assets found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $assets->links() }}</div>
    </div>
</x-app-layout>
