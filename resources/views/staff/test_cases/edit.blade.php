<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Edit Test Case</h1>
            <span>Update test case details</span>
          </div>
          <a href="{{ route('staff.test-cases.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.test-cases.update', $testCase->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ $testCase->project_id == $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $testCase->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $testCase->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" {{ $testCase->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="need_to_test" {{ $testCase->status == 'need_to_test' ? 'selected' : '' }}>Need to Test</option>
                            <option value="pass" {{ $testCase->status == 'pass' ? 'selected' : '' }}>Pass</option>
                            <option value="fail" {{ $testCase->status == 'fail' ? 'selected' : '' }}>Fail</option>
                        </select>
                        <div class="form-text text-warning">Note: Setting status to "Fail" will prompt you to create a bug.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Test Case</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
