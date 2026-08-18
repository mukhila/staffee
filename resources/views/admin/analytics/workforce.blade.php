<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Workforce Analytics</h1>
                <span>Headcount, demographics and tenure overview</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.attendance') }}" class="btn btn-outline-secondary btn-sm">Attendance</a>
                <a href="{{ route('admin.analytics.leaves') }}" class="btn btn-outline-secondary btn-sm">Leaves</a>
                <a href="{{ route('admin.analytics.turnover') }}" class="btn btn-outline-secondary btn-sm">Turnover</a>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-users fs-4 text-success"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $activeCount }}</div>
                            <div class="text-muted small">Active Employees</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-user-minus fs-4 text-danger"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $inactiveCount }}</div>
                            <div class="text-muted small">Inactive</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-clock fs-4 text-primary"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ round($avgTenureMonths / 12, 1) }} yrs</div>
                            <div class="text-muted small">Avg Tenure</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 rounded-3 p-3">
                            <i class="fi fi-rr-user-add fs-4 text-info"></i>
                        </div>
                        <div>
                            <div class="fs-4 fw-bold">{{ $newHiresThisMonth }}</div>
                            <div class="text-muted small">New Hires (This Month)</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Headcount by Department --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Headcount by Department</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th class="text-end">Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($headcountByDept as $dept)
                                <tr>
                                    <td>{{ $dept->name }}</td>
                                    <td class="text-end">{{ $dept->active_count }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No departments.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ $headcountByDept->sum('active_count') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Gender split --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Gender Distribution</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Gender</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($genderSplit as $gender => $count)
                                <tr>
                                    <td>{{ ucfirst($gender ?: 'Not specified') }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
