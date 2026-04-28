<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Edit Bug</h1>
            <span>{{ $bug->title }}</span>
          </div>
          <a href="{{ route('staff.bugs.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.bugs.update', $bug->id) }}" method="POST" id="bugEditForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <input type="text" class="form-control" value="{{ $bug->project->name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" value="{{ $bug->title }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" disabled>{{ $bug->description }}</textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="severity" class="form-label">Severity</label>
                            <select class="form-select" id="severity" name="severity" required>
                                @foreach(['low','medium','high','critical'] as $sev)
                                <option value="{{ $sev }}" {{ old('severity', $bug->severity) == $sev ? 'selected' : '' }}>{{ ucfirst($sev) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority" required>
                                @foreach(['low','medium','high','critical'] as $pri)
                                <option value="{{ $pri }}" {{ old('priority', $bug->priority ?? 'medium') == $pri ? 'selected' : '' }}>{{ ucfirst($pri) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        @php
                            $statusLabels = [
                                'open'        => 'Open',
                                'in_progress' => 'In Progress',
                                'resolved'    => 'Resolved',
                                'closed'      => 'Closed',
                            ];
                            $allowedNext = \App\Models\Bug::TRANSITIONS[$bug->status] ?? [];
                            $requiresNotes = \App\Models\Bug::REQUIRES_NOTES;
                            $currentStatus = old('status', $bug->status);
                        @endphp
                        @if(empty($allowedNext))
                            <input type="hidden" name="status" value="{{ $bug->status }}">
                            <input type="text" class="form-control" value="{{ $statusLabels[$bug->status] ?? $bug->status }}" disabled>
                            <div class="form-text text-muted">This bug is <strong>{{ $statusLabels[$bug->status] }}</strong> and cannot be transitioned further.</div>
                        @else
                            <select class="form-select" id="status" name="status" required onchange="toggleNotesField(this.value)">
                                <option value="{{ $bug->status }}" {{ $currentStatus == $bug->status ? 'selected' : '' }}>
                                    {{ $statusLabels[$bug->status] }} (current)
                                </option>
                                @foreach($allowedNext as $next)
                                <option value="{{ $next }}" {{ $currentStatus == $next ? 'selected' : '' }}>
                                    {{ $statusLabels[$next] ?? $next }}
                                    @if(in_array($next, $requiresNotes)) — requires notes @endif
                                </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="mb-3" id="resolutionNotesField" style="{{ in_array($currentStatus, $requiresNotes) ? '' : 'display:none;' }}">
                        <label for="resolution_notes" class="form-label">
                            Resolution Notes <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="resolution_notes" name="resolution_notes"
                                  rows="4" placeholder="Describe the resolution, steps taken, or reason for closing...">{{ old('resolution_notes', $bug->resolution_notes) }}</textarea>
                        <div class="form-text">Required when marking as Resolved or Closed.</div>
                    </div>

                    @if(!empty($allowedNext))
                    <button type="submit" class="btn btn-primary">Update Bug</button>
                    @endif
                    <a href="{{ route('staff.bugs.index') }}" class="btn btn-secondary ms-1">Cancel</a>
                </form>
            </div>
        </div>
    </div>

<script>
const requiresNotes = @json(\App\Models\Bug::REQUIRES_NOTES);

function toggleNotesField(status) {
    const field = document.getElementById('resolutionNotesField');
    const textarea = document.getElementById('resolution_notes');
    if (field) {
        const needed = requiresNotes.includes(status);
        field.style.display = needed ? '' : 'none';
        if (textarea) textarea.required = needed;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('status');
    if (sel) toggleNotesField(sel.value);
});
</script>
</x-app-layout>
