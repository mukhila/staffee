<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Suggestion #{{ $suggestion->id }}</h1>
                <span>{{ $suggestion->title }}</span>
            </div>
            <a href="{{ route('admin.suggestions.index') }}" class="btn btn-outline-secondary">
                <i class="fi fi-rr-arrow-left me-1"></i> Back
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Suggestion Details</h6></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Submitted by</dt>
                            <dd class="col-sm-8">{{ $suggestion->author_name }}</dd>
                            <dt class="col-sm-4">Category</dt>
                            <dd class="col-sm-8">{{ $suggestion->category ?: '—' }}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $suggestion->status_color }}">{{ $suggestion->status_label }}</span>
                            </dd>
                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">{{ $suggestion->created_at->format('d M Y, H:i') }}</dd>
                        </dl>
                        <hr>
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $suggestion->body }}</p>
                    </div>
                </div>

                @if($suggestion->admin_response)
                <div class="card mt-3">
                    <div class="card-header"><h6 class="mb-0 text-success">HR Response</h6></div>
                    <div class="card-body">
                        <p class="mb-1" style="white-space: pre-wrap;">{{ $suggestion->admin_response }}</p>
                        @if($suggestion->respondedBy)
                        <small class="text-muted">— {{ $suggestion->respondedBy->name }}</small>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Respond</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.suggestions.respond', $suggestion) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Update Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    @foreach(\App\Models\HR\Suggestion::STATUS_LABELS as $key => $label)
                                    <option value="{{ $key }}" {{ $suggestion->status === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Response <span class="text-danger">*</span></label>
                                <textarea name="admin_response" class="form-control @error('admin_response') is-invalid @enderror"
                                    rows="5" required>{{ $suggestion->admin_response }}</textarea>
                                @error('admin_response')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fi fi-rr-check me-1"></i> Save Response
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
