<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Announcements</h1>
                <span>Broadcast messages to your team</span>
            </div>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> New Announcement
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-3">
            @forelse($announcements as $announcement)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-1">{{ $announcement->title }}</h6>
                            <small class="text-muted">
                                By {{ $announcement->creator->name }} &bull;
                                {{ $announcement->created_at->format('M d, Y') }} &bull;
                                <span class="badge bg-info">{{ ucfirst($announcement->audience) }}</span>
                            </small>
                        </div>
                        <span class="badge bg-{{ $announcement->is_active ? 'success' : 'secondary' }}">
                            {{ $announcement->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">{{ Str::limit($announcement->body, 150) }}</p>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5 text-muted">
                <i class="fi fi-rr-megaphone" style="font-size: 2rem;"></i>
                <p class="mt-2">No announcements yet. Create one now.</p>
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
