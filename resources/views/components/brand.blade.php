@props([
    'mark',
    'markDark' => null,
    'size' => null,
    'href' => null,
])

@php
    $hasWordmark = ! $slot->isEmpty();
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
    @if ($markDark === null)
        <img class="lyra-brand__mark" src="{{ $mark }}" alt="">
    @else
        <img class="lyra-brand__mark lyra-brand__mark--light" src="{{ $mark }}" alt="">
        <img class="lyra-brand__mark lyra-brand__mark--dark" src="{{ $markDark }}" alt="">
    @endif
    @if ($hasWordmark)
        <span class="lyra-brand__word">{{ $slot }}</span>
    @endif
</{{ $tag }}>
