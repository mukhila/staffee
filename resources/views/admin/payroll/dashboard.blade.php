<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Payroll Dashboard</h1>
            <span>{{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y') }} overview</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.payroll.adjustments.create') }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-plus me-1"></i> New Adjustment
            </a>
            <a href="{{ route('admin.payroll.runs.index') }}" class="btn btn-primary btn-sm">
                <i class="fi fi-rr-calculator me-1"></i> Payroll Runs
            </a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fi fi-rr-calculator text-primary fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Current Month Run</div>
                            <div class="fw-bold fs-5">
                                @if($currentRun)
                                <span class="badge bg-{{ match($currentRun->status) {
                                    'paid','posted','approved' => 'success',
                                    'draft' => 'secondary',
                                    'completed','pending_approval' => 'info',
                                    'processing','calculating' => 'warning',
                                    default => 'secondary'
                                } }} fs-6">{{ ucwords(str_replace('_',' ',$currentRun->status)) }}</span>
                                @else
                                <span class="text-muted">Not Started</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fi fi-rr-sack-dollar text-success fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">This Month Net Outflow</div>
                            <div class="fw-bold fs-5">
                                {{ $outflowTrend->last() ? number_format($outflowTrend->last()->total_net, 0) : '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fi fi-rr-time-half-past text-warning fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Pending Adjustments</div>
                            <div class="fw-bold fs-5">{{ $runStats['pending'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-info-subtle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fi fi-rr-check-circle text-info fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Completed Runs (All Time)</div>
                            <div class="fw-bold fs-5">{{ $runStats['completed'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Department Breakdown --}}
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Department Breakdown — {{ \Carbon\Carbon::createFromDate($year, $month, 1)->format('M Y') }}</h6>
                </div>
                <div class="card-body p-0">
                    @if($deptBreakdown->isEmpty())
                    <div class="text-center text-muted py-5 small">No payroll data for this month.</div>
                    @else
                    @php $grandTotal = $deptBreakdown->sum('total_net'); @endphp
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th class="text-end">Headcount</th>
                                <th class="text-end">Net Pay</th>
                                <th class="text-end">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deptBreakdown as $row)
                            <tr>
                                <td class="fw-medium">{{ $row->dept_name }}</td>
                                <td class="text-end text-muted">{{ $row->headcount }}</td>
                                <td class="text-end fw-medium">{{ number_format($row->total_net, 0) }}</td>
                                <td class="text-end">
                                    @php $pct = $grandTotal > 0 ? round($row->total_net / $grandTotal * 100, 1) : 0; @endphp
                                    <div class="d-flex align-items-center gap-2 justify-content-end">
                                        <div class="progress" style="width:50px;height:6px;background:#e9ecef;">
                                            <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                        </div>
                                        <span class="text-muted small">{{ $pct }}%</span>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td class="fw-bold">Total</td>
                                <td class="text-end fw-bold">{{ $deptBreakdown->sum('headcount') }}</td>
                                <td class="text-end fw-bold">{{ number_format($grandTotal, 0) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    @endif
                </div>
            </div>
        </div>

        {{-- 6-month Trend --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header border-0 pb-0"><h6 class="card-title mb-0">Net Pay Trend (6 months)</h6></div>
                <div class="card-body">
                    @if($outflowTrend->isEmpty())
                    <div class="text-center text-muted py-4 small">No trend data available.</div>
                    @else
                    @php $maxVal = $outflowTrend->max('total_net') ?: 1; @endphp
                    <div class="d-flex align-items-end gap-2" style="height:120px;">
                        @foreach($outflowTrend as $row)
                        @php
                            $h = max(4, round($row->total_net / $maxVal * 100));
                            $label = \Carbon\Carbon::createFromDate($row->for_year, $row->for_month, 1)->format('M');
                        @endphp
                        <div class="flex-fill d-flex flex-column align-items-center gap-1">
                            <div class="text-muted" style="font-size:.65rem;">{{ number_format($row->total_net/1000, 0) }}k</div>
                            <div class="bg-primary rounded-top w-100" style="height:{{ $h }}px;min-height:4px;"></div>
                            <div class="text-muted" style="font-size:.65rem;">{{ $label }}</div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Pending Adjustments --}}
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Pending Adjustments</h6>
                    <a href="{{ route('admin.payroll.adjustments.index') }}" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    @forelse($pendingAdjustments as $adj)
                    <a href="{{ route('admin.payroll.adjustments.show', $adj) }}" class="d-flex align-items-start gap-2 p-3 border-bottom text-decoration-none text-dark">
                        <div class="rounded-circle {{ $adj->adjustment_type === 'addition' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;font-size:.7rem;">
                            <i class="fi fi-rr-{{ $adj->adjustment_type === 'addition' ? 'plus' : 'minus' }}"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-medium small text-truncate">{{ $adj->employee?->name }}</div>
                            <div class="text-muted" style="font-size:.72rem;">{{ $adj->definition?->name }} · {{ number_format($adj->amount, 0) }}</div>
                        </div>
                    </a>
                    @empty
                    <div class="text-center text-muted py-4 small">No pending adjustments.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
