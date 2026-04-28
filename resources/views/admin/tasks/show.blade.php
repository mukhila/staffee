<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $task->title }}</h1>
            <span>{{ $task->project->name }}</span>
        </div>
        <div class="d-flex gap-2">
            @php
                $sc = ['pending'=>'secondary','in_progress'=>'primary','review'=>'warning','completed'=>'success'][$task->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $sc }} fs-6">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
            <a href="{{ route('admin.tasks.edit', $task) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            <a href="{{ route('admin.tasks.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Details</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0 small">
                        <tr><th class="text-muted fw-normal ps-0">Project</th><td>{{ $task->project->name }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Assigned To</th><td>{{ $task->assignedUser?->name ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Start Date</th><td>{{ $task->start_date?->format('d M Y') ?? '—' }}</td></tr>
                        <tr><th class="text-muted fw-normal ps-0">Due Date</th>
                            <td class="{{ $task->due_date && $task->due_date->lt(today()) && $task->status !== 'completed' ? 'text-danger fw-bold' : '' }}">
                                {{ $task->due_date?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                        <tr><th class="text-muted fw-normal ps-0">Created</th><td>{{ $task->created_at->format('d M Y') }}</td></tr>
                    </table>
                </div>
            </div>
            @if($task->description)
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Description</h6></div>
                <div class="card-body"><p class="small mb-0" style="white-space:pre-wrap;">{{ $task->description }}</p></div>
            </div>
            @endif

            {{-- Task Dependencies --}}
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Dependencies</h6></div>
                <div class="card-body">
                    @if($task->isBlocked())
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="fi fi-rr-lock me-1"></i> This task is blocked by incomplete tasks below.
                    </div>
                    @endif

                    <p class="small text-muted mb-1">Blocked by (must complete first):</p>
                    @forelse($task->blockers as $blocker)
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div>
                            <span class="badge bg-{{ ['pending'=>'secondary','in_progress'=>'primary','review'=>'warning','completed'=>'success'][$blocker->status] ?? 'secondary' }} me-1">{{ ucfirst(str_replace('_',' ',$blocker->status)) }}</span>
                            <a href="{{ route('admin.tasks.show', $blocker) }}" class="small">{{ $blocker->title }}</a>
                        </div>
                        <form action="{{ route('admin.tasks.dependencies.remove', [$task->id, $blocker->id]) }}" method="POST">
                            @csrf @method('DELETE')
                            <button class="btn btn-link text-danger p-0 small">Remove</button>
                        </form>
                    </div>
                    @empty
                    <p class="small text-muted">No blockers.</p>
                    @endforelse

                    @if($candidateTasks->count())
                    <form action="{{ route('admin.tasks.dependencies.add', $task) }}" method="POST" class="d-flex gap-2 mt-3">
                        @csrf
                        <select name="blocker_id" class="form-select form-select-sm">
                            <option value="">— Add a blocker —</option>
                            @foreach($candidateTasks as $ct)
                            <option value="{{ $ct->id }}">{{ $ct->title }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-secondary btn-sm">Add</button>
                    </form>
                    @endif

                    @if($task->blocking->count())
                    <hr class="my-3">
                    <p class="small text-muted mb-1">Blocks (waiting on this task):</p>
                    @foreach($task->blocking as $blocked)
                    <div class="mb-1">
                        <span class="badge bg-{{ ['pending'=>'secondary','in_progress'=>'primary','review'=>'warning','completed'=>'success'][$blocked->status] ?? 'secondary' }} me-1">{{ ucfirst(str_replace('_',' ',$blocked->status)) }}</span>
                        <a href="{{ route('admin.tasks.show', $blocked) }}" class="small">{{ $blocked->title }}</a>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Comments <span class="badge bg-secondary ms-1">{{ $task->comments->count() }}</span></h6>
                </div>
                <div class="card-body">
                    {{-- Add comment --}}
                    <form action="{{ route('tasks.comments.store', $task) }}" method="POST" enctype="multipart/form-data" class="mb-4">
                        @csrf
                        @if($errors->any())
                        <div class="alert alert-danger py-2 small"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif
                        <div class="mb-2">
                            <textarea name="body" class="form-control form-control-sm" rows="3" placeholder="Add a comment…">{{ old('body') }}</textarea>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="file" name="attachment" class="form-control form-control-sm" style="max-width:260px;">
                            <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
                        </div>
                    </form>

                    {{-- Comments list --}}
                    @forelse($task->comments->sortByDesc('created_at') as $comment)
                    <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:36px;height:36px;background:#316AFF20;font-weight:600;color:#316AFF;font-size:.875rem;">
                            {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start">
                                <span class="fw-medium small">{{ $comment->user?->name }}</span>
                                <div class="d-flex gap-2 align-items-center">
                                    <span class="text-muted small">{{ $comment->created_at->diffForHumans() }}</span>
                                    @if($comment->user_id === auth()->id() || auth()->user()->isAdmin())
                                    <form action="{{ route('tasks.comments.destroy', $comment) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-link text-danger p-0 small" style="font-size:.75rem;">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                            <p class="small mb-1 mt-1" style="white-space:pre-wrap;">{{ $comment->body }}</p>
                            @if($comment->attachment_path)
                            <a href="{{ Storage::url($comment->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-secondary mt-1">
                                <i class="fi fi-rr-file me-1"></i> {{ $comment->attachment_name ?? 'Attachment' }}
                            </a>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-4">
                        <i class="fi fi-rr-comment-alt fs-3 d-block mb-2 opacity-25"></i>
                        No comments yet. Be the first to comment.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
