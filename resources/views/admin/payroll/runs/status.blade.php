<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="app-page-title">Payroll Run Status</h1>
                <span>{{ $payrollRun->for_month }}/{{ $payrollRun->for_year }} - {{ ucfirst($payrollRun->status) }}</span>
            </div>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.payroll.runs.process', $payrollRun) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">Reprocess</button>
                </form>
                <form method="POST" action="{{ route('admin.payroll.runs.publish', $payrollRun) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Publish Slips</button>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card"><div class="card-body"><small>Gross</small><h4>{{ data_get($payrollRun->totals_json, 'gross', '0.00') }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small>Deductions</small><h4>{{ data_get($payrollRun->totals_json, 'deductions', '0.00') }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small>Net</small><h4>{{ data_get($payrollRun->totals_json, 'net', '0.00') }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><small>Employees</small><h4>{{ data_get($payrollRun->totals_json, 'count', 0) }}</h4></div></div></div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrollRun->slips as $slip)
                            <tr>
                                <td>{{ $slip->employee?->name }}</td>
                                <td>{{ $slip->gross_earnings }}</td>
                                <td>{{ $slip->total_deductions }}</td>
                                <td>{{ $slip->net_pay }}</td>
                                <td>{{ ucfirst($slip->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
