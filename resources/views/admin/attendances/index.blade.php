<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Attendance</h1>
            <span>Manage staff attendance</span>
          </div>
        </div>

        <div class="card overflow-hidden">
            <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between border-0 pb-0">
              <h6 class="card-title mb-0">Attendance List</h6>
            </div>
            <div class="card-body px-3 pt-2 pb-0 gradient-layer">
              <table id="dt_Attendances" class="table display table-row-rounded">
                <thead class="table-light">
                  <tr>
                    <th class="minw-150px">Staff Name</th>
                    <th class="minw-150px">Date</th>
                    <th class="minw-150px">Check In</th>
                    <th class="minw-150px">Check Out</th>
                    <th class="minw-150px">Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                  <tr>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->date }}</td>
                    <td>{{ $attendance->check_in }}</td>
                    <td>{{ $attendance->check_out }}</td>
                    <td>
                        @if($attendance->status == 'present')
                            <span class="badge bg-success">Present</span>
                        @elseif($attendance->status == 'absent')
                            <span class="badge bg-danger">Absent</span>
                        @elseif($attendance->status == 'leave')
                            <span class="badge bg-warning">Leave</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($attendance->status) }}</span>
                        @endif
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button class="btn button-light-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                        <ul class="dropdown-menu" style="">
                            <li><a class="dropdown-item" href="{{ route('admin.attendances.edit', $attendance->id) }}">Edit</a></li>
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
