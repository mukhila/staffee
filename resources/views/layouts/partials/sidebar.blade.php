<!-- begin::GXON Sidebar Menu -->
<aside class="app-menubar" id="appMenubar">
  <div class="app-navbar-brand">
    <a class="navbar-brand-logo" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/images/logo.svg') }}" alt="GXON Admin Dashboard Logo">
    </a>
    <a class="navbar-brand-mini visible-light" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/images/logo-text.svg') }}" alt="GXON Admin Dashboard Logo">
    </a>
    <a class="navbar-brand-mini visible-dark" href="{{ route('dashboard') }}">
      <img src="{{ asset('assets/images/logo-text-white.svg') }}" alt="GXON Admin Dashboard Logo">
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
      <li class="menu-item">
        <a class="menu-link" href="{{ route('admin.attendances.index') }}">
          <i class="fi fi-rr-calendar-clock"></i>
          <span class="menu-label">Attendance</span>
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
        <a class="menu-link" href="{{ route('admin.kanban.index') }}">
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