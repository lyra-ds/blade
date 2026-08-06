@props([
    'active' => false,
])

{{-- asChild: JS-only Slot merging, not ported. --}}
<a
    {{ $attributes->class([
        'lyra-navlink',
        'lyra-navlink--active' => $active,
    ]) }}
    @if ($active)
        aria-current="page"
    @endif
>{{ $slot }}</a>
