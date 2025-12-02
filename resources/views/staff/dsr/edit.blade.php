<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Edit DSR</h1>
            <span>Update your daily status report</span>
          </div>
          <a href="{{ route('staff.daily-status-reports.index') }}" class="btn btn-secondary waves-effect waves-light">
            Back
          </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('staff.daily-status-reports.update', $dailyStatusReport->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="report_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="report_date" name="report_date" value="{{ $dailyStatusReport->report_date }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label for="task_name" class="form-label">Task Name</label>
                        <input type="text" class="form-control" id="task_name" name="task_name" value="{{ $dailyStatusReport->task_name }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5" required>{{ $dailyStatusReport->description }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="{{ \Carbon\Carbon::parse($dailyStatusReport->start_time)->format('H:i') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="{{ \Carbon\Carbon::parse($dailyStatusReport->end_time)->format('H:i') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" {{ $dailyStatusReport->status == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="in_progress" {{ $dailyStatusReport->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="completed" {{ $dailyStatusReport->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Report</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
