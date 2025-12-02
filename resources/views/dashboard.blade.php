<x-app-layout>
    <div class="container">

        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Dashboard</h1>
            <span>{{ now()->format('D, M d, Y') }}</span>
          </div>
          @if(Auth::user()->role === 'admin')
          <button type="button" class="btn btn-primary waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
            <i class="fi fi-rr-plus me-1"></i> Add Employee
          </button>
          @endif
        </div>

        <div class="row">

          <div class="col-xxl-9">

            <div class="row g-3">
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Attendance</h6>
                    </div>
                    <div class="card-body">
                        @if($attendance)
                            <div class="mb-3">
                                <p><strong>Date:</strong> {{ $attendance->date }}</p>
                                <p><strong>Status:</strong> <span class="badge bg-{{ $attendance->status == 'present' ? 'success' : 'warning' }}">{{ ucfirst($attendance->status) }}</span></p>
                                <p><strong>Check In:</strong> {{ $attendance->check_in }}</p>
                                @if($attendance->check_out)
                                    <p><strong>Check Out:</strong> {{ $attendance->check_out }}</p>
                                    <p><strong>Working Hours:</strong> {{ $workingHours }}</p>
                                @else
                                    <form action="{{ route('attendance.check-out') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-danger w-100">Check Out</button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <form action="{{ route('attendance.check-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Check In</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">My Pending Tasks</h6>
                        <a href="{{ route('staff.tasks.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body">
                        @if(isset($pendingTasks) && count($pendingTasks) > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($pendingTasks as $task)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">{{ $task->title }}</h6>
                                                <small class="text-muted">Due: {{ $task->due_date }}</small>
                                            </div>
                                            <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center my-4">No pending tasks.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Assigned Bugs</h6>
                        <a href="{{ route('staff.bugs.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body">
                        @if(isset($assignedBugs) && count($assignedBugs) > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($assignedBugs as $bug)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">{{ $bug->title }}</h6>
                                                <small class="text-muted">{{ ucfirst($bug->severity) }}</small>
                                            </div>
                                            <span class="badge bg-{{ $bug->status == 'open' ? 'danger' : 'warning' }}">{{ ucfirst(str_replace('_', ' ', $bug->status)) }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center my-4">No active bugs.</p>
                        @endif
                    </div>
                </div>
            </div>
            @if(Auth::user()->role === 'admin')
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-secondary bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-secondary shadow-secondary rounded-circle text-white mb-3">
                      <i class="fi fi-sr-users"></i>
                    </div>
                    <h3>1206</h3>
                    <h6 class="mb-0">Total Employee</h6>
                    <small class="fw-medium">
                      <span class="text-success">
                        <i class="fi fi-rr-arrow-small-up scale-3x"></i> +5%
                      </span> Last Month
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-info bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-info shadow-info rounded-circle text-white mb-3">
                      <i class="fi fi-sr-user-add"></i>
                    </div>
                    <h3>218</h3>
                    <h6 class="mb-0">New Employee</h6>
                    <small class="fw-medium">
                      <span class="text-success">
                        <i class="fi fi-rr-arrow-small-up scale-3x"></i> +3.2%
                      </span> Last Month
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-secondary bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-warning shadow-warning rounded-circle text-white mb-3">
                      <i class="fi fi-sr-delete-user"></i>
                    </div>
                    <h3>126</h3>
                    <h6 class="mb-0">On Leave</h6>
                    <small class="fw-medium">
                      <span class="text-danger">
                        <i class="fi fi-rr-arrow-small-down scale-3x"></i> -2%
                      </span> Last Month
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-6 col-lg">
                <div class="card bg-success bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-success shadow-success rounded-circle text-white mb-3">
                      <i class="fi fi-sr-shopping-bag"></i>
                    </div>
                    <h3>776</h3>
                    <h6 class="mb-0">Job Applicants</h6>
                    <small class="fw-medium">
                      <span class="text-success">
                        <i class="fi fi-rr-arrow-small-down scale-3x"></i> +8%
                      </span> Last Month
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6 col-lg">
                <div class="card bg-danger bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-danger shadow-danger rounded-circle text-white mb-3">
                      <i class="fi fi-sr-clock-three"></i>
                    </div>
                    <h3>1017</h3>
                    <h6 class="mb-0">Over Time</h6>
                    <small class="fw-medium">
                      <span class="text-danger">
                        <i class="fi fi-rr-arrow-small-down scale-3x"></i> -8%
                      </span> Last Month
                    </small>
                  </div>
                </div>
              </div>
            @endif
            </div>
          </div>

          <div class="col-xxl-3">
            <div class="card overflow-hidden z-1">
              <div class="card-body">
                <div class="w-75">
                  <h6 class="card-title">Create Announcement</h6>
                  <p>Make a announcement to your employee</p>
                </div>
                <img src="{{ asset('assets/images/media/svg/media1.svg') }}" alt="" class="position-absolute bottom-0 end-0 z-n1">
              </div>
              <div class="card-footer border-0 pt-0">
                <a href="#" class="btn btn-outline-light waves-effect btn-shadow">Create Now</a>
              </div>
            </div>
          </div>

        </div>

    </div>
</x-app-layout>
