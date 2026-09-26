@props([
    'workspaces' => [],
    'current' => null,
    'create' => false,
    'createLabel' => 'Create workspace',
    'createId' => 'create',
    'defaultOpen' => false,
    'labels' => [],
])

{{--
    Buttons dispatch lyra:change with the served data-id; workspaces with an href render native links
    that navigate instead. The create action deliberately reuses that event and the option binding
    (and so joins arrow-key cycling); consumers distinguish it with create-id (default: "create").
    labels: listLabel, placeholder and members (['one' => ..., 'other' => ...], ":count" replaced).
--}}
{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'workspace-switcher', ['x-modelable']);
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
    $popoverId = $rootId.'-popover';
    $labelId = $popoverId.'-label';
    $listLabel = $labels['listLabel'] ?? 'Workspaces';
    $placeholder = $labels['placeholder'] ?? 'Select workspace';
    $memberLabels = $labels['members'] ?? [];
    $formatMembers = function (int|string $count) use ($memberLabels): string {
        $template = (int) $count === 1
            ? ($memberLabels['one'] ?? ':count member')
            : ($memberLabels['other'] ?? ':count members');

        return str_replace(':count', (string) $count, $template);
    };
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
        aria-expanded="{{ $defaultOpen ? 'true' : 'false' }}"
        aria-controls="{{ $popoverId }}"
        x-bind="trigger"
    >
        <x-lyra::avatar :name="$selected['name'] ?? '?'" size="sm" shape="square" />
        <span class="lyra-wssw__id">
            <span class="lyra-wssw__name">{{ $selected['name'] ?? $placeholder }}</span>
            @if (isset($selected['plan']) && $selected['plan'] !== '')
                <span class="lyra-wssw__plan">{{ $selected['plan'] }}</span>
            @endif
        </span>
        <x-lyra::icon name="chevrons-up-down" :size="15" color="var(--text-faint)" />
    </button>
    <div
        id="{{ $popoverId }}"
        class="lyra-wssw__pop"
        role="group"
        x-bind="popover"
        aria-labelledby="{{ $labelId }}"
        @if (! $defaultOpen)
            x-cloak
        @endif
    >
        <span id="{{ $labelId }}" class="lyra-wssw__pop-label">{{ $listLabel }}</span>
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
                    $metadata[] = $formatMembers($workspace['members']);
                }
            @endphp
            @php($itemTag = isset($workspace['href']) && $workspace['href'] !== '' ? 'a' : 'button')
            <{{ $itemTag }}
                @if ($itemTag === 'a')
                    href="{{ $workspace['href'] }}"
                @else
                    type="button"
                @endif
                class="lyra-wssw__item"
                x-bind="option"
                data-id="{{ $workspace['id'] }}"
                @if ($isSelected)
                    aria-current="true"
                @endif
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
            </{{ $itemTag }}>
        @endforeach
        @if ($create)
            <hr class="lyra-wssw__sep" role="presentation">
            <button
                type="button"
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
