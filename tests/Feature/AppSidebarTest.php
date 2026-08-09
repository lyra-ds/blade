<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderAppSidebar(
    array $props = [],
    string|Htmlable $slot = '',
): string {
    $brand = $props['brand'] ?? null;
    $groups = $props['groups'] ?? [];
    $footer = $props['footer'] ?? null;
    $width = $props['width'] ?? 260;
    $collapsible = $props['collapsible'] ?? false;
    $defaultCollapsed = $props['defaultCollapsed'] ?? false;
    $labels = $props['labels'] ?? [];
    unset(
        $props['brand'],
        $props['groups'],
        $props['footer'],
        $props['width'],
        $props['collapsible'],
        $props['defaultCollapsed'],
        $props['labels'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::app-sidebar :brand="$brand" :groups="$groups" :footer="$footer" :width="$width" :collapsible="$collapsible" :default-collapsed="$defaultCollapsed" :labels="$labels" %s>{{ $slot }}</x-lyra::app-sidebar>',
            $attributes,
        ),
        compact('brand', 'groups', 'footer', 'width', 'collapsible', 'defaultCollapsed', 'labels', 'slot'),
    );
}

function appSidebarOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<nav\b(?=[^>]*\bclass="lyra-appsidebar(?: [^"]*)?")[^>]*>/',
        'groups' => '/<div\b(?=[^>]*\bclass="lyra-appsidebar__groups")[^>]*>/',
        'toggle' => '/<button\b(?=[^>]*\bclass="lyra-appsidebar__toggle")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function appSidebarClass(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*\bclass="(lyra-appsidebar(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('app sidebar class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/app-sidebar.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the app-sidebar class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(appSidebarClass(renderAppSidebar($case['props'])))->toBe($case['expected_class']);
})->with('app sidebar class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render(
        '<x-lyra::app-sidebar brand="Lyra" collapsible>Navigation</x-lyra::app-sidebar>',
    );
    $short = Blade::render(
        '<lyra:app-sidebar brand="Lyra" collapsible>Navigation</lyra:app-sidebar>',
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-appsidebar"');
});

it('serves the initial width and rail state before Alpine initializes', function (): void {
    $expanded = appSidebarOpeningTag(renderAppSidebar(['width' => 312.5]), 'root');
    $collapsed = appSidebarOpeningTag(renderAppSidebar([
        'width' => 312.5,
        'defaultCollapsed' => true,
    ]), 'root');

    expect($expanded)->toContain('class="lyra-appsidebar"')
        ->and($expanded)->toContain('style="--appsidebar-width: 312.5px; width: var(--appsidebar-width)"')
        ->and($expanded)->toContain('x-data="lyraAppSidebar({ defaultCollapsed: false, width: 312.5,')
        ->and($collapsed)->toContain('class="lyra-appsidebar lyra-appsidebar--rail"')
        ->and($collapsed)->toContain('x-data="lyraAppSidebar({ defaultCollapsed: true, width: 312.5,')
        ->and($collapsed)->toContain('style="--appsidebar-width: 64px; width: var(--appsidebar-width)"');
});

it('falls back to the default width when the supplied value is not numeric', function (): void {
    $root = appSidebarOpeningTag(renderAppSidebar([
        'width' => '1); window.pwned=1; //',
    ]), 'root');

    expect($root)->toContain('x-data="lyraAppSidebar({ defaultCollapsed: false, width: 260,')
        ->and($root)->toContain('style="--appsidebar-width: 260px; width: var(--appsidebar-width)"')
        ->and($root)->not->toContain('window.pwned')
        ->and($root)->not->toContain('//px');
});

