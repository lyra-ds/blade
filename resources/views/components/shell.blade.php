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
    'skipLink' => null,
    'mainId' => null,
])

@php
    $hasSidebar = isset($sidebar) && trim((string) $sidebar) !== '';
    $hasTopbar = isset($topbar) && trim((string) $topbar) !== '';
    $hasAside = isset($aside) && trim((string) $aside) !== '';
    $hasBanner = isset($banner) && trim((string) $banner) !== '';
    $hasSkipLink = is_array($skipLink) && isset($skipLink['label']);
    $resolvedMainId = $mainId ?? ($hasSkipLink ? 'lyra-shell-main-'.uniqid() : null);
    $mainAttributes = ($resolvedMainId !== null ? ' id="'.e($resolvedMainId).'"' : '').($hasSkipLink ? ' tabindex="-1"' : '');
    $skipHref = $hasSkipLink ? ($skipLink['href'] ?? '#'.$resolvedMainId) : null;
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
        'lyra-shell--has-banner' => $hasBanner,
    ]);

    if ($styles !== []) {
        $rootAttributes = $rootAttributes->merge([
            'style' => implode('; ', $styles),
        ]);
    }
@endphp

<div {{ $rootAttributes }}>
    @if ($hasSkipLink)
        <a
            class="lyra-shell__skip-link"
            href="{{ $skipHref }}"
            x-data
            @click="if ($event.defaultPrevented || $event.button !== 0 || $event.metaKey || $event.ctrlKey || $event.shiftKey || $event.altKey || !$el.hash) return; const url = new URL($el.href); if (url.origin !== location.origin || url.pathname !== location.pathname || url.search !== location.search) return; $el.ownerDocument.getElementById(decodeURIComponent($el.hash.slice(1)))?.focus()"
        >{{ $skipLink['label'] }}</a>
    @endif
    @if ($hasBanner)
        <header class="lyra-shell__banner">{{ $banner }}</header>
    @endif
    @if ($hasSidebar)
        @if ($sidebarLabel === null || $sidebarAs === 'div')
        <{{ $sidebarAs }} class="lyra-shell__sidebar">{{ $sidebar }}</{{ $sidebarAs }}>
        @else
        <{{ $sidebarAs }} class="lyra-shell__sidebar" aria-label="{{ $sidebarLabel }}">{{ $sidebar }}</{{ $sidebarAs }}>
        @endif
    @endif
    <{{ $mainAs }} class="lyra-shell__main"{!! $mainAttributes !!}>
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
