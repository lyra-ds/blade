@props([
    'items',
])

{{--
    Items with an href render as native anchors; the rest stay buttons. Items with an id dispatch a
    bubbling lyra:select event ({ id }) through an inline Alpine handler and carry data-id. The
    nav only serves x-data when at least one item has an id, so the markup stays inert without
    Alpine and no scope is added when there is nothing to dispatch.
--}}
@php
    $items = array_values($items);
    $hasIds = collect($items)->contains(fn (array $item): bool => isset($item['id']) && $item['id'] !== '');
@endphp

<nav
    @if ($hasIds)
        x-data
    @endif
    {{ $attributes->class('lyra-bottomnav') }}
>
    @foreach ($items as $item)
        @php
            $active = $item['active'] ?? false;
            $hasId = isset($item['id']) && $item['id'] !== '';
            $isLink = isset($item['href']) && $item['href'] !== '';
        @endphp
        @if ($isLink)
            <a
                @class([
                    'lyra-bottomnav__item',
                    'lyra-bottomnav__item--active' => $active,
                ])
                href="{{ $item['href'] }}"
                @if (isset($item['target']))
                    target="{{ $item['target'] }}"
                @endif
                @if (isset($item['rel']))
                    rel="{{ $item['rel'] }}"
                @endif
                @if ($active)
                    aria-current="page"
                @endif
                @if ($hasId)
                    data-id="{{ $item['id'] }}"
                    x-on:click="$dispatch('lyra:select', { id: $el.dataset.id })"
                @endif
            >
                <span class="lyra-bottomnav__icon">{{ $item['icon'] }}</span>
                <span class="lyra-bottomnav__label">{{ $item['label'] }}</span>
            </a>
        @else
            <button type="button"
                @class([
                    'lyra-bottomnav__item',
                    'lyra-bottomnav__item--active' => $active,
                ])
                @if ($active)
                    aria-current="page"
                @endif
                @if ($hasId)
                    data-id="{{ $item['id'] }}"
                    x-on:click="$dispatch('lyra:select', { id: $el.dataset.id })"
                @endif
            >
                <span class="lyra-bottomnav__icon">{{ $item['icon'] }}</span>
                <span class="lyra-bottomnav__label">{{ $item['label'] }}</span>
            </button>
        @endif
    @endforeach
</nav>
