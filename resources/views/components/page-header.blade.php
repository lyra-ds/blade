@props([
    'title',
    'titleAs' => 'h1',
])

@php
    $hasEyebrow = isset($eyebrow) && trim((string) $eyebrow) !== '';
    $hasDescription = isset($description) && trim((string) $description) !== '';
    $hasActions = isset($actions) && trim((string) $actions) !== '';
    $hasContent = trim((string) $slot) !== '';
@endphp

<header {{ $attributes->class('lyra-pageheader') }}>
    <div class="lyra-pageheader__row">
        <div class="lyra-pageheader__text">
            @if ($hasEyebrow)
            <span class="lyra-pageheader__eyebrow">{{ $eyebrow }}</span>
            @endif
            <{{ $titleAs }} class="lyra-pageheader__title">{{ $title }}</{{ $titleAs }}>
            @if ($hasDescription)
            <p class="lyra-pageheader__desc">{{ $description }}</p>
            @endif
        </div>
        @if ($hasActions)
        <div class="lyra-pageheader__actions">{{ $actions }}</div>
        @endif
    </div>
    @if ($hasContent)
    {{ $slot }}
    @endif
</header>
