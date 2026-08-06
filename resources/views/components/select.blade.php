@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'size' => 'md',
])

@php
    $hasLabel = (bool) $label;
    $hasError = (bool) $error;
    $hasHint = ! $hasError && (bool) $hint;
    $hasMessage = $hasError || $hasHint;
    $hasField = $hasLabel || $hasMessage;
    $selectId = $attributes->get('id') ?? 'lyra-select-'.uniqid();
    $messageId = 'lyra-select-message-'.uniqid();
    $consumerDescribedBy = $attributes->get('aria-describedby');
    $describedBy = $hasMessage
        ? trim(implode(' ', array_filter([$consumerDescribedBy, $messageId])))
        : $consumerDescribedBy;
    $ariaInvalid = $hasError ? 'true' : $attributes->get('aria-invalid');
    $selectAttributes = $attributes
        ->except(['id', 'aria-describedby', 'aria-invalid'])
        ->class([
            'lyra-input',
            'lyra-input--sm' => $size === 'sm',
            'lyra-input--lg' => $size === 'lg',
            'lyra-input--error' => $hasError,
        ])
        ->merge(array_filter([
            'id' => $selectId,
            'aria-invalid' => $ariaInvalid,
            'aria-describedby' => $describedBy,
        ], fn (mixed $value): bool => $value !== null));
@endphp

@if ($hasField)
<div class="lyra-field">
    @if ($hasLabel)
    <label class="lyra-label" for="{{ $selectId }}">{{ $label }}</label>
    @endif
@endif
    <span class="lyra-select-wrap">
        <select {{ $selectAttributes }}>{{ $slot }}</select>
    </span>
@if ($hasField)
    @if ($hasError)
    <span id="{{ $messageId }}" class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
    <span id="{{ $messageId }}" class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
@endif
