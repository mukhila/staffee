<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">My Attendance</h1>
            <span>{{ $month->format('F Y') }}</span>
        </div>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach($months as $m)
                <option value="{{ $m->ym }}" {{ request('month') == $m->ym || (!request('month') && $m->ym === now()->format('Y-m')) ? 'selected' : '' }}>
                    {{ $m->label }}
                </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        @php
        $cards = [
            ['label'=>'Present',      'value'=>$summary['present'],     'icon'=>'fi-rr-check-circle',  'color'=>'success'],
            ['label'=>'Absent',       'value'=>$summary['absent'],      'icon'=>'fi-rr-cross-circle',  'color'=>'danger'],
            ['label'=>'Late',         'value'=>$summary['late'],        'icon'=>'fi-rr-clock',         'color'=>'warning'],
            ['label'=>'Half Day',     'value'=>$summary['halfday'],     'icon'=>'fi-rr-calendar-half', 'color'=>'info'],
            ['label'=>'Total Hours',  'value'=>$summary['total_hours'].'h', 'icon'=>'fi-rr-time-add', 'color'=>'primary'],
        ];
        @endphp
        @foreach($cards as $card)
        <div class="col-6 col-md-4 col-lg">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center bg-{{ $card['color'] }}-subtle" style="width:44px;height:44px;flex-shrink:0;">
                        <i class="fi {{ $card['icon'] }} text-{{ $card['color'] }}"></i>
                    </div>
                    <div>
                        <div class="fs-5 fw-bold">{{ $card['value'] }}</div>
                        <div class="text-muted small">{{ $card['label'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Attendance table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Shift</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours Worked</th>
                            <th>Overtime</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendance as $a)
                        @php
                        $statusColors = [
                            'present'  => 'success',
                            'absent'   => 'danger',
                            'late'     => 'warning',
                            'halfday'  => 'info',
                            'holiday'  => 'secondary',
                            'weekend'  => 'light',
                            'leave'    => 'primary',
                        ];
                        $sc = $statusColors[$a->status] ?? 'secondary';
                        @endphp
                        <tr>
                            <td class="fw-medium">{{ \Carbon\Carbon::parse($a->date)->format('d M Y') }}</td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($a->date)->format('l') }}</td>
                            <td>
                                @if($a->shift)
                                <span class="badge bg-secondary-subtle text-secondary">{{ $a->shift->name }}</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $a->check_in ? \Carbon\Carbon::parse($a->check_in)->format('H:i') : '—' }}</td>
                            <td>{{ $a->check_out ? \Carbon\Carbon::parse($a->check_out)->format('H:i') : '—' }}</td>
                            <td>
                                @if($a->worked_minutes)
                                {{ floor($a->worked_minutes / 60) }}h {{ $a->worked_minutes % 60 }}m
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($a->overtime_minutes && $a->overtime_minutes > 0)
                                <span class="text-warning fw-medium">+{{ floor($a->overtime_minutes / 60) }}h {{ $a->overtime_minutes % 60 }}m</span>
                                @else
                                <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($a->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-calendar fs-3 d-block mb-2 opacity-25"></i>
                                No attendance records for {{ $month->format('F Y') }}.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
