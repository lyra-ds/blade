<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderTabs(array $props = []): string
{
    $items = $props['items'] ?? [
        ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Overview panel'],
        ['id' => 'activity', 'label' => 'Activity', 'panel' => 'Activity panel'],
    ];
    $active = $props['active'] ?? 'overview';
    $variant = $props['variant'] ?? 'line';
    unset($props['items'], $props['active'], $props['variant']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::tabs :items="$items" :active="$active" :variant="$variant" %s />',
            $attributes,
        ),
        compact('items', 'active', 'variant'),
    );
}

function tabsOpeningTag(string $html, string $target, ?string $value = null): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-data="lyraTabs\()[^>]*>/',
        'list' => '/<div\b(?=[^>]*\bdata-lyra-tabs-enhanced)[^>]*>/',
        'tab' => sprintf('/<button\b(?=[^>]*\bdata-value="%s")[^>]*>/', preg_quote((string) $value, '/')),
        'panel' => sprintf('/<section\b(?=[^>]*\bdata-value="%s")[^>]*>/', preg_quote((string) $value, '/')),
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function tabsClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-tabs(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('tabs class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/tabs.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the tabs class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React tablist class string', function (array $case): void {
    $html = renderTabs($case['props']);

    expect(tabsClass($html))->toBe($case['expected_class']);
})->with('tabs class emission');

it('emits native disabled only on disabled items', function (): void {
    $html = renderTabs([
        'items' => [
            ['id' => 'a', 'label' => 'A', 'panel' => 'A panel'],
            ['id' => 'b', 'label' => 'B', 'disabled' => true, 'panel' => 'B panel'],
            ['id' => 'c', 'label' => 'C', 'disabled' => false, 'panel' => 'C panel'],
        ],
        'active' => 'a',
    ]);

    expect(tabsOpeningTag($html, 'tab', 'a'))->not->toContain('disabled')
        ->and(tabsOpeningTag($html, 'tab', 'b'))->toMatch('/\sdisabled(\s|>|=)/')
        ->and(tabsOpeningTag($html, 'tab', 'c'))->not->toContain('disabled')
        ->and($html)->toContain('<h2>B</h2>');
});

it('renders a structural root with the exact Alpine state contract', function (): void {
    $html = renderTabs([
        'active' => 'activity',
        'id' => 'account-tabs',
        'data-track' => 'tabs',
    ]);
    $rootTag = tabsOpeningTag($html, 'root');
    $listTag = tabsOpeningTag($html, 'list');

    expect($rootTag)->toContain('id="account-tabs"')
        ->and($rootTag)->toContain('data-lyra-tabs')
        ->and($rootTag)->toContain('x-data="lyraTabs({ active: \'activity\' })"')
        ->and($rootTag)->toContain('x-modelable="active"')
        ->and($rootTag)->not->toContain(' class=')
        ->and($rootTag)->not->toContain('data-track="tabs"')
        ->and($listTag)->not->toContain('id="account-tabs"')
        ->and($listTag)->toContain('data-track="tabs"');
});

it('substitutes malformed UTF-8 in the Alpine active literal', function (): void {
    $active = "\xC3\x28";
    $rootTag = tabsOpeningTag(renderTabs([
        'items' => [
            ['id' => $active, 'label' => 'Malformed'],
        ],
        'active' => $active,
    ]), 'root');

    expect($rootTag)->toContain("x-data=\"lyraTabs({ active: '�(' })\"");
});

it('JavaScript-escapes quotes, slashes, and line breaks in the Alpine active literal', function (): void {
    $active = "a'b\\c\r\nd";
    $rootTag = tabsOpeningTag(renderTabs([
        'items' => [
            ['id' => $active, 'label' => 'Escaped'],
        ],
        'active' => $active,
    ]), 'root');

    expect($rootTag)->toContain("x-data=\"lyraTabs({ active: 'a\\'b\\\\c\\r\\nd' })\"");
});

it('generates a root id when none is supplied', function (): void {
    expect(tabsOpeningTag(renderTabs(), 'root'))->toMatch('/\bid="lyra-tabs-[^"]+"/');
});

it('renders the fallback nav with one anchor per panel id', function (): void {
    $html = renderTabs(['id' => 'account-tabs', 'label' => 'Account sections']);

    expect($html)->toMatch('/<nav\b[^>]*aria-label="Account sections"[^>]*data-lyra-tabs-fallback[^>]*x-bind="fallback"[^>]*>/')
        ->and($html)->toContain('<a href="#account-tabs-panel-0">Overview</a>')
        ->and($html)->toContain('<a href="#account-tabs-panel-1">Activity</a>')
        ->and(substr_count($html, 'id="account-tabs-panel-0"'))->toBe(1)
        ->and(substr_count($html, 'href="#account-tabs-panel-0"'))->toBe(1);
});

it('renders the enhanced list hidden, labelled and bound without a static role', function (): void {
    $listTag = tabsOpeningTag(renderTabs(['label' => 'Project views']), 'list');

    expect($listTag)->toContain('aria-label="Project views"')
        ->and($listTag)->toContain('data-lyra-tabs-enhanced')
        ->and($listTag)->toContain(' hidden')
        ->and($listTag)->toContain('x-bind="list"')
        ->and($listTag)->not->toContain('role=');
});

it('defaults the accessible label and applies it to both the nav and the list', function (): void {
    expect(substr_count(renderTabs(), 'aria-label="Tabs"'))->toBe(2);
});

it('keeps fixed attributes ahead of passthrough duplicates on the list', function (): void {
    $listTag = tabsOpeningTag(renderTabs(['x-bind' => 'consumer']), 'list');
    $componentBindingPosition = strpos($listTag, 'x-bind="list"');
    $consumerBindingPosition = strpos($listTag, 'x-bind="consumer"');

    expect($componentBindingPosition)->toBeInt()
        ->and($consumerBindingPosition)->toBeInt()
        ->and($componentBindingPosition)->toBeLessThan($consumerBindingPosition);
});

it('renders tabs without static role, selection, tabindex or active class', function (): void {
    $html = renderTabs(['active' => 'activity', 'id' => 'account-tabs']);
    $overview = tabsOpeningTag($html, 'tab', 'overview');
    $activity = tabsOpeningTag($html, 'tab', 'activity');

    foreach ([$overview, $activity] as $tag) {
        expect($tag)->toContain('type="button"')
            ->and($tag)->toContain('class="lyra-tab"')
            ->and($tag)->toContain('x-bind="tab"')
            ->and($tag)->not->toContain('role=')
            ->and($tag)->not->toContain('aria-selected')
            ->and($tag)->not->toContain('tabindex')
            ->and($tag)->not->toContain('lyra-tab--active');
    }

    expect($overview)->toContain('id="account-tabs-tab-0"')
        ->and($activity)->toContain('id="account-tabs-tab-1"');
});

it('resolves the active value when item keys are non-sequential', function (): void {
    $html = renderTabs([
        'items' => [
            3 => ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Overview panel'],
            7 => ['id' => 'activity', 'label' => 'Activity', 'panel' => 'Activity panel'],
        ],
        'active' => 'activity',
        'id' => 'seq',
    ]);

    expect(tabsOpeningTag($html, 'root'))->toContain("lyraTabs({ active: 'activity' })")
        ->and(tabsOpeningTag($html, 'panel', 'overview'))->toContain('id="seq-panel-0"')
        ->and(tabsOpeningTag($html, 'panel', 'activity'))->toContain('id="seq-panel-1"');
});

it('falls back to the first tab when active does not match an item', function (): void {
    expect(tabsOpeningTag(renderTabs(['active' => 'missing']), 'root'))
        ->toContain('x-data="lyraTabs({ active: \'overview\' })"');
});

it('safe-coerces an unknown variant to line styling', function (): void {
    expect(tabsClass(renderTabs(['variant' => 'unknown'])))->toBe('lyra-tabs');
});

it('renders Htmlable icons before labels while escaping strings', function (): void {
    $html = renderTabs([
        'items' => [
            [
                'id' => 'raw',
                'icon' => new HtmlString('<svg data-icon="raw"></svg>'),
                'label' => new HtmlString('<strong>Raw</strong>'),
            ],
            [
                'id' => 'escaped',
                'icon' => '<svg data-icon="escaped"></svg>',
                'label' => '<strong>Escaped</strong>',
            ],
        ],
        'active' => 'raw',
    ]);

    expect($html)->toContain('<svg data-icon="raw"></svg><strong>Raw</strong>')
        ->and($html)->toContain('&lt;svg data-icon=&quot;escaped&quot;&gt;&lt;/svg&gt;&lt;strong&gt;Escaped&lt;/strong&gt;');
});

it('renders a count span for zero but omits it for null or missing counts', function (): void {
    $html = renderTabs([
        'items' => [
            ['id' => 'zero', 'label' => 'Zero', 'count' => 0],
            ['id' => 'null', 'label' => 'Null', 'count' => null],
            ['id' => 'missing', 'label' => 'Missing'],
        ],
        'active' => 'zero',
    ]);

    expect($html)->toContain('<span class="lyra-tab__count">0</span>')
        ->and(substr_count($html, 'lyra-tab__count'))->toBe(1);
});

it('renders headed sections with content and no static role, tabindex or hidden', function (): void {
    $html = renderTabs([
        'items' => [
            ['id' => 'raw', 'label' => 'Raw', 'panel' => new HtmlString('<p>Raw panel</p>')],
            ['id' => 'escaped', 'label' => 'Escaped', 'panel' => '<p>Escaped panel</p>'],
            ['id' => 'empty', 'label' => 'Empty'],
        ],
        'active' => 'raw',
        'id' => 'panels',
    ]);

    foreach (['raw' => 0, 'escaped' => 1, 'empty' => 2] as $value => $index) {
        $tag = tabsOpeningTag($html, 'panel', $value);

        expect($tag)->toContain('id="panels-panel-'.$index.'"')
            ->and($tag)->toContain('x-bind="panel"')
            ->and($tag)->not->toContain('role=')
            ->and($tag)->not->toContain('tabindex')
            ->and($tag)->not->toContain('hidden');
    }

    expect($html)->toContain('<p>Raw panel</p>')
        ->and($html)->toContain('&lt;p&gt;Escaped panel&lt;/p&gt;')
        ->and(substr_count($html, '<section'))->toBe(3)
        ->and($html)->toMatch('/<section\b[^>]*data-value="raw"[^>]*>\s*<h2>Raw<\/h2>\s*<p>Raw panel<\/p>\s*<\/section>/')
        ->and($html)->toMatch('/<section\b[^>]*data-value="empty"[^>]*>\s*<h2>Empty<\/h2>\s*<\/section>/');
});

it('serves no aria-controls or aria-labelledby relationships', function (): void {
    $html = renderTabs();

    expect($html)->not->toContain('aria-controls=')
        ->and($html)->not->toContain('aria-labelledby=');
});

it('never duplicates ids across two instances on one page', function (): void {
    $html = renderTabs().renderTabs();
    preg_match_all('/\sid="([^"]+)"/', $html, $matches);

    expect($matches[1])->toHaveCount(10)
        ->and(array_unique($matches[1]))->toHaveCount(10);
});

it('moves model attributes to the modelable root and leaves passthrough on the tablist', function (): void {
    $html = renderTabs([
        'wire:model.live' => 'active',
        'x-model.number' => 'selectedTab',
        'data-track' => 'tabs',
    ]);
    $rootTag = tabsOpeningTag($html, 'root');
    $listTag = tabsOpeningTag($html, 'list');

    expect($rootTag)->toContain('wire:model.live="active"')
        ->and($rootTag)->toContain('x-model.number="selectedTab"')
        ->and($rootTag)->not->toContain('data-track="tabs"')
        ->and($listTag)->toContain('data-track="tabs"')
        ->and($listTag)->not->toContain('wire:model')
        ->and($listTag)->not->toContain('x-model');
});

it('seeds the active Livewire tab and places wire model on the structural root', function (): void {
    $component = new class extends Component
    {
        public string $active = 'activity';

        public array $items = [
            ['id' => 'overview', 'label' => 'Overview'],
            ['id' => 'activity', 'label' => 'Activity'],
        ];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::tabs :items="$items" :active="$active" wire:model="active" />
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $rootTag = tabsOpeningTag($html, 'root');
    $listTag = tabsOpeningTag($html, 'list');
    $activity = tabsOpeningTag($html, 'tab', 'activity');

    expect($rootTag)->toContain('x-data="lyraTabs({ active: \'activity\' })"')
        ->and($rootTag)->toContain('x-modelable="active"')
        ->and($rootTag)->toContain('wire:model="active"')
        ->and($listTag)->not->toContain('wire:model')
        ->and($activity)->toContain('class="lyra-tab"')
        ->and($html)->toContain('data-lyra-tabs-fallback');
});
