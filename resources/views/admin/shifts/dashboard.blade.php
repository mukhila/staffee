<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Shift Management</h1>
            <span>Today: {{ $today->format('l, d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.shifts.assignments.create') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> Assign Shift
            </a>
            <a href="{{ route('admin.shifts.create') }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-settings me-1"></i> New Shift
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    {{-- ── Stat cards ─────────────────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
        $cards = [
            ['label'=>'Active Shifts',         'value'=>$stats['total_shifts'],       'icon'=>'fi-rr-time-half-past',      'color'=>'primary'],
            ['label'=>'Assigned Today',        'value'=>$stats['assigned_employees'], 'icon'=>'fi-rr-users-alt',           'color'=>'success'],
            ['label'=>'Unassigned',            'value'=>$stats['unassigned'],         'icon'=>'fi-rr-user-slash',          'color'=>'warning'],
            ['label'=>'Pending Exceptions',    'value'=>$stats['pending_exceptions'], 'icon'=>'fi-rr-triangle-warning',    'color'=>'danger'],
            ['label'=>'Shift Change Requests', 'value'=>$stats['pending_changes'],    'icon'=>'fi-rr-arrows-repeat',       'color'=>'info'],
            ['label'=>'Upcoming Holidays',     'value'=>$stats['upcoming_holidays'],  'icon'=>'fi-rr-calendar-star',       'color'=>'secondary'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-{{ $card['color'] }}-subtle p-3">
                        <i class="fi {{ $card['icon'] }} text-{{ $card['color'] }} fs-5"></i>
                    </div>
                    <div>
                        <div class="fs-4 fw-bold">{{ $card['value'] }}</div>
                        <div class="text-muted small">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-3">
        {{-- ── Shift utilisation ─────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Shift Utilisation Today</h6>
                    <a href="{{ route('admin.shifts.index') }}" class="btn btn-sm btn-outline-primary">All shifts</a>
                </div>
                <div class="card-body">
                    @forelse($utilisation as $item)
                    @php $shift = $item['shift']; $count = $item['employee_count']; @endphp
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded" style="width:12px;height:36px;background:{{ $shift->color }};flex-shrink:0;"></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-medium small">{{ $shift->name }}</span>
                                <span class="text-muted small">{{ $count }} emp</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $shift->type_color }}-subtle text-{{ $shift->type_color }}">{{ $shift->type_label }}</span>
                                <span class="text-muted small">{{ substr($shift->start_time,0,5) }} – {{ substr($shift->end_time,0,5) }}</span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3">No active shift assignments today.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Pending exceptions ────────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Pending Exceptions <span class="badge bg-danger ms-1">{{ $stats['pending_exceptions'] }}</span></h6>
                    <a href="{{ route('admin.shifts.exceptions.index') }}" class="btn btn-sm btn-outline-danger">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($recentExceptions as $ex)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-medium small">{{ $ex->user->name }}</div>
                            <div class="text-muted small">{{ $ex->date->format('d M') }} · {{ $ex->shift->name }}</div>
                        </div>
                        <span class="badge bg-{{ $ex->type_color }}-subtle text-{{ $ex->type_color }}">{{ $ex->type_label }}</span>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No pending exceptions.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Shift change requests ─────────────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Shift Change Requests</h6>
                    <a href="{{ route('admin.shifts.change-requests.index') }}" class="btn btn-sm btn-outline-primary">View all</a>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingChanges as $cr)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-medium small">{{ $cr->requester->name }}</div>
                            <div class="text-muted small">
                                {{ $cr->currentShift->name }} → {{ $cr->requestedShift->name }}
                                · {{ $cr->effective_date->format('d M') }}
                            </div>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No pending requests.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Upcoming holidays ─────────────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Upcoming Holidays</h6>
                    <a href="{{ route('admin.shifts.holidays.index') }}" class="btn btn-sm btn-outline-secondary">Manage</a>
                </div>
                <div class="card-body p-0">
                    @forelse($upcomingHolidays as $h)
                    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                        <div class="text-center" style="min-width:40px;">
                            <div class="fw-bold text-danger">{{ $h->date->format('d') }}</div>
                            <div class="text-muted small">{{ $h->date->format('M') }}</div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">{{ $h->name }}</div>
                            @if($h->is_recurring)<span class="badge bg-secondary-subtle text-secondary">Recurring</span>@endif
                        </div>
                        <span class="badge bg-{{ $h->type_color }}-subtle text-{{ $h->type_color }}">{{ ucfirst($h->holiday_type) }}</span>
                    </div>
                    @empty
                    <p class="text-muted text-center py-4 mb-0">No upcoming holidays.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── Quick validate ────────────────────────────────────────────────── --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Run Attendance Validation</h6></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Validate all attendance records for a date against their assigned shifts. Creates exception records automatically.</p>
                    <form action="{{ route('admin.shifts.exceptions.validate-date') }}" method="POST" class="d-flex gap-2">
                        @csrf
                        <input type="date" name="date" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
                        <button class="btn btn-primary px-4 text-nowrap">
                            <i class="fi fi-rr-refresh me-1"></i> Validate
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
