@props([
    'items',
    'align' => 'start',
    'defaultOpen' => false,
    'triggerVariant' => null,
    'triggerSize' => 'md',
])

{{-- trigger: pass non-interactive content (text or icon), never a button or link. --}}
{{-- trigger-variant/trigger-size: style the one trigger element like a Button (lyra-btn classes on the span[role=button]), so there is a single tab stop; never nest a button component in the slot. --}}
{{-- items: each item may carry `id` (rendered as data-id) and `href` (renders a link menuitem). Selecting an item dispatches lyra:select with { id } on the root (bubbles), and the plugin closes the menu. --}}
{{-- root: do not pass x-data on the root; wrap the component instead. --}}
@php
    $resolvedAlign = $align === 'end' ? 'end' : 'start';
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
    $triggerClasses = ['lyra-dropdown__trigger'];

    if ($triggerVariant !== null && $triggerVariant !== '') {
        array_unshift($triggerClasses, 'lyra-btn', "lyra-btn--{$triggerVariant}", "lyra-btn--{$triggerSize}");
    }
@endphp

<span
    x-data="lyraDropdown({ defaultOpen: {!! $defaultOpenLiteral !!}, align: '{!! $resolvedAlign !!}' })"
    x-modelable="open"
    {{ $attributes->class('lyra-dropdown') }}
>
    <span
        class="{{ implode(' ', $triggerClasses) }}"
        role="button"
        tabindex="0"
        aria-haspopup="menu"
        aria-expanded="{{ $defaultOpen ? 'true' : 'false' }}"
        x-bind="trigger"
    >{{ $trigger }}</span>
    <div
        class="lyra-menu lyra-menu--{{ $resolvedAlign }}"
        role="menu"
        x-bind="menu"
        @if (! $defaultOpen)
            x-cloak
        @endif
    >
        @foreach ($items as $item)
            @if (($item['type'] ?? null) === 'separator')
            <hr class="lyra-menu__sep">
            @elseif (($item['type'] ?? null) === 'label')
            <span class="lyra-menu__label">{{ $item['label'] }}</span>
            @else
            @php
                $itemIcon = ($item['icon'] ?? '') === '' ? '' : '<span aria-hidden="true">'.e($item['icon']).'</span>';
                $itemContent = $itemIcon.e($item['label']);
                $itemId = $item['id'] ?? null;
                $itemHref = $item['href'] ?? null;
                $itemClasses = [
                    'lyra-menu__item',
                    'lyra-menu__item--danger' => $item['danger'] ?? false,
                ];
            @endphp
            @if ($itemHref !== null && $itemHref !== '')
            <a
                href="{{ $itemHref }}"
                role="menuitem"
                @class($itemClasses)
                @if ($itemId !== null && $itemId !== '') data-id="{{ $itemId }}" @endif
                x-bind="item"
                x-on:click="$dispatch('lyra:select', { id: $el.dataset.id ?? '' })"
            >{!! $itemContent !!}</a>
            @else
            <button
                type="button"
                role="menuitem"
                @class($itemClasses)
                @if ($itemId !== null && $itemId !== '') data-id="{{ $itemId }}" @endif
                x-bind="item"
                x-on:click="$dispatch('lyra:select', { id: $el.dataset.id ?? '' })"
            >{!! $itemContent !!}</button>
            @endif
            @endif
        @endforeach
    </div>
</span>
