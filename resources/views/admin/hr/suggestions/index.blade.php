<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Suggestions</h1>
                <span>Employee suggestions & feedback</span>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-0 small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-primary">Filter</button>
                        <a href="{{ route('admin.suggestions.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
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
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suggestions as $sug)
                            <tr>
                                <td>{{ $sug->id }}</td>
                                <td>{{ $sug->title }}</td>
                                <td>{{ $sug->author_name }}</td>
                                <td>{{ $sug->category ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $sug->status_color }}">{{ $sug->status_label }}</span>
                                </td>
                                <td>{{ $sug->created_at->format('d M Y') }}</td>
                                <td>
                                    <a href="{{ route('admin.suggestions.show', $sug) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No suggestions found.</td>
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
