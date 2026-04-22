<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Project Progress Report</h1>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary waves-effect">
                <i class="fi fi-rr-arrow-left me-1"></i> Reports
            </a>
        </div>

        <div class="row g-3">
            @forelse($projects as $project)
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">{{ $project->name }}</h6>
                        <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'primary' : 'warning') }}">
                            {{ ucfirst($project->status) }}
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Progress</span>
                            <span>{{ $project->progress }}%</span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar bg-{{ $project->progress >= 80 ? 'success' : ($project->progress >= 40 ? 'info' : 'warning') }}"
                                style="width: {{ $project->progress }}%"></div>
                        </div>
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="fw-bold">{{ $project->total_tasks }}</div>
                                <div class="small text-muted">Tasks</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-success">{{ $project->completed_tasks }}</div>
                                <div class="small text-muted">Done</div>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold text-danger">{{ $project->open_bugs }}</div>
                                <div class="small text-muted">Open Bugs</div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted">{{ $project->users->count() }} team member(s)</span>
                            <span class="text-muted">
                                {{ $project->start_date }} → {{ $project->end_date ?? 'Ongoing' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12 text-center py-5 text-muted">No projects found.</div>
            @endforelse
        </div>
    </div>
</x-app-layout>
