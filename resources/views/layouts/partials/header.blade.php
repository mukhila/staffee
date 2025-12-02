    <!-- begin::GXON Page Header -->
    <header class="app-header">
      <div class="app-header-inner">
        <button class="app-toggler" type="button" aria-label="app toggler">
          <span></span>
          <span></span>
          <span></span>
        </button>
        <div class="app-header-start d-none d-md-flex">
          <form class="d-flex align-items-center h-100 w-lg-250px w-xxl-300px position-relative" action="#">
            <button type="button" class="btn btn-sm border-0 position-absolute start-0 ms-3 p-0">
              <i class="fi fi-rr-search"></i>
            </button>
            <input type="text" class="form-control rounded-5 ps-5" placeholder="Search anything's" data-bs-toggle="modal" data-bs-target="#searchResultsModal">
          </form>
        </div>
        <div class="app-header-end">
          <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
            <div class="dropdown">
              <button class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative" id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false" data-bs-toggle="dropdown">
                <i class="fi fi-rr-brightness scale-1x theme-icon-active"></i>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="light" aria-pressed="false">
                    <i class="fi fi-rr-brightness scale-1x" data-theme="light"></i> Light
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                    <i class="fi fi-rr-moon scale-1x" data-theme="dark"></i> Dark
                  </button>
                </li>
                <li>
                  <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="auto" aria-pressed="true">
                    <i class="fi fi-br-circle-half-stroke scale-1x" data-theme="auto"></i> Auto
                  </button>
                </li>
              </ul>
            </div>
          </div>
          <div class="vr my-3"></div>
          <div class="d-flex align-items-center gap-sm-2 gap-0 px-lg-4 px-sm-2 px-1">
            
            <div class="dropdown text-end">
              <button type="button" class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
                <i class="fi fi-rr-bell"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-lg-end p-0 w-300px mt-2">
                <div class="px-3 py-3 border-bottom d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Notifications <span class="badge badge-sm rounded-pill bg-primary ms-2">0</span>
                  </h6>
                  <i class="bi bi-x-lg cursor-pointer"></i>
                </div>
                <div class="p-2" style="height: 300px;" data-simplebar>
                  <ul class="list-group list-group-hover list-group-smooth list-group-unlined">
                    <!-- Notifications here -->
                  </ul>
                </div>
                <div class="p-2">
                  <a href="javascript:void(0);" class="btn w-100 btn-primary waves-effect waves-light">View all notifications</a>
                </div>
              </div>
            </div>
          </div>
          <div class="vr my-3"></div>
          <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
            <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="true">
              <div class="text-end me-2 d-none d-lg-inline-block">
                <div class="fw-bold text-dark">{{ Auth::user()->name ?? 'Guest' }}</div>
                <small class="text-body d-block lh-sm">
                  <i class="fi fi-rr-angle-down text-3xs me-1"></i> {{ Auth::user()->role ?? 'Guest' }}
                </small>
              </div>
              <div class="avatar avatar-sm rounded-circle avatar-status-success">
                @if(Auth::user()->avatar)
                    <img src="{{ Str::startsWith(Auth::user()->avatar, 'http') ? Auth::user()->avatar : asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="rounded-circle object-cover">
                @else
                    <img src="{{ asset('assets/images/avatar/avatar1.webp') }}" alt="">
                @endif
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
              <li class="d-flex align-items-center p-2">
                <div class="avatar avatar-sm rounded-circle">
                    @if(Auth::user()->avatar)
                        <img src="{{ Str::startsWith(Auth::user()->avatar, 'http') ? Auth::user()->avatar : asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="rounded-circle object-cover">
                    @else
                        <img src="{{ asset('assets/images/avatar/avatar1.webp') }}" alt="">
                    @endif
                </div>
                <div class="ms-2">
                  <div class="fw-bold text-dark">{{ Auth::user()->name ?? 'Guest' }}</div>
                  <small class="text-body d-block lh-sm">{{ Auth::user()->email ?? '' }}</small>
                </div>
              </li>
              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                  <i class="fi fi-rr-user scale-1x"></i> View Profile
                </a>
              </li>
              <li>
                <div class="dropdown-divider my-1"></div>
              </li>
              <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); this.closest('form').submit();">
                      <i class="fi fi-sr-exit scale-1x"></i> Log Out
                    </a>
                </form>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </header>
    <!-- end::GXON Page Header -->
