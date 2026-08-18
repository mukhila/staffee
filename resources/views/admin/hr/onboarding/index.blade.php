<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Onboarding Checklists</h1>
            <span>Track onboarding progress for new hires</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    @php
    $statusColors = ['pending'=>'secondary','in_progress'=>'warning','completed'=>'success'];
    @endphp

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>New Hire</th>
                            <th>Template</th>
                            <th>Status</th>
                            <th>Due Date</th>
                            <th>Completed At</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checklists as $checklist)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $checklist->user?->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $checklist->user?->email }}</div>
                            </td>
                            <td>{{ $checklist->template_name }}</td>
                            <td>
                                @php $c = $statusColors[$checklist->status] ?? 'secondary'; @endphp
                                <span class="badge bg-{{ $c }}-subtle text-{{ $c }}">{{ ucfirst(str_replace('_',' ',$checklist->status)) }}</span>
                            </td>
                            <td class="small">{{ $checklist->due_date?->format('d M Y') ?? '—' }}</td>
                            <td class="small">{{ $checklist->completed_at?->format('d M Y') ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.onboarding.show', $checklist) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="fi fi-rr-user-check fs-3 d-block mb-2 opacity-25"></i>
                                No onboarding checklists found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($checklists->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">
            {{ $checklists->links() }}
        </div>
        @endif
    </div>
</div>
</x-app-layout>
