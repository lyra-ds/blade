@props([
    'items',
    'active',
    'variant' => 'line',
])

{{-- panel is a Blade extension because React supplies labelled empty panels while the Alpine canonical markup carries panel content. --}}
{{-- onChange is not ported; consumer state flows through x-model or wire:model. --}}
@php
    $items = array_values($items);
    $resolvedVariant = $variant === 'pills' ? 'pills' : 'line';
    $activeIndex = array_search($active, array_column($items, 'id'), true);
    $activeIndex = $activeIndex === false ? 0 : $activeIndex;
    $resolvedActive = $items[$activeIndex]['id'] ?? $active;
    $escapedActive = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $resolvedActive);
    $escapedActive = htmlspecialchars($escapedActive, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $listAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
@endphp

<div
    x-data="lyraTabs({ active: '{!! $escapedActive !!}' })"
    x-modelable="active"
    {{ $modelAttributes }}
>
    <div
        role="tablist"
        x-bind="list"
        {{ $listAttributes->class([
            'lyra-tabs',
            'lyra-tabs--pills' => $resolvedVariant === 'pills',
        ]) }}
    >
        @foreach ($items as $index => $item)
            @php
                $selected = $index === $activeIndex;
            @endphp
            <button
                type="button"
                role="tab"
                aria-selected="{{ $selected ? 'true' : 'false' }}"
                tabindex="{{ $selected ? 0 : -1 }}"
                @class([
                    'lyra-tab',
                    'lyra-tab--active' => $selected,
                ])
                data-value="{{ $item['id'] }}"
                x-bind="tab"
            >{{ $item['icon'] ?? '' }}{{ $item['label'] }}@if (($item['count'] ?? null) !== null)<span class="lyra-tab__count">{{ $item['count'] }}</span>@endif</button>
        @endforeach
    </div>
    @foreach ($items as $index => $item)
        <div
            role="tabpanel"
            tabindex="0"
            data-value="{{ $item['id'] }}"
            @if ($index !== $activeIndex)
                hidden
            @endif
            x-bind="panel"
        >{{ $item['panel'] ?? '' }}</div>
    @endforeach
</div>
