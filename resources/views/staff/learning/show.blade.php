<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('staff.learning.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                <i class="fi fi-rr-arrow-left"></i>
            </a>
            <h4 class="mb-0">{{ $enrollment->course?->title }}</h4>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header"><strong>Enrollment Details</strong></div>
                    <div class="card-body">
                        @php
                            $ec = match($enrollment->status) {
                                'enrolled' => 'secondary', 'in_progress' => 'primary',
                                'completed' => 'success', 'dropped' => 'warning', 'failed' => 'danger',
                                default => 'secondary'
                            };
                        @endphp
                        <dl class="row mb-0">
                            <dt class="col-5">Status</dt>
                            <dd class="col-7"><span class="badge bg-{{ $ec }}">{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span></dd>
                            <dt class="col-5">Provider</dt>
                            <dd class="col-7">{{ $enrollment->course?->provider ?? '—' }}</dd>
                            <dt class="col-5">Category</dt>
                            <dd class="col-7">{{ $enrollment->course?->category ?? '—' }}</dd>
                            <dt class="col-5">Duration</dt>
                            <dd class="col-7">{{ $enrollment->course?->duration_hours ? $enrollment->course->duration_hours . ' hrs' : '—' }}</dd>
                            <dt class="col-5">Enrolled At</dt>
                            <dd class="col-7">{{ $enrollment->enrolled_at->format('d M Y') }}</dd>
                            @if($enrollment->completed_at)
                            <dt class="col-5">Completed At</dt>
                            <dd class="col-7">{{ $enrollment->completed_at->format('d M Y') }}</dd>
                            @endif
                            @if($enrollment->completion_score !== null)
                            <dt class="col-5">Score</dt>
                            <dd class="col-7">{{ $enrollment->completion_score }}%</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                @if($enrollment->status === 'enrolled')
                {{-- Start course --}}
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.learning.start', $enrollment) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fi fi-rr-play me-1"></i> Start Course
                            </button>
                        </form>
                    </div>
                </div>
                @endif

                @if(in_array($enrollment->status, ['enrolled', 'in_progress']))
                {{-- Complete course --}}
                <div class="card mb-3">
                    <div class="card-header"><strong>Mark as Completed</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.learning.complete', $enrollment) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Completion Score (%)</label>
                                <input type="number" name="completion_score" class="form-control" min="0" max="100" step="0.01"
                                    placeholder="Optional">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Certificate (PDF/Image)</label>
                                <input type="file" name="certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <button type="submit" class="btn btn-success">Mark Complete</button>
                        </form>
                    </div>
                </div>

                {{-- Drop course --}}
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('staff.learning.drop', $enrollment) }}"
                            onsubmit="return confirm('Are you sure you want to drop this course?')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">Drop Course</button>
                        </form>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
