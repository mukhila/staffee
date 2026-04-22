<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">{{ $project->name }}</h1>
                <span>
                    <span class="badge bg-{{ $project->status === 'active' ? 'success' : ($project->status === 'completed' ? 'primary' : 'warning') }}">
                        {{ ucfirst($project->status) }}
                    </span>
                    &bull; {{ $project->start_date }} → {{ $project->end_date ?? 'Ongoing' }}
                </span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-outline-primary waves-effect">Edit</a>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary waves-effect">
                    <i class="fi fi-rr-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-3">
            <!-- Info -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Description</h6></div>
                    <div class="card-body">
                        <p class="text-muted">{{ $project->description ?: 'No description provided.' }}</p>
                        @if($project->estimation_time)
                        <p class="mb-0"><strong>Estimated Time:</strong> {{ $project->estimation_time }}</p>
                        @endif
                    </div>
                </div>

                <!-- Tasks -->
                <div class="card mb-3">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between">
                        <h6 class="card-title mb-0">Tasks ({{ $project->tasks->count() }})</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Title</th><th>Assigned To</th><th>Status</th><th>Due</th></tr></thead>
                                <tbody>
                                    @forelse($project->tasks as $task)
                                    <tr>
                                        <td>{{ $task->title }}</td>
                                        <td>{{ $task->assignedUser->name ?? '-' }}</td>
                                        <td><span class="badge bg-{{ ['pending'=>'warning','in_progress'=>'info','review'=>'secondary','completed'=>'success'][$task->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                                        <td>{{ $task->due_date ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No tasks yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bugs -->
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Bugs ({{ $project->bugs->count() }})</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead><tr><th>Title</th><th>Severity</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse($project->bugs as $bug)
                                    <tr>
                                        <td>{{ $bug->title }}</td>
                                        <td><span class="badge bg-{{ ['critical'=>'danger','high'=>'warning','medium'=>'info','low'=>'success'][$bug->severity] ?? 'secondary' }}">{{ ucfirst($bug->severity) }}</span></td>
                                        <td><span class="badge bg-{{ $bug->status === 'open' ? 'danger' : 'success' }}">{{ ucfirst(str_replace('_',' ',$bug->status)) }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">No bugs reported.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Team -->
                <div class="card mb-3">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Team Members</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($project->users as $member)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <div class="avatar avatar-xs bg-primary rounded-circle text-white">
                                    {{ strtoupper(substr($member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="small fw-medium">{{ $member->name }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ ucfirst($member->role) }}</div>
                                </div>
                            </li>
                            @empty
                            <li class="list-group-item text-muted small text-center">No team members.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <!-- Documents -->
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Documents ({{ count($project->documents ?? []) }})</h6>
                    </div>
                    <div class="card-body p-0">
                        @forelse($project->documents ?? [] as $index => $doc)
                        <div class="d-flex align-items-center gap-2 p-2 border-bottom">
                            <i class="fi fi-rr-file text-primary"></i>
                            <span class="flex-grow-1 small text-truncate" title="{{ basename($doc) }}">{{ basename($doc) }}</span>
                            <a href="{{ route('admin.projects.documents.download', [$project, $index]) }}"
                               class="btn btn-icon btn-sm btn-outline-primary" title="Download">
                                <i class="fi fi-rr-download"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.projects.documents.delete', [$project, $index]) }}"
                                  onsubmit="return confirm('Delete this document?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Delete">
                                    <i class="fi fi-rr-trash"></i>
                                </button>
                            </form>
                        </div>
                        @empty
                        <div class="p-3 text-center text-muted small">No documents uploaded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
