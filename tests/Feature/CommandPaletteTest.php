<?php

use Illuminate\Support\Facades\Blade;

function commandPaletteIconSlots(): string
{
    return <<<'BLADE'
        <x-slot:searchIcon><svg data-slot="search-icon" x-bind:data-query="query"></svg></x-slot:searchIcon>
        <x-slot:itemIcon><svg data-slot="item-icon" x-bind:data-item="entry.item.id" x-bind:data-group="group.index"></svg></x-slot:itemIcon>
    BLADE;
}

function renderCommandPalette(array $props = [], string $slots = ''): string
{
    $groups = $props['groups'] ?? [];
    $defaultOpen = $props['defaultOpen'] ?? false;
    $placeholder = $props['placeholder'] ?? null;
    $emptyMessage = $props['emptyMessage'] ?? null;
    $searchLabel = $props['searchLabel'] ?? null;
    $hints = $props['hints'] ?? [];
    $hotkey = array_key_exists('hotkey', $props) ? $props['hotkey'] : 'k';
    $inline = $props['inline'] ?? false;
    $label = $props['label'] ?? null;
    unset(
        $props['groups'],
        $props['defaultOpen'],
        $props['placeholder'],
        $props['emptyMessage'],
        $props['searchLabel'],
        $props['hints'],
        $props['hotkey'],
        $props['inline'],
        $props['label'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $attributeValue, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $attributeValue, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::command-palette :groups="$groups" :default-open="$defaultOpen" :placeholder="$placeholder" :empty-message="$emptyMessage" :search-label="$searchLabel" :hints="$hints" :hotkey="$hotkey" :inline="$inline" :label="$label" %s>%s</x-lyra::command-palette>',
            $attributes,
            $slots,
        ),
        compact(
            'groups',
            'defaultOpen',
            'placeholder',
            'emptyMessage',
            'searchLabel',
            'hints',
            'hotkey',
            'inline',
            'label',
        ),
    );
}

function commandPaletteOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-data="lyraCommandPalette\()[^>]*>/',
        'overlay' => '/<div\b(?=[^>]*\bclass="lyra-cmdk-overlay")[^>]*>/',
        'panel' => '/<div\b(?=[^>]*\bclass="lyra-cmdk(?: [^"]*)?")[^>]*>/',
        'search' => '/<div\b(?=[^>]*\bclass="lyra-cmdk__search")[^>]*>/',
        'search-input' => '/<input\b(?=[^>]*\bx-bind="search")[^>]*>/',
        'body' => '/<div\b(?=[^>]*\bclass="lyra-cmdk__body")[^>]*>/',
        'empty' => '/<p\b(?=[^>]*\bclass="lyra-cmdk__empty")[^>]*>/',
        'group' => '/<div\b(?=[^>]*\bclass="lyra-cmdk__group")[^>]*>/',
        'group-label' => '/<span\b(?=[^>]*\bclass="lyra-cmdk__group-label")[^>]*>/',
        'item' => '/<button\b(?=[^>]*\bclass="lyra-cmdk__item")[^>]*>/',
        'item-icon' => '/<span\b(?=[^>]*\bclass="lyra-cmdk__item-icon")[^>]*>/',
        'item-label' => '/<span\b(?=[^>]*\bclass="lyra-cmdk__item-label")[^>]*>/',
        'item-hint' => '/<span\b(?=[^>]*\bclass="lyra-cmdk__item-hint")[^>]*>/',
        'shortcut' => '/<span\b(?=[^>]*\bclass="lyra-cmdk__shortcut")[^>]*>/',
        'item-kbd' => '/<kbd\b(?=[^>]*\bclass="lyra-kbd")(?=[^>]*\bx-text="key")[^>]*>/',
        'kbd' => '/<kbd\b(?=[^>]*\bclass="lyra-kbd")[^>]*>/',
        'footer' => '/<div\b(?=[^>]*\bclass="lyra-cmdk__footer")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function commandPaletteAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/(?:^|\s)%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1
        ? html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : null;
}

function commandPaletteClass(string $html, string $target): string
{
    return commandPaletteAttribute(commandPaletteOpeningTag($html, $target), 'class') ?? '';
}

