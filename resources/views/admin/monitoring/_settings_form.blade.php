@php $s = $settings; @endphp

<div class="mb-3 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="enabled" id="enabled" value="1"
           {{ old('enabled', $s?->enabled ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="enabled">Monitoring Enabled</label>
</div>

<div class="mb-3 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="screenshot_enabled" id="screenshot_enabled" value="1"
           {{ old('screenshot_enabled', $s?->screenshot_enabled ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="screenshot_enabled">Screenshot Capture</label>
</div>

<div class="mb-3">
    <label class="form-label form-label-sm">Screenshot Interval (seconds)</label>
    <input type="number" name="screenshot_interval_seconds" class="form-control form-control-sm"
           value="{{ old('screenshot_interval_seconds', $s?->screenshot_interval_seconds ?? 300) }}"
           min="60" max="3600">
</div>

<div class="mb-3 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="activity_tracking_enabled" id="activity_tracking_enabled" value="1"
           {{ old('activity_tracking_enabled', $s?->activity_tracking_enabled ?? true) ? 'checked' : '' }}>
    <label class="form-check-label" for="activity_tracking_enabled">Activity Tracking (keyboard/mouse)</label>
</div>

<div class="mb-3">
    <label class="form-label form-label-sm">Idle Threshold (seconds)</label>
    <input type="number" name="idle_threshold_seconds" class="form-control form-control-sm"
           value="{{ old('idle_threshold_seconds', $s?->idle_threshold_seconds ?? 300) }}"
           min="30" max="3600">
</div>

<div class="mb-3 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="working_hours_only" id="working_hours_only" value="1"
           {{ old('working_hours_only', $s?->working_hours_only ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="working_hours_only">Working Hours Only</label>
</div>

<div class="row g-2">
    <div class="col-6">
        <label class="form-label form-label-sm">Work Start</label>
        <input type="time" name="work_start_time" class="form-control form-control-sm"
               value="{{ old('work_start_time', $s?->work_start_time ?? '09:00') }}">
    </div>
    <div class="col-6">
        <label class="form-label form-label-sm">Work End</label>
        <input type="time" name="work_end_time" class="form-control form-control-sm"
               value="{{ old('work_end_time', $s?->work_end_time ?? '18:00') }}">
    </div>
</div>

<div class="mt-3 form-check form-switch">
    <input class="form-check-input" type="checkbox" name="notify_employee" id="notify_employee" value="1"
           {{ old('notify_employee', $s?->notify_employee ?? false) ? 'checked' : '' }}>
    <label class="form-check-label" for="notify_employee">Notify Employee (transparency mode)</label>
</div>
