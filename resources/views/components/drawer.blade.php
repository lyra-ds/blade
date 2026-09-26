@props([
    'title',
    'closable' => true,
    'closeLabel' => 'Close',
    'defaultOpen' => false,
    'labelId' => null,
    'returnFocusTo' => null,
])

{{-- Public contract (frozen at 1.0): return-focus-to takes a CSS selector (e.g. "#open-dialog") resolved with document.querySelector when the overlay closes; empty falls back to the previously focused element. --}}

{{-- closable maps React's onClose-provided condition to rendering the plugin-owned close control. --}}
{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'drawer', ['x-modelable', 'x-bind']);
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $panelAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
    $labelIdOption = '';

    if ($labelId !== null) {
        $escapedLabelId = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $labelId);
        $escapedLabelId = htmlspecialchars($escapedLabelId, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
        $labelIdOption = ", labelId: '{$escapedLabelId}'";
    }
    $returnFocusResolver = \LyraDs\Blade\FocusResolver::selector($returnFocusTo);
    $returnFocusOption = $returnFocusResolver === null ? '' : ', returnFocusTo: '.$returnFocusResolver;
    $closeLabelBinding = json_encode((string) $closeLabel, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
@endphp

<div
    class="lyra-drawer-overlay"
    x-data="lyraDrawer({ defaultOpen: {!! $defaultOpenLiteral !!}{!! $labelIdOption !!}{{ $returnFocusOption }} })"
    x-modelable="open"
    x-bind="overlay"
    @if (! $defaultOpen)
        x-cloak
    @endif
    {{ $modelAttributes }}
>
    <div
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        @if ($labelId !== null)
            aria-labelledby="{{ $labelId }}"
        @endif
        x-bind="panel"
        {{ $panelAttributes->class('lyra-drawer') }}
    >
        <div class="lyra-drawer__header">
            <h2
                class="lyra-drawer__title"
                @if ($labelId !== null)
                    id="{{ $labelId }}"
                @endif
                x-bind="title"
            >{{ $title }}</h2>
            @if ($closable)
                <button
                    type="button"
                    class="lyra-drawer__close"
                    aria-label="{{ $closeLabel }}"
                    x-bind="close"
                    :aria-label="{{ $closeLabelBinding }}"
                >
                    <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                    >
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            @endif
        </div>
        <div class="lyra-drawer__body">{{ $slot }}</div>
        @isset($footer)
            <div class="lyra-drawer__footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
