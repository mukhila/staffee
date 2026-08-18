<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Test Case Details</h1>
            <span>{{ $testCase->title }}</span>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('staff.test-cases.edit', $testCase->id) }}" class="btn btn-primary waves-effect waves-light">Edit</a>
            <a href="{{ route('staff.test-cases.index') }}" class="btn btn-secondary waves-effect waves-light">Back</a>
          </div>
        </div>

        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Title</dt>
                    <dd class="col-sm-9">{{ $testCase->title }}</dd>

                    <dt class="col-sm-3">Project</dt>
                    <dd class="col-sm-9">{{ $testCase->project->name ?? '—' }}</dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9">{{ $testCase->description ?? '—' }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $testCase->status)) }}</dd>

                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9">{{ $testCase->created_at->format('d M Y, h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
