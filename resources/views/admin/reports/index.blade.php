<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Reports & Analytics</h1>
                <span>Overview of team performance and project health</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.reports.attendance') }}" class="btn btn-outline-primary waves-effect">Attendance</a>
                <a href="{{ route('admin.reports.projects') }}" class="btn btn-outline-info waves-effect">Projects</a>
                <a href="{{ route('admin.reports.bugs') }}" class="btn btn-outline-danger waves-effect">Bugs</a>
            </div>
        </div>

        <!-- Summary cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-lg">
                <div class="card bg-primary bg-opacity-10 border-0 shadow-none h-100">
                    <div class="card-body">
                        <div class="avatar bg-primary rounded-circle text-white mb-3"><i class="fi fi-sr-users"></i></div>
                        <h3 class="mb-0">{{ $totalStaff }}</h3>
                        <div class="text-muted small">Total Staff</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="card bg-success bg-opacity-10 border-0 shadow-none h-100">
                    <div class="card-body">
                        <div class="avatar bg-success rounded-circle text-white mb-3"><i class="fi fi-sr-briefcase"></i></div>
                        <h3 class="mb-0">{{ $activeProjects }}<span class="text-muted small">/{{ $totalProjects }}</span></h3>
                        <div class="text-muted small">Active Projects</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="card bg-info bg-opacity-10 border-0 shadow-none h-100">
                    <div class="card-body">
                        <div class="avatar bg-info rounded-circle text-white mb-3"><i class="fi fi-sr-list-check"></i></div>
                        <h3 class="mb-0">{{ $completedTasks }}<span class="text-muted small">/{{ $totalTasks }}</span></h3>
                        <div class="text-muted small">Tasks Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="card bg-danger bg-opacity-10 border-0 shadow-none h-100">
                    <div class="card-body">
                        <div class="avatar bg-danger rounded-circle text-white mb-3"><i class="fi fi-sr-bug"></i></div>
                        <h3 class="mb-0">{{ $openBugs }}<span class="text-muted small">/{{ $totalBugs }}</span></h3>
                        <div class="text-muted small">Open Bugs</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg">
                <div class="card bg-warning bg-opacity-10 border-0 shadow-none h-100">
                    <div class="card-body">
                        <div class="avatar bg-warning rounded-circle text-white mb-3"><i class="fi fi-sr-check-circle"></i></div>
                        <h3 class="mb-0">{{ $resolvedBugs }}</h3>
                        <div class="text-muted small">Bugs Resolved</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <!-- Task distribution -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Tasks by Status</h6>
                    </div>
                    <div class="card-body">
                        @foreach(['pending' => 'warning', 'in_progress' => 'info', 'review' => 'secondary', 'completed' => 'success'] as $status => $color)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                                <span class="fw-medium">{{ $tasksByStatus[$status] ?? 0 }}</span>
                            </div>
                            @php $pct = $totalTasks > 0 ? round((($tasksByStatus[$status] ?? 0) / $totalTasks) * 100) : 0; @endphp
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Bug severity -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Bugs by Severity</h6>
                    </div>
                    <div class="card-body">
                        @foreach(['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'] as $severity => $color)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ ucfirst($severity) }}</span>
                                <span class="fw-medium">{{ $bugsBySeverity[$severity] ?? 0 }}</span>
                            </div>
                            @php $pct = $totalBugs > 0 ? round((($bugsBySeverity[$severity] ?? 0) / $totalBugs) * 100) : 0; @endphp
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar bg-{{ $color }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Top performers -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Top Performers</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($topPerformers as $i => $staff)
                            <li class="list-group-item d-flex align-items-center gap-2">
                                <span class="badge bg-{{ $i === 0 ? 'warning' : ($i === 1 ? 'secondary' : 'light text-dark') }} rounded-circle">{{ $i + 1 }}</span>
                                <span class="flex-grow-1">{{ $staff->name }}</span>
                                <span class="badge bg-success">{{ $staff->completed_tasks }} tasks</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Attendance trend (last 30 days) -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0">
                        <h6 class="card-title mb-0">Attendance Trend (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Date</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>On Leave</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($attendanceSummary as $day)
                                    <tr>
                                        <td>{{ $day->date }}</td>
                                        <td><span class="badge bg-success">{{ $day->present }}</span></td>
                                        <td><span class="badge bg-danger">{{ $day->absent }}</span></td>
                                        <td><span class="badge bg-warning">{{ $day->on_leave }}</span></td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="text-center text-muted">No attendance data.</td></tr>
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
