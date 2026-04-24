<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Time Categories</h1>
            <span>Define billable vs non-billable categories</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.time.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Time Log
            </a>
            <a href="{{ route('admin.time.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> Add Category
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><i class="fi fi-rr-check me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Category</th>
                        <th>Billable</th>
                        <th class="text-end">Entries</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr>
                        <td><span class="text-muted small">{{ $cat->sort_order }}</span></td>
                        <td>
                            <span class="rounded-circle d-inline-block me-2" style="width:12px;height:12px;background:{{ $cat->color }}"></span>
                            <strong>{{ $cat->name }}</strong>
                        </td>
                        <td>
                            @if($cat->is_billable)
                            <span class="badge bg-success">Billable</span>
                            @else
                            <span class="badge bg-secondary">Non-Billable</span>
                            @endif
                        </td>
                        <td class="text-end"><span class="badge bg-light text-dark">{{ $cat->time_trackers_count }}</span></td>
                        <td>
                            @if($cat->is_active)
                            <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.time.categories.edit', $cat) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fi fi-rr-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.time.categories.destroy', $cat) }}" class="d-inline"
                                  onsubmit="return confirm('Delete this category?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fi fi-rr-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No categories defined.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-app-layout>