it('wires only the modelable state and bindings exported by lyraAppSidebar', function (): void {
    $root = appSidebarOpeningTag(renderAppSidebar([
        'width' => 288,
        'collapsible' => true,
        'defaultCollapsed' => true,
        'labels' => [
            'collapse' => 'Close navigation',
            'expand' => 'Open navigation',
        ],
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraAppSidebar({ defaultCollapsed: true, width: 288, labels: { collapse: 'Close navigation', expand: 'Open navigation' } })\"")
        ->and($root)->toContain('x-modelable="collapsed"')
        ->and($root)->toContain('x-bind="root"')
        ->and($root)->not->toContain('x-bind="chevron"');
});

it('serves the toggle only when collapsible with the correct initial labels', function (): void {
    $plain = renderAppSidebar();
    $expanded = renderAppSidebar(['collapsible' => true]);
    $collapsed = renderAppSidebar([
        'collapsible' => true,
        'defaultCollapsed' => true,
        'labels' => [
            'collapse' => 'Recolher menu',
            'expand' => 'Expandir menu',
        ],
    ]);
    $expandedToggle = appSidebarOpeningTag($expanded, 'toggle');
    $collapsedToggle = appSidebarOpeningTag($collapsed, 'toggle');

    expect($plain)->not->toContain('lyra-appsidebar__toggle')
        ->and($expandedToggle)->toContain('type="button"')
        ->and(substr_count($expandedToggle, 'type='))->toBe(1)
        ->and($expandedToggle)->toContain('aria-label="Collapse sidebar"')
        ->and($expandedToggle)->toContain('title="Collapse sidebar"')
        ->and($expandedToggle)->toContain('x-bind="toggle"')
        ->and($collapsedToggle)->toContain('aria-label="Expandir menu"')
        ->and($collapsedToggle)->toContain('title="Expandir menu"')
        ->and(substr_count($collapsedToggle, 'aria-label='))->toBe(1)
        ->and(substr_count($collapsedToggle, 'title='))->toBe(1);
});

it('falls back to default toggle labels when entries are null or missing', function (): void {
    $expanded = renderAppSidebar([
        'collapsible' => true,
        'labels' => ['collapse' => null],
    ]);
    $collapsed = renderAppSidebar([
        'collapsible' => true,
        'defaultCollapsed' => true,
        'labels' => ['expand' => null],
    ]);
    $expandedRoot = appSidebarOpeningTag($expanded, 'root');
    $collapsedRoot = appSidebarOpeningTag($collapsed, 'root');
    $expandedToggle = appSidebarOpeningTag($expanded, 'toggle');
    $collapsedToggle = appSidebarOpeningTag($collapsed, 'toggle');

    expect($expandedRoot)->toContain("labels: { collapse: 'Collapse sidebar', expand: 'Expand sidebar' }")
        ->and($collapsedRoot)->toContain("labels: { collapse: 'Collapse sidebar', expand: 'Expand sidebar' }")
        ->and($expandedToggle)->toContain('aria-label="Collapse sidebar"')
        ->and($expandedToggle)->toContain('title="Collapse sidebar"')
        ->and($collapsedToggle)->toContain('aria-label="Expand sidebar"')
        ->and($collapsedToggle)->toContain('title="Expand sidebar"');
});

it('serves both chevrons and cloaks whichever path is initially hidden', function (): void {
    $expanded = renderAppSidebar(['collapsible' => true]);
    $collapsed = renderAppSidebar([
        'collapsible' => true,
        'defaultCollapsed' => true,
    ]);
    expect($expanded)->toMatch('/<svg\s+aria-hidden="true"\s+width="15"\s+height="15"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s*>/s')
        ->and($expanded)->toMatch('/<path\s+d="m15 18-6-6 6-6"\s+x-show="!collapsed"\s*\/>/s')
        ->and($expanded)->toMatch('/<path\s+d="m9 18 6-6-6-6"\s+x-show="collapsed"\s+x-cloak\s*\/>/s')
        ->and($collapsed)->toMatch('/<path\s+d="m15 18-6-6 6-6"\s+x-show="!collapsed"\s+x-cloak\s*\/>/s')
        ->and($collapsed)->toMatch('/<path\s+d="m9 18 6-6-6-6"\s+x-show="collapsed"\s*\/>/s')
        ->and(substr_count($expanded, '<path'))->toBe(2)
        ->and(substr_count($collapsed, '<path'))->toBe(2);
});

it('omits absent brand and footer sections and serves present content', function (): void {
    $absent = renderAppSidebar();
    $present = renderAppSidebar([
        'brand' => new HtmlString('<strong>Lyra</strong>'),
        'footer' => new HtmlString('<a href="/account">Account</a>'),
    ]);

    expect($absent)->not->toContain('lyra-appsidebar__brand')
        ->and($absent)->not->toContain('lyra-appsidebar__footer')
        ->and($present)->toContain('<div class="lyra-appsidebar__brand"><strong>Lyra</strong></div>')
        ->and($present)->toContain('<div class="lyra-appsidebar__footer"><a href="/account">Account</a></div>');
});

it('composes data groups with permanent headings and item titles in both states', function (): void {
    $groups = [[
        'heading' => 'Workspace',
        'items' => [
            ['id' => 'overview', 'label' => 'Overview', 'active' => true],
            ['id' => 'reports', 'label' => 'Reports', 'badge' => 3],
        ],
    ]];
    $expanded = renderAppSidebar(['groups' => $groups]);
    $collapsed = renderAppSidebar([
        'groups' => $groups,
        'defaultCollapsed' => true,
    ]);

    foreach ([$expanded, $collapsed] as $html) {
        expect($html)->toContain('<div class="lyra-sbgroup__label">Workspace</div>')
            ->and($html)->toContain('data-id="overview"')
            ->and($html)->toContain('title="Overview"')
            ->and($html)->toContain('aria-current="page"')
            ->and($html)->toContain('data-id="reports"')
            ->and($html)->toContain('title="Reports"')
            ->and($html)->toContain('<span class="lyra-sbgroup__item-badge">3</span>');
    }
});

it('renders composition content inside the groups container after data groups', function (): void {
    $html = renderAppSidebar(
        ['groups' => [[
            'heading' => 'Main',
            'items' => [['id' => 'home', 'label' => 'Home']],
        ]]],
        new HtmlString('<a class="custom-link" href="/settings" title="Settings" aria-label="Settings">Settings</a>'),
    );
    $groupsStart = strpos($html, appSidebarOpeningTag($html, 'groups'));
    $groupsEnd = strpos($html, '</div>', strpos($html, 'class="custom-link"'));
    $groups = substr($html, $groupsStart, $groupsEnd - $groupsStart + strlen('</div>'));

    expect($groups)->toContain('data-id="home"')
        ->and($groups)->toContain('<a class="custom-link"')
        ->and(strpos($groups, 'data-id="home"'))->toBeLessThan(strpos($groups, '<a class="custom-link"'));
});

it('preserves consumer attributes with stateful values before consumer overrides', function (): void {
    $root = appSidebarOpeningTag(renderAppSidebar([
        'class' => 'consumer utility',
        'id' => 'primary-navigation',
        'data-track' => 'sidebar',
        'style' => 'color: red',
        'x-data' => 'consumerState',
    ]), 'root');

    expect($root)->toContain('class="lyra-appsidebar consumer utility"')
        ->and($root)->toContain('id="primary-navigation"')
        ->and($root)->toContain('data-track="sidebar"')
        ->and($root)->toMatch('/style="--appsidebar-width: 260px; width: var\(--appsidebar-width\);?\s*color: red;?"/')
        ->and(strpos($root, 'x-data="lyraAppSidebar('))->toBeLessThan(strpos($root, 'x-data="consumerState"'));
});
