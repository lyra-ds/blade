@props([
    'width' => '100%',
    'height' => 14,
    'circle' => false,
])

@php
    $formatDimension = static fn (int|float|string $value): string => is_int($value) || is_float($value)
        ? "{$value}px"
        : $value;

    $heightValue = $formatDimension($height);
    $widthValue = $circle ? $heightValue : $formatDimension($width);
@endphp

<span {{ $attributes->except('aria-hidden')->class([
    'lyra-skeleton',
    'lyra-skeleton--circle' => $circle,
])->merge([
    'aria-hidden' => 'true',
    'style' => "width: {$widthValue}; height: {$heightValue}",
]) }}></span>
