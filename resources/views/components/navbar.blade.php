@props([
    'sticky' => true,
    'navLabel' => null,
])

@php
    $hasBrand = isset($brand) && trim((string) $brand) !== '';
    $hasNav = isset($nav) && trim((string) $nav) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
@endphp

<header {{ $attributes->class([
    'lyra-navbar',
    'lyra-navbar--static' => $sticky === false,
]) }}>
    <x-lyra::container class="lyra-navbar__inner">
        @if ($hasBrand)
        <div class="lyra-navbar__brand">{{ $brand }}</div>
        @endif
        @if ($hasNav)
        <nav class="lyra-navbar__nav"@if ($navLabel !== null) aria-label="{{ $navLabel }}"@endif>{{ $nav }}</nav>
        @endif
        @if ($hasActions)
        <div class="lyra-navbar__actions">{{ $actions }}</div>
        @endif
    </x-lyra::container>
</header>
