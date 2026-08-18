<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="{{ route('admin.learning.courses.index') }}" class="btn btn-sm btn-outline-secondary me-3">
                    <i class="fi fi-rr-arrow-left"></i>
                </a>
                <h4 class="mb-0">{{ $course->title }}</h4>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-4">
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header"><strong>Course Details</strong></div>
                    <div class="card-body">
                        @php $sc = match($course->status) { 'active' => 'success', 'draft' => 'warning', 'archived' => 'secondary', default => 'secondary' }; @endphp
                        <dl class="row mb-0">
                            <dt class="col-5">Status</dt>
                            <dd class="col-7"><span class="badge bg-{{ $sc }}">{{ ucfirst($course->status) }}</span></dd>
                            <dt class="col-5">Provider</dt>
                            <dd class="col-7">{{ $course->provider ?? '—' }}</dd>
                            <dt class="col-5">Category</dt>
                            <dd class="col-7">{{ $course->category ?? '—' }}</dd>
                            <dt class="col-5">Duration</dt>
                            <dd class="col-7">{{ $course->duration_hours ? $course->duration_hours . ' hrs' : '—' }}</dd>
                            <dt class="col-5">Cost</dt>
                            <dd class="col-7">{{ $course->cost > 0 ? number_format($course->cost, 2) : 'Free' }}</dd>
                            <dt class="col-5">Mandatory</dt>
                            <dd class="col-7">{{ $course->is_mandatory ? 'Yes' : 'No' }}</dd>
                            <dt class="col-5">Created By</dt>
                            <dd class="col-7">{{ $course->createdBy?->name }}</dd>
                            @if($course->description)
                            <dt class="col-5">Description</dt>
                            <dd class="col-7">{{ $course->description }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card">
                    <div class="card-header"><strong>Enroll Employee</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.learning.courses.enroll', $course) }}" class="row g-2">
                            @csrf
                            <div class="col-md-8">
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select employee</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Enroll</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Enrollments --}}
        <div class="card mt-4">
            <div class="card-header"><strong>Enrollments ({{ $course->enrollments->count() }})</strong></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Enrolled At</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Completed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($course->enrollments as $en)
                            <tr>
                                <td>{{ $en->user?->name }}</td>
                                <td>{{ $en->enrolled_at->format('d M Y') }}</td>
                                <td>
                                    @php
                                        $ec = match($en->status) {
                                            'enrolled' => 'secondary', 'in_progress' => 'primary',
                                            'completed' => 'success', 'dropped' => 'warning', 'failed' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $ec }}">{{ ucfirst(str_replace('_', ' ', $en->status)) }}</span>
                                </td>
                                <td>{{ $en->completion_score ? $en->completion_score . '%' : '—' }}</td>
                                <td>{{ $en->completed_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No enrollments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
