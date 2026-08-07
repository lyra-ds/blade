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
        'list' => '/<div\b(?=[^>]*\bclass="lyra-tabs(?: [^"]*)?")[^>]*>/',
        'tab' => sprintf('/<button\b(?=[^>]*\bdata-value="%s")[^>]*>/', preg_quote((string) $value, '/')),
        'panel' => sprintf('/<div\b(?=[^>]*\bdata-value="%s")[^>]*>/', preg_quote((string) $value, '/')),
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

it('renders a plain structural root with the exact Alpine state contract', function (): void {
    $html = renderTabs([
        'active' => 'activity',
        'id' => 'account-tabs',
        'data-track' => 'tabs',
    ]);
    $rootTag = tabsOpeningTag($html, 'root');
    $listTag = tabsOpeningTag($html, 'list');

    expect($rootTag)->toContain('x-data="lyraTabs({ active: \'activity\' })"')
        ->and($rootTag)->toContain('x-modelable="active"')
        ->and($rootTag)->not->toContain(' class=')
        ->and($rootTag)->not->toContain('id="account-tabs"')
        ->and($rootTag)->not->toContain('data-track="tabs"')
        ->and($listTag)->toContain('id="account-tabs"')
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

it('binds the tablist and keeps fixed attributes ahead of passthrough duplicates', function (): void {
    $listTag = tabsOpeningTag(renderTabs([
        'role' => 'group',
        'x-bind' => 'consumer',
    ]), 'list');
    $componentRolePosition = strpos($listTag, 'role="tablist"');
    $consumerRolePosition = strpos($listTag, 'role="group"');
    $componentBindingPosition = strpos($listTag, 'x-bind="list"');
    $consumerBindingPosition = strpos($listTag, 'x-bind="consumer"');

    expect($componentRolePosition)->toBeInt()
        ->and($consumerRolePosition)->toBeInt()
        ->and($componentRolePosition)->toBeLessThan($consumerRolePosition)
        ->and($componentBindingPosition)->toBeInt()
        ->and($consumerBindingPosition)->toBeInt()
        ->and($componentBindingPosition)->toBeLessThan($consumerBindingPosition);
});

it('renders the complete active and inactive tab contracts', function (): void {
    $html = renderTabs(['active' => 'activity']);
    $overview = tabsOpeningTag($html, 'tab', 'overview');
    $activity = tabsOpeningTag($html, 'tab', 'activity');

    expect($overview)->toContain('type="button"')
        ->and($overview)->toContain('role="tab"')
        ->and($overview)->toContain('data-value="overview"')
        ->and($overview)->toContain('x-bind="tab"')
        ->and($overview)->toContain('aria-selected="false"')
        ->and($overview)->toContain('tabindex="-1"')
        ->and($overview)->toContain('class="lyra-tab"')
        ->and($activity)->toContain('type="button"')
        ->and($activity)->toContain('role="tab"')
        ->and($activity)->toContain('data-value="activity"')
        ->and($activity)->toContain('x-bind="tab"')
        ->and($activity)->toContain('aria-selected="true"')
        ->and($activity)->toContain('tabindex="0"')
        ->and($activity)->toContain('class="lyra-tab lyra-tab--active"');
});

it('resolves the active tab position when item keys are non-sequential', function (): void {
    $html = renderTabs([
        'items' => [
            3 => ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Overview panel'],
            7 => ['id' => 'activity', 'label' => 'Activity', 'panel' => 'Activity panel'],
        ],
        'active' => 'activity',
    ]);
    $overview = tabsOpeningTag($html, 'tab', 'overview');
    $activity = tabsOpeningTag($html, 'tab', 'activity');
    $overviewPanel = tabsOpeningTag($html, 'panel', 'overview');
    $activityPanel = tabsOpeningTag($html, 'panel', 'activity');

    expect($overview)->toContain('aria-selected="false"')
        ->and($activity)->toContain('aria-selected="true"')
        ->and($activity)->toContain('class="lyra-tab lyra-tab--active"')
        ->and($overviewPanel)->toContain(' hidden')
        ->and($activityPanel)->not->toContain(' hidden');
});

it('falls back to the first tab when active does not match an item', function (): void {
    $html = renderTabs(['active' => 'missing']);
    $root = tabsOpeningTag($html, 'root');
    $overview = tabsOpeningTag($html, 'tab', 'overview');
    $activity = tabsOpeningTag($html, 'tab', 'activity');

    expect($root)->toContain('x-data="lyraTabs({ active: \'overview\' })"')
        ->and($overview)->toContain('aria-selected="true"')
        ->and($overview)->toContain('tabindex="0"')
        ->and($overview)->toContain('class="lyra-tab lyra-tab--active"')
        ->and($activity)->toContain('aria-selected="false"')
        ->and($activity)->toContain('tabindex="-1"')
        ->and($activity)->toContain('class="lyra-tab"');
});

it('safe-coerces an unknown variant to line styling', function (): void {
    $html = renderTabs(['variant' => 'unknown']);

    expect(tabsClass($html))->toBe('lyra-tabs');
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
        ->and($html)->toContain('&lt;svg data-icon=&quot;escaped&quot;&gt;&lt;/svg&gt;&lt;strong&gt;Escaped&lt;/strong&gt;')
        ->and(strpos($html, '<svg data-icon="raw"></svg>'))->toBeLessThan(strpos($html, '<strong>Raw</strong>'))
        ->and(strpos($html, '&lt;svg data-icon=&quot;escaped&quot;&gt;'))->toBeLessThan(strpos($html, '&lt;strong&gt;Escaped&lt;/strong&gt;'));
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

it('renders labelled panels with content and hides only inactive panels', function (): void {
    $html = renderTabs([
        'items' => [
            ['id' => 'raw', 'label' => 'Raw', 'panel' => new HtmlString('<p>Raw panel</p>')],
            ['id' => 'escaped', 'label' => 'Escaped', 'panel' => '<p>Escaped panel</p>'],
            ['id' => 'empty', 'label' => 'Empty'],
        ],
        'active' => 'raw',
    ]);
    $raw = tabsOpeningTag($html, 'panel', 'raw');
    $escaped = tabsOpeningTag($html, 'panel', 'escaped');
    $empty = tabsOpeningTag($html, 'panel', 'empty');

    expect($raw)->toContain('role="tabpanel"')
        ->and($raw)->toContain('tabindex="0"')
        ->and($raw)->toContain('data-value="raw"')
        ->and($raw)->toContain('x-bind="panel"')
        ->and($raw)->not->toContain(' hidden')
        ->and($escaped)->toContain(' hidden')
        ->and($empty)->toContain(' hidden')
        ->and($html)->toContain('<p>Raw panel</p>')
        ->and($html)->toContain('&lt;p&gt;Escaped panel&lt;/p&gt;')
        ->and($html)->toMatch('/<div\b[^>]*data-value="empty"[^>]*>\s*<\/div>/');
});

it('serves no ids or tab-panel relationship attributes', function (): void {
    $html = renderTabs();

    expect($html)->not->toMatch('/\sid=/')
        ->and($html)->not->toContain('aria-controls=')
        ->and($html)->not->toContain('aria-labelledby=');
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
        ->and($activity)->toContain('aria-selected="true"')
        ->and($activity)->toContain('tabindex="0"')
        ->and($activity)->toContain('class="lyra-tab lyra-tab--active"');
});
