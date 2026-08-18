<x-app-layout>
    <div class="container-fluid py-4">
        <h4 class="mb-4">My Assets</h4>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tag</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Brand / Model</th>
                                <th>Assigned On</th>
                                <th>Warranty Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                            <tr>
                                <td><code>{{ $assignment->asset->asset_tag }}</code></td>
                                <td>{{ $assignment->asset->name }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $assignment->asset->category)) }}</td>
                                <td>{{ $assignment->asset->brand }} {{ $assignment->asset->model_number }}</td>
                                <td>{{ $assignment->assigned_at->format('d M Y') }}</td>
                                <td>{{ $assignment->asset->warranty_expiry?->format('d M Y') ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No assets assigned to you.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
