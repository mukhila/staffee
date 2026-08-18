<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">My Document Requests</h1>
                <span>Request HR documents</span>
            </div>
            <a href="{{ route('staff.document-requests.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> New Request
            </a>
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
                                <th>#</th>
                                <th>Document Type</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Fulfilled</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                            <tr>
                                <td>{{ $req->id }}</td>
                                <td>{{ $req->type_label }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($req->purpose, 50) ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $req->status_color }}">
                                        {{ ucfirst($req->status) }}
                                    </span>
                                </td>
                                <td>{{ $req->requested_at->format('d M Y') }}</td>
                                <td>{{ $req->fulfilled_at ? $req->fulfilled_at->format('d M Y') : '—' }}</td>
                                <td>
                                    @if($req->status === 'ready' && $req->document_path)
                                    <a href="{{ Storage::disk('public')->url($req->document_path) }}"
                                       target="_blank" class="btn btn-sm btn-outline-success">
                                        <i class="fi fi-rr-download"></i> Download
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No document requests yet.</td>
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
