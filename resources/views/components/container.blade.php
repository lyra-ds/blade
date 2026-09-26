@props([
    'max' => null,
])

@php
    $sizes = ['sm' => 640, 'md' => 768, 'lg' => 1024, 'xl' => 1280];
    // Mirrors the React resolveMax: keywords match the raw value, numeric strings are trimmed first.
    $trimmed = is_string($max) ? preg_replace('/^[\s\p{Z}\x{FEFF}]+|[\s\p{Z}\x{FEFF}]+$/u', '', $max) : null;

    $maxPixels = match (true) {
        is_string($max) && array_key_exists($max, $sizes) => $sizes[$max],
        is_int($max) => $max,
        is_float($max) && is_finite($max) => $max,
        is_string($trimmed) && preg_match('/^\d+(\.\d+)?$/D', $trimmed) === 1 => $trimmed,
        default => null,
    };

    $rootAttributes = $attributes->class('lyra-container');

    if ($maxPixels !== null) {
        $rootAttributes = $rootAttributes->merge([
            'style' => "--container-max: {$maxPixels}px",
        ]);
    }
@endphp

<div {{ $rootAttributes }}>{{ $slot }}</div>
