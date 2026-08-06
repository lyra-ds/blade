@props([
    'sidebarAs' => 'aside',
    'asideAs' => 'aside',
    'sidebarLabel' => null,
    'asideLabel' => null,
    'mainAs' => 'main',
    'scroll' => 'page',
    'sidebarWidth' => null,
    'asideWidth' => null,
    'top' => null,
])

@php
    $hasSidebar = isset($sidebar) && trim((string) $sidebar) !== '';
    $hasTopbar = isset($topbar) && trim((string) $topbar) !== '';
    $hasAside = isset($aside) && trim((string) $aside) !== '';
    $styles = [];

    if ($sidebarWidth !== null) {
        $styles[] = "--shell-sidebar: {$sidebarWidth}px";
    }

    if ($asideWidth !== null) {
        $styles[] = "--shell-aside: {$asideWidth}px";
    }

    if ($top !== null) {
        $styles[] = "--shell-top: {$top}px";
    }

    $rootAttributes = $attributes->class([
        'lyra-shell',
        "lyra-shell--{$scroll}",
        'lyra-shell--has-sidebar' => $hasSidebar,
        'lyra-shell--has-aside' => $hasAside,
    ]);

    if ($styles !== []) {
        $rootAttributes = $rootAttributes->merge([
            'style' => implode('; ', $styles),
        ]);
    }
@endphp

<div {{ $rootAttributes }}>
    @if ($hasSidebar)
        @if ($sidebarLabel === null)
        <{{ $sidebarAs }} class="lyra-shell__sidebar">{{ $sidebar }}</{{ $sidebarAs }}>
        @else
        <{{ $sidebarAs }} class="lyra-shell__sidebar" aria-label="{{ $sidebarLabel }}">{{ $sidebar }}</{{ $sidebarAs }}>
        @endif
    @endif
    <{{ $mainAs }} class="lyra-shell__main">
        @if ($hasTopbar)
        <div class="lyra-shell__topbar">{{ $topbar }}</div>
        @endif
        <div class="lyra-shell__content">{{ $slot }}</div>
    </{{ $mainAs }}>
    @if ($hasAside)
        @if ($asideLabel === null)
        <{{ $asideAs }} class="lyra-shell__aside">{{ $aside }}</{{ $asideAs }}>
        @else
        <{{ $asideAs }} class="lyra-shell__aside" aria-label="{{ $asideLabel }}">{{ $aside }}</{{ $asideAs }}>
        @endif
    @endif
</div>
