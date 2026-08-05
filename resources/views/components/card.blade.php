@props([
    'padded' => true,
    'interactive' => false,
])

@php
    $hasTitle = isset($title) && trim((string) $title) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $hasFooter = isset($footer) && trim((string) $footer) !== '';
    $hasStructure = $hasTitle || $hasFooter;
    $classes = collect([
        'lyra-card',
        $interactive ? 'lyra-card--interactive' : null,
        ! $hasStructure && $padded ? 'lyra-card--padded' : null,
    ])->filter()->implode(' ');
@endphp

{{-- React's asChild prop is intentionally omitted in phase 1. --}}
@if (! $hasStructure)
<div {{ $attributes->class($classes) }}>{{ $slot }}</div>
@else
<div {{ $attributes->class($classes) }}>
    @if ($hasTitle)
    <div class="lyra-card__header"><h3 class="lyra-card__title">{{ $title }}</h3>@if ($hasActions)<div class="lyra-card__actions">{{ $actions }}</div>@endif</div>
    @endif
    @if ($padded)
    <div class="lyra-card__body">{{ $slot }}</div>
    @else
    <div>{{ $slot }}</div>
    @endif
    @if ($hasFooter)
    <div class="lyra-card__footer">{{ $footer }}</div>
    @endif
</div>
@endif
