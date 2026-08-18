<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Document Request #{{ $dr->id }}</h1>
                <span>Review and action this request</span>
            </div>
            <a href="{{ route('admin.document-requests.index') }}" class="btn btn-outline-secondary">
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
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0">Request Details</h6></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Employee</dt>
                            <dd class="col-sm-8">{{ $dr->user?->name }}</dd>
                            <dt class="col-sm-4">Document Type</dt>
                            <dd class="col-sm-8">{{ $dr->type_label }}</dd>
                            <dt class="col-sm-4">Purpose</dt>
                            <dd class="col-sm-8">{{ $dr->purpose ?: '—' }}</dd>
                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-{{ $dr->status_color }}">{{ ucfirst($dr->status) }}</span>
                            </dd>
                            <dt class="col-sm-4">Requested</dt>
                            <dd class="col-sm-8">{{ $dr->requested_at->format('d M Y, H:i') }}</dd>
                            @if($dr->fulfilled_at)
                            <dt class="col-sm-4">Fulfilled</dt>
                            <dd class="col-sm-8">{{ $dr->fulfilled_at->format('d M Y, H:i') }}</dd>
                            @endif
                            @if($dr->admin_notes)
                            <dt class="col-sm-4">Admin Notes</dt>
                            <dd class="col-sm-8">{{ $dr->admin_notes }}</dd>
                            @endif
                        </dl>

                        @if($dr->status === 'ready' && $dr->document_path)
                        <div class="mt-3">
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dr->document_path) }}"
                               target="_blank" class="btn btn-outline-success btn-sm">
                                <i class="fi fi-rr-download me-1"></i> Download Document
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                @if(in_array($dr->status, ['pending', 'processing']))
                {{-- Fulfill --}}
                <div class="card mb-3">
                    <div class="card-header"><h6 class="mb-0 text-success">Fulfill Request</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.document-requests.fulfill', $dr) }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Upload Document <span class="text-danger">*</span></label>
                                <input type="file" name="document" class="form-control @error('document') is-invalid @enderror"
                                       accept=".pdf,.doc,.docx" required>
                                <div class="form-text">PDF, DOC, DOCX — max 10MB</div>
                                @error('document')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fi fi-rr-check me-1"></i> Mark as Ready
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Reject --}}
                <div class="card">
                    <div class="card-header"><h6 class="mb-0 text-danger">Reject Request</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.document-requests.reject', $dr) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                <textarea name="admin_notes" class="form-control @error('admin_notes') is-invalid @enderror"
                                    rows="2" required></textarea>
                                @error('admin_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('Reject this request?')">
                                <i class="fi fi-rr-cross me-1"></i> Reject
                            </button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
