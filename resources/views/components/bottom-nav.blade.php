@props([
    'items',
])

{{-- id/onClick/onSelect: JS selection flow, phase 2 --}}
<nav {{ $attributes->class('lyra-bottomnav') }}>
    @foreach ($items as $item)
        <button type="button"
            @class([
                'lyra-bottomnav__item',
                'lyra-bottomnav__item--active' => $item['active'] ?? false,
            ])
            @if ($item['active'] ?? false)
                aria-current="page"
            @endif
        >
            <span class="lyra-bottomnav__icon">{{ $item['icon'] }}</span>
            <span class="lyra-bottomnav__label">{{ $item['label'] }}</span>
        </button>
    @endforeach
</nav>
