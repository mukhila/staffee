<x-app-layout>
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">{{ $email->subject }}</h1>
            <span>{{ $email->from->name }} → {{ $email->to->name }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('mail.index') }}" class="btn btn-secondary btn-sm">Back</a>
            @if($email->to_id == auth()->id())
            <a href="{{ route('mail.create', ['reply_to' => $email->id]) }}" class="btn btn-outline-primary btn-sm">
                <i class="fi fi-rr-reply me-1"></i> Reply
            </a>
            @endif
            <a href="{{ route('mail.create', ['forward' => $email->id]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fi fi-rr-share me-1"></i> Forward
            </a>
            <form action="{{ route('mail.destroy', $email) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this email?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fi fi-rr-trash"></i></button>
            </form>
        </div>
    </div>

    {{-- Original / parent thread --}}
    @if($email->parent)
    <div class="card mb-2" style="border-left:3px solid #dee2e6;">
        <div class="card-body py-2 px-3">
            <div class="text-muted small mb-1">{{ ucfirst($email->mail_type) }} of: <strong>{{ $email->parent->subject }}</strong> from {{ $email->parent->from->name }} on {{ $email->parent->created_at->format('d M Y H:i') }}</div>
        </div>
    </div>
    @endif

    <div class="card mb-3">
        <div class="card-header border-0 pb-0">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-medium">{{ $email->from->name }}</div>
                    <div class="text-muted small">To: {{ $email->to->name }} · {{ $email->created_at->format('d M Y, H:i') }}</div>
                </div>
                @if($email->mail_type !== 'normal')
                <span class="badge bg-secondary-subtle text-secondary">{{ ucfirst($email->mail_type) }}</span>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div style="white-space:pre-wrap;line-height:1.7;">{{ $email->body }}</div>
        </div>
    </div>

    {{-- Replies thread --}}
    @if($email->replies->isNotEmpty())
    <h6 class="mb-2 text-muted small">{{ $email->replies->count() }} Repl{{ $email->replies->count() === 1 ? 'y' : 'ies' }}</h6>
    @foreach($email->replies as $reply)
    <div class="card mb-2" style="border-left:3px solid #316AFF30;">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-medium small">{{ $reply->from->name }}</span>
                <span class="text-muted small">{{ $reply->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div style="white-space:pre-wrap;font-size:.875rem;">{{ $reply->body }}</div>
        </div>
    </div>
    @endforeach
    @endif
</div>
</x-app-layout>
