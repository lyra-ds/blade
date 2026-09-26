@props([
    'max' => null,
])

@php
    $sizes = ['sm' => 640, 'md' => 768, 'lg' => 1024, 'xl' => 1280];
    $maxKey = is_string($max) ? trim($max) : $max;

    $maxPixels = match (true) {
        is_string($maxKey) && array_key_exists($maxKey, $sizes) => $sizes[$maxKey],
        is_int($maxKey) => $maxKey,
        is_float($maxKey) && is_finite($maxKey) => $maxKey,
        is_string($maxKey) && preg_match('/^\d+(\.\d+)?$/D', $max) === 1 => $maxKey,
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
