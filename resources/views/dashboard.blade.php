<x-app-layout>
    <div class="container">

        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Dashboard</h1>
            <span>{{ now()->format('D, M d, Y') }}</span>
          </div>
          @if(Auth::user()->isAdmin())
          <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary waves-effect waves-light">
            <i class="fi fi-rr-megaphone me-1"></i> Create Announcement
          </a>
          @endif
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row">

          <div class="col-xxl-9">

            <div class="row g-3">
              <!-- Attendance card -->
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Attendance</h6>
                    </div>
                    <div class="card-body">
                        @if($attendance)
                            <p><strong>Date:</strong> {{ $attendance->date }}</p>
                            <p><strong>Status:</strong> <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : 'warning' }}">{{ ucfirst($attendance->status) }}</span></p>
                            <p><strong>Check In:</strong> {{ $attendance->check_in }}</p>
                            @if($attendance->check_out)
                                <p><strong>Check Out:</strong> {{ $attendance->check_out }}</p>
                                <p class="mb-0"><strong>Working Hours:</strong> {{ $workingHours }}</p>
                            @else
                                <form action="{{ route('attendance.check-out') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100">Check Out</button>
                                </form>
                            @endif
                        @else
                            <p class="text-muted small mb-3">You haven't checked in today.</p>
                            <form action="{{ route('attendance.check-in') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">Check In</button>
                            </form>
                        @endif
                    </div>
                </div>
              </div>

              <!-- Pending tasks -->
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">My Pending Tasks</h6>
                        <a href="{{ route('staff.tasks.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body">
                        @if(count($pendingTasks) > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($pendingTasks as $task)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">{{ $task->title }}</h6>
                                                <small class="text-muted">Due: {{ $task->due_date ?? 'N/A' }}</small>
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

              <!-- Assigned bugs -->
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Assigned Bugs</h6>
                        <a href="{{ route('staff.bugs.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body">
                        @if(count($assignedBugs) > 0)
                            <ul class="list-group list-group-flush">
                                @foreach($assignedBugs as $bug)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0">{{ $bug->title }}</h6>
                                                <small class="text-muted">{{ ucfirst($bug->severity) }}</small>
                                            </div>
                                            <span class="badge bg-{{ $bug->status === 'open' ? 'danger' : 'warning' }}">{{ ucfirst(str_replace('_', ' ', $bug->status)) }}</span>
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

              @if(Auth::user()->isAdmin() && $adminStats)
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-secondary bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-secondary shadow-secondary rounded-circle text-white mb-3">
                      <i class="fi fi-sr-users"></i>
                    </div>
                    <h3>{{ $adminStats['total_staff'] }}</h3>
                    <h6 class="mb-0">Total Staff</h6>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-info bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-info shadow-info rounded-circle text-white mb-3">
                      <i class="fi fi-sr-briefcase"></i>
                    </div>
                    <h3>{{ $adminStats['active_projects'] }}</h3>
                    <h6 class="mb-0">Active Projects</h6>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-4 col-lg">
                <div class="card bg-warning bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-warning shadow-warning rounded-circle text-white mb-3">
                      <i class="fi fi-sr-delete-user"></i>
                    </div>
                    <h3>{{ $adminStats['on_leave'] }}</h3>
                    <h6 class="mb-0">On Leave Today</h6>
                  </div>
                </div>
              </div>
              <div class="col-6 col-md-6 col-lg">
                <div class="card bg-danger bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-danger shadow-danger rounded-circle text-white mb-3">
                      <i class="fi fi-sr-bug"></i>
                    </div>
                    <h3>{{ $adminStats['open_bugs'] }}</h3>
                    <h6 class="mb-0">Open Bugs</h6>
                  </div>
                </div>
              </div>
              <div class="col-12 col-md-6 col-lg">
                <div class="card bg-success bg-opacity-05 shadow-none border-0">
                  <div class="card-body">
                    <div class="avatar bg-success shadow-success rounded-circle text-white mb-3">
                      <i class="fi fi-sr-list-check"></i>
                    </div>
                    <h3>{{ $adminStats['pending_tasks'] }}</h3>
                    <h6 class="mb-0">Pending Tasks</h6>
                  </div>
                </div>
              </div>
              @endif
            </div>
          </div>

          <div class="col-xxl-3">
            <!-- Announcements -->
            <div class="card">
              <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Announcements</h6>
                @if(Auth::user()->isAdmin())
                <a href="{{ route('admin.announcements.create') }}" class="btn btn-sm btn-link">New</a>
                @endif
              </div>
              <div class="card-body p-0">
                @forelse($announcements as $announcement)
                <div class="p-3 border-bottom">
                  <div class="fw-medium small">{{ $announcement->title }}</div>
                  <div class="text-muted" style="font-size: 0.75rem;">{{ Str::limit($announcement->body, 100) }}</div>
                  <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ $announcement->created_at->diffForHumans() }}</div>
                </div>
                @empty
                <div class="p-3 text-center text-muted small">No announcements.</div>
                @endforelse
              </div>
              @if(Auth::user()->isAdmin())
              <div class="card-footer pt-0 border-0">
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-light waves-effect btn-shadow w-100">View All</a>
              </div>
              @endif
            </div>

            <!-- Quick links -->
            <div class="card mt-3">
              <div class="card-header border-0 pb-0">
                <h6 class="card-title mb-0">Quick Links</h6>
              </div>
              <div class="card-body p-0">
                <a href="{{ route('staff.leaves.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-calendar-minus text-warning"></i>
                  <span class="small">Apply for Leave</span>
                </a>
                <a href="{{ route('staff.daily-status-reports.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-document text-info"></i>
                  <span class="small">Submit DSR</span>
                </a>
                <a href="{{ route('staff.bugs.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-bug text-danger"></i>
                  <span class="small">Report a Bug</span>
                </a>
                <a href="{{ route('chat.index') }}" class="d-flex align-items-center gap-2 p-3 text-decoration-none text-dark">
                  <i class="fi fi-rr-comment-alt text-primary"></i>
                  <span class="small">Open Chat</span>
                </a>
              </div>
            </div>
          </div>

        </div>

    </div>
</x-app-layout>
