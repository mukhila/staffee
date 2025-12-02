<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Edit Bug</h1>
            <span>Update bug details</span>
          </div>
          <a href="{{ route('staff.bugs.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.bugs.update', $bug->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <input type="text" class="form-control" value="{{ $bug->project->name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" value="{{ $bug->title }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" disabled>{{ $bug->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="open" {{ $bug->status == 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $bug->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="resolved" {{ $bug->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="closed" {{ $bug->status == 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="severity" class="form-label">Severity</label>
                        <select class="form-select" id="severity" name="severity" required>
                            <option value="low" {{ $bug->severity == 'low' ? 'selected' : '' }}>Low</option>
                            <option value="medium" {{ $bug->severity == 'medium' ? 'selected' : '' }}>Medium</option>
                            <option value="high" {{ $bug->severity == 'high' ? 'selected' : '' }}>High</option>
                            <option value="critical" {{ $bug->severity == 'critical' ? 'selected' : '' }}>Critical</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Bug</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
