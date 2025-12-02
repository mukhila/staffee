<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Daily Status Reports</h1>
            <span>Manage your daily reports</span>
          </div>
          <a href="{{ route('staff.daily-status-reports.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-plus me-1"></i> Create Report
          </a>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Report List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_DSR" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    @if(Auth::user()->isAdmin() || Auth::user()->role !== 'staff')
                    <th class="minw-150px">Staff Name</th>
                    @endif
                    <th class="minw-100px">Date</th>
                    <th class="minw-150px">Task Name</th>
                    <th class="minw-100px">Start Time</th>
                    <th class="minw-100px">End Time</th>
                    <th class="minw-100px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($reports as $report)
                  <tr>
                    @if(Auth::user()->isAdmin() || Auth::user()->role !== 'staff')
                    <td>{{ $report->user->name }}</td>
                    @endif
                    <td>{{ $report->report_date }}</td>
                    <td>{{ $report->task_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($report->start_time)->format('H:i') }}</td>
                    <td>{{ \Carbon\Carbon::parse($report->end_time)->format('H:i') }}</td>
                    <td><span class="badge bg-info">{{ ucfirst($report->status) }}</span></td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('staff.daily-status-reports.edit', $report->id) }}">Edit</a></li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
        </div>
    </div>
</x-app-layout>
