<x-app-layout>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Learning Courses</h4>
            <a href="{{ route('admin.learning.courses.create') }}" class="btn btn-primary">
                <i class="fi fi-rr-plus me-1"></i> Add Course
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Provider</th>
                                <th>Category</th>
                                <th>Duration</th>
                                <th>Cost</th>
                                <th>Mandatory</th>
                                <th>Status</th>
                                <th>Enrolled</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                            <tr>
                                <td>{{ $course->title }}</td>
                                <td>{{ $course->provider ?? '—' }}</td>
                                <td>{{ $course->category ?? '—' }}</td>
                                <td>{{ $course->duration_hours ? $course->duration_hours . 'h' : '—' }}</td>
                                <td>{{ $course->cost > 0 ? number_format($course->cost, 2) : 'Free' }}</td>
                                <td>
                                    @if($course->is_mandatory)
                                        <span class="badge bg-danger">Yes</span>
                                    @else
                                        <span class="text-muted">No</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $sc = match($course->status) { 'active' => 'success', 'draft' => 'warning', 'archived' => 'secondary', default => 'secondary' };
                                    @endphp
                                    <span class="badge bg-{{ $sc }}">{{ ucfirst($course->status) }}</span>
                                </td>
                                <td>{{ $course->enrollments_count }}</td>
                                <td>
                                    <a href="{{ route('admin.learning.courses.show', $course) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">No courses found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="mt-3">{{ $courses->links() }}</div>
    </div>
</x-app-layout>
