<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Create DSR</h1>
            <span>Submit your daily status report</span>
          </div>
          <a href="{{ route('staff.daily-status-reports.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        @if($activities->count() > 0)
        <div class="card mb-4">
            <div class="card-header border-0 pb-0">
                <h6 class="card-title mb-0">Today's Activity (Click to Fill)</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($activities as $activity)
                        <button type="button" class="btn btn-outline-secondary btn-sm activity-btn" 
                            data-title="{{ $activity['title'] }}" 
                            data-status="{{ $activity['status'] }}"
                            data-description="{{ $activity['description'] }}">
                            <span class="badge bg-primary me-1">{{ $activity['type'] }}</span> {{ $activity['title'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.daily-status-reports.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="report_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="report_date" name="report_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="task_name" class="form-label">Task Name</label>
                        <input type="text" class="form-control" id="task_name" name="task_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Report</button>
                </form>
            </div>
        </div>
    </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const activityBtns = document.querySelectorAll('.activity-btn');
            const taskNameInput = document.getElementById('task_name');
            const descriptionInput = document.getElementById('description');
            const statusSelect = document.getElementById('status');

            activityBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    taskNameInput.value = this.dataset.title;
                    descriptionInput.value = this.dataset.description;
                    
                    // Map status if possible
                    const status = this.dataset.status;
                    // Simple mapping, might need adjustment based on exact status strings
                    for (let i = 0; i < statusSelect.options.length; i++) {
                        if (statusSelect.options[i].value === status) {
                            statusSelect.selectedIndex = i;
                            break;
                        }
                    }
                });
            });
        });
    </script>
</x-app-layout>
