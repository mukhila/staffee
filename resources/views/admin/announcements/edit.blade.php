<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Edit Announcement</h1>
            </div>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                            @csrf @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title', $announcement->title) }}" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Audience</label>
                                <select name="audience" class="form-select" required>
                                    <option value="all" {{ $announcement->audience === 'all' ? 'selected' : '' }}>All Employees</option>
                                    <option value="staff" {{ $announcement->audience === 'staff' ? 'selected' : '' }}>Staff Only</option>
                                    <option value="pm" {{ $announcement->audience === 'pm' ? 'selected' : '' }}>Project Managers Only</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Message</label>
                                <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="6" required>{{ old('body', $announcement->body) }}</textarea>
                                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
                                    {{ $announcement->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active (visible to employees)</label>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Save Changes</button>
                                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary waves-effect">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
