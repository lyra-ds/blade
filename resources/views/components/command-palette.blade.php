{{--
    Blade treats an explicit null hotkey like an omitted prop, so both use the public default "k".
    Consumers can disable the shortcut by passing false or an empty string instead.
--}}
@props([
    'groups' => [],
    'defaultOpen' => false,
    'placeholder' => null,
    'emptyMessage' => null,
    'searchLabel' => null,
    'hints' => [],
    'hotkey' => 'k',
    'inline' => false,
    'label' => null,
])

{{--
    APG keyboard, focus, modal presence, hotkey, and axe coverage live in the upstream
    lyraCommandPalette browser suite. This server-render suite verifies the complete ARIA
    scaffold and binding hooks instead of pretending to execute JavaScript.

    Groups and items intentionally render through nested x-for templates: filtering, flat active
    indexes, and group omission belong to the data-driven binding. The searchIcon slot renders
    before the search input. The itemIcon slot renders inside the item template, where Alpine's
    entry and group variables are available to consumer markup.

    React exposes CommandPalette.Trigger as a separate optional component; CommandPalette itself
    does not serve a trigger. Consumers likewise place ordinary trigger markup outside this
    component and control it through x-model. React callbacks are represented by the binding's
    lyra:select event, so they are not PHP props; React also exposes no disabled prop.

    The class attribute belongs to the .lyra-cmdk panel in both modes, matching React's className.
    The unstyled binding root remains necessary in inline mode so the binding can find and focus
    its descendant panel; inline deliberately omits the overlay and modelable modal state.
--}}
@php
    $resolvedGroups = [];

    if (is_array($groups)) {
        foreach ($groups as $group) {
            if (
                ! is_array($group)
                || ! array_key_exists('items', $group)
                || ! is_array($group['items'])
                || (array_key_exists('label', $group) && ! is_string($group['label']))
            ) {
                continue;
            }

            $resolvedGroup = [];

            if (array_key_exists('label', $group)) {
                $resolvedGroup['label'] = $group['label'];
            }

            $resolvedItems = [];

            foreach ($group['items'] as $item) {
                if (
                    ! is_array($item)
                    || ! array_key_exists('id', $item)
                    || ! is_string($item['id'])
                    || ! array_key_exists('label', $item)
                    || ! is_string($item['label'])
                ) {
                    continue;
                }

                $resolvedItem = [
                    'id' => $item['id'],
                    'label' => $item['label'],
                ];

                foreach (['hint', 'shortcut'] as $optionalKey) {
                    if (array_key_exists($optionalKey, $item) && is_string($item[$optionalKey])) {
                        $resolvedItem[$optionalKey] = $item[$optionalKey];
                    }
                }

                $resolvedItems[] = $resolvedItem;
            }

            $resolvedGroup['items'] = $resolvedItems;
            $resolvedGroups[] = $resolvedGroup;
        }
    }

    $resolvedHints = [];

    if (is_array($hints)) {
        foreach (['navigate', 'select', 'close'] as $hintKey) {
            if (array_key_exists($hintKey, $hints) && is_string($hints[$hintKey])) {
                $resolvedHints[$hintKey] = $hints[$hintKey];
            }
        }
    }

    $resolvedOpen = (bool) $defaultOpen;
    $resolvedInline = (bool) $inline;
    $bindingOptions = [
        'groups' => $resolvedGroups,
        'open' => $resolvedOpen,
    ];

    if (is_string($placeholder)) {
        $bindingOptions['placeholder'] = $placeholder;
    }

    if (is_string($emptyMessage)) {
        $bindingOptions['emptyMessage'] = $emptyMessage;
    }

    if (is_string($searchLabel)) {
        $bindingOptions['searchLabel'] = $searchLabel;
    }

    if ($resolvedHints !== []) {
        $bindingOptions['hints'] = $resolvedHints;
    }

    $bindingOptions['hotkey'] = is_string($hotkey) && $hotkey !== '' ? $hotkey : false;
    $bindingOptions['inline'] = $resolvedInline;

    if (is_string($label)) {
        $bindingOptions['label'] = $label;
    }

    $optionsLiteral = json_encode(
        $bindingOptions,
        JSON_THROW_ON_ERROR
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE,
    );
    $modelAttributes = $attributes
        ->whereStartsWith(['wire:model', 'x-model'])
        ->except(['x-modelable']);
    $panelAttributes = $attributes
        ->whereDoesntStartWith(['wire:model', 'x-model'])
        ->except(['x-data', 'x-modelable']);
@endphp

<div
    x-data="{{ 'lyraCommandPalette('.$optionsLiteral.')' }}"
    @if (! $resolvedInline)
        x-modelable="open"
        {{ $modelAttributes }}
    @endif
>
    @if (! $resolvedInline)
        <div
            class="lyra-cmdk-overlay"
            x-bind="overlay"
            @if (! $resolvedOpen)
                x-cloak
            @endif
        >
    @endif

        <div
            x-bind="panel"
            {{ $panelAttributes->class('lyra-cmdk') }}
        >
            <div class="lyra-cmdk__search">
                @isset($searchIcon)
                    {{ $searchIcon }}
                @endisset
                <input x-bind="search">
                <kbd class="lyra-kbd">esc</kbd>
            </div>
            <div class="lyra-cmdk__body" x-bind="list">
                <p
                    class="lyra-cmdk__empty"
                    x-bind="empty"
                    x-text="emptyText()"
                ></p>
                <template x-for="group in visibleGroups()" :key="group.index">
                    <div
                        class="lyra-cmdk__group"
                        role="group"
                        :aria-labelledby="groupLabelledby(group)"
                    >
                        <template x-if="group.label">
                            <span
                                class="lyra-cmdk__group-label"
                                :id="groupLabelId(group)"
                                x-text="group.label"
                            ></span>
                        </template>
                        <template x-for="entry in group.items" :key="entry.item.id">
                            <button
                                class="lyra-cmdk__item"
                                x-bind="item(entry.index)"
                                :id="optionId(entry.index)"
                                :class="itemClass(entry.index)"
                                :aria-selected="isActive(entry.index) ? 'true' : 'false'"
                                x-on:mouseenter="setActive(entry.index)"
                                x-on:click="pick(entry.item)"
                            >
                                @isset($itemIcon)
                                    <span class="lyra-cmdk__item-icon">{{ $itemIcon }}</span>
                                @endisset
                                <span
                                    class="lyra-cmdk__item-label"
                                    x-text="entry.item.label"
                                ></span>
                                <span
                                    class="lyra-cmdk__item-hint"
                                    x-show="entry.item.hint"
                                    x-text="entry.item.hint"
                                ></span>
                                <span
                                    class="lyra-cmdk__shortcut"
                                    x-show="entry.item.shortcut"
                                >
                                    <template x-for="(key, keyIndex) in (entry.item.shortcut || '').split(' ')" :key="keyIndex">
                                        <kbd class="lyra-kbd" x-text="key"></kbd>
                                    </template>
                                </span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
            <div class="lyra-cmdk__footer">
                <span>
                    <kbd class="lyra-kbd">↑</kbd><kbd class="lyra-kbd">↓</kbd>
                    <span x-text="hints.navigate"></span>
                </span>
                <span>
                    <kbd class="lyra-kbd">↵</kbd>
                    <span x-text="hints.select"></span>
                </span>
                <span>
                    <kbd class="lyra-kbd">esc</kbd>
                    <span x-text="hints.close"></span>
                </span>
            </div>
        </div>

    @if (! $resolvedInline)
        </div>
    @endif
</div>
