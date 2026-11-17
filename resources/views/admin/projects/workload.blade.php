@php
    use App\Services\Project\WorkloadService;
@endphp
<x-app-layout>
@php
    $h    = fn (float $v) => rtrim(rtrim(number_format($v, 1), '0'), '.') . 'h';
    $tone = fn (int $p) => $p > 100 ? 'danger' : ($p >= 80 ? 'warning' : ($p > 0 ? 'success' : 'secondary'));
    $overloaded = $rows->filter(fn ($r) => $r['peak_pct'] > 100)->count();
@endphp
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Team Workload</h1>
            <span>Estimated work against available hours, week by week</span>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="project_id">Project</label>
                    <select id="project_id" name="project_id" class="form-select form-select-sm">
                        <option value="">All projects</option>
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ ($filters['project_id'] ?? null) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-select form-select-sm">
                        <option value="">All departments</option>
                        @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ ($filters['department_id'] ?? null) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="from">Starting</label>
                    <input type="date" id="from" name="from" class="form-control form-control-sm" value="{{ $weeks->first()->toDateString() }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1" for="weeks">Show</label>
                    <select id="weeks" name="weeks" class="form-select form-select-sm">
                        @foreach([2, 4, 6, 8] as $n)
                        <option value="{{ $n }}" {{ $filters['weeks'] == $n ? 'selected' : '' }}>{{ $n }} weeks</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-fill">Apply</button>
                    <a href="{{ route('admin.projects.workload') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    @if($overloaded)
    <div class="alert alert-danger small py-2">
        <i class="fi fi-rr-triangle-warning me-1"></i>
        {{ $overloaded }} {{ Str::plural('person', $overloaded) }} {{ $overloaded === 1 ? 'has' : 'have' }} more estimated work than available hours in at least one week.
        Click a cell to see the tasks behind it.
    </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 190px">Person</th>
                            @foreach($weeks as $w)
                            <th class="text-center small" style="min-width: 120px">
                                Week of {{ $w->format('d M') }}
                                @if($w->isCurrentWeek())<div class="text-primary" style="font-size:.7rem">this week</div>@endif
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $row['user']->name }}</div>
                                <div class="text-muted small">{{ $row['user']->department?->name ?? '—' }}</div>
                            </td>
                            @foreach($row['weeks'] as $weekKey => $cell)
                            @php
                                $p     = WorkloadService::percent($cell['load'], $cell['capacity']);
                                $t     = $tone($p);
                                $cellId = 'wl-' . $row['user']->id . '-' . $weekKey;
                                $hasDetail = $cell['tasks'] || $cell['unestimated'];
                            @endphp
                            <td class="text-center p-1">
                                <button type="button" class="btn w-100 py-2 bg-{{ $t }}-subtle border-0 {{ $hasDetail ? '' : 'pe-none' }}"
                                        @if($hasDetail) data-bs-toggle="collapse" data-bs-target="#{{ $cellId }}" aria-expanded="false" aria-controls="{{ $cellId }}" @endif
                                        aria-label="{{ $row['user']->name }}, week of {{ \Carbon\Carbon::parse($weekKey)->format('d M') }}: {{ $h($cell['load']) }} of {{ $h($cell['capacity']) }}">
                                    <div class="fw-bold text-{{ $t }}">{{ $p >= 999 ? '—' : $p . '%' }}</div>
                                    <div class="small text-muted">{{ $h($cell['load']) }} / {{ $h($cell['capacity']) }}</div>
                                    @if($cell['leave_days'] > 0)
                                    <div class="text-muted" style="font-size:.7rem">{{ rtrim(rtrim(number_format($cell['leave_days'], 1), '0'), '.') }}d leave</div>
                                    @endif
                                    @if($cell['unestimated'])
                                    <div class="text-warning" style="font-size:.7rem">+{{ count($cell['unestimated']) }} not estimated</div>
                                    @endif
                                </button>
                                @if($hasDetail)
                                <div class="collapse text-start small mt-1" id="{{ $cellId }}">
                                    <ul class="list-unstyled mb-0 border rounded p-2 bg-body">
                                        @foreach($cell['tasks'] as $task)
                                        <li class="mb-1">
                                            <a href="{{ route('admin.tasks.show', $task['id']) }}" class="text-decoration-none">{{ Str::limit($task['title'], 34) }}</a>
                                            <span class="text-muted">· {{ $h($task['hours']) }}</span>
                                            @if($task['overdue'])<span class="badge bg-danger-subtle text-danger">overdue</span>@endif
                                            <div class="text-muted" style="font-size:.7rem">{{ $task['project'] }}@if($task['due']) · due {{ \Carbon\Carbon::parse($task['due'])->format('d M') }}@endif</div>
                                        </li>
                                        @endforeach
                                        @foreach($cell['unestimated'] as $task)
                                        <li class="mb-1">
                                            <a href="{{ route('admin.tasks.edit', $task['id']) }}" class="text-decoration-none">{{ Str::limit($task['title'], 34) }}</a>
                                            <span class="badge bg-warning-subtle text-warning">add estimate</span>
                                            <div class="text-muted" style="font-size:.7rem">{{ $task['project'] }}</div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr><td colspan="{{ $weeks->count() + 1 }}" class="text-center text-muted py-5">No one matches these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-3 small text-muted mt-2">
        <span><span class="badge bg-success-subtle text-success">under 80%</span> has room</span>
        <span><span class="badge bg-warning-subtle text-warning">80–100%</span> nearly full</span>
        <span><span class="badge bg-danger-subtle text-danger">over 100%</span> overloaded</span>
        <span>Available hours exclude weekends, public holidays and approved leave. Work on a task is spread evenly between its start and due dates; overdue work counts in the current week.</span>
    </div>
</div>
</x-app-layout>
