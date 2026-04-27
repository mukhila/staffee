@props(['status'])

@if ($status)
<div {{ $attributes->merge(['class' => '']) }}>
    <div class="p-3" style="background:#f0fdf4;border-radius:12px;border:1px solid #bbf7d0;">
        <div class="d-flex align-items-center gap-2">
            <i class="fi fi-rr-check-circle" style="color:#22c55e;font-size:1rem;flex-shrink:0;"></i>
            <span style="font-size:.85rem;color:#166534;font-weight:500;">{{ $status }}</span>
        </div>
    </div>
</div>
@endif
