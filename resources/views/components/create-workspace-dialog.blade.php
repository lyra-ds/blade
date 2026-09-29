@props([
    'title' => 'Create workspace',
    'slugPrefix' => 'lyra.dev/',
    'defaultOpen' => false,
    'closeOnEsc' => true,
    'closeOnOverlayClick' => true,
    'returnFocusTo' => null,
    'closeLabel' => 'Close',
    'nameLabel' => 'Workspace name',
    'namePlaceholder' => 'Acme Inc',
    'slugLabel' => 'URL',
    'slugPlaceholder' => 'acme-inc',
    'slugHint' => 'Lowercase letters, numbers, and hyphens.',
    'previewHint' => 'The avatar uses the name initials.',
    'errorLabel' => 'Workspace creation error',
    'cancelLabel' => 'Cancel',
    'createLabel' => 'Create workspace',
    'messages' => [],
])

{{-- The Alpine binding composes lyraDialog and owns this root. Consumer persistence belongs on a parent scope or event listener. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'create-workspace-dialog', ['x-modelable', 'x-bind']);
    $rootAttributes = $attributes->whereStartsWith(['wire:model', 'x-model', 'x-on:lyra:create-workspace', '@lyra:create-workspace']);
    $panelAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model', 'x-on:lyra:create-workspace', '@lyra:create-workspace']);
    $id = 'lyra-wscreate-'.uniqid();
    $formId = $id.'-form';
    $nameId = $id.'-name';
    $slugId = $id.'-slug';
    $titleId = $id.'-title';
    $option = [
        'defaultOpen: '.($defaultOpen ? 'true' : 'false'),
        'closeOnEsc: '.($closeOnEsc ? 'true' : 'false'),
        'closeOnOverlayClick: '.($closeOnOverlayClick ? 'true' : 'false'),
        'slugPrefix: '.\Illuminate\Support\Js::from($slugPrefix),
        'labelId: '.\Illuminate\Support\Js::from($titleId),
        'messages: '.\Illuminate\Support\Js::from(array_merge([
            'nameRequired' => 'Enter a workspace name.',
            'slugRequired' => 'Enter a workspace URL.',
            'createFailed' => 'We could not create the workspace. Please try again.',
        ], is_array($messages) ? $messages : [])),
    ];
    $returnFocusResolver = \LyraDs\Blade\FocusResolver::selector($returnFocusTo);
    if ($returnFocusResolver !== null) {
        $option[] = 'returnFocusTo: '.$returnFocusResolver;
    }
    $closeLabelBinding = \Illuminate\Support\Js::from($closeLabel);
@endphp

<div
    id="{{ $id }}"
    class="lyra-dialog-overlay"
    x-data="lyraCreateWorkspaceDialog({{ '{ '.implode(', ', $option).' }' }})"
    x-modelable="open"
    x-bind="overlay"
    @if (! $defaultOpen) x-cloak @endif
    {{ $rootAttributes }}
>
    <div
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $titleId }}"
        tabindex="-1"
        x-bind="panel"
        {{ $panelAttributes->class('lyra-dialog') }}
    >
        <div class="lyra-dialog__header">
            <h2 id="{{ $titleId }}" class="lyra-dialog__title" x-bind="title">{{ $title }}</h2>
            <button type="button" class="lyra-dialog__close" aria-label="{{ $closeLabel }}" x-bind="close" :aria-label="{{ $closeLabelBinding }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="lyra-dialog__body">
            <form id="{{ $formId }}" class="lyra-wscreate" aria-label="{{ $title }}" x-bind="form">
                <div class="lyra-wscreate__preview">
                    <span class="lyra-avatar lyra-avatar--lg lyra-avatar--square"><span aria-hidden="true" x-bind="avatar">?</span></span>
                    <span class="lyra-wscreate__preview-hint">{{ $previewHint }}</span>
                </div>
                <div class="lyra-hint lyra-hint--error" data-lyra-wscreate-error aria-label="{{ $errorLabel }}" x-bind="errorSummary" style="display: none"></div>
                <div class="lyra-field">
                    <label class="lyra-label" for="{{ $nameId }}">{{ $nameLabel }}</label>
                    <input id="{{ $nameId }}" class="lyra-input" placeholder="{{ $namePlaceholder }}" data-lyra-wscreate-name x-bind="nameInput" :aria-describedby="fieldErrors.name ? '{{ $nameId }}-error' : null" />
                    <span id="{{ $nameId }}-error" class="lyra-hint lyra-hint--error" x-show="fieldErrors.name" x-text="fieldErrors.name" style="display: none"></span>
                </div>
                <div class="lyra-field">
                    <label class="lyra-label" for="{{ $slugId }}">{{ $slugLabel }}</label>
                    <span class="lyra-wscreate__slug">
                        <span class="lyra-wscreate__slug-prefix" x-bind="slugPrefixBinding">{{ $slugPrefix }}</span>
                        <input id="{{ $slugId }}" class="lyra-wscreate__slug-input" placeholder="{{ $slugPlaceholder }}" data-lyra-wscreate-slug x-bind="slugInput" :aria-describedby="fieldErrors.slug ? '{{ $slugId }}-error' : null" />
                    </span>
                    <span id="{{ $slugId }}-error" class="lyra-hint lyra-hint--error" x-show="fieldErrors.slug" x-text="fieldErrors.slug" style="display: none"></span>
                    <span class="lyra-hint" x-show="!fieldErrors.slug">{{ $slugHint }}</span>
                </div>
            </form>
        </div>
        <div class="lyra-dialog__footer">
            <button class="lyra-btn lyra-btn--ghost lyra-btn--md" x-bind="cancelButton">{{ $cancelLabel }}</button>
            <button class="lyra-btn lyra-btn--primary lyra-btn--md" form="{{ $formId }}" x-bind="createButton">
                <span class="lyra-btn__spinner" aria-hidden="true" x-show="pending" style="display: none"></span>{{ $createLabel }}
            </button>
        </div>
    </div>
</div>