/** @return array<string, mixed> */
function commandPaletteOptions(string $html): array
{
    $expression = commandPaletteAttribute(commandPaletteOpeningTag($html, 'root'), 'x-data');

    expect($expression)->not->toBeNull()
        ->and($expression)->toStartWith('lyraCommandPalette(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraCommandPalette('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

dataset('command palette class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/command-palette.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the command-palette class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact static React class strings without duplicating runtime modifiers', function (array $case): void {
    $html = renderCommandPalette($case['props'], commandPaletteIconSlots());
    $tag = commandPaletteOpeningTag($html, $case['target']);
    preg_match_all('/(?:^|\s)class="/', $tag, $classAttributes);

    expect(commandPaletteClass($html, $case['target']))->toBe($case['expected_class'])
        ->and($classAttributes[0])->toHaveCount(1)
        ->and($html)->not->toContain('class="lyra-cmdk-overlay lyra-cmdk-overlay--closing"')
        ->and($html)->not->toContain('class="lyra-cmdk lyra-cmdk--closing"')
        ->and($html)->not->toContain('class="lyra-cmdk__item lyra-cmdk__item--active"');
})->with('command palette class emission');

it('serializes the complete binding contract as valid JSON on exactly one modelable root', function (): void {
    $groups = [[
        'label' => 'Actions',
        'items' => [[
            'id' => 'new',
            'label' => 'New file',
            'hint' => 'Create a document',
            'shortcut' => '⌘ N',
        ]],
    ]];
    $html = renderCommandPalette([
        'groups' => $groups,
        'defaultOpen' => true,
        'placeholder' => 'Type a command',
        'emptyMessage' => 'Nothing for',
        'searchLabel' => 'Search the command menu',
        'hints' => [
            'navigate' => 'navigate commands',
            'select' => 'choose command',
            'close' => 'dismiss',
        ],
        'hotkey' => 'p',
        'inline' => false,
        'label' => 'Workspace commands',
    ]);
    $root = commandPaletteOpeningTag($html, 'root');

    expect(commandPaletteOptions($html))->toBe([
        'groups' => $groups,
        'open' => true,
        'placeholder' => 'Type a command',
        'emptyMessage' => 'Nothing for',
        'searchLabel' => 'Search the command menu',
        'hints' => [
            'navigate' => 'navigate commands',
            'select' => 'choose command',
            'close' => 'dismiss',
        ],
        'hotkey' => 'p',
        'inline' => false,
        'label' => 'Workspace commands',
    ])->and(substr_count($html, 'x-data='))->toBe(1)
        ->and($root)->toContain('x-modelable="open"');
});

it('leaves optional copy and labels to the binding defaults', function (): void {
    expect(commandPaletteOptions(renderCommandPalette()))->toBe([
        'groups' => [],
        'open' => false,
        'hotkey' => 'k',
        'inline' => false,
    ])->not->toHaveKeys([
        'placeholder',
        'emptyMessage',
        'searchLabel',
        'hints',
        'label',
        'disabled',
    ]);
});

it('guards group item and hint shapes to the exact Alpine interface', function (): void {
    $options = commandPaletteOptions(renderCommandPalette([
        'groups' => [
            [
                'label' => 'Actions',
                'invented' => 'discarded',
                'items' => [
                    [
                        'id' => 'new',
                        'label' => 'New file',
                        'hint' => 'Create a document',
                        'shortcut' => '⌘ N',
                        'icon' => '<svg></svg>',
                        'onSelect' => 'alert(1)',
                    ],
                    ['id' => 7, 'label' => 'Invalid id'],
                    ['id' => 'missing-label'],
                    'invalid item',
                ],
            ],
            ['label' => 7, 'items' => [['id' => 'settings', 'label' => 'Settings']]],
            ['label' => 'Missing items'],
            'invalid group',
        ],
        'hints' => [
            'navigate' => 'move',
            'select' => 7,
            'close' => 'dismiss',
            'invented' => 'discarded',
        ],
    ]));

    expect($options['groups'])->toBe([
        [
            'label' => 'Actions',
            'items' => [[
                'id' => 'new',
                'label' => 'New file',
                'hint' => 'Create a document',
                'shortcut' => '⌘ N',
            ]],
        ],
    ])->and($options['hints'])->toBe([
        'navigate' => 'move',
        'close' => 'dismiss',
    ]);
});

it('normalizes every falsy or invalid hotkey to false without leaking it', function (mixed $hotkey): void {
    $html = renderCommandPalette(['hotkey' => $hotkey]);

    expect(commandPaletteOptions($html)['hotkey'])->toBeFalse()
        ->and($html)->not->toContain('Array')
        ->and($html)->not->toContain('stdClass');
})->with([
    'false' => false,
    'empty string' => '',
    'zero integer' => 0,
    'array' => [['k']],
    'object' => new stdClass,
]);

it('preserves supported string hotkeys', function (string $hotkey): void {
    expect(commandPaletteOptions(renderCommandPalette(['hotkey' => $hotkey]))['hotkey'])
        ->toBe($hotkey);
})->with(['letter' => 'p', 'digit string' => '0']);

it('places model attributes on the binding root and consumer attributes on the React panel', function (): void {
    $html = renderCommandPalette([
        'class' => 'consumer elevated',
        'id' => 'workspace-commands',
        'wire:model.live' => 'commandOpen',
        'x-model.fill' => 'paletteOpen',
        'data-track' => 'command-palette',
    ]);
    $root = commandPaletteOpeningTag($html, 'root');
    $panel = commandPaletteOpeningTag($html, 'panel');
    $overlay = commandPaletteOpeningTag($html, 'overlay');

    expect(commandPaletteClass($html, 'panel'))->toBe('lyra-cmdk consumer elevated')
        ->and($root)->toContain('wire:model.live="commandOpen"')
        ->and($root)->toContain('x-model.fill="paletteOpen"')
        ->and($root)->not->toContain('class=')
        ->and($root)->not->toContain('id="workspace-commands"')
        ->and($panel)->toContain('id="workspace-commands"')
        ->and($panel)->toContain('data-track="command-palette"')
        ->and($panel)->not->toContain('wire:model')
        ->and($panel)->not->toContain('x-model')
        ->and($overlay)->not->toContain('consumer elevated');
});

it('renders the overlay panel search body empty and footer binding scaffold', function (): void {
    $html = renderCommandPalette();
    $root = commandPaletteOpeningTag($html, 'root');
    $overlay = commandPaletteOpeningTag($html, 'overlay');
    $panel = commandPaletteOpeningTag($html, 'panel');
    $searchInput = commandPaletteOpeningTag($html, 'search-input');
    $body = commandPaletteOpeningTag($html, 'body');
    $empty = commandPaletteOpeningTag($html, 'empty');

    expect($root)->toContain('x-modelable="open"')
        ->and($overlay)->toContain('class="lyra-cmdk-overlay"')
        ->and($overlay)->toContain('x-bind="overlay"')
        ->and($overlay)->toContain('x-cloak')
        ->and($panel)->toContain('class="lyra-cmdk"')
        ->and($panel)->toContain('x-bind="panel"')
        ->and(commandPaletteOpeningTag($html, 'search'))->toContain('class="lyra-cmdk__search"')
        ->and($searchInput)->toContain('x-bind="search"')
        ->and($body)->toContain('class="lyra-cmdk__body"')
        ->and($body)->toContain('x-bind="list"')
        ->and($empty)->toContain('class="lyra-cmdk__empty"')
        ->and($empty)->toContain('x-bind="empty"')
        ->and($empty)->toContain('x-text="emptyText()"')
        ->and(commandPaletteOpeningTag($html, 'footer'))->toContain('class="lyra-cmdk__footer"');
});

it('does not cloak an initially open overlay', function (): void {
    expect(commandPaletteOpeningTag(renderCommandPalette(['defaultOpen' => true]), 'overlay'))
        ->not->toContain('x-cloak');
});

it('renders canonical nested group and item templates with binding-owned item semantics', function (): void {
    $html = renderCommandPalette();
    $group = commandPaletteOpeningTag($html, 'group');
    $groupLabel = commandPaletteOpeningTag($html, 'group-label');
    $item = commandPaletteOpeningTag($html, 'item');

    expect($html)->toContain('<template x-for="group in visibleGroups()" :key="group.index">')
        ->and($group)->toContain('role="group"')
        ->and($group)->toContain(':aria-labelledby="groupLabelledby(group)"')
        ->and($html)->toContain('<template x-if="group.label">')
        ->and($groupLabel)->toContain(':id="groupLabelId(group)"')
        ->and($groupLabel)->toContain('x-text="group.label"')
        ->and($html)->toContain('<template x-for="entry in group.items" :key="entry.item.id">')
        ->and($item)->toContain('class="lyra-cmdk__item"')
        ->and($item)->toContain('x-bind="item(entry.index)"')
        ->and($item)->toContain(':id="optionId(entry.index)"')
        ->and($item)->toContain(':class="itemClass(entry.index)"')
        ->and($item)->toContain(":aria-selected=\"isActive(entry.index) ? 'true' : 'false'\"")
        ->and($item)->toContain('x-on:mouseenter="setActive(entry.index)"')
        ->and($item)->toContain('x-on:click="pick(entry.item)"')
        ->and($item)->not->toContain('type="button"')
        ->and($item)->not->toContain('tabindex=')
        ->and($item)->not->toContain('role="option"');
});

it('renders item label hint tokenized shortcuts and footer hints in Alpine scope', function (): void {
    $groups = [['items' => [
        ['id' => 'new', 'label' => 'New', 'shortcut' => '⌘ N'],
        ['id' => 'open', 'label' => 'Open', 'shortcut' => 'Enter'],
        ['id' => 'settings', 'label' => 'Settings'],
    ]]];
    $html = renderCommandPalette(['groups' => $groups]);
    $itemLabel = commandPaletteOpeningTag($html, 'item-label');
    $itemHint = commandPaletteOpeningTag($html, 'item-hint');
    $shortcut = commandPaletteOpeningTag($html, 'shortcut');
    $footer = substr($html, strpos($html, commandPaletteOpeningTag($html, 'footer')));

    expect(commandPaletteOptions($html)['groups'])->toBe($groups)
        ->and($itemLabel)->toContain('x-text="entry.item.label"')
        ->and($itemHint)->toContain('x-show="entry.item.hint"')
        ->and($itemHint)->toContain('x-text="entry.item.hint"')
        ->and($shortcut)->toContain('x-show="entry.item.shortcut"')
        ->and($html)->toContain('<template x-for="(key, keyIndex) in (entry.item.shortcut || \'\').split(\' \')" :key="keyIndex">')
        ->and(commandPaletteOpeningTag($html, 'item-kbd'))->toContain('x-text="key"')
        ->and($footer)->toContain('<kbd class="lyra-kbd">↑</kbd>')
        ->and($footer)->toContain('<kbd class="lyra-kbd">↓</kbd>')
        ->and($footer)->toContain('<kbd class="lyra-kbd">↵</kbd>')
        ->and($footer)->toContain('<kbd class="lyra-kbd">esc</kbd>')
        ->and($footer)->toContain('x-text="hints.navigate"')
        ->and($footer)->toContain('x-text="hints.select"')
        ->and($footer)->toContain('x-text="hints.close"');
});

it('renders named search and item icon slots in their canonical positions and Alpine scope', function (): void {
    $html = renderCommandPalette(slots: commandPaletteIconSlots());
    $searchStart = strpos($html, commandPaletteOpeningTag($html, 'search'));
    $searchIcon = strpos($html, 'data-slot="search-icon"', $searchStart);
    $searchInput = strpos($html, 'x-bind="search"', $searchStart);
    $itemStart = strpos($html, commandPaletteOpeningTag($html, 'item'));
    $itemIcon = strpos($html, commandPaletteOpeningTag($html, 'item-icon'), $itemStart);
    $itemSlot = strpos($html, 'data-slot="item-icon"', $itemIcon);
    $itemLabel = strpos($html, 'class="lyra-cmdk__item-label"', $itemStart);

    expect($searchStart)->toBeInt()
        ->and($searchIcon)->toBeInt()->toBeGreaterThan($searchStart)->toBeLessThan($searchInput)
        ->and($itemStart)->toBeInt()
        ->and($itemIcon)->toBeInt()->toBeGreaterThan($itemStart)->toBeLessThan($itemLabel)
        ->and($itemSlot)->toBeInt()->toBeGreaterThan($itemIcon)->toBeLessThan($itemLabel)
        ->and($html)->toContain('x-bind:data-query="query"')
        ->and($html)->toContain('x-bind:data-item="entry.item.id"')
        ->and($html)->toContain('x-bind:data-group="group.index"');
});

it('omits optional icon wrappers and the separate React trigger when consumers provide neither', function (): void {
    $html = renderCommandPalette();

    expect($html)->not->toContain('lyra-cmdk__item-icon')
        ->and($html)->not->toContain('lyra-cmdk-trigger')
        ->and($html)->not->toContain('lyra-cmdk-trigger__icon')
        ->and($html)->not->toContain('lyra-cmdk-trigger__label');
});

it('renders inline mode as a non-modal panel without an overlay or modelable open state', function (): void {
    $html = renderCommandPalette([
        'inline' => true,
        'defaultOpen' => true,
        'class' => 'embedded',
        'data-track' => 'inline-commands',
    ]);
    $root = commandPaletteOpeningTag($html, 'root');
    $panel = commandPaletteOpeningTag($html, 'panel');

    expect(commandPaletteOptions($html)['inline'])->toBeTrue()
        ->and($html)->not->toContain('lyra-cmdk-overlay')
        ->and($root)->not->toContain('x-modelable="open"')
        ->and($root)->not->toContain('wire:model')
        ->and($panel)->toContain('class="lyra-cmdk embedded"')
        ->and($panel)->toContain('x-bind="panel"')
        ->and($panel)->toContain('data-track="inline-commands"')
        ->and($panel)->not->toContain('role="dialog"')
        ->and($panel)->not->toContain('aria-modal=')
        ->and($panel)->not->toContain('aria-label=');
});

it('keeps every hostile binding value inside the encoded JSON literal', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $groups = [[
        'label' => $payload,
        'items' => [[
            'id' => $payload,
            'label' => $payload,
            'hint' => $payload,
            'shortcut' => $payload,
        ]],
    ]];
    $html = renderCommandPalette([
        'groups' => $groups,
        'placeholder' => $payload,
        'emptyMessage' => $payload,
        'searchLabel' => $payload,
        'hints' => [
            'navigate' => $payload,
            'select' => $payload,
            'close' => $payload,
        ],
        'hotkey' => $payload,
        'label' => $payload,
    ]);
    $root = commandPaletteOpeningTag($html, 'root');
    $options = commandPaletteOptions($html);

    expect($options['groups'])->toBe($groups)
        ->and($options['placeholder'])->toBe($payload)
        ->and($options['emptyMessage'])->toBe($payload)
        ->and($options['searchLabel'])->toBe($payload)
        ->and($options['hints'])->toBe([
            'navigate' => $payload,
            'select' => $payload,
            'close' => $payload,
        ])->and($options['hotkey'])->toBe($payload)
        ->and($options['label'])->toBe($payload)
        ->and(substr_count($html, 'x-data='))->toBe(1)
        ->and($root)->toContain('\\u003C/script\\u003E')
        ->and($root)->not->toContain("'); window.pwned=1; //")
        ->and($html)->not->toContain('</script>');
});

it('rejects a consumer x-data override and always emits exactly one binding root', function (): void {
    foreach ([false, true] as $inline) {
        $html = renderCommandPalette([
            'inline' => $inline,
            'x-data' => 'alert(1)',
        ]);

        expect(substr_count($html, 'x-data='))->toBe(1)
            ->and($html)->not->toContain('alert(1)');
    }
});

it('renders namespaced and short syntax identically', function (): void {
    $groups = [['items' => [['id' => 'search', 'label' => 'Search']]]];
    $namespaced = Blade::render(
        '<x-lyra::command-palette :groups="$groups" search-label="Search commands" />',
        compact('groups'),
    );
    $short = Blade::render(
        '<lyra:command-palette :groups="$groups" search-label="Search commands" />',
        compact('groups'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraCommandPalette(');
});
