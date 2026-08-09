<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderTableOfContents(array $props = []): string
{
    $label = $props['label'] ?? 'On this page';
    $items = $props['items'] ?? [
        ['id' => 'overview', 'label' => 'Overview', 'level' => 2],
        ['id' => 'api', 'label' => 'API', 'level' => 3],
    ];
    $activeId = $props['activeId'] ?? null;
    unset($props['label'], $props['items'], $props['activeId']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::table-of-contents :label="$label" :items="$items" :active-id="$activeId" %s />',
            $attributes,
        ),
        compact('label', 'items', 'activeId'),
    );
}

function tableOfContentsOpeningTag(string $html, string $target, ?string $id = null): string
{
    $pattern = match ($target) {
        'root' => '/<nav\b[^>]*>/',
        'item' => sprintf('/<li\b(?=[^>]*\bdata-level="[^"]*")[^>]*>\s*<a\b(?=[^>]*\bhref="#%s")/s', preg_quote((string) $id, '/')),
        'link' => sprintf('/<a\b(?=[^>]*\bhref="#%s")[^>]*>/', preg_quote((string) $id, '/')),
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    if ($target === 'item') {
        preg_match('/\A<li\b[^>]*>/', $matches[0], $itemMatches);

        return $itemMatches[0];
    }

    return $matches[0];
}

function tableOfContentsClass(string $html, string $target, ?string $id = null): string
{
    $pattern = match ($target) {
        'root' => '/<nav\b[^>]*\bclass="([^"]*)"/',
        'title' => '/<span\b[^>]*\bclass="([^"]*)"/',
        'list' => '/<ul\b[^>]*\bclass="([^"]*)"/',
        'link' => sprintf('/<a\b(?=[^>]*\bhref="#%s")[^>]*\bclass="([^"]*)"/', preg_quote((string) $id, '/')),
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('table of contents class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/table-of-contents.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the table-of-contents class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    $html = renderTableOfContents([
        ...$case['props'],
        'activeId' => 'api',
    ]);
    $item = tableOfContentsOpeningTag($html, 'item', 'overview');

    expect(tableOfContentsClass($html, 'root'))->toBe($case['expected_class'])
        ->and(tableOfContentsClass($html, 'title'))->toBe($case['expected_title_class'])
        ->and(tableOfContentsClass($html, 'list'))->toBe($case['expected_list_class'])
        ->and($item)->not->toContain(' class=')
        ->and($case['expected_item_class'])->toBe('')
        ->and(tableOfContentsClass($html, 'link', 'overview'))->toBe($case['expected_link_class'])
        ->and(tableOfContentsClass($html, 'link', 'api'))->toBe($case['expected_active_link_class'])
        ->and(tableOfContentsClass($html, 'link', 'api'))->toContain($case['expected_active_modifier_class']);
})->with('table of contents class emission');

