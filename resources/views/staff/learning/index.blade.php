<x-app-layout>
    <div class="container-fluid py-4">
        <h4 class="mb-4">Learning & Certification</h4>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        {{-- My enrollments --}}
        <h5 class="mb-3">My Courses</h5>
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Course</th>
                                <th>Provider</th>
                                <th>Status</th>
                                <th>Enrolled</th>
                                <th>Score</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($enrollments as $en)
                            <tr>
                                <td>{{ $en->course?->title }}</td>
                                <td>{{ $en->course?->provider ?? '—' }}</td>
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
                                <td>{{ $en->enrolled_at->format('d M Y') }}</td>
                                <td>{{ $en->completion_score ? $en->completion_score . '%' : '—' }}</td>
                                <td>
                                    <a href="{{ route('staff.learning.show', $en) }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">You have not enrolled in any courses.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Available courses --}}
        @if($availableCourses->isNotEmpty())
        <h5 class="mb-3">Available Courses</h5>
        <div class="row g-3">
            @foreach($availableCourses as $course)
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h6>{{ $course->title }}</h6>
                        <p class="text-muted small mb-1">{{ $course->provider ?? 'Internal' }}</p>
                        @if($course->description)
                        <p class="small">{{ Str::limit($course->description, 80) }}</p>
                        @endif
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            @if($course->duration_hours)
                                <span class="badge bg-light text-dark">{{ $course->duration_hours }}h</span>
                            @endif
                            @if($course->is_mandatory)
                                <span class="badge bg-danger">Mandatory</span>
                            @endif
                            <span class="badge bg-light text-dark">{{ $course->cost > 0 ? number_format($course->cost, 2) : 'Free' }}</span>
                        </div>
                    </div>
                    <div class="card-footer">
                        <form method="POST" action="{{ route('staff.learning.enroll', $course) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">Enroll</button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-app-layout>
