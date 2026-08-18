<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Leave Utilisation</h1>
                <span>Approved leave breakdown for {{ $year }}</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.workforce') }}" class="btn btn-outline-secondary btn-sm">Workforce</a>
                <a href="{{ route('admin.analytics.attendance') }}" class="btn btn-outline-secondary btn-sm">Attendance</a>
                <a href="{{ route('admin.analytics.turnover') }}" class="btn btn-outline-secondary btn-sm">Turnover</a>
            </div>
        </div>

        {{-- Year selector --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form class="row g-2 align-items-end">
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

        <div class="row g-3">
            {{-- By leave type --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">By Leave Type</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Leave Type</th>
                                    <th class="text-end">Requests</th>
                                    <th class="text-end">Total Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leaveTypes as $lt)
                                @php $row = $byType->get($lt->id); @endphp
                                <tr>
                                    <td>{{ $lt->name }}</td>
                                    <td class="text-end">{{ $row?->requests ?? 0 }}</td>
                                    <td class="text-end">{{ $row ? number_format($row->total_days, 1) : '0.0' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No leave types configured.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ $byType->sum('requests') }}</th>
                                    <th class="text-end">{{ number_format($byType->sum('total_days'), 1) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- By department --}}
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">By Department</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th class="text-end">Requests</th>
                                    <th class="text-end">Total Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $dept)
                                @php $row = $byDept->get($dept->id); @endphp
                                <tr>
                                    <td>{{ $dept->name }}</td>
                                    <td class="text-end">{{ $row?->requests ?? 0 }}</td>
                                    <td class="text-end">{{ $row ? number_format($row->total_days, 1) : '0.0' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No departments.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
