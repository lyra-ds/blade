@props([
    'items',
    'active',
    'variant' => 'line',
    'label' => 'Tabs',
])

{{-- Emits the complete progressive-enhancement markup required by @lyra-ds/alpine >=1.0 lyraTabs: root data-lyra-tabs with an id, a labelled fallback nav of anchors, the enhanced list, and headed sections. --}}
{{-- panel is a Blade extension because React supplies labelled empty panels while the Alpine canonical markup carries panel content. --}}
{{-- onChange is not ported; consumer state flows through x-model or wire:model, or the lyra:tabs-before-change and lyra:tabs-change events. --}}
@php
    $items = array_values($items);
    $resolvedVariant = $variant === 'pills' ? 'pills' : 'line';
    $activeIndex = array_search($active, array_column($items, 'id'), true);
    $activeIndex = $activeIndex === false ? 0 : $activeIndex;
    $resolvedActive = $items[$activeIndex]['id'] ?? $active;
    $escapedActive = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $resolvedActive);
    $escapedActive = htmlspecialchars($escapedActive, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    $rootId = $attributes->get('id') ?? 'lyra-tabs-'.uniqid();
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $listAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model', 'id']);
@endphp

<div
    id="{{ $rootId }}"
    data-lyra-tabs
    x-data="lyraTabs({ active: '{!! $escapedActive !!}' })"
    x-modelable="active"
    {{ $modelAttributes }}
>
    <nav aria-label="{{ $label }}" data-lyra-tabs-fallback x-bind="fallback">
        @foreach ($items as $index => $item)
            <a href="#{{ $rootId }}-panel-{{ $index }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
    <div
        aria-label="{{ $label }}"
        data-lyra-tabs-enhanced
        hidden
        x-bind="list"
        {{ $listAttributes->class([
            'lyra-tabs',
            'lyra-tabs--pills' => $resolvedVariant === 'pills',
        ]) }}
    >
        @foreach ($items as $index => $item)
            <button
                type="button"
                class="lyra-tab"
                id="{{ $rootId }}-tab-{{ $index }}"
                data-value="{{ $item['id'] }}"
                x-bind="tab"
            >{{ $item['icon'] ?? '' }}{{ $item['label'] }}@if (($item['count'] ?? null) !== null)<span class="lyra-tab__count">{{ $item['count'] }}</span>@endif</button>
        @endforeach
    </div>
    @foreach ($items as $index => $item)
        <section
            id="{{ $rootId }}-panel-{{ $index }}"
            data-value="{{ $item['id'] }}"
            x-bind="panel"
        >
            <h2>{{ $item['label'] }}</h2>
            {{ $item['panel'] ?? '' }}
        </section>
    @endforeach
</div>
