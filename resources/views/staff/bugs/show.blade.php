<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Bug Details</h1>
            <span>{{ $bug->title }}</span>
          </div>
          <div class="d-flex gap-2">
            @if($bug->reported_by == auth()->id())
            <a href="{{ route('staff.bugs.edit', $bug->id) }}" class="btn btn-primary waves-effect waves-light">Edit</a>
            @endif
            <a href="{{ route('staff.bugs.index') }}" class="btn btn-secondary waves-effect waves-light">Back</a>
          </div>
        </div>

        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Title</dt>
                    <dd class="col-sm-9">{{ $bug->title }}</dd>

                    <dt class="col-sm-3">Project</dt>
                    <dd class="col-sm-9">{{ $bug->project->name ?? '—' }}</dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $bug->description ?? '—' }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $bug->status)) }}</dd>

                    <dt class="col-sm-3">Severity</dt>
                    <dd class="col-sm-9">{{ ucfirst($bug->severity) }}</dd>

                    <dt class="col-sm-3">Priority</dt>
                    <dd class="col-sm-9">{{ ucfirst($bug->priority ?? '—') }}</dd>

                    <dt class="col-sm-3">Assigned To</dt>
                    <dd class="col-sm-9">{{ $bug->assignedUser->name ?? '—' }}</dd>

                    @if($bug->resolution_notes)
                    <dt class="col-sm-3">Resolution Notes</dt>
                    <dd class="col-sm-9">{{ $bug->resolution_notes }}</dd>
                    @endif

                    <dt class="col-sm-3">Reported</dt>
                    <dd class="col-sm-9">{{ $bug->created_at->format('d M Y, h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
