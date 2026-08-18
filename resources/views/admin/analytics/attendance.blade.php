<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Attendance Analytics</h1>
                <span>Department attendance for {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.workforce') }}" class="btn btn-outline-secondary btn-sm">Workforce</a>
                <a href="{{ route('admin.analytics.leaves') }}" class="btn btn-outline-secondary btn-sm">Leaves</a>
                <a href="{{ route('admin.analytics.turnover') }}" class="btn btn-outline-secondary btn-sm">Turnover</a>
            </div>
        </div>

        {{-- Month selector --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label mb-0 small">Month</label>
                        <select name="month" class="form-select form-select-sm">
                            @foreach($months as $m => $label)
                            <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-0 small">Year</label>
                        <input type="number" name="year" class="form-control form-control-sm" value="{{ $year }}" min="2020" max="{{ now()->year }}" style="width:90px">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-sm btn-primary">Apply</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">Department Attendance Summary</h6></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th class="text-end">Employees</th>
                            <th class="text-end">Expected Days</th>
                            <th class="text-end">Present Days</th>
                            <th class="text-end">Absent Days</th>
                            <th class="text-end">Late Check-ins</th>
                            <th class="text-end">Overtime (hrs)</th>
                            <th class="text-end">Rate %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deptStats as $row)
                        <tr>
                            <td>{{ $row['department'] }}</td>
                            <td class="text-end">{{ $row['employees'] }}</td>
                            <td class="text-end">{{ $row['expected_days'] }}</td>
                            <td class="text-end">{{ $row['present_days'] }}</td>
                            <td class="text-end">{{ $row['absent_days'] }}</td>
                            <td class="text-end">{{ $row['late_checkins'] }}</td>
                            <td class="text-end">{{ $row['overtime_hours'] }}</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $row['attendance_rate'] >= 90 ? 'success' : ($row['attendance_rate'] >= 75 ? 'warning' : 'danger') }}">
                                    {{ $row['attendance_rate'] }}%
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No attendance data for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
