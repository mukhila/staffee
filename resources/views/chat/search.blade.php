<x-app-layout>
@php
    // Escape first, then wrap matches, so message text can never inject markup.
    $highlight = function (?string $text) use ($q) {
        $safe = e(\Illuminate\Support\Str::limit((string) $text, 220));
        if ($q === '') return $safe;
        return preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark class="px-0">$1</mark>', $safe);
    };
@endphp
<div class="container">
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div>
            <h1 class="app-page-title">Search messages</h1>
            <span>Your direct messages and the channels you belong to</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('chat.index') }}" class="btn btn-outline-secondary btn-sm">Direct messages</a>
            <a href="{{ route('chat.channels.index') }}" class="btn btn-outline-secondary btn-sm">Channels</a>
        </div>
    </div>

    <form method="GET" class="mb-3" role="search">
        <div class="input-group">
            <span class="input-group-text"><i class="fi fi-rr-search"></i></span>
            <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search for words or a file name" aria-label="Search messages" autofocus minlength="2" maxlength="100">
            <button class="btn btn-primary">Search</button>
        </div>
        @error('q')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </form>

    @if($q === '')
    <p class="text-muted">Type at least two characters to search.</p>
    @elseif(mb_strlen($q) < 2)
    <p class="text-muted">Type at least two characters to search.</p>
    @elseif($direct->isEmpty() && $channel->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">No messages contain “{{ $q }}”.</div></div>
    @else
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Direct messages</h6><span class="small text-muted">{{ $direct->count() }}{{ $direct->count() >= $limit ? '+' : '' }}</span></div>
                <ul class="list-group list-group-flush">
                    @forelse($direct as $m)
                    @php $other = $m->from_id === $me ? $m->to : $m->from; @endphp
                    <li class="list-group-item">
                        <a href="{{ route('chat.index', ['user' => $other?->id]) }}" class="text-decoration-none d-block">
                            <div class="d-flex justify-content-between small">
                                <strong class="text-body">{{ $m->from_id === $me ? 'You → ' . ($other?->name ?? 'Unknown') : ($other?->name ?? 'Unknown') . ' → you' }}</strong>
                                <span class="text-muted">{{ $m->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="text-body">{!! $highlight($m->body) !!}</div>
                            @if($m->attachment_name)<div class="small text-muted"><i class="fi fi-rr-clip"></i> {!! $highlight($m->attachment_name) !!}</div>@endif
                        </a>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small">No direct messages match.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between"><h6 class="mb-0">Channels</h6><span class="small text-muted">{{ $channel->count() }}{{ $channel->count() >= $limit ? '+' : '' }}</span></div>
                <ul class="list-group list-group-flush">
                    @forelse($channel as $m)
                    <li class="list-group-item">
                        <a href="{{ route('chat.channels.show', $m->channel_id) }}#message-{{ $m->id }}" class="text-decoration-none d-block">
                            <div class="d-flex justify-content-between small">
                                <strong class="text-body">#{{ $m->channel?->name }} · {{ $m->user?->name }}</strong>
                                <span class="text-muted">{{ $m->created_at->format('d M Y H:i') }}</span>
                            </div>
                            <div class="text-body">{!! $highlight($m->body) !!}</div>
                            @if($m->attachment_name)<div class="small text-muted"><i class="fi fi-rr-clip"></i> {!! $highlight($m->attachment_name) !!}</div>@endif
                        </a>
                    </li>
                    @empty
                    <li class="list-group-item text-muted small">No channel messages match.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    @if($direct->count() >= $limit || $channel->count() >= $limit)
    <p class="small text-muted mt-2">Showing the newest {{ $limit }} matches in each list. Use more specific words to narrow it down.</p>
    @endif
    @endif
</div>
</x-app-layout>
