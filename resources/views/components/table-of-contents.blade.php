@props([
    'items',
    'activeId' => null,
    'label',
])

{{-- The Alpine binding owns scroll-spy updates; this template always serves the complete link list. --}}
@php
    $items = array_values($items);
    $resolvedActiveId = $activeId === null ? '' : (string) $activeId;
    $escapedActiveId = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $resolvedActiveId);
    $escapedActiveId = htmlspecialchars($escapedActiveId, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
@endphp

<nav
    x-data="lyraTableOfContents({ activeId: '{!! $escapedActiveId !!}' })"
    x-modelable="activeId"
    {{ $attributes->merge(['aria-label' => $label])->class('lyra-toc') }}
>
    <span class="lyra-toc__title">{{ $label }}</span>
    <ul class="lyra-toc__list">
        @foreach ($items as $item)
            @php
                $active = $item['id'] === $activeId;
            @endphp
            <li data-level="{{ $item['level'] ?? 2 }}">
                <a
                    href="#{{ $item['id'] }}"
                    @class([
                        'lyra-toc__link',
                        'lyra-toc__link--active' => $active,
                    ])
                    @if ($active)
                        aria-current="location"
                    @endif
                    x-bind="link"
                >{{ $item['label'] }}</a>
            </li>
        @endforeach
    </ul>
</nav>
