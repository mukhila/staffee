<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Onboarding: {{ $checklist->user?->name }}</h1>
            <span>{{ $checklist->template_name }}</span>
        </div>
        <a href="{{ route('admin.onboarding.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i>Back
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $checkStatusColors = ['pending'=>'secondary','in_progress'=>'warning','completed'=>'success'];
    $taskStatusColors = ['pending'=>'secondary','in_progress'=>'warning','done'=>'success','skipped'=>'dark'];
    $cs = $checkStatusColors[$checklist->status] ?? 'secondary';
    @endphp

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5">Hire</dt>
                        <dd class="col-7">{{ $checklist->user?->name ?? '—' }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            <span class="badge bg-{{ $cs }}-subtle text-{{ $cs }}">{{ ucfirst(str_replace('_',' ',$checklist->status)) }}</span>
                        </dd>
                        <dt class="col-5">Due</dt>
                        <dd class="col-7">{{ $checklist->due_date?->format('d M Y') ?? '—' }}</dd>
                        <dt class="col-5">Completed</dt>
                        <dd class="col-7">{{ $checklist->completed_at?->format('d M Y') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header"><strong>Tasks ({{ $checklist->tasks->count() }})</strong></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Task</th>
                                    <th>Assigned To</th>
                                    <th>Status</th>
                                    <th>Completed</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($checklist->tasks as $task)
                                <tr>
                                    <td class="text-muted small">{{ $task->sort_order }}</td>
                                    <td>
                                        <div class="fw-medium {{ $task->status === 'done' ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</div>
                                        @if($task->description)
                                        <div class="text-muted small">{{ $task->description }}</div>
                                        @endif
                                    </td>
                                    <td class="small">{{ $task->assignedTo?->name ?? '—' }}</td>
                                    <td>
                                        @php $tc = $taskStatusColors[$task->status] ?? 'secondary'; @endphp
                                        <span class="badge bg-{{ $tc }}-subtle text-{{ $tc }}">{{ ucfirst($task->status) }}</span>
                                    </td>
                                    <td class="small">
                                        @if($task->completed_at)
                                            {{ $task->completed_at->format('d M Y') }}
                                            <div class="text-muted">by {{ $task->completedBy?->name }}</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if(!in_array($task->status, ['done', 'skipped']))
                                        <form method="POST" action="{{ route('admin.onboarding.tasks.complete', $task) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-success">Done</button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
