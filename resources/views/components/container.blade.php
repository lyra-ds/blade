@props([
    'max' => null,
])

@php
    $sizes = ['sm' => 640, 'md' => 768, 'lg' => 1024, 'xl' => 1280];
    $maxKey = is_string($max) ? trim($max) : $max;

    $maxPixels = match (true) {
        is_string($maxKey) && array_key_exists($maxKey, $sizes) => $sizes[$maxKey],
        is_int($maxKey) || is_float($maxKey) => $maxKey,
        is_string($maxKey) && is_numeric($maxKey) => $maxKey,
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
