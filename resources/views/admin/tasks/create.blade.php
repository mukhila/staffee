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
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date">
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
