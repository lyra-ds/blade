@props([
    'tip',
    'placement' => 'top',
])

{{-- For full keyboard/screen-reader support, the slot content should be focusable; the wrapper carries the plugin's target binding, so aria-describedby lands on the wrapper instead of the child as a Blade limitation. --}}
@php
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
    <span x-bind="target">{{ $slot }}</span>
    <span role="tooltip" hidden x-bind="bubble">{{ $tip }}</span>
</span>
