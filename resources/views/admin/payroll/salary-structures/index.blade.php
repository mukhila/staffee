<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="app-page-title">Salary Structures</h1>
                <span>Manage active salary versions and components</span>
            </div>
            <a href="{{ route('admin.payroll.salary-structures.create') }}" class="btn btn-primary">Create Structure</a>
        </div>

        <div class="card">
            <div class="card-body table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Base Salary</th>
                            <th>Currency</th>
                            <th>Version</th>
                            <th>Effective</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($structures as $structure)
                            <tr>
                                <td>{{ $structure->employee?->name }}</td>
                                <td>{{ $structure->base_salary }}</td>
                                <td>{{ $structure->currency_code }}</td>
                                <td>{{ $structure->version_no }}</td>
                                <td>{{ $structure->effective_from?->format('d M Y') }}</td>
                                <td><span class="badge bg-info">{{ ucfirst($structure->status) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.payroll.salary-structures.edit', $structure) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <a href="{{ route('admin.payroll.salary-structures.revisions', $structure) }}" class="btn btn-sm btn-outline-secondary">Revisions</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No salary structures found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $structures->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
