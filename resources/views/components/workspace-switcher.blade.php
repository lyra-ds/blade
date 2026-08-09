@props([
    'workspaces' => [],
    'current' => null,
    'create' => false,
    'createLabel' => 'Create workspace',
    'createId' => 'create',
    'defaultOpen' => false,
])

{{--
    Selection dispatches lyra:change with the served data-id. The create action deliberately reuses
    that event and the option binding; consumers distinguish it with create-id (default: "create").
--}}
@php
    $workspaces = array_values($workspaces);
    $selected = null;

    foreach ($workspaces as $workspace) {
        if ($workspace['id'] === $current) {
            $selected = $workspace;
            break;
        }
    }

    $selected ??= $workspaces[0] ?? null;
    $rootId = $attributes->get('id') ?? 'lyra-wssw-'.uniqid();
    $listboxId = $rootId.'-listbox';
    $labelId = $listboxId.'-label';
    $defaultOpenLiteral = $defaultOpen ? 'true' : 'false';
@endphp

<div
    id="{{ $rootId }}"
    x-data="lyraWorkspaceSwitcher({ defaultOpen: {!! $defaultOpenLiteral !!} })"
    x-modelable="open"
    {{ $attributes->except('id')->class('lyra-wssw') }}
>
    <button
        type="button"
        class="lyra-wssw__trigger"
        aria-haspopup="listbox"
        aria-expanded="{{ $defaultOpen ? 'true' : 'false' }}"
        aria-controls="{{ $listboxId }}"
        x-bind="trigger"
    >
        <x-lyra::avatar :name="$selected['name'] ?? '?'" size="sm" shape="square" />
        <span class="lyra-wssw__id">
            <span class="lyra-wssw__name">{{ $selected['name'] ?? 'Select workspace' }}</span>
            @if (isset($selected['plan']) && $selected['plan'] !== '')
                <span class="lyra-wssw__plan">{{ $selected['plan'] }}</span>
            @endif
        </span>
        <x-lyra::icon name="chevrons-up-down" :size="15" color="var(--text-faint)" />
    </button>
    <div
        id="{{ $listboxId }}"
        class="lyra-wssw__pop"
        role="listbox"
        x-bind="popover"
        aria-labelledby="{{ $labelId }}"
        @if (! $defaultOpen)
            x-cloak
        @endif
    >
        <span id="{{ $labelId }}" class="lyra-wssw__pop-label">Workspaces</span>
        @foreach ($workspaces as $workspace)
            @php
                $isSelected = $workspace['id'] === ($selected['id'] ?? null);
                $hasPlan = isset($workspace['plan']) && $workspace['plan'] !== '';
                $hasMembers = array_key_exists('members', $workspace);
                $metadata = [];

                if ($hasPlan) {
                    $metadata[] = $workspace['plan'];
                }

                if ($hasMembers) {
                    $metadata[] = $workspace['members'].' members';
                }
            @endphp
            <button
                type="button"
                role="option"
                aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                class="lyra-wssw__item"
                x-bind="option"
                data-id="{{ $workspace['id'] }}"
            >
                <x-lyra::avatar :name="$workspace['name']" size="sm" shape="square" />
                <span class="lyra-wssw__id">
                    <span class="lyra-wssw__name">{{ $workspace['name'] }}</span>
                    @if ($hasPlan || $hasMembers)
                        <span class="lyra-wssw__meta">{{ implode(' · ', $metadata) }}</span>
                    @endif
                </span>
                @if ($isSelected)
                    <x-lyra::icon name="check" :size="15" color="var(--accent)" />
                @endif
            </button>
        @endforeach
        @if ($create)
            <hr class="lyra-wssw__sep" role="presentation">
            <button
                type="button"
                role="option"
                aria-selected="false"
                class="lyra-wssw__item lyra-wssw__create"
                x-bind="option"
                data-id="{{ $createId }}"
            >
                <span class="lyra-wssw__plus"><x-lyra::icon name="plus" :size="15" /></span>
                <span class="lyra-wssw__create-label">{{ $createLabel }}</span>
            </button>
        @endif
    </div>
</div>
