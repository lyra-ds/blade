@props([
    'label' => null,
    'items' => [],
    'collapsible' => false,
    'defaultCollapsed' => false,
])

{{--
    Unlike React, collapsible items stay in the served DOM and x-show controls visibility. Using
    x-if would put the navigation inside an inert template when Alpine is absent, while a visible
    expanded group is a complete no-JS state. Only an initially collapsed group is x-cloaked, so
    both initial states match React without a flash while Alpine boots.

    Item selection is exposed by the Alpine binding as lyra:select with the served data-id. The
    label and item buttons deliberately serve type="button" even though their bindings also set it,
    preventing accidental form submission before Alpine initializes.
--}}
@php
    $items = array_values($items);
    $startsCollapsed = $collapsible && $defaultCollapsed;
    $defaultCollapsedLiteral = $startsCollapsed ? 'true' : 'false';
@endphp

<div
    x-data="lyraSidebarGroup({ defaultCollapsed: {!! $defaultCollapsedLiteral !!} })"
    x-bind="root"
    {{ $attributes->class([
        'lyra-sbgroup',
        'lyra-sbgroup--collapsed' => $startsCollapsed,
    ]) }}
>
    @if ($label !== null && $label !== '')
        @if ($collapsible)
            <button
                type="button"
                class="lyra-sbgroup__label lyra-sbgroup__label--btn"
                aria-expanded="{{ $startsCollapsed ? 'false' : 'true' }}"
                x-bind="label"
            >
                <span>{{ $label }}</span>
                <svg
                    class="lyra-sbgroup__chev"
                    aria-hidden="true"
                    width="13"
                    height="13"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
        @else
            <div class="lyra-sbgroup__label">{{ $label }}</div>
        @endif
    @endif
    <div
        class="lyra-sbgroup__items"
        @if ($collapsible)
            x-show="!collapsed"
        @endif
        @if ($startsCollapsed)
            x-cloak
        @endif
    >
        @foreach ($items as $item)
            @php
                $active = $item['active'] ?? false;
                $icon = $item['icon'] ?? null;
                $hasIcon = $icon instanceof \Illuminate\Contracts\Support\Htmlable
                    ? $icon->toHtml() !== ''
                    : (bool) $icon;
                $hasBadge = array_key_exists('badge', $item) && $item['badge'] !== null;
            @endphp
            <button
                type="button"
                @class([
                    'lyra-sbgroup__item',
                    'lyra-sbgroup__item--active' => $active,
                ])
                x-bind="item"
                data-id="{{ $item['id'] }}"
                @if ($active)
                    aria-current="page"
                @endif
                @if (isset($item['title']))
                    title="{{ $item['title'] }}"
                @endif
            >
                @if ($hasIcon)
                    <span class="lyra-sbgroup__item-icon">{{ $icon }}</span>
                @endif
                <span class="lyra-sbgroup__item-label">{{ $item['label'] }}</span>
                @if ($hasBadge)
                    <span class="lyra-sbgroup__item-badge">{{ $item['badge'] }}</span>
                @endif
            </button>
        @endforeach
        {{ $slot }}
    </div>
</div>
