@props([
    'title' => null,
    'ariaLabel' => null,
    'closable' => true,
    'closeLabel' => 'Close',
    'defaultOpen' => false,
])

{{-- closable maps React's onClose-provided condition to rendering the plugin-owned close control. --}}
@php
    $hasTitle = $title !== null;
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
    $titleId = $hasTitle ? 'lyra-bottom-sheet-title-'.uniqid() : null;
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $panelAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
@endphp

<div
    class="lyra-bottomsheet-overlay"
    x-data="lyraBottomSheet({ defaultOpen: {!! $defaultOpenLiteral !!} })"
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
        @if ($hasTitle)
            aria-labelledby="{{ $titleId }}"
        @else
            aria-label="{{ $ariaLabel }}"
        @endif
        tabindex="-1"
        x-bind="panel"
        {{ $panelAttributes->class('lyra-bottomsheet') }}
    >
        @if ($hasTitle || $closable)
            <div class="lyra-bottomsheet__header">
                @if ($hasTitle)
                    <h2 id="{{ $titleId }}" class="lyra-bottomsheet__title">{{ $title }}</h2>
                @endif
                @if ($closable)
                    <button
                        type="button"
                        class="lyra-bottomsheet__close"
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
        @endif
        <div class="lyra-bottomsheet__body">{{ $slot }}</div>
    </div>
</div>
