<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Milestones</h1>
            <span>{{ $project->name }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.sprints.index', $project) }}" class="btn btn-outline-primary btn-sm">Sprints</a>
            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-secondary btn-sm">Back to Project</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            @forelse($milestones as $ms)
            @php
                $color = match($ms->status) { 'completed' => 'success', 'in_progress' => 'primary', 'missed' => 'danger', default => 'secondary' };
                $pct = $ms->completionPercentage();
            @endphp
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $ms->status)) }}</span>
                                <h6 class="mb-0 fw-semibold">{{ $ms->title }}</h6>
                                @if($ms->isOverdue())
                                <span class="badge bg-danger">Overdue</span>
                                @endif
                            </div>
                            @if($ms->description)
                            <p class="small text-muted mb-2">{{ $ms->description }}</p>
                            @endif
                            <div class="d-flex gap-3 small text-muted mb-2">
                                <span><i class="fi fi-rr-calendar me-1"></i>Due {{ $ms->due_date->format('d M Y') }}</span>
                                <span><i class="fi fi-rr-check me-1"></i>{{ $ms->tasks_count }} task(s)</span>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div>
                            </div>
                            <div class="small text-muted mt-1">{{ $pct }}% complete</div>
                        </div>
                        <div class="ms-3 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMs{{ $ms->id }}">Edit</button>
                            <form action="{{ route('admin.projects.milestones.destroy', [$project, $ms]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this milestone?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit Modal --}}
            <div class="modal fade" id="editMs{{ $ms->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form action="{{ route('admin.projects.milestones.update', [$project, $ms]) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-header"><h5 class="modal-title">Edit Milestone</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ $ms->title }}" required></div>
                            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ $ms->description }}</textarea></div>
                            <div class="mb-3"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control" value="{{ $ms->due_date->format('Y-m-d') }}" required></div>
                            <div class="mb-3"><label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach(['pending','in_progress','completed','missed'] as $s)
                                    <option value="{{ $s }}" {{ $ms->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                    </form>
                </div></div>
            </div>
            @empty
            <div class="card"><div class="card-body text-center text-muted py-5">No milestones yet. Add one to the right.</div></div>
            @endforelse
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Add Milestone</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.projects.milestones.store', $project) }}" method="POST">
                        @csrf
                        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label>
                            <input name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3"><label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                        </div>
                        <div class="mb-3"><label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100">Create Milestone</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
