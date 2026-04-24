<x-app-layout>
    <div class="container">
        <div class="app-page-head mb-4">
            <h1 class="app-page-title">Salary Revisions</h1>
            <span>{{ $salaryStructure->employee?->name }}</span>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Effective Date</th>
                            <th>Type</th>
                            <th>Old Gross</th>
                            <th>New Gross</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($revisions as $revision)
                            <tr>
                                <td>{{ $revision->effective_date?->format('d M Y') }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $revision->revision_type)) }}</td>
                                <td>{{ $revision->old_gross_monthly }}</td>
                                <td>{{ $revision->new_gross_monthly }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $revision->status)) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No revisions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
