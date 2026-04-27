<x-app-layout>
    <div class="container">

        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="clearfix">
            <h1 class="app-page-title">Dashboard</h1>
            <span>{{ now()->format('D, M d, Y') }}</span>
          </div>
          @if(Auth::user()->isAdmin())
          <div class="d-flex gap-2">
            <a href="{{ route('admin.payroll.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-calculator me-1"></i> Payroll Dashboard
            </a>
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary waves-effect waves-light btn-sm">
                <i class="fi fi-rr-megaphone me-1"></i> Announcement
            </a>
          </div>
          @endif
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="row g-3">

          <div class="col-xxl-9">

            {{-- ── Admin stat cards ─────────────────────────────────────────────── --}}
            @if(Auth::user()->isAdmin() && $adminStats)
            <div class="row g-3 mb-3">
              @php
              $statCards = [
                ['label'=>'Total Staff',     'value'=>$adminStats['total_staff'],        'icon'=>'fi-sr-users',      'color'=>'secondary'],
                ['label'=>'Active Projects', 'value'=>$adminStats['active_projects'],    'icon'=>'fi-sr-briefcase',  'color'=>'info'],
                ['label'=>'On Leave Today',  'value'=>$adminStats['on_leave'],           'icon'=>'fi-sr-delete-user','color'=>'warning'],
                ['label'=>'Open Bugs',       'value'=>$adminStats['open_bugs'],          'icon'=>'fi-sr-bug',        'color'=>'danger'],
                ['label'=>'Pending Tasks',   'value'=>$adminStats['pending_tasks'],      'icon'=>'fi-sr-list-check', 'color'=>'success'],
                ['label'=>'Pending Adj.',    'value'=>$adminStats['pending_adjustments'],'icon'=>'fi-sr-sack-dollar','color'=>'primary'],
              ];
              @endphp
              @foreach($statCards as $card)
              <div class="col-6 col-md-4 col-lg-2">
                <div class="card bg-{{ $card['color'] }} bg-opacity-05 shadow-none border-0 h-100">
                  <div class="card-body">
                    <div class="avatar bg-{{ $card['color'] }} shadow-{{ $card['color'] }} rounded-circle text-white mb-3">
                      <i class="fi {{ $card['icon'] }}"></i>
                    </div>
                    <h3>{{ $card['value'] }}</h3>
                    <h6 class="mb-0 small">{{ $card['label'] }}</h6>
                  </div>
                </div>
              </div>
              @endforeach
            </div>

            {{-- Admin HR Alerts --}}
            @if($adminAlerts && ($adminAlerts['probationAlerts']->count() || $adminAlerts['contractAlerts']->count()))
            <div class="row g-3 mb-3">
                @if($adminAlerts['probationAlerts']->count())
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-warning"><i class="fi fi-rr-triangle-warning me-1"></i> Probation Ending Soon</h6>
                            <span class="badge bg-warning">{{ $adminAlerts['probationAlerts']->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            @foreach($adminAlerts['probationAlerts'] as $emp)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div class="fw-medium small">{{ $emp->name }}</div>
                                <div class="text-warning small fw-medium">{{ \Carbon\Carbon::parse($emp->probation_end_date)->format('d M Y') }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                @if($adminAlerts['contractAlerts']->count())
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-danger"><i class="fi fi-rr-file-invoice me-1"></i> Contracts Expiring Soon</h6>
                            <span class="badge bg-danger">{{ $adminAlerts['contractAlerts']->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            @foreach($adminAlerts['contractAlerts'] as $emp)
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="fw-medium small">{{ $emp->name }}</div>
                                    <div class="text-muted" style="font-size:.72rem;">{{ ucwords(str_replace('_',' ',$emp->contract_type)) }}</div>
                                </div>
                                <div class="text-danger small fw-medium">{{ \Carbon\Carbon::parse($emp->contract_end_date)->format('d M Y') }}</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif
            @endif

            {{-- ── Staff widgets row ───────────────────────────────────────────── --}}
            <div class="row g-3 mb-3">

              {{-- Attendance card --}}
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Attendance</h6></div>
                    <div class="card-body">
                        @if($attendance)
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-{{ $attendance->status === 'present' ? 'success' : 'warning' }}">{{ ucfirst($attendance->status) }}</span>
                                <span class="text-muted small">{{ $attendance->date }}</span>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="text-muted small mb-1">Check In</div>
                                    <div class="fw-medium">{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small mb-1">Check Out</div>
                                    <div class="fw-medium">{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '—' }}</div>
                                </div>
                            </div>
                            @if($workingHours)
                                <div class="alert alert-success py-2 mb-3 text-center">
                                    <i class="fi fi-rr-clock me-1"></i> <strong>{{ $workingHours }}</strong> worked today
                                </div>
                            @endif
                            @if(!$attendance->check_out)
                                <form action="{{ route('attendance.check-out') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger w-100 btn-sm">Check Out</button>
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

              {{-- Leave Balances --}}
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Leave Balance</h6>
                        <span class="text-muted small">{{ now()->year }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($leaveBalances as $balance)
                        <div class="px-3 py-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-medium">{{ $balance->leaveType?->name ?? 'Leave' }}</span>
                                <span class="small text-muted">{{ number_format($balance->available_balance, 1) }} / {{ number_format(($balance->opening_balance + $balance->carry_forward_days + $balance->accrued_days), 1) }} days</span>
                            </div>
                            @php
                                $total = ($balance->opening_balance + $balance->carry_forward_days + $balance->accrued_days);
                                $pct = $total > 0 ? min(100, round($balance->available_balance / $total * 100)) : 0;
                                $barColor = $pct > 50 ? 'success' : ($pct > 20 ? 'warning' : 'danger');
                            @endphp
                            <div class="progress" style="height:4px;">
                                <div class="progress-bar bg-{{ $barColor }}" style="width:{{ $pct }}%"></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4 small">No leave balances for {{ now()->year }}.</div>
                        @endforelse
                    </div>
                    @if($leaveBalances->isNotEmpty())
                    <div class="card-footer border-0 pt-0">
                        <a href="{{ route('staff.leaves.create') }}" class="btn btn-sm btn-outline-primary w-100">Apply for Leave</a>
                    </div>
                    @endif
                </div>
              </div>

              {{-- Today's Time / Running Timer --}}
              <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Today's Time</h6></div>
                    <div class="card-body">
                        @php
                            $loggedH = floor($todayLoggedMinutes / 60);
                            $loggedM = $todayLoggedMinutes % 60;
                        @endphp
                        <div class="text-center mb-3">
                            <div class="fs-3 fw-bold text-primary">{{ sprintf('%02d', $loggedH) }}:{{ sprintf('%02d', $loggedM) }}</div>
                            <div class="text-muted small">hours logged today</div>
                        </div>

                        @if($runningTimer)
                        <div class="alert alert-success py-2 mb-0" style="border-radius:10px;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block bg-success rounded-circle" style="width:8px;height:8px;animation:pulse 1.5s infinite;flex-shrink:0;"></span>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small fw-medium text-truncate">{{ $runningTimer->trackable?->title ?? 'Timer Running' }}</div>
                                    <div class="text-muted" style="font-size:.7rem;">Started {{ $runningTimer->start_time->format('H:i') }}</div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center text-muted small">No active timer</div>
                        @endif
                    </div>
                    <div class="card-footer border-0 pt-0">
                        <a href="{{ route('staff.time-log.index') }}" class="btn btn-sm btn-outline-secondary w-100">View Time Log</a>
                    </div>
                </div>
              </div>
            </div>

            <div class="row g-3">
              {{-- Pending tasks --}}
              <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">My Pending Tasks</h6>
                        <a href="{{ route('staff.tasks.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body p-0">
                        @if(count($pendingTasks) > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($pendingTasks as $task)
                            <li class="list-group-item px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium small">{{ $task->title }}</div>
                                        <div class="text-muted" style="font-size:.72rem;">Due: {{ $task->due_date ?? 'N/A' }}</div>
                                    </div>
                                    <span class="badge bg-info-subtle text-info">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <p class="text-muted text-center my-4 small">No pending tasks.</p>
                        @endif
                    </div>
                </div>
              </div>

              {{-- Assigned bugs --}}
              <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">Assigned Bugs</h6>
                        <a href="{{ route('staff.bugs.index') }}" class="btn btn-sm btn-link">View All</a>
                    </div>
                    <div class="card-body p-0">
                        @if(count($assignedBugs) > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($assignedBugs as $bug)
                            <li class="list-group-item px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium small">{{ $bug->title }}</div>
                                        <div class="text-muted" style="font-size:.72rem;">{{ ucfirst($bug->severity) }}</div>
                                    </div>
                                    @php
                                    $bc = ['open'=>'danger','in_progress'=>'warning','resolved'=>'info'][$bug->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $bc }}-subtle text-{{ $bc }}">{{ ucfirst(str_replace('_',' ',$bug->status)) }}</span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                        @else
                        <p class="text-muted text-center my-4 small">No active bugs.</p>
                        @endif
                    </div>
                </div>
              </div>
            </div>
          </div>

          {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
          <div class="col-xxl-3">
            {{-- Announcements --}}
            <div class="card mb-3">
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
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-light waves-effect btn-shadow w-100 btn-sm">View All</a>
              </div>
              @endif
            </div>

            {{-- Quick links --}}
            <div class="card">
              <div class="card-header border-0 pb-0">
                <h6 class="card-title mb-0">Quick Links</h6>
              </div>
              <div class="card-body p-0">
                <a href="{{ route('staff.leaves.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-calendar-minus text-warning"></i>
                  <span class="small">Apply for Leave</span>
                </a>
                <a href="{{ route('staff.time-log.index') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-clock text-primary"></i>
                  <span class="small">Log Time</span>
                </a>
                <a href="{{ route('staff.daily-status-reports.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-document text-info"></i>
                  <span class="small">Submit DSR</span>
                </a>
                <a href="{{ route('staff.bugs.create') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-bug text-danger"></i>
                  <span class="small">Report a Bug</span>
                </a>
                <a href="{{ route('staff.attendance.index') }}" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none text-dark">
                  <i class="fi fi-rr-calendar-clock text-success"></i>
                  <span class="small">My Attendance</span>
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

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .3; }
}
</style>
</x-app-layout>
