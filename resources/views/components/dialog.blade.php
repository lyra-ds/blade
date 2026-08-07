@props([
    'title',
    'closable' => true,
    'closeLabel' => 'Close',
    'defaultOpen' => false,
    'closeOnEsc' => true,
    'closeOnOverlayClick' => true,
    'labelId' => null,
])

{{-- closable maps React's onClose-provided condition to rendering the plugin-owned close control. --}}
@php
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
    $closeOnEscLiteral = $closeOnEsc ? 'true' : 'false';
    $closeOnOverlayClickLiteral = $closeOnOverlayClick ? 'true' : 'false';
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $panelAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
    $labelIdOption = '';

    if ($labelId !== null) {
        $escapedLabelId = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], $labelId);
        $escapedLabelId = htmlspecialchars($escapedLabelId, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
        $labelIdOption = ", labelId: '{$escapedLabelId}'";
    }
@endphp

<div
    class="lyra-dialog-overlay"
    x-data="lyraDialog({ defaultOpen: {!! $defaultOpenLiteral !!}, closeOnEsc: {!! $closeOnEscLiteral !!}, closeOnOverlayClick: {!! $closeOnOverlayClickLiteral !!}{!! $labelIdOption !!} })"
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
        {{ $panelAttributes->class('lyra-dialog') }}
    >
        <div class="lyra-dialog__header">
            <h2
                class="lyra-dialog__title"
                @if ($labelId !== null)
                    id="{{ $labelId }}"
                @endif
                x-bind="title"
            >{{ $title }}</h2>
            @if ($closable)
                <button
                    type="button"
                    class="lyra-dialog__close"
                    aria-label="{{ $closeLabel }}"
                    x-bind="close"
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
        <div class="lyra-dialog__body">{{ $slot }}</div>
        @isset($footer)
            <div class="lyra-dialog__footer">{{ $footer }}</div>
        @endisset
    </div>
</div>
