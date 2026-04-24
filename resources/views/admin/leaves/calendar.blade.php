<x-app-layout>
<div class="container-fluid">
    @php
        $monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        $prevMonth  = $month === 1 ? 12 : $month - 1;
        $prevYear   = $month === 1 ? $year - 1 : $year;
        $nextMonth  = $month === 12 ? 1 : $month + 1;
        $nextYear   = $month === 12 ? $year + 1 : $year;

        // Build day → [leave, ...] map
        $dayMap = [];
        foreach ($leaves as $leave) {
            $cur = $leave->from_date->copy();
            while ($cur->lte($leave->to_date) && $cur->month == $month) {
                $dayMap[$cur->day][] = $leave;
                $cur->addDay();
            }
            // Handle leaves that start before this month
            if ($leave->from_date->month < $month) {
                $cur = $start->copy();
                while ($cur->lte($leave->to_date) && $cur->month == $month) {
                    $dayMap[$cur->day][] = $leave;
                    $cur->addDay();
                }
            }
        }
    @endphp

    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Team Leave Calendar</h1>
            <span>{{ $monthNames[$month - 1] }} {{ $year }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('admin.leaves.calendar') }}?year={{ $prevYear }}&month={{ $prevMonth }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <a href="{{ route('admin.leaves.calendar') }}" class="btn btn-outline-secondary btn-sm">Today</a>
            <a href="{{ route('admin.leaves.calendar') }}?year={{ $nextYear }}&month={{ $nextMonth }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-angle-right"></i>
            </a>
            <a href="{{ route('admin.leaves.index') }}" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="fi fi-rr-list me-1"></i> All Requests
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            {{-- Day headers --}}
            <div class="row g-0 border-bottom">
                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                <div class="col text-center py-2 fw-semibold text-muted small border-end">{{ $d }}</div>
                @endforeach
            </div>

            {{-- Calendar grid --}}
            @php
                $firstDayOfWeek = ($start->dayOfWeek + 6) % 7; // 0=Mon
                $daysInMonth    = $start->daysInMonth;
                $cells          = $firstDayOfWeek + $daysInMonth;
                $rows           = ceil($cells / 7);
                $cellNum        = 0;
            @endphp

            @for($row = 0; $row < $rows; $row++)
            <div class="row g-0" style="min-height:100px">
                @for($col = 0; $col < 7; $col++)
                @php
                    $day      = $cellNum - $firstDayOfWeek + 1;
                    $isValid  = $day >= 1 && $day <= $daysInMonth;
                    $isToday  = $isValid && $day == now()->day && $month == now()->month && $year == now()->year;
                    $isWeekend = $col >= 5;
                    $cellNum++;
                @endphp
                <div class="col border-end border-bottom p-1 {{ $isWeekend ? 'bg-light' : '' }}">
                    @if($isValid)
                    <div class="fw-semibold small {{ $isToday ? 'text-white bg-primary rounded-circle d-inline-flex align-items-center justify-content-center' : 'text-muted' }}"
                         style="{{ $isToday ? 'width:24px;height:24px;font-size:0.75rem' : '' }}">
                        {{ $day }}
                    </div>
                    @if(isset($dayMap[$day]))
                    @foreach(array_unique($dayMap[$day], SORT_REGULAR) as $leave)
                    <div class="mt-1 rounded px-1 text-white small text-truncate"
                         style="background-color: {{ $leave->leaveType?->color ?? '#6b7280' }}; font-size:0.7rem"
                         title="{{ $leave->user->name }} — {{ $leave->type_label }}">
                        {{ $leave->user->name }}
                    </div>
                    @endforeach
                    @endif
                    @endif
                </div>
                @endfor
            </div>
            @endfor
        </div>
    </div>

    {{-- Legend --}}
    @if($leaves->isNotEmpty())
    <div class="card mt-3">
        <div class="card-header"><h6 class="mb-0">On Leave This Month</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th class="text-end">Days</th></tr>
                    </thead>
                    <tbody>
                        @foreach($leaves as $leave)
                        <tr>
                            <td><strong>{{ $leave->user->name }}</strong><small class="text-muted d-block">{{ $leave->user->department?->name }}</small></td>
                            <td>
                                <span class="badge" style="background-color: {{ $leave->leaveType?->color ?? '#6b7280' }}">
                                    {{ $leave->type_label }}
                                </span>
                            </td>
                            <td>{{ $leave->from_date->format('d M') }}</td>
                            <td>{{ $leave->to_date->format('d M') }}</td>
                            <td class="text-end">{{ $leave->days }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
</x-app-layout>
