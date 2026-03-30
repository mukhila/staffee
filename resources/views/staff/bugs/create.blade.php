<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Report Bug</h1>
            <span>Create a new bug report</span>
          </div>
          <a href="{{ route('staff.bugs.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.bugs.store') }}" method="POST">
                    @csrf
                    @if($testCase)
                        <input type="hidden" name="test_case_id" value="{{ $testCase->id }}">
                        <div class="alert alert-warning">
                            Creating bug from failed test case: <strong>{{ $testCase->title }}</strong>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" {{ ($testCase && $testCase->project_id == $project->id) ? 'selected' : '' }}>{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label">Bug Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ $testCase ? 'Bug: ' . $testCase->title : '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ $testCase ? "Test Case Failed:\n" . $testCase->description : '' }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="severity" class="form-label">Severity</label>
                        <select class="form-select" id="severity" name="severity" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to" required>
                            <option value="">Select Project First</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Report Bug</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var projectSelect = document.getElementById('project_id');
            if (projectSelect.value) {
                loadProjectMembers(projectSelect.value);
            }

            projectSelect.addEventListener('change', function() {
                loadProjectMembers(this.value);
            });

            function loadProjectMembers(projectId) {
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
            }
        });
    </script>
</x-app-layout>