it('renders namespaced and short syntax identically', function (): void {
    $items = [['id' => 'overview', 'label' => 'Overview', 'level' => 2]];
    $namespaced = Blade::render(
        '<x-lyra::table-of-contents label="Contents" :items="$items" />',
        compact('items'),
    );
    $short = Blade::render(
        '<lyra:table-of-contents label="Contents" :items="$items" />',
        compact('items'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-toc"');
});

it('renders the labelled navigation and complete served item markup', function (): void {
    $html = renderTableOfContents([
        'label' => 'Component topics',
        'items' => [
            ['id' => 'overview', 'label' => 'Overview'],
            ['id' => 'keyboard', 'label' => 'Keyboard support', 'level' => 3],
        ],
    ]);
    $root = tableOfContentsOpeningTag($html, 'root');
    $overview = tableOfContentsOpeningTag($html, 'item', 'overview');
    $keyboard = tableOfContentsOpeningTag($html, 'item', 'keyboard');

    expect($root)->toContain('aria-label="Component topics"')
        ->and($html)->toContain('<span class="lyra-toc__title">Component topics</span>')
        ->and($overview)->toContain('data-level="2"')
        ->and($keyboard)->toContain('data-level="3"')
        ->and($html)->toContain('href="#overview"')
        ->and($html)->toContain('>Overview</a>')
        ->and($html)->toContain('href="#keyboard"')
        ->and($html)->toContain('>Keyboard support</a>');
});

it('serves exactly one controlled active link when activeId matches', function (): void {
    $html = renderTableOfContents(['activeId' => 'api']);
    $overview = tableOfContentsOpeningTag($html, 'link', 'overview');
    $api = tableOfContentsOpeningTag($html, 'link', 'api');

    expect($overview)->toContain('class="lyra-toc__link"')
        ->and($overview)->not->toContain('aria-current=')
        ->and($api)->toContain('class="lyra-toc__link lyra-toc__link--active"')
        ->and($api)->toContain('aria-current="location"')
        ->and(substr_count($html, 'aria-current="location"'))->toBe(1);
});

it('serves no active marker when activeId is absent or unmatched', function (): void {
    $absent = renderTableOfContents();
    $unmatched = renderTableOfContents(['activeId' => 'missing']);

    expect($absent)->not->toContain('aria-current=')
        ->and($absent)->not->toContain('lyra-toc__link--active')
        ->and($unmatched)->not->toContain('aria-current=')
        ->and($unmatched)->not->toContain('lyra-toc__link--active');
});

it('wires only names exported by the Alpine binding with the effective initial state', function (): void {
    $default = renderTableOfContents();
    $active = renderTableOfContents(['activeId' => 'api']);
    $defaultRoot = tableOfContentsOpeningTag($default, 'root');
    $activeRoot = tableOfContentsOpeningTag($active, 'root');

    expect($defaultRoot)->toContain("x-data=\"lyraTableOfContents({ activeId: '' })\"")
        ->and($activeRoot)->toContain("x-data=\"lyraTableOfContents({ activeId: 'api' })\"")
        ->and($activeRoot)->toContain('x-modelable="activeId"')
        ->and($activeRoot)->not->toContain('x-bind="root"')
        ->and(tableOfContentsOpeningTag($active, 'link', 'overview'))->toContain('x-bind="link"')
        ->and(tableOfContentsOpeningTag($active, 'link', 'api'))->toContain('x-bind="link"');
});

it('JavaScript-escapes the activeId option while serving escaped content', function (): void {
    $activeId = "a'b\\c\r\nd";
    $html = renderTableOfContents([
        'activeId' => $activeId,
        'items' => [[
            'id' => $activeId,
            'label' => '<Unsafe>',
            'level' => 2,
        ]],
    ]);
    $root = tableOfContentsOpeningTag($html, 'root');

    expect($root)->toContain("x-data=\"lyraTableOfContents({ activeId: 'a\\'b\\\\c\\r\\nd' })\"")
        ->and($html)->toContain('&lt;Unsafe&gt;')
        ->and($html)->not->toContain('><Unsafe></a>');
});

it('supports Livewire model binding through activeId', function (): void {
    $component = new class extends Component
    {
        public string $activeId = 'api';

        public array $items = [
            ['id' => 'overview', 'label' => 'Overview', 'level' => 2],
            ['id' => 'api', 'label' => 'API', 'level' => 2],
        ];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::table-of-contents
                    label="Contents"
                    :items="$items"
                    :active-id="$activeId"
                    wire:model.live="activeId"
                />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = tableOfContentsOpeningTag($html, 'root');

    expect($root)->toContain('x-modelable="activeId"')
        ->and($root)->toContain('wire:model.live="activeId"')
        ->and($root)->toContain("x-data=\"lyraTableOfContents({ activeId: 'api' })\"");
});

it('passes root attributes through with user classes last', function (): void {
    $root = tableOfContentsOpeningTag(renderTableOfContents([
        'class' => 'consumer utility',
        'id' => 'docs-contents',
        'data-track' => 'toc',
        'aria-label' => 'Consumer label',
        'x-data' => 'consumerState',
    ]), 'root');

    expect($root)->toContain('class="lyra-toc consumer utility"')
        ->and($root)->toContain('id="docs-contents"')
        ->and($root)->toContain('data-track="toc"')
        ->and($root)->toContain('aria-label="Consumer label"')
        ->and(substr_count($root, 'aria-label='))->toBe(1)
        ->and(strpos($root, "x-data=\"lyraTableOfContents({ activeId: '' })\""))->toBeLessThan(strpos($root, 'x-data="consumerState"'));
});
