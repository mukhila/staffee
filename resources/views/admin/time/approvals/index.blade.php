<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Time Entry Approvals</h1>
            <span>Review retroactive time entries pending approval</span>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Task / Item</th>
                            <th>Project</th>
                            <th>Duration</th>
                            <th>Description</th>
                            <th>Reason</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $entry->user?->name }}</div>
                                <div class="text-muted small">{{ $entry->user?->department?->name }}</div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $entry->start_time->format('d M Y') }}</div>
                                <div class="text-muted small">{{ $entry->start_time->format('H:i') }} – {{ $entry->end_time?->format('H:i') }}</div>
                            </td>
                            <td>
                                @if($entry->trackable)
                                <div class="small">{{ $entry->trackable->title ?? $entry->trackable->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:.72rem;">{{ class_basename($entry->trackable_type) }}</div>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $entry->project?->name ?? '—' }}</td>
                            <td class="fw-medium">{{ $entry->duration_formatted }}</td>
                            <td class="text-muted small">{{ Str::limit($entry->description, 40) }}</td>
                            <td class="text-muted small fst-italic">{{ Str::limit($entry->notes, 40) }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <form action="{{ route('admin.time-entries.approvals.approve', $entry) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                            <i class="fi fi-rr-check"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#rejectModal{{ $entry->id }}" title="Reject">
                                        <i class="fi fi-rr-cross"></i>
                                    </button>
                                </div>

                                {{-- Reject modal --}}
                                <div class="modal fade" id="rejectModal{{ $entry->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.time-entries.approvals.reject', $entry) }}" method="POST">
                                                @csrf
                                                <div class="modal-header border-0 pb-0">
                                                    <h6 class="modal-title fw-bold">Reject Entry</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <textarea name="approval_notes" class="form-control form-control-sm" rows="3" required placeholder="Reason for rejection..."></textarea>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="submit" class="btn btn-danger btn-sm w-100">Confirm Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fi fi-rr-check-circle fs-3 d-block mb-2 opacity-25 text-success"></i>
                                No pending time entry approvals.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
