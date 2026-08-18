<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Job Postings</h1>
            <span>Manage recruitment postings and applications</span>
        </div>
        <a href="{{ route('admin.recruitment.postings.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Posting
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search title…" value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach(['draft','open','closed','on_hold'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-secondary btn-sm" type="submit">Filter</button>
                    <a href="{{ route('admin.recruitment.postings.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    @php
    $statusColors = ['draft'=>'secondary','open'=>'success','closed'=>'danger','on_hold'=>'warning'];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Openings</th>
                            <th>Status</th>
                            <th>Applications</th>
                            <th>Posted</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($postings as $posting)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $posting->title }}</div>
                                @if($posting->location)
                                <div class="text-muted small"><i class="fi fi-rr-marker me-1"></i>{{ $posting->location }}</div>
                                @endif
                            </td>
                            <td>{{ $posting->department?->name ?? '—' }}</td>
                            <td><span class="badge bg-light text-dark">{{ ucwords(str_replace('_',' ',$posting->employment_type)) }}</span></td>
                            <td>{{ $posting->openings }}</td>
                            <td>
                                @php $c = $statusColors[$posting->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucwords(str_replace('_',' ',$posting->status)) }}</span>
                            </td>
                            <td>{{ $posting->applications_count ?? $posting->applications()->count() }}</td>
                            <td class="small text-muted">{{ $posting->published_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.recruitment.postings.show', $posting) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                @if($posting->status === 'draft')
                                <form method="POST" action="{{ route('admin.recruitment.postings.publish', $posting) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success">Publish</button>
                                </form>
                                @elseif($posting->status === 'open')
                                <form method="POST" action="{{ route('admin.recruitment.postings.close', $posting) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger">Close</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-user-add fs-3 d-block mb-2 opacity-25"></i>
                                No job postings found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($postings->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $postings->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
