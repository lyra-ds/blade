@props([
    'columns' => null,
    'minItem' => null,
    'gap' => null,
])

@php
    $styles = [];

    if ($minItem !== null) {
        $styles[] = "--lyra-grid-columns: repeat(auto-fit, minmax(min({$minItem}px, 100%), 1fr))";
    } elseif ($columns !== null) {
        $columnsValue = is_numeric($columns)
            ? "repeat({$columns}, minmax(0, 1fr))"
            : $columns;
        $styles[] = "--lyra-grid-columns: {$columnsValue}";
    }

    if ($gap !== null) {
        $gapValue = is_numeric($gap)
            ? "var(--space-{$gap})"
            : $gap;
        $styles[] = "--lyra-grid-gap: {$gapValue}";
    }

    $rootAttributes = $attributes->class('lyra-grid');

    if ($styles !== []) {
        $rootAttributes = $rootAttributes->merge([
            'style' => implode('; ', $styles),
        ]);
    }
@endphp

<div {{ $rootAttributes }}>{{ $slot }}</div>
