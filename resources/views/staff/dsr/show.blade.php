<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Daily Status Report</h1>
            <span>{{ $dailyStatusReport->report_date }}</span>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ route('staff.daily-status-reports.edit', $dailyStatusReport->id) }}" class="btn btn-primary waves-effect waves-light">Edit</a>
            <a href="{{ route('staff.daily-status-reports.index') }}" class="btn btn-secondary waves-effect waves-light">Back</a>
          </div>
        </div>

        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Date</dt>
                    <dd class="col-sm-9">{{ $dailyStatusReport->report_date }}</dd>

                    <dt class="col-sm-3">Task Name</dt>
                    <dd class="col-sm-9">{{ $dailyStatusReport->task_name }}</dd>

                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9" style="white-space: pre-wrap;">{{ $dailyStatusReport->description }}</dd>

                    <dt class="col-sm-3">Start Time</dt>
                    <dd class="col-sm-9">{{ \Carbon\Carbon::parse($dailyStatusReport->start_time)->format('h:i A') }}</dd>

                    <dt class="col-sm-3">End Time</dt>
                    <dd class="col-sm-9">{{ \Carbon\Carbon::parse($dailyStatusReport->end_time)->format('h:i A') }}</dd>

                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $dailyStatusReport->status)) }}</dd>

                    <dt class="col-sm-3">Submitted</dt>
                    <dd class="col-sm-9">{{ $dailyStatusReport->created_at->format('d M Y, h:i A') }}</dd>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
