<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Team Leave Calendar</h1>
            <span>Approved leaves for your department — {{ $start->format('F Y') }}</span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="?month={{ $month == 1 ? 12 : $month - 1 }}&year={{ $month == 1 ? $year - 1 : $year }}" class="btn btn-sm btn-outline-secondary">&laquo; Prev</a>
            <span class="fw-semibold">{{ $start->format('F Y') }}</span>
            <a href="?month={{ $month == 12 ? 1 : $month + 1 }}&year={{ $month == 12 ? $year + 1 : $year }}" class="btn btn-sm btn-outline-secondary">Next &raquo;</a>
            <a href="{{ route('staff.leaves.index') }}" class="btn btn-secondary btn-sm">My Leaves</a>
        </div>
    </div>

    @php
        $daysInMonth = $start->daysInMonth;
        $firstDow = $start->dayOfWeek; // 0=Sun
        $leavesByDay = [];
        foreach ($leaves as $leave) {
            $cur = $leave->from_date->copy();
            while ($cur->lte($leave->to_date) && $cur->lte($end)) {
                if ($cur->gte($start)) {
                    $leavesByDay[$cur->day][] = $leave;
                }
                $cur->addDay();
            }
        }
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0" style="table-layout:fixed;">
                <thead class="table-light text-center">
                    <tr>
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                        <th class="py-2 small fw-semibold">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $day = 1; $started = false; @endphp
                    @while($day <= $daysInMonth)
                    <tr>
                        @for($dow = 0; $dow < 7; $dow++)
                            @if(!$started && $dow < $firstDow)
                            <td class="bg-light" style="height:90px;"></td>
                            @elseif($day <= $daysInMonth)
                            @php $started = true; $isToday = ($day == now()->day && $month == now()->month && $year == now()->year); @endphp
                            <td class="align-top p-1" style="height:90px;">
                                <div class="fw-semibold small mb-1 {{ $isToday ? 'text-primary' : 'text-muted' }}">{{ $day }}</div>
                                @foreach($leavesByDay[$day] ?? [] as $leave)
                                <div class="rounded px-1 mb-1 small" style="background:#e8f0ff;font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                                     title="{{ $leave->user->name }} — {{ $leave->typeLabel }}">
                                    <span style="color:#316AFF;">{{ Str::limit($leave->user->name, 12) }}</span>
                                </div>
                                @endforeach
                            </td>
                            @php $day++; @endphp
                            @else
                            <td class="bg-light" style="height:90px;"></td>
                            @endif
                        @endfor
                    </tr>
                    @endwhile
                </tbody>
            </table>
        </div>
    </div>

    @if($leaves->count())
    <div class="card mt-4">
        <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Leave List — {{ $start->format('F Y') }}</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr>
                    <th>Staff Member</th>
                    <th>Leave Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Days</th>
                </tr></thead>
                <tbody>
                    @foreach($leaves as $leave)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $leave->user->name }}</div>
                        </td>
                        <td>{{ $leave->typeLabel }}</td>
                        <td>{{ $leave->from_date->format('d M Y') }}</td>
                        <td>{{ $leave->to_date->format('d M Y') }}</td>
                        <td>{{ $leave->days }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
</x-app-layout>
