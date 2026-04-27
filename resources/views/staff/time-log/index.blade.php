<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Time Log</h1>
            <span>View and manually log time entries</span>
        </div>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logTimeModal">
            <i class="fi fi-rr-plus me-1"></i> Log Time
        </button>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Project</label>
                    <select name="project" class="form-select form-select-sm">
                        <option value="">All Projects</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('staff.time-log.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary --}}
    <div class="alert alert-info py-2 mb-3">
        <i class="fi fi-rr-clock me-2"></i>
        <strong>{{ $totalHours }}h</strong> total logged for the current filter
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Task / Item</th>
                            <th>Project</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Duration</th>
                            <th>Description</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="fw-medium">{{ $entry->start_time->format('d M Y') }}</td>
                            <td>
                                @if($entry->trackable)
                                <div class="fw-medium small">{{ $entry->trackable->title ?? $entry->trackable->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.72rem;">{{ class_basename($entry->trackable_type) }}</div>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $entry->project?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $entry->start_time->format('H:i') }}</td>
                            <td class="text-muted small">{{ $entry->end_time?->format('H:i') ?? '—' }}</td>
                            <td class="fw-medium">{{ $entry->duration_formatted }}</td>
                            <td class="text-muted small">{{ Str::limit($entry->description, 40) }}</td>
                            <td class="text-end">
                                <form action="{{ route('staff.time-log.destroy', $entry) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this time entry?')">
                                        <i class="fi fi-rr-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-clock fs-3 d-block mb-2 opacity-25"></i>
                                No time entries found. <a href="#" data-bs-toggle="modal" data-bs-target="#logTimeModal">Log your first entry.</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $entries->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Log Time Modal --}}
<div class="modal fade" id="logTimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('staff.time-log.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Log Time Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-sm py-2"><ul class="mb-0 small">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Task <span class="text-danger">*</span></label>
                        <select name="task_id" class="form-select" required>
                            <option value="">— Select Task —</option>
                            @foreach($tasks as $task)
                            <option value="{{ $task->id }}" {{ old('task_id') == $task->id ? 'selected' : '' }}>
                                {{ $task->title }} @if($task->project)({{ $task->project->name }})@endif
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Only your active tasks are listed.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control"
                               max="{{ today()->format('Y-m-d') }}"
                               value="{{ old('date', today()->format('Y-m-d')) }}" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '09:00') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '10:00') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="What did you work on?">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fi fi-rr-check me-1"></i> Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('logTimeModal')).show();
});
</script>
@endif
</x-app-layout>
