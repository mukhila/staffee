<!-- begin::GXON Sidebar Menu -->
<aside class="app-menubar" id="appMenubar">
  <div class="app-navbar-brand">
    <a class="navbar-brand-logo" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee logo" class="h-11 w-11 rounded-2xl border border-slate-200 object-cover shadow-sm">
    </a>
    <a class="navbar-brand-mini visible-light" href="{{ route('dashboard') }}">
       <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee logo" class="h-11 w-11 rounded-2xl border border-slate-200 object-cover shadow-sm">
    </a>
    <a class="navbar-brand-mini visible-dark" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/images/logo.png') }}" alt="Staffee logo" class="h-11 w-11 rounded-2xl border border-slate-200 object-cover shadow-sm">
    </a>
  </div>
  <nav class="app-navbar" data-simplebar>
    <ul class="menubar">
      <li class="menu-item">
        <a class="menu-link" href="{{ route('dashboard') }}">
          <i class="fi fi-rr-apps"></i>
          <span class="menu-label">Dashboard</span>
        </a>
      </li>

      @if(Auth::user()->isAdmin())
      <li class="menu-heading">
        <span class="menu-label">Staff Management</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.staff.index') }}">
          <i class="fi fi-rr-users"></i>
          <span class="menu-label">Staff</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.departments.index') }}">
          <i class="fi fi-rr-building"></i>
          <span class="menu-label">Departments</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.roles.index') }}">
          <i class="fi fi-rr-id-badge"></i>
          <span class="menu-label">Roles</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.leaves.index') }}">
          <i class="fi fi-rr-calendar-exclamation"></i>
          <span class="menu-label">Leave Requests</span>
          @php $pendingLeaves = \App\Models\LeaveRequest::where('status','pending')->count(); @endphp
          @if($pendingLeaves > 0)
          <span class="badge bg-warning ms-auto">{{ $pendingLeaves }}</span>
          @endif
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.attendances.index') }}">
          <i class="fi fi-rr-calendar-clock"></i>
          <span class="menu-label">Attendance</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.shifts.dashboard') }}">
          <i class="fi fi-rr-time-half-past"></i>
          <span class="menu-label">Shifts</span>
          @php $pendingExceptions = \App\Models\Shift\AttendanceException::where('status','pending')->whereDate('date','>=',now()->subDays(7))->count(); @endphp
          @if($pendingExceptions > 0)
          <span class="badge bg-danger ms-auto">{{ $pendingExceptions }}</span>
          @endif
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">HR Management</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.hr.dashboard') }}">
          <i class="fi fi-rr-people"></i>
          <span class="menu-label">HR Dashboard</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.hr.employees.index') }}">
          <i class="fi fi-rr-id-card-clip-alt"></i>
          <span class="menu-label">Employees</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.hr.promotions.index') }}">
          <i class="fi fi-rr-arrow-up"></i>
          <span class="menu-label">Promotions</span>
          @php $pendingPromotions = \App\Models\HR\PromotionRequest::whereIn('status',['pending_manager','manager_approved','hr_approved'])->count(); @endphp
          @if($pendingPromotions > 0)
          <span class="badge bg-info ms-auto">{{ $pendingPromotions }}</span>
          @endif
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.hr.resignations.index') }}">
          <i class="fi fi-rr-file-invoice"></i>
          <span class="menu-label">Resignations</span>
          @php $pendingResignations = \App\Models\HR\ResignationRequest::whereIn('status',['manager_reviewing','manager_accepted'])->count(); @endphp
          @if($pendingResignations > 0)
          <span class="badge bg-warning ms-auto">{{ $pendingResignations }}</span>
          @endif
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.hr.terminations.index') }}">
          <i class="fi fi-rr-user-minus"></i>
          <span class="menu-label">Terminations</span>
          @php $activeTerminations = \App\Models\HR\TerminationRequest::whereNotIn('status',['completed','cancelled'])->count(); @endphp
          @if($activeTerminations > 0)
          <span class="badge bg-danger ms-auto">{{ $activeTerminations }}</span>
          @endif
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Project Management</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.projects.index') }}">
          <i class="fi fi-rr-briefcase"></i>
          <span class="menu-label">Projects</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.tasks.index') }}">
          <i class="fi fi-rr-list-check"></i>
          <span class="menu-label">All Tasks</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Finance</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.payroll.runs.index') }}">
          <i class="fi fi-rr-payroll"></i>
          <span class="menu-label">Payroll Runs</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.payroll.salary-structures.index') }}">
          <i class="fi fi-rr-sack-dollar"></i>
          <span class="menu-label">Salary Structures</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Time Tracking</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.time.index') }}">
          <i class="fi fi-rr-clock"></i>
          <span class="menu-label">Time Logs</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.time.reports.index') }}">
          <i class="fi fi-rr-chart-line-up"></i>
          <span class="menu-label">Time Reports</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Communication</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.announcements.index') }}">
          <i class="fi fi-rr-megaphone"></i>
          <span class="menu-label">Announcements</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Analytics</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.reports.index') }}">
          <i class="fi fi-rr-chart-histogram"></i>
          <span class="menu-label">Reports</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Monitoring</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.monitoring.index') }}">
          <i class="fi fi-rr-desktop"></i>
          <span class="menu-label">Live Status</span>
          @php
            $onlineCount = \App\Models\Monitoring\MonitoringSession::where('status','active')
              ->where('last_heartbeat_at','>=',now()->subMinutes(3))->count();
          @endphp
          @if($onlineCount > 0)
          <span class="badge bg-success ms-auto">{{ $onlineCount }}</span>
          @endif
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.monitoring.settings.index') }}">
          <i class="fi fi-rr-shield-check"></i>
          <span class="menu-label">Settings & Tokens</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Settings</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.settings.index') }}">
          <i class="fi fi-rr-settings"></i>
          <span class="menu-label">Company Settings</span>
        </a>
      </li>
      @endif

      <li class="menu-heading">
        <span class="menu-label">Work</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('staff.tasks.index') }}">
          <i class="fi fi-rr-list-check"></i>
          <span class="menu-label">My Tasks</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('kanban.index') }}">
          <i class="fi fi-rr-layout-fluid"></i>
          <span class="menu-label">Kanban Board</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('staff.test-cases.index') }}">
          <i class="fi fi-rr-test-tube"></i>
          <span class="menu-label">Test Cases</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('staff.bugs.index') }}">
          <i class="fi fi-rr-bug"></i>
          <span class="menu-label">Bugs</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('staff.daily-status-reports.index') }}">
          <i class="fi fi-rr-document"></i>
          <span class="menu-label">DSR</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('staff.leaves.index') }}">
          <i class="fi fi-rr-calendar-minus"></i>
          <span class="menu-label">My Leaves</span>
        </a>
      </li>

      <li class="menu-heading">
        <span class="menu-label">Communication</span>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('chat.index') }}">
          <i class="fi fi-rr-comment-alt"></i>
          <span class="menu-label">Chat</span>
        </a>
      </li>
      <li class="menu-item">
        <a class="menu-link" href="{{ route('mail.index') }}">
          <i class="fi fi-rr-envelope"></i>
          <span class="menu-label">Mail</span>
        </a>
      </li>
    </ul>
  </nav>
  <div class="app-footer">
    <a href="#" class="btn btn-outline-light waves-effect btn-shadow btn-app-nav w-100">
      <i class="fi fi-rs-interrogation text-primary"></i>
      <span class="nav-text">Help and Support</span>
    </a>
  </div>
</aside>
<!-- end::GXON Sidebar Menu -->
