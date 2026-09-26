@props([
    'id' => null,
    'name' => null,
    'label' => 'Drag files here or click to select',
    'hint' => null,
    'accept' => null,
    'maxSizeMB' => null,
    'multiple' => true,
    'disabled' => false,
    'required' => false,
    'items' => [],
    'messages' => [],
    'statusLabels' => [],
    'cancelLabel' => 'Cancel',
    'retryLabel' => 'Retry',
])

{{-- Upload rows are the documented runtime-rendered exception: Alpine stamps this served x-for. --}}
{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'file-upload', ['x-modelable']);
    $rootId = $id ?? $attributes->get('id') ?? 'lyra-upload-'.uniqid();
    $inputId = $rootId.'-input';
    $generatedHint = implode(' · ', array_filter([
        $accept,
        $maxSizeMB !== null ? 'Up to '.$maxSizeMB.' MB per file' : null,
    ], static fn (mixed $part): bool => $part !== null && $part !== ''));
    $resolvedHint = $hint ?: $generatedHint;
    $encode = static fn (mixed $value): string => json_encode(
        $value,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
    $options = [];

    if ($name !== null && $name !== '') {
        $options[] = 'name: '.$encode((string) $name);
    }

    if ($accept !== null && $accept !== '') {
        $options[] = 'accept: '.$encode((string) $accept);
    }

    if ($maxSizeMB !== null) {
        $options[] = 'maxSizeMB: '.$encode($maxSizeMB);
    }

    $options[] = 'multiple: '.($multiple ? 'true' : 'false');

    if ($disabled) {
        $options[] = 'disabled: true';
    }

    if ($required) {
        $options[] = 'required: true';
    }

    if ($messages !== []) {
        $options[] = 'messages: '.$encode($messages);
    }

    $options[] = 'items: '.$encode(array_values($items));
    $optionsLiteral = implode(', ', $options);
    $statusText = array_merge([
        'selected' => 'Selected',
        'uploading' => 'Uploading',
        'canceling' => 'Canceling',
        'success' => 'Complete',
        'canceled' => 'Canceled',
        'error' => 'Failed',
    ], $statusLabels);
    // Alpine 1.x no longer ships an icon helper; the extension map lives in the served markup.
    $iconKind = '('.$encode([
        'png' => 'image', 'jpg' => 'image', 'jpeg' => 'image', 'gif' => 'image',
        'webp' => 'image', 'svg' => 'image', 'avif' => 'image',
        'pdf' => 'file-text', 'doc' => 'file-text', 'docx' => 'file-text',
        'txt' => 'file-text', 'md' => 'file-text', 'rtf' => 'file-text',
        'xls' => 'file-spreadsheet', 'xlsx' => 'file-spreadsheet', 'csv' => 'file-spreadsheet',
        'zip' => 'file-archive', 'rar' => 'file-archive', '7z' => 'file-archive',
        'tar' => 'file-archive', 'gz' => 'file-archive',
        'mp4' => 'film', 'mov' => 'film', 'webm' => 'film', 'mkv' => 'film', 'avi' => 'film',
    ])."[item.name.split('.').pop().toLowerCase()] ?? 'file')";
@endphp

<div
    x-data="lyraFileUpload({{ '{ '.$optionsLiteral.' }' }})"
    x-modelable="items"
    {{ $attributes->merge(['id' => $rootId])->class('lyra-upload') }}
    data-state="idle"
    @if ($disabled)
        data-disabled
    @endif
>
    <label
        class="lyra-upload__zone"
        for="{{ $inputId }}"
        x-bind="zone"
    >
        <span class="lyra-upload__zone-icon" aria-hidden="true">
            <x-lyra::icon name="cloud-upload" :size="22" />
        </span>
        <span class="lyra-upload__zone-label">{{ $label }}</span>
        @if ($resolvedHint !== null && $resolvedHint !== '')
            <span class="lyra-upload__zone-hint">{{ $resolvedHint }}</span>
        @endif
    </label>
    <input
        id="{{ $inputId }}"
        class="lyra-upload__input"
        type="file"
        @if ($name !== null && $name !== '')
            name="{{ $name }}"
        @endif
        @if ($accept !== null)
            accept="{{ $accept }}"
        @endif
        @if ($multiple)
            multiple
        @endif
        @if ($disabled)
            disabled
        @endif
        @if ($required)
            required
        @endif
        x-bind="input"
    >

    <ul class="lyra-upload__list">
        <template x-for="item in items" :key="item.id">
            <li class="lyra-upload__item" x-bind="itemBindings(item)">
                <span class="lyra-upload__item-icon" aria-hidden="true">
                    <template x-if="item.status === 'error'">
                        <x-lyra::icon name="circle-alert" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'image'">
                        <x-lyra::icon name="image" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'file-text'">
                        <x-lyra::icon name="file-text" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'file-spreadsheet'">
                        <x-lyra::icon name="file-spreadsheet" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'file-archive'">
                        <x-lyra::icon name="file-archive" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'film'">
                        <x-lyra::icon name="film" :size="17" />
                    </template>
                    <template x-if="item.status !== 'error' && {{ $iconKind }} === 'file'">
                        <x-lyra::icon name="file" :size="17" />
                    </template>
                </span>
                <span class="lyra-upload__item-body">
                    <span class="lyra-upload__item-row">
                        <span class="lyra-upload__item-name" x-text="item.name"></span>
                        <span
                            class="lyra-upload__item-meta"
                            x-text="item.status === 'error' ? item.error.message : {{ $encode($statusText) }}[item.status]"
                        ></span>
                    </span>
                    <template x-if="item.status === 'uploading' || item.status === 'canceling'">
                        <progress
                            class="lyra-upload__bar"
                            x-bind="progressBindings(item)"
                            x-effect="item.progress.kind === 'determinate' ? $el.setAttribute('value', item.progress.value) : $el.removeAttribute('value')"
                        ></progress>
                    </template>
                </span>

                <template x-if="item.status === 'uploading'">
                    <button class="lyra-upload__cancel" x-bind="actionBindings('cancel', item)">{{ $cancelLabel }}</button>
                </template>
                <template x-if="item.status === 'canceled' || (item.status === 'error' && item.error.retryable)">
                    <button class="lyra-upload__retry" x-bind="actionBindings('retry', item)">{{ $retryLabel }}</button>
                </template>
                <template x-if="item.status === 'selected' || item.status === 'success' || item.status === 'canceled' || item.status === 'error'">
                    <button class="lyra-upload__remove" x-bind="actionBindings('remove', item)">
                        <x-lyra::icon name="x" :size="15" />
                    </button>
                </template>
            </li>
        </template>
    </ul>

    <span
        class="lyra-upload__live lyra-visually-hidden"
        aria-live="polite"
        aria-atomic="true"
        x-bind="liveRegion"
    ></span>
</div>
