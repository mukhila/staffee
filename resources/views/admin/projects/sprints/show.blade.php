<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $sprint->name }}</h1>
            <span>{{ $project->name }} · {{ $sprint->start_date->format('d M') }} – {{ $sprint->end_date->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            @php $color = match($sprint->status){ 'active'=>'primary','completed'=>'success','cancelled'=>'danger', default=>'secondary' }; @endphp
            <span class="badge bg-{{ $color }} fs-6">{{ ucfirst($sprint->status) }}</span>
            <a href="{{ route('admin.projects.sprints.index', $project) }}" class="btn btn-secondary btn-sm">Back to Sprints</a>
        </div>
    </div>

    @if($sprint->goal)
    <div class="alert alert-info py-2 small mb-3"><strong>Sprint Goal:</strong> {{ $sprint->goal }}</div>
    @endif

    @php
        $byStatus = $sprint->tasks->groupBy('status');
        $pct = $sprint->completionPercentage();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-sm-3"><div class="card text-center"><div class="card-body py-3"><h4 class="mb-0">{{ $sprint->tasks->count() }}</h4><div class="small text-muted">Total Tasks</div></div></div></div>
        <div class="col-sm-3"><div class="card text-center"><div class="card-body py-3"><h4 class="mb-0">{{ $byStatus->get('completed', collect())->count() }}</h4><div class="small text-muted">Completed</div></div></div></div>
        <div class="col-sm-3"><div class="card text-center"><div class="card-body py-3"><h4 class="mb-0">{{ $byStatus->get('in_progress', collect())->count() }}</h4><div class="small text-muted">In Progress</div></div></div></div>
        <div class="col-sm-3"><div class="card text-center"><div class="card-body py-3"><h4 class="mb-0">{{ $pct }}%</h4><div class="small text-muted">Complete</div></div></div></div>
    </div>

    <div class="row g-3">
        @foreach(['pending'=>'secondary','in_progress'=>'primary','review'=>'warning','completed'=>'success'] as $status => $c)
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header border-0 pb-0 d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $c }}">{{ ucfirst(str_replace('_',' ',$status)) }}</span>
                    <span class="text-muted small">{{ $byStatus->get($status, collect())->count() }}</span>
                </div>
                <div class="card-body pt-2">
                    @forelse($byStatus->get($status, collect()) as $task)
                    <div class="p-2 mb-2 rounded border small">
                        <div class="fw-medium"><a href="{{ route('admin.tasks.show', $task) }}" class="text-decoration-none">{{ $task->title }}</a></div>
                        @if($task->assignedUser) <div class="text-muted mt-1">{{ $task->assignedUser->name }}</div> @endif
                        @if($task->due_date) <div class="text-muted">Due {{ $task->due_date->format('d M') }}</div> @endif
                    </div>
                    @empty
                    <div class="text-muted text-center py-3 small">No tasks</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
</x-app-layout>
