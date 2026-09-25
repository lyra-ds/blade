@props([
    'mark' => null,
    'markDark' => null,
    'size' => null,
    'href' => null,
])

@php
    $hasWordmark = ! $slot->isEmpty();
    $initialSource = $hasWordmark ? strip_tags((string) $slot) : (string) ($attributes->get('aria-label') ?? '');
    $initial = mb_strtoupper(mb_substr(trim($initialSource), 0, 1));
    $tag = $href !== null ? 'a' : 'span';
    $rootAttributes = $attributes->class('lyra-brand');

    if ($size !== null) {
        $rootAttributes = $rootAttributes->merge([
            'style' => "--brand-mark-size: {$size}px",
        ]);
    }

    if ($href !== null) {
        $rootAttributes = $rootAttributes->merge(['href' => $href]);
    } elseif (! $hasWordmark) {
        $rootAttributes = $rootAttributes->merge(['role' => 'img']);
    }
@endphp

{{-- asChild: JS-only Slot merging, not ported. --}}
<{{ $tag }} {{ $rootAttributes }}>
    @if ($mark === null)
        @if ($initial !== '')
            <span class="lyra-brand__mark lyra-brand__mark--initial" aria-hidden="true">{{ $initial }}</span>
        @endif
    @elseif ($markDark === null)
        <img class="lyra-brand__mark" src="{{ $mark }}" alt="">
    @else
        <img class="lyra-brand__mark lyra-brand__mark--light" src="{{ $mark }}" alt="">
        <img class="lyra-brand__mark lyra-brand__mark--dark" src="{{ $markDark }}" alt="">
    @endif
    @if ($hasWordmark)
        <span class="lyra-brand__word">{{ $slot }}</span>
    @endif
</{{ $tag }}>
