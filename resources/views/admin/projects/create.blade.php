<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Create Project</h1>
            <span>Add a new project</span>
          </div>
          <a href="{{ route('admin.projects.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.projects.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Project Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="estimation_time" class="form-label">Estimation Time</label>
                        <input type="text" class="form-control" id="estimation_time" name="estimation_time" placeholder="e.g. 2 weeks, 100 hours">
                    </div>
                    <div class="mb-3">
                        <label for="team_members" class="form-label">Team Members</label>
                        <select class="form-select" id="team_members" name="team_members[]" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }})</option>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl (Windows) or Command (Mac) to select multiple.</div>
                    </div>
                    <div class="mb-3">
                        <label for="documents" class="form-label">Documents</label>
                        <input type="file" class="form-control" id="documents" name="documents[]" multiple>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Project</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
