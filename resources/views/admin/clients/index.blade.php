<x-app-layout>
    <div class="container">
        <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="clearfix">
                <h1 class="app-page-title">Clients</h1>
                <span>Manage clients and billing contacts</span>
            </div>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary waves-effect waves-light">
                <i class="fi fi-rr-plus me-1"></i> Add Client
            </a>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Country</th>
                                <th>Currency</th>
                                <th>Invoices</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($clients as $client)
                            <tr>
                                <td><a href="{{ route('admin.clients.show', $client) }}">{{ $client->name }}</a></td>
                                <td>{{ $client->contact_person ?? '—' }}</td>
                                <td>{{ $client->email ?? '—' }}</td>
                                <td>{{ $client->country ?? '—' }}</td>
                                <td>{{ $client->currency }}</td>
                                <td>{{ $client->invoices_count }}</td>
                                <td>
                                    <span class="badge bg-{{ $client->is_active ? 'success' : 'secondary' }}">
                                        {{ $client->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fi fi-rr-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fi fi-rr-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No clients yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($clients->hasPages())
            <div class="card-footer">{{ $clients->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
