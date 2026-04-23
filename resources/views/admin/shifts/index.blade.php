<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Shifts</h1>
            <span>Configure shift definitions and patterns</span>
        </div>
        <a href="{{ route('admin.shifts.create') }}" class="btn btn-primary btn-sm">
            <i class="fi fi-rr-plus me-1"></i> New Shift
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        @forelse($shifts as $shift)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-bold"
                             style="width:44px;height:44px;background:{{ $shift->color }};font-size:.75rem;flex-shrink:0;">
                            {{ strtoupper($shift->code) }}
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="mb-0">{{ $shift->name }}</h6>
                                @if(!$shift->is_active)<span class="badge bg-secondary-subtle text-secondary">Inactive</span>@endif
                            </div>
                            <div class="d-flex gap-2 mt-1">
                                <span class="badge bg-{{ $shift->type_color }}-subtle text-{{ $shift->type_color }}">{{ $shift->type_label }}</span>
                                @if($shift->crosses_midnight)
                                <span class="badge bg-dark-subtle text-dark">Night</span>
                                @endif
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                <i class="fi fi-rr-menu-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('admin.shifts.show', $shift) }}"><i class="fi fi-rr-eye me-2"></i>View</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.shifts.edit', $shift) }}"><i class="fi fi-rr-edit me-2"></i>Edit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('admin.shifts.destroy', $shift) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger" onclick="return confirm('Delete this shift?')">
                                            <i class="fi fi-rr-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="row g-2 text-center mb-3">
                        <div class="col">
                            <div class="bg-light rounded p-2">
                                <div class="fw-semibold">{{ substr($shift->start_time,0,5) }}</div>
                                <div class="text-muted" style="font-size:.7rem;">CHECK-IN</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="bg-light rounded p-2">
                                <div class="fw-semibold">{{ substr($shift->end_time,0,5) }}</div>
                                <div class="text-muted" style="font-size:.7rem;">CHECK-OUT</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="bg-light rounded p-2">
                                <div class="fw-semibold">{{ $shift->break_duration_minutes }}m</div>
                                <div class="text-muted" style="font-size:.7rem;">BREAK</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 text-muted small">
                        <span><i class="fi fi-rr-users me-1"></i>{{ $shift->assignments_count }} assigned</span>
                        <span><i class="fi fi-rr-clock me-1"></i>Grace: {{ $shift->grace_in_minutes }}m in / {{ $shift->grace_out_minutes }}m out</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="text-muted">
                    <i class="fi fi-rr-time-half-past fs-2 d-block mb-2 opacity-25"></i>
                    No shifts configured yet. <a href="{{ route('admin.shifts.create') }}">Create the first shift.</a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
</x-app-layout>
