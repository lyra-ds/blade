@props([
    'label' => 'Drag files here or click to select',
    'hint' => null,
    'accept' => null,
    'maxSizeMB' => null,
    'multiple' => true,
    'uploadDuration' => 1800,
    'defaultItems' => [],
    'doneLabel' => 'Upload complete',
    'removeLabel' => 'Remove',
])

{{-- Upload rows are the documented runtime-rendered exception: Alpine stamps this served x-for. --}}
@php
    $generatedHint = implode(' · ', array_filter([
        $accept,
        $maxSizeMB !== null ? 'Up to '.$maxSizeMB.' MB per file' : null,
    ], static fn (mixed $part): bool => $part !== null && $part !== ''));
    $resolvedHint = $hint ?: $generatedHint;
    $options = [];

    if ($maxSizeMB !== null) {
        $options[] = 'maxSizeMB: '.json_encode($maxSizeMB, JSON_THROW_ON_ERROR);
    }

    $options[] = 'multiple: '.($multiple ? 'true' : 'false');
    $options[] = 'uploadDuration: '.(int) $uploadDuration;
    $options[] = 'defaultItems: '.json_encode(
        array_values($defaultItems),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
    $optionsLiteral = implode(', ', $options);
    $escapedRemoveLabel = str_replace(
        ['\\', "'", "\r", "\n"],
        ['\\\\', "\\'", '\\r', '\\n'],
        (string) $removeLabel,
    );
    $escapedRemoveLabel = htmlspecialchars($escapedRemoveLabel, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
@endphp

<div
    x-data="lyraFileUpload({{ '{ '.$optionsLiteral.' }' }})"
    x-modelable="items"
    {{ $attributes->class('lyra-upload') }}
>
    <button
        type="button"
        class="lyra-upload__zone"
        x-bind="zone"
    >
        <span class="lyra-upload__zone-icon" aria-hidden="true">
            <x-lyra::icon name="cloud-upload" :size="22" />
        </span>
        <span class="lyra-upload__zone-label">{{ $label }}</span>
        @if ($resolvedHint !== null && $resolvedHint !== '')
            <span class="lyra-upload__zone-hint">{{ $resolvedHint }}</span>
        @endif
        <input
            type="file"
            @if ($accept !== null)
                accept="{{ $accept }}"
            @endif
            @if ($multiple)
                multiple
            @endif
            hidden
            tabindex="-1"
            x-bind="input"
        >
    </button>

    <template x-if="items.length > 0">
        <ul class="lyra-upload__list">
            <template x-for="item in items" :key="item.id">
                <li
                    class="lyra-upload__item"
                    x-bind:class="{ 'lyra-upload__item--error': item.status === 'error' }"
                >
                    <span class="lyra-upload__item-icon" aria-hidden="true">
                        <template x-if="item.status === 'error'">
                            <x-lyra::icon name="circle-alert" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'image'">
                            <x-lyra::icon name="image" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'file-text'">
                            <x-lyra::icon name="file-text" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'file-spreadsheet'">
                            <x-lyra::icon name="file-spreadsheet" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'file-archive'">
                            <x-lyra::icon name="file-archive" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'film'">
                            <x-lyra::icon name="film" :size="17" />
                        </template>
                        <template x-if="item.status !== 'error' && iconFor(item.name) === 'file'">
                            <x-lyra::icon name="file" :size="17" />
                        </template>
                    </span>
                    <span class="lyra-upload__item-body">
                        <span class="lyra-upload__item-row">
                            <span class="lyra-upload__item-name" x-text="item.name"></span>
                            <span
                                class="lyra-upload__item-meta"
                                x-text="item.status === 'error' ? item.error : item.status === 'done' ? formatBytes(item.size) : `${Math.round(item.progress)}%`"
                            ></span>
                        </span>
                        <template x-if="item.status === 'uploading'">
                            <span class="lyra-upload__bar">
                                <span
                                    class="lyra-upload__bar-fill"
                                    x-bind:style="`width: ${item.progress}%`"
                                ></span>
                            </span>
                        </template>
                    </span>
                    <template x-if="item.status === 'done'">
                        <span class="lyra-upload__check" role="img" aria-label="{{ $doneLabel }}">
                            <x-lyra::icon name="circle-check" :size="17" />
                        </span>
                    </template>
                    <button
                        type="button"
                        class="lyra-upload__remove"
                        x-bind:aria-label="'{!! $escapedRemoveLabel !!} ' + item.name"
                        x-on:click="remove(item.id)"
                    >
                        <x-lyra::icon name="x" :size="15" />
                    </button>
                </li>
            </template>
        </ul>
    </template>
</div>
