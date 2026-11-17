<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Create Task</h1>
            <span>Add a new task</span>
          </div>
          <a href="{{ route('admin.tasks.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.tasks.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Task Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">Select Project First</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date') }}">
                            @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" step="0.25" min="0" class="form-control @error('estimated_hours') is-invalid @enderror" id="estimated_hours" name="estimated_hours" value="{{ old('estimated_hours') }}" placeholder="e.g. 6">
                            @error('estimated_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 form-text mt-n2 mb-3">Dates and an estimate let the task appear on the project timeline and in the team workload view.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('project_id').addEventListener('change', function() {
            var projectId = this.value;
            var assignedToSelect = document.getElementById('assigned_to');
            assignedToSelect.innerHTML = '<option value="">Loading...</option>';

            if (projectId) {
                fetch('/admin/api/projects/' + projectId + '/members')
                    .then(response => response.json())
                    .then(data => {
                        assignedToSelect.innerHTML = '<option value="">Select Staff</option>';
                        data.forEach(user => {
                            assignedToSelect.innerHTML += '<option value="' + user.id + '">' + user.name + ' (' + user.role + ')</option>';
                        });
                    });
            } else {
                assignedToSelect.innerHTML = '<option value="">Select Project First</option>';
            }
        });
    </script>
</x-app-layout>
