@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'length' => 6,
    'name' => null,
    'value' => null,
    'defaultValue' => null,
    'invalid' => false,
    'digitLabel' => 'Digit',
    'disabled' => false,
])

@php
    $count = max(1, (int) $length);
    $validationError = $name && isset($errors) && $errors->has($name)
        ? $errors->first($name)
        : null;
    $message = $error ?: $validationError;
    $isInvalid = (bool) ($message || $invalid);
    $hasHint = ! $message && (bool) $hint;
    $selected = $name && function_exists('old')
        ? old($name, $value ?? $defaultValue ?? '')
        : ($value ?? $defaultValue ?? '');
    $selected = substr(preg_replace('/[^0-9]/', '', (string) $selected), 0, $count);
    $groupId = $attributes->get('id') ?? 'lyra-otp-'.uniqid();
    $labelId = $groupId.'-label';
    $messageId = $groupId.'-message';
    $consumerDescribedBy = $attributes->get('aria-describedby');
    $describedBy = trim(implode(' ', array_filter([
        $consumerDescribedBy,
        ($message || $hasHint) ? $messageId : null,
    ])));
    $options = [
        'length' => $count,
        'defaultValue' => $selected,
        'invalid' => $isInvalid,
        'digitLabel' => (string) $digitLabel,
    ];
    $optionsLiteral = json_encode($options, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $groupAttributes = $attributes
        ->whereDoesntStartWith(['wire:model', 'x-model'])
        ->except(['id', 'aria-label', 'aria-labelledby', 'aria-describedby', 'role', 'name'])
        ->class('lyra-otp');
@endphp

<div class="lyra-field" x-data="lyraOtpInput({{ $optionsLiteral }})" x-modelable="code" {{ $modelAttributes }}>
    @if ($label)
    <span id="{{ $labelId }}" class="lyra-label">{{ $label }}</span>
    @endif
    <div
        {{ $groupAttributes }}
        id="{{ $groupId }}"
        role="group"
        @if ($label)
            aria-labelledby="{{ $labelId }}"
        @elseif ($attributes->has('aria-label'))
            aria-label="{{ $attributes->get('aria-label') }}"
        @endif
        @if ($describedBy !== '')
            aria-describedby="{{ $describedBy }}"
        @endif
    >
        <template x-for="index in positions" :key="index">
            <input
                type="text"
                @class(['lyra-input', 'lyra-otp__digit', 'lyra-input--error' => $isInvalid])
                :data-index="index"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="{{ $count }}"
                :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                @if ($isInvalid)
                    aria-invalid="true"
                @endif
                @if ($message)
                    aria-describedby="{{ $messageId }}"
                @endif
                @if ($disabled)
                    disabled
                @endif
                x-bind="digit"
            >
        </template>
    </div>
    @if ($name)
    <input type="hidden" name="{{ $name }}" value="{{ $selected }}" x-bind:value="code" @disabled($disabled)>
    @endif
    @if ($message)
    <span id="{{ $messageId }}" class="lyra-hint lyra-hint--error">{{ $message }}</span>
    @elseif ($hasHint)
    <span id="{{ $messageId }}" class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
