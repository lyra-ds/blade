@props([
    'defaultOpen' => false,
    'side' => 'auto',
    'align' => null,
    'width' => null,
    'ariaLabel' => 'Popover',
    'wrapTrigger' => true,
])

{{--
    trigger: required. With wrapTrigger=true (the default), pass non-interactive content because
    the component supplies the interactive wrapper. With wrapTrigger=false, pass an interactive
    element carrying x-bind="trigger"; the slot is rendered directly without a wrapper.
--}}
@php
    $resolvedSide = in_array($side, ['auto', 'bottom', 'top'], true)
        ? $side
        : 'auto';
    $resolvedAlign = in_array($align, ['start', 'end', 'center'], true)
        ? $align
        : null;
    $resolvedWidth = $width === null ? null : (int) $width;
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
    $escapedAriaLabel = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $ariaLabel);
    $escapedAriaLabel = htmlspecialchars($escapedAriaLabel, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    $alignOption = $resolvedAlign === null ? '' : ", align: '{$resolvedAlign}'";
    $widthOption = $resolvedWidth === null ? '' : ", width: {$resolvedWidth}";
    $initialSide = $resolvedSide === 'top' ? 'top' : 'bottom';
    $initialAlign = $resolvedAlign ?? 'start';
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $rootAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
@endphp

<span
    x-data="lyraPopover({ defaultOpen: {!! $defaultOpenLiteral !!}, side: '{!! $resolvedSide !!}'{!! $alignOption !!}{!! $widthOption !!}, ariaLabel: '{!! $escapedAriaLabel !!}' })"
    x-modelable="open"
    {{ $modelAttributes }}
    {{ $rootAttributes->class('lyra-popover-anchor') }}
>
    @if ($wrapTrigger)
        <span
            role="button"
            tabindex="0"
            aria-haspopup="dialog"
            aria-expanded="{{ $defaultOpen ? 'true' : 'false' }}"
            x-bind="trigger"
        >{{ $trigger }}</span>
    @else
        {{ $trigger }}
    @endif
    <div
        class="lyra-popover lyra-popover--{{ $initialSide }} lyra-popover--align-{{ $initialAlign }}"
        role="dialog"
        aria-label="{{ $ariaLabel }}"
        @if ($resolvedWidth !== null)
            style="width: {{ $resolvedWidth }}px"
        @endif
        x-bind="panel"
        @if (! $defaultOpen)
            x-cloak
        @endif
    >{{ $slot }}</div>
</span>
