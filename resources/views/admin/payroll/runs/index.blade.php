<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="app-page-title">Payroll Runs</h1>
                <span>Initiate and monitor monthly payroll</span>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.payroll.runs.initiate') }}" class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">Month</label>
                        <input type="number" min="1" max="12" name="month" class="form-control" value="{{ now()->month }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Year</label>
                        <input type="number" min="2000" max="2100" name="year" class="form-control" value="{{ now()->year }}">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Initiate Payroll</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Run Type</th>
                            <th>Status</th>
                            <th>Slips</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($runs as $run)
                            <tr>
                                <td>{{ $run->for_month }}/{{ $run->for_year }}</td>
                                <td>{{ ucfirst($run->run_type) }}</td>
                                <td>{{ ucfirst($run->status) }}</td>
                                <td>{{ $run->slips_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payroll.runs.status', $run) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No payroll runs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $runs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
