<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Drafts</h1>
            <span>Unsent messages</span>
        </div>
        <a href="{{ route('mail.create') }}" class="btn btn-primary btn-sm"><i class="fi fi-rr-paper-plane me-1"></i> Compose</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Last Saved</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($emails as $draft)
                        <tr>
                            <td class="text-muted small">{{ $draft->to?->name ?? '—' }}</td>
                            <td class="fw-medium">{{ $draft->subject ?: '(No subject)' }}</td>
                            <td class="text-muted small">{{ $draft->updated_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <a href="{{ route('mail.create', ['draft' => $draft->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No drafts saved.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($emails->hasPages())
        <div class="card-footer border-0 d-flex justify-content-end">{{ $emails->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
