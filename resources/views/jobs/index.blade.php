<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Openings — Staffee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark px-4">
    <span class="navbar-brand fw-bold">Staffee Careers</span>
</nav>

<div class="container py-5">
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        {{ session('success') }}
    </div>
    @endif

    <div class="mb-4">
        <h2 class="fw-bold">Open Positions</h2>
        <p class="text-muted">Join our growing team. Browse current openings below.</p>
    </div>

    @forelse($postings as $posting)
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1">{{ $posting->title }}</h5>
                    <div class="text-muted small">
                        @if($posting->department) <span class="me-3">{{ $posting->department->name }}</span> @endif
                        @if($posting->location) <span class="me-3">{{ $posting->location }}</span> @endif
                        <span>{{ ucwords(str_replace('_',' ',$posting->employment_type)) }}</span>
                    </div>
                </div>
                <a href="{{ route('jobs.show', $posting) }}" class="btn btn-primary btn-sm">Apply Now</a>
            </div>
            @if($posting->description)
            <p class="mt-2 mb-0 small text-muted">{{ Str::limit($posting->description, 200) }}</p>
            @endif
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <p class="fs-5">No open positions at the moment. Please check back soon.</p>
        </div>
    </div>
    @endforelse

    @if($postings->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $postings->links() }}
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
