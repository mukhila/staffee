<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">New Announcement</h1>
            </div>
            <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>
        <div class="row">
            <div class="col-md-7">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.announcements.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title') }}" placeholder="Announcement title" required>
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Audience <span class="text-danger">*</span></label>
                                <select name="audience" class="form-select @error('audience') is-invalid @enderror" required>
                                    <option value="all" {{ old('audience') === 'all' ? 'selected' : '' }}>All Employees</option>
                                    <option value="staff" {{ old('audience') === 'staff' ? 'selected' : '' }}>Staff Only</option>
                                    <option value="pm" {{ old('audience') === 'pm' ? 'selected' : '' }}>Project Managers Only</option>
                                </select>
                                @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">Message <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control @error('body') is-invalid @enderror" rows="6"
                                    placeholder="Write your announcement..." required>{{ old('body') }}</textarea>
                                @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Publish</button>
                                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary waves-effect">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
