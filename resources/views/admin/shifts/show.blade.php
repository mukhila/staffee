<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">
                <span class="me-2 rounded-2 d-inline-block" style="width:14px;height:14px;background:{{ $shift->color }};vertical-align:middle;"></span>
                {{ $shift->name }}
            </h1>
            <span>Code: <code>{{ $shift->code }}</code> · {{ $shift->type_label }}</span>
        </div>
        <div class="d-flex gap-2">
            @if(!$shift->is_active)<span class="badge bg-secondary fs-6">Inactive</span>@endif
            <a href="{{ route('admin.shifts.edit', $shift) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            <a href="{{ route('admin.shifts.index') }}" class="btn btn-secondary btn-sm">Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        {{-- Shift details --}}
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Shift Configuration</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Type</th><td><span class="badge bg-{{ $shift->type_color }}-subtle text-{{ $shift->type_color }}">{{ $shift->type_label }}</span></td></tr>
                        <tr><th class="text-muted fw-normal">Check-In</th><td>{{ substr($shift->start_time,0,5) }}</td></tr>
                        <tr><th class="text-muted fw-normal">Check-Out</th><td>{{ substr($shift->end_time,0,5) }} {{ $shift->crosses_midnight ? '(+1 day)' : '' }}</td></tr>
                        <tr><th class="text-muted fw-normal">Break</th><td>{{ $shift->break_duration_minutes }} minutes</td></tr>
                        <tr><th class="text-muted fw-normal">Net Hours</th><td>{{ number_format(($shift->shiftDurationMinutes() - $shift->break_duration_minutes) / 60, 1) }}h</td></tr>
                        <tr><th class="text-muted fw-normal">Timezone</th><td>{{ $shift->timezone }}</td></tr>
                        <tr><th class="text-muted fw-normal">Working Days</th><td>{{ implode(', ', $shift->working_days ?? ['Mon-Fri']) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Tolerances</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Grace In</th><td>{{ $shift->grace_in_minutes }} min</td></tr>
                        <tr><th class="text-muted fw-normal">Grace Out</th><td>{{ $shift->grace_out_minutes }} min</td></tr>
                        <tr><th class="text-muted fw-normal">OT Threshold</th><td>{{ $shift->overtime_threshold_minutes }} min</td></tr>
                        <tr><th class="text-muted fw-normal">Full Day Min</th><td>{{ $shift->min_hours_for_full_day }}h</td></tr>
                        <tr><th class="text-muted fw-normal">Half Day &lt;</th><td>{{ $shift->half_day_threshold_hours }}h</td></tr>
                    </table>
                </div>
            </div>

            @if($shift->isRotating() && $shift->patterns->isNotEmpty())
            @php $pattern = $shift->patterns->first(); @endphp
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Rotating Pattern ({{ $pattern->cycle_length_days }}-day cycle)</h6></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($pattern->days as $day)
                        <div class="text-center rounded p-2" style="min-width:52px;background:{{ $day->is_working_day ? '#d1e7dd' : '#f8d7da' }};">
                            <div class="fw-semibold small">Day {{ $day->day_number }}</div>
                            <div class="small" style="font-size:.7rem;color:{{ $day->is_working_day ? '#198754' : '#dc3545' }};">
                                {{ $day->is_working_day ? 'Work' : 'Off' }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($shift->isFlexible())
            <div class="card">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Flexible Window</h6></div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tr><th class="text-muted fw-normal">Window Start</th><td>{{ substr($shift->flexible_window_start,0,5) }}</td></tr>
                        <tr><th class="text-muted fw-normal">Window End</th><td>{{ substr($shift->flexible_window_end,0,5) }}</td></tr>
                        <tr><th class="text-muted fw-normal">Required Hours</th><td>{{ $shift->flexible_duration_hours }}h</td></tr>
                    </table>
                </div>
            </div>
            @endif
        </div>

        {{-- Active assignments --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-0 pb-0">
                    <h6 class="card-title mb-0">Active Assignments ({{ $shift->assignments->count() }})</h6>
                    <a href="{{ route('admin.shifts.assignments.create', ['shift_id' => $shift->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fi fi-rr-user-add me-1"></i> Assign
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Effective From</th>
                                    <th>Effective To</th>
                                    <th>Assigned By</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shift->assignments as $a)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $a->user->name }}</div>
                                        <div class="text-muted small">{{ $a->user->employee_id }}</div>
                                    </td>
                                    <td class="text-muted small">{{ $a->user->department?->name ?? '—' }}</td>
                                    <td>{{ $a->effective_from->format('d M Y') }}</td>
                                    <td>{{ $a->effective_to ? $a->effective_to->format('d M Y') : '<span class="text-muted">Ongoing</span>' }}</td>
                                    <td class="text-muted small">{{ $a->assignedBy?->name }}</td>
                                    <td>
                                        <form action="{{ route('admin.shifts.assignments.destroy', $a) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this assignment?')">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No active assignments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
