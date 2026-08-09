<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderSidebarGroup(
    array $props = [],
    string|Htmlable $slot = '',
): string {
    $label = $props['label'] ?? null;
    $items = $props['items'] ?? [];
    $collapsible = $props['collapsible'] ?? false;
    $defaultCollapsed = $props['defaultCollapsed'] ?? false;
    unset($props['label'], $props['items'], $props['collapsible'], $props['defaultCollapsed']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::sidebar-group :label="$label" :items="$items" :collapsible="$collapsible" :default-collapsed="$defaultCollapsed" %s>{{ $slot }}</x-lyra::sidebar-group>',
            $attributes,
        ),
        compact('label', 'items', 'collapsible', 'defaultCollapsed', 'slot'),
    );
}

function sidebarGroupOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-sbgroup(?: [^"]*)?")[^>]*>/',
        'label-button' => '/<button\b(?=[^>]*\bclass="lyra-sbgroup__label lyra-sbgroup__label--btn")[^>]*>/',
        'items' => '/<div\b(?=[^>]*\bclass="lyra-sbgroup__items")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/**
 * @return list<string>
 */
function sidebarGroupItemOpeningTags(string $html): array
{
    $matched = preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-sbgroup__item(?: [^"]*)?")[^>]*>/',
        $html,
        $matches,
    );

    expect($matched)->not->toBeFalse();

    return $matches[0];
}

function sidebarGroupClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-sbgroup(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('sidebar group class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/sidebar-group.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the sidebar-group class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(sidebarGroupClass(renderSidebarGroup($case['props'])))->toBe($case['expected_class']);
})->with('sidebar group class emission');

it('renders namespaced and short syntax identically', function (): void {
    $items = [['id' => 'home', 'label' => 'Home']];
    $namespaced = Blade::render(
        '<x-lyra::sidebar-group label="Navigation" :items="$items">Extra</x-lyra::sidebar-group>',
        compact('items'),
    );
    $short = Blade::render(
        '<lyra:sidebar-group label="Navigation" :items="$items">Extra</lyra:sidebar-group>',
        compact('items'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-sbgroup"');
});

it('always wires the private Alpine state machine with a typed effective initial state', function (): void {
    $default = sidebarGroupOpeningTag(renderSidebarGroup(), 'root');
    $collapsed = sidebarGroupOpeningTag(renderSidebarGroup([
        'collapsible' => true,
        'defaultCollapsed' => true,
    ]), 'root');
    $nonCollapsible = sidebarGroupOpeningTag(renderSidebarGroup([
        'defaultCollapsed' => true,
    ]), 'root');

    expect($default)->toContain('x-data="lyraSidebarGroup({ defaultCollapsed: false })"')
        ->and($collapsed)->toContain('x-data="lyraSidebarGroup({ defaultCollapsed: true })"')
        ->and($nonCollapsible)->toContain('x-data="lyraSidebarGroup({ defaultCollapsed: false })"')
        ->and($default)->toContain('x-bind="root"')
        ->and($collapsed)->toContain('x-bind="root"')
        ->and($nonCollapsible)->toContain('x-bind="root"')
        ->and($default)->not->toContain('x-modelable')
        ->and($collapsed)->not->toContain('x-modelable')
        ->and($nonCollapsible)->not->toContain('x-modelable');
});

it('omits the label element when no label is supplied', function (): void {
    expect(renderSidebarGroup())->not->toContain('lyra-sbgroup__label');
});

it('renders a non-collapsible label as a plain div', function (): void {
    $html = renderSidebarGroup(['label' => 'Navigation']);

    expect($html)->toContain('<div class="lyra-sbgroup__label">Navigation</div>')
        ->and($html)->not->toContain('lyra-sbgroup__label--btn')
        ->and($html)->not->toContain('x-bind="label"')
        ->and($html)->not->toContain('aria-expanded=');
});

it('renders the collapsible label button with served state and the exact inline chevron', function (): void {
    $expanded = renderSidebarGroup([
        'label' => 'Navigation',
        'collapsible' => true,
    ]);
    $collapsed = renderSidebarGroup([
        'label' => 'Navigation',
        'collapsible' => true,
        'defaultCollapsed' => true,
    ]);
    $expandedButton = sidebarGroupOpeningTag($expanded, 'label-button');
    $collapsedButton = sidebarGroupOpeningTag($collapsed, 'label-button');

    expect($expandedButton)->toContain('type="button"')
        ->and(substr_count($expandedButton, 'type='))->toBe(1)
        ->and($expandedButton)->toContain('x-bind="label"')
        ->and($expandedButton)->toContain('aria-expanded="true"')
        ->and(substr_count($expandedButton, 'aria-expanded='))->toBe(1)
        ->and($collapsedButton)->toContain('aria-expanded="false"')
        ->and(substr_count($collapsedButton, 'aria-expanded='))->toBe(1)
        ->and($expanded)->toMatch('/<span>Navigation<\/span>\s*<svg\s+class="lyra-sbgroup__chev"\s+aria-hidden="true"\s+width="13"\s+height="13"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s*>\s*<path d="m6 9 6 6 6-6"\s*\/>\s*<\/svg>/s');
});

it('renders item anatomy and optional fields in supplied order', function (): void {
    $html = renderSidebarGroup([
        'items' => [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'title' => 'Open dashboard',
                'icon' => new HtmlString('<svg data-icon="dashboard"></svg>'),
                'badge' => 0,
                'active' => true,
            ],
            [
                'id' => 'reports',
                'label' => 'Reports',
            ],
        ],
    ]);
    $items = sidebarGroupItemOpeningTags($html);

    expect($items)->toHaveCount(2)
        ->and($items[0])->toContain('type="button"')
        ->and(substr_count($items[0], 'type='))->toBe(1)
        ->and($items[0])->toContain('class="lyra-sbgroup__item lyra-sbgroup__item--active"')
        ->and($items[0])->toContain('x-bind="item"')
        ->and($items[0])->toContain('data-id="dashboard"')
        ->and($items[0])->toContain('aria-current="page"')
        ->and($items[0])->toContain('title="Open dashboard"')
        ->and($items[1])->toContain('type="button"')
        ->and($items[1])->toContain('class="lyra-sbgroup__item"')
        ->and($items[1])->toContain('x-bind="item"')
        ->and($items[1])->toContain('data-id="reports"')
        ->and($items[1])->not->toContain('aria-current=')
        ->and($items[1])->not->toContain('title=')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-icon"><svg data-icon="dashboard"></svg></span>')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-label">Dashboard</span>')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-badge">0</span>')
        ->and(substr_count($html, 'lyra-sbgroup__item-icon'))->toBe(1)
        ->and(substr_count($html, 'lyra-sbgroup__item-badge'))->toBe(1)
        ->and(strpos($html, '>Dashboard</span>'))->toBeLessThan(strpos($html, '>Reports</span>'));
});

it('escapes item content and ids without losing Htmlable icon and badge content', function (): void {
    $html = renderSidebarGroup([
        'items' => [[
            'id' => 'nav"<unsafe>',
            'label' => '<Reports>',
            'title' => 'A "quoted" title',
            'icon' => '<svg data-unsafe></svg>',
            'badge' => new HtmlString('<strong>New</strong>'),
        ]],
    ]);
    $item = sidebarGroupItemOpeningTags($html)[0];

    expect($item)->toContain('data-id="nav&quot;&lt;unsafe&gt;"')
        ->and($item)->toContain('title="A &quot;quoted&quot; title"')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-icon">&lt;svg data-unsafe&gt;&lt;/svg&gt;</span>')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-label">&lt;Reports&gt;</span>')
        ->and($html)->toContain('<span class="lyra-sbgroup__item-badge"><strong>New</strong></span>')
        ->and($html)->not->toContain('data-id="nav"<unsafe>"');
});

it('renders the slot after all items inside the items container', function (): void {
    $html = renderSidebarGroup(
        ['items' => [['id' => 'home', 'label' => 'Home']]],
        new HtmlString('<a data-extra href="/settings">Settings</a>'),
    );
    $itemsStart = strpos($html, sidebarGroupOpeningTag($html, 'items'));
    $itemsEnd = strpos($html, '</div>', $itemsStart);
    $container = substr($html, $itemsStart, $itemsEnd - $itemsStart + strlen('</div>'));

    expect($container)->toContain('<span class="lyra-sbgroup__item-label">Home</span>')
        ->and($container)->toContain('<a data-extra href="/settings">Settings</a>')
        ->and(strpos($container, '>Home</span>'))->toBeLessThan(strpos($container, '<a data-extra'));
});

it('keeps items served and applies visibility attributes only to collapsible states', function (): void {
    $plain = renderSidebarGroup(['items' => [['id' => 'home', 'label' => 'Home']]]);
    $expanded = renderSidebarGroup([
        'items' => [['id' => 'home', 'label' => 'Home']],
        'collapsible' => true,
    ]);
    $collapsed = renderSidebarGroup([
        'items' => [['id' => 'home', 'label' => 'Home']],
        'collapsible' => true,
        'defaultCollapsed' => true,
    ]);
    $plainItems = sidebarGroupOpeningTag($plain, 'items');
    $expandedItems = sidebarGroupOpeningTag($expanded, 'items');
    $collapsedItems = sidebarGroupOpeningTag($collapsed, 'items');

    expect($plainItems)->not->toContain('x-show=')
        ->and($plainItems)->not->toContain('x-cloak')
        ->and($expandedItems)->toContain('x-show="!collapsed"')
        ->and($expandedItems)->not->toContain('x-cloak')
        ->and($collapsedItems)->toContain('x-show="!collapsed"')
        ->and($collapsedItems)->toContain('x-cloak')
        ->and($plain)->toContain('data-id="home"')
        ->and($expanded)->toContain('data-id="home"')
        ->and($collapsed)->toContain('data-id="home"')
        ->and($collapsed)->not->toContain('<template')
        ->and($collapsed)->not->toContain('x-if');
});

it('passes consumer attributes through the root without overriding the state contract', function (): void {
    $root = sidebarGroupOpeningTag(renderSidebarGroup([
        'class' => 'consumer',
        'id' => 'primary-navigation',
        'data-track' => 'sidebar',
        'x-data' => 'consumerState',
    ]), 'root');

    expect($root)->toContain('class="lyra-sbgroup consumer"')
        ->and($root)->toContain('id="primary-navigation"')
        ->and($root)->toContain('data-track="sidebar"')
        ->and(strpos($root, 'x-data="lyraSidebarGroup({ defaultCollapsed: false })"'))->toBeLessThan(strpos($root, 'x-data="consumerState"'));
});
