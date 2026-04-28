<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Sprints</h1>
            <span>{{ $project->name }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.milestones.index', $project) }}" class="btn btn-outline-primary btn-sm">Milestones</a>
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
            @forelse($sprints as $sprint)
            @php
                $color = match($sprint->status) { 'active' => 'primary', 'completed' => 'success', 'cancelled' => 'danger', default => 'secondary' };
                $pct = $sprint->completionPercentage();
                $duration = $sprint->start_date->diffInDays($sprint->end_date) + 1;
            @endphp
            <div class="card mb-3 {{ $sprint->isActive() ? 'border-primary' : '' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-{{ $color }}">{{ ucfirst($sprint->status) }}</span>
                                <h6 class="mb-0 fw-semibold">{{ $sprint->name }}</h6>
                                @if($sprint->isActive()) <span class="badge bg-warning text-dark">Active</span> @endif
                            </div>
                            @if($sprint->goal)
                            <p class="small text-muted mb-2">{{ $sprint->goal }}</p>
                            @endif
                            <div class="d-flex gap-3 small text-muted mb-2">
                                <span><i class="fi fi-rr-calendar me-1"></i>{{ $sprint->start_date->format('d M') }} → {{ $sprint->end_date->format('d M Y') }}</span>
                                <span><i class="fi fi-rr-clock me-1"></i>{{ $duration }} day(s)</span>
                                <span><i class="fi fi-rr-check me-1"></i>{{ $sprint->tasks_count }} task(s)</span>
                            </div>
                            <div class="progress mb-1" style="height:6px;"><div class="progress-bar bg-{{ $color }}" style="width:{{ $pct }}%"></div></div>
                            <div class="small text-muted">{{ $pct }}% complete · Velocity: {{ $sprint->velocity() }} tasks</div>
                        </div>
                        <div class="ms-3 d-flex gap-2">
                            <a href="{{ route('admin.projects.sprints.show', [$project, $sprint]) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSp{{ $sprint->id }}">Edit</button>
                            <form action="{{ route('admin.projects.sprints.destroy', [$project, $sprint]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this sprint?')">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Edit Modal --}}
            <div class="modal fade" id="editSp{{ $sprint->id }}" tabindex="-1">
                <div class="modal-dialog"><div class="modal-content">
                    <form action="{{ route('admin.projects.sprints.update', [$project, $sprint]) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="modal-header"><h5 class="modal-title">Edit Sprint</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $sprint->name }}" required></div>
                            <div class="mb-3"><label class="form-label">Goal</label><textarea name="goal" class="form-control" rows="2">{{ $sprint->goal }}</textarea></div>
                            <div class="row g-3 mb-3">
                                <div class="col"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control" value="{{ $sprint->start_date->format('Y-m-d') }}" required></div>
                                <div class="col"><label class="form-label">End Date</label><input type="date" name="end_date" class="form-control" value="{{ $sprint->end_date->format('Y-m-d') }}" required></div>
                            </div>
                            <div class="mb-3"><label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    @foreach(['planned','active','completed','cancelled'] as $s)
                                    <option value="{{ $s }}" {{ $sprint->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                    </form>
                </div></div>
            </div>
            @empty
            <div class="card"><div class="card-body text-center text-muted py-5">No sprints yet. Create one to the right.</div></div>
            @endforelse
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">New Sprint</h6></div>
                <div class="card-body">
                    <form action="{{ route('admin.projects.sprints.store', $project) }}" method="POST">
                        @csrf
                        <div class="mb-3"><label class="form-label">Name <span class="text-danger">*</span></label>
                            <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Sprint 1" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3"><label class="form-label">Sprint Goal</label>
                            <textarea name="goal" class="form-control" rows="2" placeholder="What should be achieved?">{{ old('goal') }}</textarea>
                        </div>
                        <div class="mb-3"><label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3"><label class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary w-100">Create Sprint</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
