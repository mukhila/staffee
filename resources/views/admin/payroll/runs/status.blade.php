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
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payrollRun->slips as $slip)
                            <tr>
                                <td>{{ $slip->employee?->name }}</td>
                                <td>{{ number_format($slip->gross_earnings, 2) }}</td>
                                <td>{{ number_format($slip->total_deductions, 2) }}</td>
                                <td>{{ number_format($slip->net_pay, 2) }}</td>
                                <td>
                                    <span class="badge bg-{{ $slip->status === 'published' ? 'success' : 'secondary' }}-subtle text-{{ $slip->status === 'published' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($slip->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('payroll.slips.show', $slip) }}" class="btn btn-outline-secondary btn-sm py-0">View</a>
                                    <a href="{{ route('payroll.slips.download', $slip) }}" class="btn btn-outline-primary btn-sm py-0">PDF</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
