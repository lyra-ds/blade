<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderDropdown(array $props = [], string $trigger = 'Actions'): string
{
    $items = $props['items'] ?? [
        ['label' => 'Edit'],
    ];
    $align = $props['align'] ?? 'start';
    $defaultOpen = $props['defaultOpen'] ?? false;
    unset($props['items'], $props['align'], $props['defaultOpen']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::dropdown :items="$items" :align="$align" :default-open="$defaultOpen" %s><x-slot:trigger>%s</x-slot:trigger></x-lyra::dropdown>',
            $attributes,
            $trigger,
        ),
        compact('items', 'align', 'defaultOpen'),
    );
}

function dropdownClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="(lyra-dropdown(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function dropdownOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<span\b(?=[^>]*\bclass="lyra-dropdown(?: [^"]*)?")[^>]*>/',
        'trigger' => '/<span\b(?=[^>]*\bclass="lyra-dropdown__trigger")[^>]*>/',
        'menu' => '/<div\b(?=[^>]*\bclass="lyra-menu(?: [^"]*)?")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('dropdown class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/dropdown.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the dropdown class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    $html = renderDropdown($case['props']);

    expect(dropdownClass($html))->toBe($case['expected_class']);
})->with('dropdown class emission');

it('emits default Alpine state and passes attributes through on the root', function (): void {
    $html = renderDropdown([
        'class' => 'x y',
        'id' => 'account-actions',
        'data-track' => 'dropdown',
    ]);
    $openingTag = dropdownOpeningTag($html, 'root');

    expect(dropdownClass($html))->toBe('lyra-dropdown x y')
        ->and($openingTag)->toContain('id="account-actions"')
        ->and($openingTag)->toContain('data-track="dropdown"')
        ->and($openingTag)->toContain('x-data="lyraDropdown({ defaultOpen: false, align: \'start\' })"')
        ->and($openingTag)->toContain('x-modelable="open"');
});

it('keeps the component Alpine binding ahead of a consumer x-data attribute', function (): void {
    $openingTag = dropdownOpeningTag(renderDropdown([
        'x-data' => 'consumerState',
    ]), 'root');
    $componentBindingPosition = strpos($openingTag, 'x-data="lyraDropdown({ defaultOpen: false, align: \'start\' })"');
    $consumerBindingPosition = strpos($openingTag, 'x-data="consumerState"');

    expect($componentBindingPosition)->toBeInt()
        ->and($consumerBindingPosition)->toBeInt()
        ->and($componentBindingPosition)->toBeLessThan($consumerBindingPosition);
});

it('seeds an open end-aligned Alpine state with an exact JavaScript literal', function (): void {
    $openingTag = dropdownOpeningTag(renderDropdown([
        'align' => 'end',
        'defaultOpen' => true,
    ]), 'root');

    expect($openingTag)->toContain('x-data="lyraDropdown({ defaultOpen: true, align: \'end\' })"')
        ->and($openingTag)->toContain('x-modelable="open"');
});

it('wraps trigger content with the complete trigger contract', function (): void {
    $closed = renderDropdown(trigger: '<svg data-trigger></svg>Actions');
    $open = renderDropdown(['defaultOpen' => true]);
    $closedTag = dropdownOpeningTag($closed, 'trigger');
    $openTag = dropdownOpeningTag($open, 'trigger');

    expect($closedTag)->toContain('class="lyra-dropdown__trigger"')
        ->and($closedTag)->toContain('role="button"')
        ->and($closedTag)->toContain('tabindex="0"')
        ->and($closedTag)->toContain('aria-haspopup="menu"')
        ->and($closedTag)->toContain('aria-expanded="false"')
        ->and($closedTag)->toContain('x-bind="trigger"')
        ->and($closed)->toContain('<svg data-trigger></svg>Actions</span>')
        ->and($openTag)->toContain('aria-expanded="true"');
});

