<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Leave Types</h1>
            <span>Define and manage leave categories</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back to Requests
            </a>
            <a href="{{ route('admin.leaves.types.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> Add Type
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
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Paid</th>
                            <th>Max Days/Year</th>
                            <th>Half Day</th>
                            <th>Policies</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="rounded-circle d-inline-block" style="width:12px;height:12px;background:{{ $type->color }}"></span>
                                    <strong>{{ $type->name }}</strong>
                                </div>
                                @if($type->description)
                                <small class="text-muted">{{ Str::limit($type->description, 60) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $type->code }}</code></td>
                            <td><span class="badge bg-light text-dark">{{ $type->category_label }}</span></td>
                            <td>
                                @if($type->is_paid)
                                <span class="badge bg-success-subtle text-success">Paid</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary">Unpaid</span>
                                @endif
                            </td>
                            <td>{{ $type->max_days_per_year ?? '—' }}</td>
                            <td>
                                @if($type->allow_half_day)
                                <i class="fi fi-rr-check text-success"></i>
                                @else
                                <i class="fi fi-rr-minus text-muted"></i>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $type->policies_count }}</span>
                            </td>
                            <td>
                                @if($type->is_active)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.leaves.types.edit', $type) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fi fi-rr-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.leaves.types.destroy', $type) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this leave type? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center py-5 text-muted">No leave types defined yet. <a href="{{ route('admin.leaves.types.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
