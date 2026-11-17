{{-- Upcoming birthdays (day and month only) and work anniversaries --}}
<ul class="list-group list-group-flush">
    @forelse($celebrations as $c)
    <li class="list-group-item d-flex align-items-center gap-2 {{ $c['is_today'] ? 'bg-warning-subtle' : '' }}">
        @include('people._avatar', ['person' => $c['user'], 'size' => 32])
        <div class="flex-grow-1 min-w-0">
            <div class="fw-medium small text-truncate">{{ $c['user']->name }}</div>
            <div class="text-muted" style="font-size:.75rem">
                @if($c['type'] === 'birthday')
                <i class="fi fi-rr-cake-birthday"></i> Birthday
                @else
                <i class="fi fi-rr-trophy"></i> {{ $c['years'] }} {{ Str::plural('year', $c['years']) }} at the company
                @endif
            </div>
        </div>
        <span class="small text-nowrap {{ $c['is_today'] ? 'fw-bold text-warning' : 'text-muted' }}">
            {{ $c['is_today'] ? 'Today' : $c['date']->format('d M') }}
        </span>
    </li>
    @empty
    <li class="list-group-item text-muted small">{{ $emptyText ?? 'No birthdays or work anniversaries coming up.' }}</li>
    @endforelse
</ul>
