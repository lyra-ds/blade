@props([
    'items',
    'align' => 'start',
    'defaultOpen' => false,
])

{{-- trigger: pass non-interactive content (text or icon), never a button or link. --}}
{{-- items: onSelect is not ported; consumer Alpine/Livewire code owns selection, and the plugin closes the menu. --}}
{{-- root: do not pass x-data on the root; wrap the component instead. --}}
@php
    $resolvedAlign = $align === 'end' ? 'end' : 'start';
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
@endphp

<span
    x-data="lyraDropdown({ defaultOpen: {!! $defaultOpenLiteral !!}, align: '{!! $resolvedAlign !!}' })"
    x-modelable="open"
    {{ $attributes->class('lyra-dropdown') }}
>
    <span
        class="lyra-dropdown__trigger"
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
                $itemContent = e($item['icon'] ?? '').e($item['label']);
            @endphp
            <button
                type="button"
                role="menuitem"
                @class([
                    'lyra-menu__item',
                    'lyra-menu__item--danger' => $item['danger'] ?? false,
                ])
                x-bind="item"
            >{!! $itemContent !!}</button>
            @endif
        @endforeach
    </div>
</span>
