<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Expense Categories</h1>
                <span>Manage expense categories</span>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fi fi-rr-plus me-1"></i> Add Category
            </button>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Requires Receipt</th>
                                <th>Claims</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $cat)
                            <tr>
                                <td>{{ $cat->name }}</td>
                                <td>{{ $cat->description ?: '—' }}</td>
                                <td>
                                    @if($cat->requires_receipt)
                                    <span class="badge bg-warning">Required</span>
                                    @else
                                    <span class="badge bg-secondary">Optional</span>
                                    @endif
                                </td>
                                <td>{{ $cat->claims_count }}</td>
                                <td class="text-end">
                                    <form action="{{ route('admin.expense-categories.destroy', $cat) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fi fi-rr-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No categories yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($categories->hasPages())
            <div class="card-footer">{{ $categories->links() }}</div>
            @endif
        </div>
    </div>

    {{-- Add Modal --}}
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.expense-categories.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Expense Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="requires_receipt" value="1" id="reqReceipt" checked>
                            <label class="form-check-label" for="reqReceipt">Requires Receipt</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
