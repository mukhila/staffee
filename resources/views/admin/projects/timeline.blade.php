<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Project Timeline</h1>
            <span>{{ $project->name }} — {{ $rangeStart->format('d M Y') }} to {{ $rangeEnd->format('d M Y') }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Back to Project
            </a>
        </div>
    </div>

    @php
        $statusColors = [
            'pending'     => '#6c757d',
            'not_started' => '#6c757d',
            'in_progress' => '#316AFF',
            'completed'   => '#198754',
            'on_hold'     => '#fd7e14',
            'cancelled'   => '#dc3545',
        ];
        // Build month labels across the range
        $months = collect();
        $cur = $rangeStart->copy()->startOfMonth();
        while ($cur->lte($rangeEnd)) {
            $months->push($cur->copy());
            $cur->addMonth();
        }
    @endphp

    <div class="card">
        <div class="card-body">
            @if($tasks->isEmpty())
            <div class="text-center text-muted py-5">
                <i class="fi fi-rr-calendar-lines fs-3 d-block mb-2 opacity-25"></i>
                No tasks with dates found. Add start/due dates to tasks to see them here.
            </div>
            @else

            {{-- Month header --}}
            <div class="mb-2" style="padding-left:220px;">
                <div class="d-flex" style="position:relative;">
                    @foreach($months as $m)
                    @php $widthPct = min(100, max(0, $m->daysInMonth / $totalDays * 100)); @endphp
                    <div style="width:{{ $widthPct }}%;border-left:1px solid #dee2e6;padding:0 4px;font-size:.72rem;color:#6c757d;white-space:nowrap;overflow:hidden;">
                        {{ $m->format('M Y') }}
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Task rows --}}
            @foreach($tasks as $task)
            @php
                $start = $task->start_date ?? $task->due_date;
                $end   = $task->due_date ?? $task->start_date;
                if (!$start || !$end) continue;

                $startOffset = max(0, $rangeStart->diffInDays($start)) / $totalDays * 100;
                $duration    = max(1, $start->diffInDays($end) + 1) / $totalDays * 100;
                $barColor    = $statusColors[$task->status] ?? '#316AFF';
                $isOverdue   = $task->due_date && $task->due_date->lt(today()) && $task->status !== 'completed';
            @endphp
            <div class="d-flex align-items-center mb-2" style="min-height:36px;">
                {{-- Task label --}}
                <div style="width:220px;flex-shrink:0;padding-right:12px;overflow:hidden;">
                    <div class="fw-medium small text-truncate" title="{{ $task->title }}">{{ $task->title }}</div>
                    <div class="text-muted" style="font-size:.7rem;">{{ $task->assignedUser?->name }}</div>
                </div>
                {{-- Bar track --}}
                <div class="flex-grow-1 position-relative" style="height:28px;background:#f0f2f5;border-radius:4px;overflow:hidden;">
                    <div title="{{ $start->format('d M') }} – {{ $end->format('d M') }} · {{ ucfirst(str_replace('_',' ',$task->status)) }}"
                         style="position:absolute;left:{{ $startOffset }}%;width:{{ $duration }}%;height:100%;background:{{ $barColor }};border-radius:4px;display:flex;align-items:center;padding:0 6px;white-space:nowrap;overflow:hidden;font-size:.7rem;color:#fff;cursor:default;">
                        {{ $start->format('d M') }}@if($start->ne($end)) – {{ $end->format('d M') }}@endif
                        @if($isOverdue) ⚠ @endif
                    </div>
                </div>
                <div class="ms-2 text-muted" style="font-size:.7rem;width:60px;flex-shrink:0;">
                    {{ $start->format('d M') }}
                </div>
            </div>
            @endforeach

            {{-- Legend --}}
            <div class="d-flex gap-3 mt-4 flex-wrap">
                @foreach($statusColors as $st => $color)
                <span style="font-size:.75rem;display:flex;align-items:center;gap:4px;">
                    <span style="width:12px;height:12px;border-radius:2px;background:{{ $color }};display:inline-block;"></span>
                    {{ ucfirst(str_replace('_',' ',$st)) }}
                </span>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
