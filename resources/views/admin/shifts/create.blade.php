<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $editing ? 'Edit Shift: '.$shift->name : 'New Shift' }}</h1>
            <span>{{ $editing ? 'Update shift configuration' : 'Define a new shift type for your organisation' }}</span>
        </div>
        <a href="{{ route('admin.shifts.index') }}" class="btn btn-secondary btn-sm">
            <i class="fi fi-rr-arrow-left me-1"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ $editing ? route('admin.shifts.update', $shift) : route('admin.shifts.store') }}" method="POST">
        @csrf
        @if($editing) @method('PUT') @endif

        {{-- ── Basic Info ──────────────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Basic Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $shift->name) }}" placeholder="e.g. Morning Shift" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" maxlength="20"
                               value="{{ old('code', $shift->code) }}" placeholder="e.g. M1" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="shift_type" id="shiftType" class="form-select" required>
                            @foreach(\App\Models\Shift\Shift::TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('shift_type', $shift->shift_type) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color w-100"
                               value="{{ old('color', $shift->color ?? '#3B82F6') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control" id="startTime"
                               value="{{ old('start_time', substr($shift->start_time ?? '09:00', 0, 5)) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control" id="endTime"
                               value="{{ old('end_time', substr($shift->end_time ?? '18:00', 0, 5)) }}" required>
                        <div id="crossesMidnightHint" class="form-text text-warning d-none">
                            <i class="fi fi-rr-moon me-1"></i> This shift crosses midnight.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Break Duration (minutes)</label>
                        <input type="number" name="break_duration_minutes" class="form-control" min="0" max="240"
                               value="{{ old('break_duration_minutes', $shift->break_duration_minutes ?? 60) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timezone</label>
                        <input type="text" name="timezone" class="form-control"
                               value="{{ old('timezone', $shift->timezone ?? 'Asia/Kolkata') }}" placeholder="e.g. Asia/Kolkata">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Working Days</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                            @php $checked = in_array($day, old('working_days', $shift->working_days ?? ['Mon','Tue','Wed','Thu','Fri'])); @endphp
                            <div class="form-check form-check-inline me-0">
                                <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $day }}" id="day_{{ $day }}" {{ $checked ? 'checked' : '' }}>
                                <label class="form-check-label" for="day_{{ $day }}">{{ $day }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $shift->description) }}</textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                   {{ old('is_active', $shift->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Tolerances ──────────────────────────────────────────────────────── --}}
        <div class="card mb-3">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Tolerances & Thresholds</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Grace In (minutes)</label>
                        <input type="number" name="grace_in_minutes" class="form-control" min="0" max="60"
                               value="{{ old('grace_in_minutes', $shift->grace_in_minutes ?? 10) }}">
                        <div class="form-text">Late-arrival tolerance</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grace Out (minutes)</label>
                        <input type="number" name="grace_out_minutes" class="form-control" min="0" max="60"
                               value="{{ old('grace_out_minutes', $shift->grace_out_minutes ?? 10) }}">
                        <div class="form-text">Early-departure tolerance</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Overtime After (minutes)</label>
                        <input type="number" name="overtime_threshold_minutes" class="form-control" min="0" max="120"
                               value="{{ old('overtime_threshold_minutes', $shift->overtime_threshold_minutes ?? 30) }}">
                        <div class="form-text">Minutes past shift end</div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Min Hours for Full Day</label>
                        <input type="number" name="min_hours_for_full_day" class="form-control" min="1" max="24"
                               value="{{ old('min_hours_for_full_day', $shift->min_hours_for_full_day ?? 8) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Half Day Threshold (hours)</label>
                        <input type="number" name="half_day_threshold_hours" class="form-control" min="1" max="12"
                               value="{{ old('half_day_threshold_hours', $shift->half_day_threshold_hours ?? 4) }}">
                        <div class="form-text">Below this = half day</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Flexible-shift window (conditional) ─────────────────────────────── --}}
        <div class="card mb-3" id="flexibleSection" style="display:none;">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Flexible Window</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Earliest Check-In</label>
                        <input type="time" name="flexible_window_start" class="form-control"
                               value="{{ old('flexible_window_start', substr($shift->flexible_window_start ?? '08:00', 0, 5)) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Latest Check-In</label>
                        <input type="time" name="flexible_window_end" class="form-control"
                               value="{{ old('flexible_window_end', substr($shift->flexible_window_end ?? '10:00', 0, 5)) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Required Duration (hours)</label>
                        <input type="number" name="flexible_duration_hours" class="form-control" min="1" max="24"
                               value="{{ old('flexible_duration_hours', $shift->flexible_duration_hours ?? 9) }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Rotating pattern (conditional) ─────────────────────────────────── --}}
        <div class="card mb-3" id="rotatingSection" style="display:none;">
            <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Rotating Pattern</h6></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Cycle Length (days)</label>
                        <input type="number" name="cycle_length_days" id="cycleLength" class="form-control" min="2" max="28"
                               value="{{ old('cycle_length_days', $shift->patterns->first()?->cycle_length_days ?? 4) }}">
                    </div>
                </div>
                <div id="patternGrid">
                    {{-- Rendered by JS based on cycle length --}}
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-end mb-4">
            <a href="{{ route('admin.shifts.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fi fi-rr-disk me-1"></i> {{ $editing ? 'Update Shift' : 'Create Shift' }}
            </button>
        </div>
    </form>
</div>

<script>
const shiftTypeEl = document.getElementById('shiftType');
const startTimeEl = document.getElementById('startTime');
const endTimeEl   = document.getElementById('endTime');
const cycleLenEl  = document.getElementById('cycleLength');

// Existing pattern days (for edit mode)
const existingWorkingDays = @json($shift->patterns->first()?->days->where('is_working_day',true)->pluck('day_number')->toArray() ?? []);

function toggleSections() {
    const type = shiftTypeEl.value;
    document.getElementById('flexibleSection').style.display = type === 'flexible' ? '' : 'none';
    document.getElementById('rotatingSection').style.display = type === 'rotating' ? '' : 'none';
    if (type === 'rotating') renderPatternGrid();
}

function checkCrossesMidnight() {
    const s = startTimeEl.value, e = endTimeEl.value;
    document.getElementById('crossesMidnightHint').classList.toggle('d-none', !s || !e || e >= s);
}

function renderPatternGrid() {
    const n   = parseInt(cycleLenEl?.value) || 4;
    const grid = document.getElementById('patternGrid');
    if (!grid) return;
    let html = '<div class="d-flex flex-wrap gap-2">';
    for (let i = 1; i <= n; i++) {
        const checked = existingWorkingDays.includes(i) ? 'checked' : '';
        html += `<div class="form-check form-check-inline me-0">
            <input class="form-check-input" type="checkbox" name="pattern_working_days[]" value="${i}" id="pd${i}" ${checked}>
            <label class="form-check-label" for="pd${i}">Day ${i}</label>
        </div>`;
    }
    html += '</div><div class="form-text">Check the days that are working days within the cycle.</div>';
    grid.innerHTML = html;
}

shiftTypeEl.addEventListener('change', toggleSections);
startTimeEl.addEventListener('change', checkCrossesMidnight);
endTimeEl.addEventListener('change', checkCrossesMidnight);
if (cycleLenEl) cycleLenEl.addEventListener('input', renderPatternGrid);

toggleSections();
checkCrossesMidnight();
</script>
</x-app-layout>
