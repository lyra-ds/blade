@props([
    'direction' => 'flat',
])

@php
    $arrow = match ($direction) {
        'up' => '↑',
        'down' => '↓',
        default => '→',
    };
@endphp

<div {{ $attributes->class('lyra-stat') }}>
    <span class="lyra-stat__label">{{ $label }}</span>
    <span class="lyra-stat__value">{{ $value }}</span>
    @if (isset($delta))
    <span class="lyra-stat__delta lyra-stat__delta--{{ $direction }}">{{ $arrow }} {{ $delta }}</span>
    @endif
</div>
