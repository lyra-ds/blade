@props([
    'label' => null,
    'hint' => null,
    'error' => null,
])

@php
    $hasLabel = (bool) $label;
    $hasError = (bool) $error;
    $hasHint = ! $hasError && (bool) $hint;
    $hasMessage = $hasError || $hasHint;
    $hasField = $hasLabel || $hasMessage;
    $textareaId = $attributes->get('id') ?? 'lyra-textarea-'.uniqid();
    $messageId = 'lyra-textarea-message-'.uniqid();
    $consumerDescribedBy = $attributes->get('aria-describedby');
    $describedBy = $hasMessage
        ? trim(implode(' ', array_filter([$consumerDescribedBy, $messageId])))
        : $consumerDescribedBy;
    $ariaInvalid = $hasError ? 'true' : $attributes->get('aria-invalid');
    $textareaAttributes = $attributes
        ->except(['id', 'aria-describedby', 'aria-invalid'])
        ->class([
            'lyra-input',
            'lyra-textarea',
            'lyra-input--error' => $hasError,
        ])
        ->merge(array_filter([
            'id' => $textareaId,
            'aria-invalid' => $ariaInvalid,
            'aria-describedby' => $describedBy,
        ], fn (mixed $value): bool => $value !== null));
@endphp

@if ($hasField)
<div class="lyra-field">
    @if ($hasLabel)
    <label class="lyra-label" for="{{ $textareaId }}">{{ $label }}</label>
    @endif
@endif
    <textarea {{ $textareaAttributes }}>{{ $slot }}</textarea>
@if ($hasField)
    @if ($hasError)
    <span id="{{ $messageId }}" class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
    <span id="{{ $messageId }}" class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
@endif
