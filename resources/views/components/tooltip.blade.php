@props([
    'tip',
    'placement' => 'top',
    'bubbleId' => null,
])

{{-- Slot content should be one focusable element. Without bubble-id the wrapper carries the plugin's target binding, so aria-describedby lands on the (non-focusable) wrapper. --}}
{{-- With bubble-id the wrapper is dropped: the bubble renders that id and the child carries aria-describedby="<bubble-id>" itself, as in the Alpine docs. The plugin only adds hover/focus behavior on the root. --}}
{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'tooltip', ['x-bind']);
    $resolvedPlacement = in_array($placement, ['top', 'bottom', 'left', 'right'], true)
        ? $placement
        : 'top';
    $escapedTip = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $tip);
    $escapedTip = htmlspecialchars($escapedTip, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
@endphp

<span
    x-data="lyraTooltip({ tip: '{!! $escapedTip !!}', placement: '{!! $resolvedPlacement !!}' })"
    x-bind="root"
    data-tip="{{ $tip }}"
    data-state="closed"
    {{ $attributes->class([
        'lyra-tooltip',
        "lyra-tooltip--{$resolvedPlacement}" => $resolvedPlacement !== 'top',
    ]) }}
>
    @if ($bubbleId !== null && $bubbleId !== '')
    {{ $slot }}
    <span id="{{ $bubbleId }}" role="tooltip" hidden>{{ $tip }}</span>
    @else
    <span x-bind="target">{{ $slot }}</span>
    <span role="tooltip" hidden x-bind="bubble">{{ $tip }}</span>
    @endif
</span>
