@props([
    'items',
    'defaultOpen' => null,
    'multiple' => false,
])

{{-- State changes are owned by the Alpine plugin and may be consumed through x-model or wire:model. --}}
@php
    $items = array_values($items);
    $multipleLiteral = $multiple ? 'true' : 'false';
    $defaultOpenOption = '';

    if ($defaultOpen !== null) {
        $escapedDefaultOpen = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $defaultOpen);
        $escapedDefaultOpen = htmlspecialchars($escapedDefaultOpen, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
        $defaultOpenOption = ", defaultOpen: '{$escapedDefaultOpen}'";
    }
@endphp

<div
    x-data="lyraAccordion({ multiple: {!! $multipleLiteral !!}{!! $defaultOpenOption !!} })"
    x-modelable="openItems"
    {{ $attributes->class('lyra-accordion') }}
>
    @foreach ($items as $item)
        @php
            $open = $item['id'] === $defaultOpen;
        @endphp
        <div
            @class([
                'lyra-acc__item',
                'lyra-acc__item--open' => $open,
            ])
            data-value="{{ $item['id'] }}"
            x-bind="item"
        >
            <button
                type="button"
                class="lyra-acc__trigger"
                aria-expanded="{{ $open ? 'true' : 'false' }}"
                x-bind="trigger"
            >{{ $item['title'] }}<span class="lyra-acc__chevron" aria-hidden="true"></span></button>
            <div
                class="lyra-acc__panel-wrap"
                @if (! $open)
                    inert
                @endif
                x-bind="panelWrap"
            >
                <div class="lyra-acc__panel-clip">
                    <div
                        class="lyra-acc__panel"
                        x-bind="panel"
                    >{{ $item['content'] }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