it('renders the menu binding and cloaks only the closed initial state', function (): void {
    $closed = renderDropdown();
    $open = renderDropdown([
        'align' => 'end',
        'defaultOpen' => true,
    ]);
    $closedRootTag = dropdownOpeningTag($closed, 'root');
    $closedTag = dropdownOpeningTag($closed, 'menu');
    $openTag = dropdownOpeningTag($open, 'menu');

    expect($closedTag)->toContain('class="lyra-menu lyra-menu--start"')
        ->and($closedTag)->toContain('role="menu"')
        ->and($closedTag)->toContain('x-bind="menu"')
        ->and($closedTag)->toContain('x-cloak')
        ->and($openTag)->toContain('class="lyra-menu lyra-menu--end"')
        ->and($openTag)->toContain('role="menu"')
        ->and($openTag)->toContain('x-bind="menu"')
        ->and($openTag)->not->toContain('x-cloak')
        ->and($closed)->not->toContain('aria-controls=')
        ->and($closedRootTag)->not->toContain(' id=')
        ->and($closedTag)->not->toContain(' id=');
});

it('preserves item order and emits each item type contract', function (): void {
    $html = renderDropdown([
        'items' => [
            ['label' => 'Edit', 'id' => 'ignored-command-id'],
            ['type' => 'separator'],
            ['type' => 'label', 'label' => 'Danger zone'],
            ['label' => 'Delete', 'danger' => true],
        ],
    ]);
    $editPosition = strpos($html, '>Edit</button>');
    $separatorPosition = strpos($html, '<hr class="lyra-menu__sep">');
    $labelPosition = strpos($html, '<span class="lyra-menu__label">Danger zone</span>');
    $deletePosition = strpos($html, '>Delete</button>');

    expect($html)->toMatch('/<button\s+type="button"\s+role="menuitem"\s+class="lyra-menu__item"\s+x-bind="item"\s*>Edit<\/button>/s')
        ->and($html)->toContain('<hr class="lyra-menu__sep">')
        ->and($html)->toContain('<span class="lyra-menu__label">Danger zone</span>')
        ->and($html)->toMatch('/<button\s+type="button"\s+role="menuitem"\s+class="lyra-menu__item lyra-menu__item--danger"\s+x-bind="item"\s*>Delete<\/button>/s')
        ->and($html)->not->toContain('ignored-command-id')
        ->and($editPosition)->toBeInt()
        ->and($separatorPosition)->toBeInt()
        ->and($labelPosition)->toBeInt()
        ->and($deletePosition)->toBeInt()
        ->and($editPosition)->toBeLessThan($separatorPosition)
        ->and($separatorPosition)->toBeLessThan($labelPosition)
        ->and($labelPosition)->toBeLessThan($deletePosition);
});

it('renders item icons before labels with Htmlable-aware escaping', function (): void {
    $html = renderDropdown([
        'items' => [
            [
                'icon' => new HtmlString('<svg data-icon="raw"></svg>'),
                'label' => new HtmlString('<strong>Raw</strong>'),
            ],
            [
                'icon' => '<svg data-icon="escaped"></svg>',
                'label' => '<strong>Escaped</strong>',
            ],
        ],
    ]);

    expect($html)->toContain('<svg data-icon="raw"></svg><strong>Raw</strong>')
        ->and($html)->toContain('&lt;svg data-icon=&quot;escaped&quot;&gt;&lt;/svg&gt;&lt;strong&gt;Escaped&lt;/strong&gt;')
        ->and(strpos($html, '<svg data-icon="raw"></svg>'))->toBeLessThan(strpos($html, '<strong>Raw</strong>'))
        ->and(strpos($html, '&lt;svg data-icon=&quot;escaped&quot;&gt;'))->toBeLessThan(strpos($html, '&lt;strong&gt;Escaped&lt;/strong&gt;'));
});

it('renders an open wire-modelled dropdown through Livewire', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public array $items = [
            ['label' => 'Edit'],
        ];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::dropdown :items="$items" :default-open="$open" wire:model="open">
                    <x-slot:trigger>Actions</x-slot:trigger>
                </x-lyra::dropdown>
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $rootOpeningTag = dropdownOpeningTag($html, 'root');

    expect($rootOpeningTag)->toContain('wire:model="open"')
        ->and($html)->toContain('aria-expanded="true"');
});
