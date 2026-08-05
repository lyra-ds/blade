@props([
    'variant' => 'primary',
    'size' => 'md',
    'loading' => false,
    'disabled' => false,
    'full' => false,
])

@php
    $classes = collect([
        'lyra-btn',
        "lyra-btn--{$variant}",
        "lyra-btn--{$size}",
        $loading ? 'lyra-btn--loading' : null,
        $full ? 'lyra-btn--full' : null,
    ])->filter()->implode(' ');
@endphp

{{-- React's asChild prop is intentionally omitted in phase 1. --}}
<button {{ $attributes->class($classes) }} @disabled($disabled || $loading) @if ($loading) aria-busy="true" @endif>
    @if ($loading)
        <span class="lyra-btn__spinner" aria-hidden="true"></span>
    @endif
    @isset($iconLeft)
        {{ $iconLeft }}
    @endisset
    @if (! $slot->isEmpty())
        <span class="lyra-btn__label">{{ $slot }}</span>
    @endif
    @isset($iconRight)
        {{ $iconRight }}
    @endisset
</button>
