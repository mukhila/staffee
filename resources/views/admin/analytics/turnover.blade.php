<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Turnover Analytics</h1>
                <span>Hire vs exit trend — last 12 months</span>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.analytics.workforce') }}" class="btn btn-outline-secondary btn-sm">Workforce</a>
                <a href="{{ route('admin.analytics.attendance') }}" class="btn btn-outline-secondary btn-sm">Attendance</a>
                <a href="{{ route('admin.analytics.leaves') }}" class="btn btn-outline-secondary btn-sm">Leaves</a>
            </div>
        </div>

        <div class="row g-3">
            {{-- Monthly trend --}}
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Monthly Hire vs Exit (Last 12 Months)</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end text-success">Hires</th>
                                    <th class="text-end text-danger">Exits</th>
                                    <th class="text-end">Net</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($months as $row)
                                @php $net = $row['hires'] - $row['exits']; @endphp
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="text-end text-success">{{ $row['hires'] }}</td>
                                    <td class="text-end text-danger">{{ $row['exits'] }}</td>
                                    <td class="text-end">
                                        <span class="badge bg-{{ $net >= 0 ? 'success' : 'danger' }}">
                                            {{ $net >= 0 ? '+' : '' }}{{ $net }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end text-success">{{ collect($months)->sum('hires') }}</th>
                                    <th class="text-end text-danger">{{ collect($months)->sum('exits') }}</th>
                                    <th class="text-end">
                                        @php $totalNet = collect($months)->sum('hires') - collect($months)->sum('exits'); @endphp
                                        <span class="badge bg-{{ $totalNet >= 0 ? 'success' : 'danger' }}">
                                            {{ $totalNet >= 0 ? '+' : '' }}{{ $totalNet }}
                                        </span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tenure buckets --}}
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Tenure Distribution (Active)</h6></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tenure</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($buckets as $label => $count)
                                <tr>
                                    <td>{{ $label }}</td>
                                    <td class="text-end">{{ $count }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th>Total</th>
                                    <th class="text-end">{{ array_sum($buckets) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
