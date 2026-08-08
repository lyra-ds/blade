@props([
    'direction' => null,
    'gap' => null,
    'align' => null,
    'justify' => null,
    'wrap' => false,
    'as' => 'div',
])

@php
    $styles = [];

    if ($direction !== null) {
        $styles[] = "--lyra-stack-direction: {$direction}";
    }

    if ($gap !== null) {
        $gapValue = is_numeric($gap)
            ? "var(--space-{$gap})"
            : $gap;
        $styles[] = "--lyra-stack-gap: {$gapValue}";
    }

    if ($align !== null) {
        $styles[] = "--lyra-stack-align: {$align}";
    }

    if ($justify !== null) {
        $styles[] = "--lyra-stack-justify: {$justify}";
    }

    if ($wrap) {
        $styles[] = '--lyra-stack-wrap: wrap';
    }

    $rootAttributes = $attributes->class('lyra-stack');

    if ($styles !== []) {
        $rootAttributes = $rootAttributes->merge([
            'style' => implode('; ', $styles),
        ]);
    }
@endphp

<{{ $as }} {{ $rootAttributes }}>{{ $slot }}</{{ $as }}>
