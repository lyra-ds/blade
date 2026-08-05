@props([
    'value',
    'tone' => null,
])

@php
    $clampedValue = max(0, min(100, $value));
@endphp

<div {{ $attributes->except([
    'role',
    'aria-valuenow',
    'aria-valuemin',
    'aria-valuemax',
])->class([
    'lyra-progress',
    "lyra-progress--{$tone}" => $tone !== null,
])->merge([
    'role' => 'progressbar',
    'aria-valuenow' => $clampedValue,
    'aria-valuemin' => 0,
    'aria-valuemax' => 100,
]) }}>
    <div class="lyra-progress__fill" style="width: {{ $clampedValue }}%"></div>
</div>
