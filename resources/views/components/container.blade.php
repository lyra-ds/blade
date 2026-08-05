@props([
    'max' => null,
])

@php
    $rootAttributes = $attributes->class('lyra-container');

    if ($max !== null) {
        $rootAttributes = $rootAttributes->merge([
            'style' => "--container-max: {$max}px",
        ]);
    }
@endphp

<div {{ $rootAttributes }}>{{ $slot }}</div>
