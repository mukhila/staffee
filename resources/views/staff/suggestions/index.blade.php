<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">My Suggestions</h1>
                <span>Share your ideas and feedback</span>
            </div>
            <a href="{{ route('staff.suggestions.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> New Suggestion
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
                                <th>Title</th>
                                <th>Category</th>
                                <th>Anonymous</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suggestions as $sug)
                            <tr>
                                <td>{{ $sug->id }}</td>
                                <td>{{ $sug->title }}</td>
                                <td>{{ $sug->category ?: '—' }}</td>
                                <td>
                                    @if($sug->is_anonymous)
                                    <span class="badge bg-secondary">Anonymous</span>
                                    @else
                                    <span class="text-muted small">No</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $sug->status_color }}">{{ $sug->status_label }}</span>
                                </td>
                                <td>{{ $sug->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($sug->admin_response)
                                    <span class="text-success small" title="{{ $sug->admin_response }}">
                                        <i class="fi fi-rr-check-circle me-1"></i>
                                        {{ \Illuminate\Support\Str::limit($sug->admin_response, 40) }}
                                    </span>
                                    @else
                                    <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No suggestions submitted yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($suggestions->hasPages())
            <div class="card-footer">{{ $suggestions->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
