<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Document Requests</h1>
                <span>Manage employee document requests</span>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-0 small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                                {{ ucfirst($s) }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-primary">Filter</button>
                        <a href="{{ route('admin.document-requests.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Document Type</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->id }}</td>
                                <td>{{ $req->user?->name ?? '—' }}</td>
                                <td>{{ $req->type_label }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($req->purpose, 50) ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $req->status_color }}">{{ ucfirst($req->status) }}</span>
                                </td>
                                <td>{{ $req->requested_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.document-requests.show', $req) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fi fi-rr-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No document requests found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($requests->hasPages())
            <div class="card-footer">{{ $requests->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
