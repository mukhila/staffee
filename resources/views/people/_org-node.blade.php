{{-- One person in the org chart, with their reports nested underneath. --}}
@php
    $reports = $byManager->get($person->id, collect());
    $size    = $teamSize[$person->id] ?? 0;
    $looped  = in_array($person->id, $path, true); // reporting loop guard
@endphp
<li>
    @if($reports->isNotEmpty() && !$looped)
    <details {{ $depth < 2 ? 'open' : '' }}>
        <summary>
    @else
    <div class="org-leaf">
    @endif
            <span class="org-card {{ isset($focus) && $focus?->id === $person->id ? 'org-focus' : '' }}">
                @include('people._avatar', ['person' => $person, 'size' => 32])
                <span class="min-w-0">
                    <a href="{{ route('people.org-chart', ['root' => $person->id]) }}" class="fw-medium text-decoration-none d-block text-truncate">{{ $person->name }}</a>
                    <span class="d-block text-muted text-truncate" style="font-size:.72rem">
                        {{ $person->designation ?: '—' }}@if($person->department) · {{ $person->department->name }}@endif
                    </span>
                </span>
                @if($size > 0)
                <span class="badge bg-secondary-subtle text-secondary ms-auto" title="{{ $reports->count() }} direct, {{ $size }} in total">{{ $size }}</span>
                @endif
            </span>
    @if($reports->isNotEmpty() && !$looped)
        </summary>
        <ul>
            @foreach($reports as $report)
                @include('people._org-node', ['person' => $report, 'depth' => $depth + 1, 'path' => [...$path, $person->id]])
            @endforeach
        </ul>
    </details>
    @else
    </div>
    @endif
</li>
