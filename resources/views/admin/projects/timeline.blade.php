<x-app-layout>
@php
    $statusColors = [
        'pending'     => '#6c757d',
        'not_started' => '#6c757d',
        'in_progress' => '#316AFF',
        'review'      => '#6f42c1',
        'completed'   => '#198754',
        'on_hold'     => '#fd7e14',
        'cancelled'   => '#adb5bd',
    ];
    $today   = \Carbon\Carbon::today();
    $pct     = fn ($date) => max(0, min(100, $rangeStart->diffInDays(\Carbon\Carbon::parse($date)) / $totalDays * 100));
    $width   = fn ($from, $to) => max(100 / $totalDays, (\Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1) / $totalDays * 100);
    $weeks   = collect();
    for ($w = $rangeStart->copy(); $w->lte($rangeEnd); $w->addWeek()) { $weeks->push($w->copy()); }
    $dayPx   = 16; // keeps bars readable on long projects; the chart scrolls sideways
    $minWidth = max(700, $totalDays * $dayPx);
    $todayIn = $today->betweenIncluded($rangeStart, $rangeEnd);
@endphp
<style>
  .gantt-wrap   { overflow-x: auto; }
  .gantt        { position: relative; }
  .gantt-row    { display: flex; align-items: center; min-height: 38px; border-bottom: 1px solid var(--bs-border-color-translucent, #eef0f4); }
  .gantt-label  { width: 260px; flex-shrink: 0; padding: 4px 12px 4px 0; position: sticky; left: 0; background: var(--bs-body-bg, #fff); z-index: 2; }
  .gantt-track  { position: relative; flex-grow: 1; height: 38px; }
  .gantt-bar    { position: absolute; top: 8px; height: 22px; border-radius: 5px; color: #fff; font-size: .7rem; padding: 0 6px; display: flex; align-items: center; white-space: nowrap; overflow: hidden; text-decoration: none; }
  .gantt-bar:hover { filter: brightness(1.08); color: #fff; }
  .gantt-bar.overdue { box-shadow: 0 0 0 2px #dc3545; }
  .gantt-grid   { position: absolute; top: 0; bottom: 0; border-left: 1px dashed var(--bs-border-color, #e3e6ec); }
  .gantt-today  { position: absolute; top: 0; bottom: 0; border-left: 2px solid #dc3545; z-index: 1; }
  .gantt-today span { position: absolute; top: -18px; left: -18px; font-size: .65rem; color: #dc3545; font-weight: 600; }
  .gantt-diamond { position: absolute; top: 11px; width: 14px; height: 14px; transform: translateX(-7px) rotate(45deg); border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.15); }
  .gantt-sprint { position: absolute; top: 9px; height: 20px; border-radius: 4px; background: rgba(49,106,255,.12); border: 1px solid rgba(49,106,255,.35); color: #1f4fd1; font-size: .68rem; padding: 0 6px; display: flex; align-items: center; white-space: nowrap; overflow: hidden; }
</style>

<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Project Timeline</h1>
            <span>{{ $project->name }} · {{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.workload', ['project_id' => $project->id]) }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-users-alt me-1"></i> Team workload
            </a>
            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back to Project
            </a>
        </div>
    </div>

    @if($unscheduled > 0)
    <div class="alert alert-info small py-2">
        <i class="fi fi-rr-info me-1"></i>
        {{ $unscheduled }} {{ Str::plural('task', $unscheduled) }} {{ $unscheduled === 1 ? 'has' : 'have' }} no dates and {{ $unscheduled === 1 ? 'is' : 'are' }} not shown. Add a start or due date to place {{ $unscheduled === 1 ? 'it' : 'them' }} on the timeline.
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if($tasks->isEmpty() && $milestones->isEmpty() && $sprints->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fi fi-rr-calendar-lines fs-3 d-block mb-2 opacity-25"></i>
                Nothing to show yet. Add start or due dates to tasks, or add milestones and sprints.
            </div>
            @else
            <div class="gantt-wrap">
            <div class="gantt" style="min-width: {{ $minWidth + 260 }}px; padding-top: 18px;">

                {{-- Week header --}}
                <div class="gantt-row" style="min-height: 28px;">
                    <div class="gantt-label small text-muted fw-medium">Week of</div>
                    <div class="gantt-track" style="height: 28px;">
                        @foreach($weeks as $w)
                        <div class="gantt-grid" style="left: {{ $pct($w) }}%;"></div>
                        <div class="position-absolute small text-muted" style="left: calc({{ $pct($w) }}% + 4px); top: 6px; font-size: .7rem; white-space: nowrap;">{{ $w->format('d M') }}</div>
                        @endforeach
                        @if($todayIn)<div class="gantt-today" style="left: {{ $pct($today) }}%;"><span>Today</span></div>@endif
                    </div>
                </div>

                {{-- Sprints --}}
                @if($sprints->isNotEmpty())
                <div class="gantt-row">
                    <div class="gantt-label small fw-medium">Sprints</div>
                    <div class="gantt-track">
                        @foreach($weeks as $w)<div class="gantt-grid" style="left: {{ $pct($w) }}%;"></div>@endforeach
                        @if($todayIn)<div class="gantt-today" style="left: {{ $pct($today) }}%;"></div>@endif
                        @foreach($sprints as $sprint)
                        <div class="gantt-sprint" style="left: {{ $pct($sprint->start_date) }}%; width: {{ $width($sprint->start_date, $sprint->end_date) }}%;"
                             title="{{ $sprint->name }}: {{ \Carbon\Carbon::parse($sprint->start_date)->format('d M') }} – {{ \Carbon\Carbon::parse($sprint->end_date)->format('d M') }} ({{ $sprint->status }})">
                            {{ $sprint->name }}
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Milestones --}}
                @if($milestones->isNotEmpty())
                <div class="gantt-row">
                    <div class="gantt-label small fw-medium">Milestones</div>
                    <div class="gantt-track">
                        @foreach($weeks as $w)<div class="gantt-grid" style="left: {{ $pct($w) }}%;"></div>@endforeach
                        @if($todayIn)<div class="gantt-today" style="left: {{ $pct($today) }}%;"></div>@endif
                        @foreach($milestones as $m)
                        @php
                            $due  = \Carbon\Carbon::parse($m->due_date);
                            $late = $m->status !== 'completed' && $due->lt($today);
                            $mColor = $m->status === 'completed' ? '#198754' : ($late ? '#dc3545' : '#f59f00');
                        @endphp
                        <div class="gantt-diamond" style="left: calc({{ $pct($due) }}% + {{ 50 / $totalDays }}%); background: {{ $mColor }};"
                             title="{{ $m->title }} · due {{ $due->format('d M Y') }} · {{ $late ? 'missed' : str_replace('_', ' ', $m->status) }}"
                             role="img" aria-label="Milestone {{ $m->title }}, due {{ $due->format('d M Y') }}"></div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Tasks --}}
                @foreach($tasks as $task)
                @php
                    $start   = ($task->start_date ?? $task->due_date)->copy();
                    $end     = ($task->due_date ?? $task->start_date)->copy();
                    if ($end->lt($start)) { $end = $start->copy(); }
                    $color   = $statusColors[$task->status] ?? '#316AFF';
                    $overdue = $task->due_date && $task->due_date->lt($today) && $task->status !== 'completed';
                    $blocked = $task->status !== 'completed' && $task->blockers->isNotEmpty();
                    $est     = $task->estimated_hours !== null ? rtrim(rtrim((string) $task->estimated_hours, '0'), '.') . ' h' : null;
                    $tip     = $task->title . ' · ' . $start->format('d M') . ($start->ne($end) ? ' – ' . $end->format('d M') : '')
                             . ' · ' . ucfirst(str_replace('_', ' ', $task->status))
                             . ($est ? ' · estimate ' . $est : '') . ($overdue ? ' · OVERDUE' : '') . ($blocked ? ' · waiting on another task' : '');
                @endphp
                <div class="gantt-row">
                    <div class="gantt-label">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="fw-medium small text-truncate d-block text-decoration-none" title="{{ $task->title }}">
                            @if($blocked)<i class="fi fi-rr-lock text-warning me-1" title="Waiting on another task"></i>@endif{{ $task->title }}
                        </a>
                        <div class="text-muted text-truncate" style="font-size: .7rem;">
                            {{ $task->assignedUser?->name ?? 'Unassigned' }}@if($est) · {{ $est }}@endif
                        </div>
                    </div>
                    <div class="gantt-track">
                        @foreach($weeks as $w)<div class="gantt-grid" style="left: {{ $pct($w) }}%;"></div>@endforeach
                        @if($todayIn)<div class="gantt-today" style="left: {{ $pct($today) }}%;"></div>@endif
                        <a href="{{ route('admin.tasks.show', $task) }}" class="gantt-bar {{ $overdue ? 'overdue' : '' }}"
                           style="left: {{ $pct($start) }}%; width: {{ $width($start, $end) }}%; background: {{ $color }};"
                           title="{{ $tip }}" aria-label="{{ $tip }}">
                            {{ $start->format('d M') }}@if($start->ne($end)) – {{ $end->format('d M') }}@endif
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
            </div>

            {{-- Legend --}}
            <div class="d-flex gap-3 mt-4 flex-wrap small">
                @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'review' => 'Review', 'completed' => 'Completed'] as $st => $label)
                <span class="d-flex align-items-center gap-1">
                    <span style="width:12px;height:12px;border-radius:2px;background:{{ $statusColors[$st] }};display:inline-block;"></span>{{ $label }}
                </span>
                @endforeach
                <span class="d-flex align-items-center gap-1"><span style="width:12px;height:12px;border-radius:2px;box-shadow:0 0 0 2px #dc3545;display:inline-block;"></span>Overdue</span>
                <span class="d-flex align-items-center gap-1"><i class="fi fi-rr-lock text-warning"></i>Waiting on another task</span>
                <span class="d-flex align-items-center gap-1"><span style="width:10px;height:10px;transform:rotate(45deg);background:#f59f00;display:inline-block;"></span>Milestone</span>
                <span class="d-flex align-items-center gap-1"><span style="width:2px;height:12px;background:#dc3545;display:inline-block;"></span>Today</span>
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
