<x-app-layout>
<div class="container-fluid">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Screenshots — {{ $user->name }}</h1>
            <span>{{ $date->format('d M Y') }} &middot; {{ $screenshots->total() }} capture(s)</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.monitoring.show', $user) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-arrow-left me-1"></i> Activity
            </a>
        </div>
    </div>

    {{-- Date selector --}}
    <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
        @foreach($availableDates as $day)
        <a href="{{ route('admin.monitoring.screenshots.index', [$user, 'date' => $day]) }}"
           class="btn btn-sm {{ $date->format('Y-m-d') === $day ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ \Carbon\Carbon::parse($day)->format('d M') }}
        </a>
        @endforeach
        <form method="GET" class="d-flex gap-1 ms-auto">
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $date->format('Y-m-d') }}">
            <button class="btn btn-sm btn-secondary">Go</button>
        </form>
    </div>

    @if($screenshots->isEmpty())
    <div class="text-center text-muted py-5">No screenshots on this date.</div>
    @else
    <div class="row g-2">
        @foreach($screenshots as $shot)
        <div class="col-6 col-md-4 col-lg-3 col-xl-2">
            <div class="card h-100 {{ $shot->is_flagged ? 'border-danger' : '' }}">
                <a href="{{ $shot->url }}" target="_blank">
                    <img src="{{ $shot->thumbnail_url }}" class="card-img-top"
                         style="height:110px;object-fit:cover;background:#eee"
                         onerror="this.src='{{ asset('assets/images/no-screenshot.png') }}'">
                </a>
                <div class="card-body p-2">
                    <div class="small text-muted">{{ $shot->captured_at->format('H:i:s') }}</div>
                    @if($shot->active_window_title)
                    <div class="small text-truncate" title="{{ $shot->active_window_title }}">
                        {{ $shot->active_window_title }}
                    </div>
                    @endif
                    @php $rc = match($shot->review_status ?? 'pending'){ 'accepted'=>'success','escalated'=>'danger',default=>'secondary' }; @endphp
                    <span class="badge bg-{{ $rc }} mt-1" title="{{ $shot->review_notes }}">
                        {{ ucfirst($shot->review_status ?? 'pending') }}
                    </span>
                    @if($shot->is_flagged)<span class="badge bg-danger mt-1">Flagged</span>@endif
                </div>
                <div class="card-footer p-1">
                    <div class="d-flex gap-1 justify-content-between mb-1">
                        <form method="POST" action="{{ route('admin.monitoring.screenshots.flag', $shot) }}">
                            @csrf
                            <button class="btn btn-xs btn-sm {{ $shot->is_flagged ? 'btn-danger' : 'btn-outline-warning' }}" title="{{ $shot->is_flagged ? 'Unflag' : 'Flag' }}">
                                <i class="fi fi-rr-flag"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.monitoring.screenshots.destroy', $shot) }}"
                              onsubmit="return confirm('Delete this screenshot?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-xs btn-sm btn-outline-danger" title="Delete"><i class="fi fi-rr-trash"></i></button>
                        </form>
                    </div>
                    @if(($shot->review_status ?? 'pending') === 'pending')
                    <div class="d-flex gap-1">
                        <form method="POST" action="{{ route('admin.monitoring.screenshots.accept', $shot) }}" class="flex-fill">
                            @csrf
                            <button class="btn btn-xs btn-sm btn-outline-success w-100" title="Accept as appropriate">Accept</button>
                        </form>
                        <button class="btn btn-xs btn-sm btn-outline-danger flex-fill" title="Escalate for review"
                                data-bs-toggle="modal" data-bs-target="#escalateModal{{ $shot->id }}">Escalate</button>
                    </div>
                    @endif
                </div>

                {{-- Escalate Modal --}}
                <div class="modal fade" id="escalateModal{{ $shot->id }}" tabindex="-1">
                    <div class="modal-dialog modal-sm"><div class="modal-content">
                        <form method="POST" action="{{ route('admin.monitoring.screenshots.escalate', $shot) }}">
                            @csrf
                            <div class="modal-header py-2"><h6 class="modal-title mb-0">Escalate Screenshot</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <textarea name="review_notes" class="form-control form-control-sm" rows="3" placeholder="Reason for escalation…"></textarea>
                            </div>
                            <div class="modal-footer py-2"><button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-sm btn-danger">Escalate</button></div>
                        </form>
                    </div></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-3">{{ $screenshots->links() }}</div>
    @endif
</div>
</x-app-layout>
