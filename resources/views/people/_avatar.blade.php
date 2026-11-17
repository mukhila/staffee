@php
    $size = $size ?? 40;
    $initials = collect(explode(' ', trim($person->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
@if($person->avatar_url)
<img src="{{ $person->avatar_url }}" alt="" class="rounded-circle flex-shrink-0" style="width:{{ $size }}px;height:{{ $size }}px;object-fit:cover;">
@else
<span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 bg-primary-subtle text-primary fw-semibold"
      style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.38) }}px;" aria-hidden="true">{{ $initials }}</span>
@endif
