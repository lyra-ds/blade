<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderWorkspaceSwitcher(array $props = []): string
{
    $workspaces = $props['workspaces'] ?? [];
    $current = $props['current'] ?? null;
    $defaultOpen = $props['defaultOpen'] ?? false;
    $create = $props['create'] ?? false;
    $createLabel = $props['createLabel'] ?? 'Create workspace';
    $createId = $props['createId'] ?? 'create';
    unset(
        $props['workspaces'],
        $props['current'],
        $props['defaultOpen'],
        $props['create'],
        $props['createLabel'],
        $props['createId'],
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
            '<x-lyra::workspace-switcher :workspaces="$workspaces" :current="$current" :default-open="$defaultOpen" :create="$create" :create-label="$createLabel" :create-id="$createId" %s />',
            $attributes,
        ),
        compact('workspaces', 'current', 'defaultOpen', 'create', 'createLabel', 'createId'),
    );
}

function workspaceSwitcherOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-wssw(?: [^"]*)?")[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-wssw__trigger")[^>]*>/',
        'popover' => '/<div\b(?=[^>]*\bclass="lyra-wssw__pop")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function workspaceSwitcherClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-wssw(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

/**
 * @return list<string>
 */
function workspaceSwitcherOptionTags(string $html): array
{
    $matched = preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-wssw__item(?: [^"]*)?")[^>]*>/',
        $html,
        $matches,
    );

    expect($matched)->not->toBeFalse();

    return $matches[0];
}

/**
 * @return list<string>
 */
function workspaceSwitcherOptionBlocks(string $html): array
{
    $matched = preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-wssw__item(?: [^"]*)?")[^>]*>.*?<\/button>/s',
        $html,
        $matches,
    );

    expect($matched)->not->toBeFalse();

    return $matches[0];
}

dataset('workspace switcher class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/workspace-switcher.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the workspace-switcher class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(workspaceSwitcherClass(renderWorkspaceSwitcher($case['props'])))->toBe($case['expected_class']);
})->with('workspace switcher class emission');

it('renders namespaced and short syntax identically', function (): void {
    $workspaces = [
        ['id' => 'design', 'name' => 'Design', 'plan' => 'Pro', 'members' => 4],
        ['id' => 'engineering', 'name' => 'Engineering'],
    ];
    $namespaced = Blade::render(
        '<x-lyra::workspace-switcher id="workspace-menu" :workspaces="$workspaces" current="design" create />',
        compact('workspaces'),
    );
    $short = Blade::render(
        '<lyra:workspace-switcher id="workspace-menu" :workspaces="$workspaces" current="design" create />',
        compact('workspaces'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-wssw"');
});

it('serves generated and consumer ids with the modelable Alpine root contract', function (): void {
    $generatedHtml = renderWorkspaceSwitcher();
    $generated = workspaceSwitcherOpeningTag($generatedHtml, 'root');
    $generatedTrigger = workspaceSwitcherOpeningTag($generatedHtml, 'trigger');
    $generatedPopover = workspaceSwitcherOpeningTag($generatedHtml, 'popover');
    $matched = preg_match('/\bid="([^"]+)"/', $generated, $matches);
    $provided = workspaceSwitcherOpeningTag(renderWorkspaceSwitcher([
        'id' => 'workspace-menu',
        'data-track' => 'workspace-switcher',
    ]), 'root');

    expect($matched)->toBe(1)
        ->and($matches[1])->toStartWith('lyra-wssw-')
        ->and($generatedTrigger)->toContain('aria-controls="'.$matches[1].'-listbox"')
        ->and($generatedPopover)->toContain('id="'.$matches[1].'-listbox"')
        ->and($generatedPopover)->toContain('aria-labelledby="'.$matches[1].'-listbox-label"')
        ->and($generated)->toContain('x-data="lyraWorkspaceSwitcher({ defaultOpen: false })"')
        ->and($generated)->toContain('x-modelable="open"')
        ->and($provided)->toContain('id="workspace-menu"')
        ->and(substr_count($provided, 'id="workspace-menu"'))->toBe(1)
        ->and($provided)->toContain('data-track="workspace-switcher"');
});

it('keeps component-owned Alpine state ahead of consumer attributes', function (): void {
    $root = workspaceSwitcherOpeningTag(renderWorkspaceSwitcher([
        'x-data' => 'consumerState',
    ]), 'root');
    $componentPosition = strpos($root, 'x-data="lyraWorkspaceSwitcher({ defaultOpen: false })"');
    $consumerPosition = strpos($root, 'x-data="consumerState"');

    expect($componentPosition)->toBeInt()
        ->and($consumerPosition)->toBeInt()
        ->and($componentPosition)->toBeLessThan($consumerPosition);
});

it('renders the empty trigger and complete served trigger contract', function (): void {
    $html = renderWorkspaceSwitcher(['id' => 'workspace-menu']);
    $trigger = workspaceSwitcherOpeningTag($html, 'trigger');

    expect($trigger)->toContain('type="button"')
        ->and($trigger)->toContain('class="lyra-wssw__trigger"')
        ->and($trigger)->toContain('aria-haspopup="listbox"')
        ->and($trigger)->toContain('aria-expanded="false"')
        ->and($trigger)->toContain('aria-controls="workspace-menu-listbox"')
        ->and($trigger)->toContain('x-bind="trigger"')
        ->and($html)->toContain('title="?"')
        ->and($html)->toContain('lyra-avatar lyra-avatar--sm lyra-avatar--square')
        ->and($html)->toContain('<span class="lyra-wssw__id">')
        ->and($html)->toContain('<span class="lyra-wssw__name">Select workspace</span>')
        ->and($html)->not->toContain('lyra-wssw__plan')
        ->and($html)->toMatch('/<svg\b(?=[^>]*width="15")(?=[^>]*height="15")(?=[^>]*stroke="var\(--text-faint\)")[^>]*>/');
});

it('selects current by id and renders its trigger identity', function (): void {
    $html = renderWorkspaceSwitcher([
        'workspaces' => [
            ['id' => 'design', 'name' => 'Design', 'plan' => 'Starter'],
            ['id' => 'engineering', 'name' => 'Engineering', 'plan' => 'Enterprise'],
        ],
        'current' => 'engineering',
    ]);
    $triggerEnd = strpos($html, '</button>');
    $triggerContent = substr($html, 0, $triggerEnd === false ? 0 : $triggerEnd);

    expect($triggerContent)->toContain('title="Engineering"')
        ->and($triggerContent)->toContain('<span class="lyra-wssw__name">Engineering</span>')
        ->and($triggerContent)->toContain('<span class="lyra-wssw__plan">Enterprise</span>')
        ->and($triggerContent)->not->toContain('Starter');
});

it('falls selection back to the first workspace', function (?string $current): void {
    $html = renderWorkspaceSwitcher([
        'workspaces' => [
            ['id' => 'design', 'name' => 'Design'],
            ['id' => 'engineering', 'name' => 'Engineering'],
        ],
        'current' => $current,
    ]);
    $options = workspaceSwitcherOptionTags($html);

    expect($html)->toContain('<span class="lyra-wssw__name">Design</span>')
        ->and($options[0])->toContain('aria-selected="true"')
        ->and($options[1])->toContain('aria-selected="false"');
})->with([
    'absent current' => null,
    'unmatched current' => 'missing',
]);

it('serves the labelled listbox and cloaks only the closed initial state', function (): void {
    $closed = renderWorkspaceSwitcher(['id' => 'workspace-menu']);
    $open = renderWorkspaceSwitcher([
        'id' => 'workspace-menu',
        'defaultOpen' => true,
    ]);
    $closedRoot = workspaceSwitcherOpeningTag($closed, 'root');
    $closedPopover = workspaceSwitcherOpeningTag($closed, 'popover');
    $openRoot = workspaceSwitcherOpeningTag($open, 'root');
    $openTrigger = workspaceSwitcherOpeningTag($open, 'trigger');
    $openPopover = workspaceSwitcherOpeningTag($open, 'popover');

    expect($closedPopover)->toContain('id="workspace-menu-listbox"')
        ->and($closedPopover)->toContain('class="lyra-wssw__pop"')
        ->and($closedPopover)->toContain('role="listbox"')
        ->and($closedPopover)->toContain('x-bind="popover"')
        ->and($closedPopover)->toContain('aria-labelledby="workspace-menu-listbox-label"')
        ->and($closedPopover)->toContain('x-cloak')
        ->and($closedPopover)->not->toContain('lyra-wssw__pop--up')
        ->and($closed)->toMatch('/<div\b[^>]*class="lyra-wssw__pop"[^>]*>\s*<span id="workspace-menu-listbox-label" class="lyra-wssw__pop-label">Workspaces<\/span>/s')
        ->and($closedRoot)->toContain('x-data="lyraWorkspaceSwitcher({ defaultOpen: false })"')
        ->and($openRoot)->toContain('x-data="lyraWorkspaceSwitcher({ defaultOpen: true })"')
        ->and($openTrigger)->toContain('aria-expanded="true"')
        ->and($openPopover)->not->toContain('x-cloak')
        ->and($openPopover)->not->toContain('lyra-wssw__pop--up');
});

it('renders every workspace option with binding selectors and selected-only check icon', function (): void {
    $html = renderWorkspaceSwitcher([
        'workspaces' => [
            ['id' => 'design', 'name' => 'Design'],
            ['id' => 'engineering', 'name' => 'Engineering'],
        ],
        'current' => 'engineering',
    ]);
    $tags = workspaceSwitcherOptionTags($html);
    $blocks = workspaceSwitcherOptionBlocks($html);

    expect($tags)->toHaveCount(2)
        ->and($tags[0])->toContain('type="button"')
        ->and($tags[0])->toContain('role="option"')
        ->and($tags[0])->toContain('aria-selected="false"')
        ->and($tags[0])->toContain('class="lyra-wssw__item"')
        ->and($tags[0])->toContain('x-bind="option"')
        ->and($tags[0])->toContain('data-id="design"')
        ->and($tags[1])->toContain('aria-selected="true"')
        ->and($tags[1])->toContain('data-id="engineering"')
        ->and($blocks[0])->toContain('title="Design"')
        ->and($blocks[0])->toContain('<span class="lyra-wssw__name">Design</span>')
        ->and($blocks[0])->not->toContain('stroke="var(--accent)"')
        ->and($blocks[1])->toContain('title="Engineering"')
        ->and($blocks[1])->toMatch('/<svg\b(?=[^>]*width="15")(?=[^>]*height="15")(?=[^>]*stroke="var\(--accent\)")[^>]*>/');
});

it('renders metadata when plan or members is present and preserves zero members', function (): void {
    $html = renderWorkspaceSwitcher([
        'workspaces' => [
            ['id' => 'both', 'name' => 'Both', 'plan' => 'Pro', 'members' => 12],
            ['id' => 'plan', 'name' => 'Plan', 'plan' => 'Starter'],
            ['id' => 'zero', 'name' => 'Zero', 'members' => 0],
            ['id' => 'plain', 'name' => 'Plain'],
        ],
    ]);
    $blocks = workspaceSwitcherOptionBlocks($html);

    expect($blocks[0])->toContain('<span class="lyra-wssw__meta">Pro · 12 members</span>')
        ->and($blocks[1])->toContain('<span class="lyra-wssw__meta">Starter</span>')
        ->and($blocks[2])->toContain('<span class="lyra-wssw__meta">0 members</span>')
        ->and($blocks[3])->not->toContain('lyra-wssw__meta');
});

it('omits the create action unless enabled', function (): void {
    $html = renderWorkspaceSwitcher();

    expect($html)->not->toContain('lyra-wssw__sep')
        ->and($html)->not->toContain('lyra-wssw__create')
        ->and($html)->not->toContain('Create workspace');
});

it('renders the create action as a bound option with defaults and overrides', function (): void {
    $default = renderWorkspaceSwitcher(['create' => true]);
    $custom = renderWorkspaceSwitcher([
        'create' => true,
        'createLabel' => 'New team',
        'createId' => 'new-team',
    ]);
    $defaultTags = workspaceSwitcherOptionTags($default);
    $defaultBlocks = workspaceSwitcherOptionBlocks($default);
    $customTags = workspaceSwitcherOptionTags($custom);
    $customBlocks = workspaceSwitcherOptionBlocks($custom);

    expect($default)->toMatch('/<hr class="lyra-wssw__sep" role="presentation">\s*<button/s')
        ->and($defaultTags)->toHaveCount(1)
        ->and($defaultTags[0])->toContain('type="button"')
        ->and($defaultTags[0])->toContain('role="option"')
        ->and($defaultTags[0])->toContain('aria-selected="false"')
        ->and($defaultTags[0])->toContain('class="lyra-wssw__item lyra-wssw__create"')
        ->and($defaultTags[0])->toContain('x-bind="option"')
        ->and($defaultTags[0])->toContain('data-id="create"')
        ->and($defaultBlocks[0])->toContain('<span class="lyra-wssw__plus">')
        ->and($defaultBlocks[0])->toMatch('/<svg\b(?=[^>]*width="15")(?=[^>]*height="15")[^>]*>/')
        ->and($defaultBlocks[0])->toContain('<span class="lyra-wssw__create-label">Create workspace</span>')
        ->and($customTags[0])->toContain('data-id="new-team"')
        ->and($customBlocks[0])->toContain('<span class="lyra-wssw__create-label">New team</span>');
});

it('escapes workspace and create ids safely into data attributes', function (): void {
    $html = renderWorkspaceSwitcher([
        'workspaces' => [
            ['id' => 'team"<script>', 'name' => 'Unsafe'],
        ],
        'create' => true,
        'createId' => 'new"><team',
    ]);
    $tags = workspaceSwitcherOptionTags($html);

    expect($tags[0])->toContain('data-id="team&quot;&lt;script&gt;"')
        ->and($tags[1])->toContain('data-id="new&quot;&gt;&lt;team"')
        ->and(implode('', $tags))->not->toContain('<script>');
});

it('renders an open wire-modelled workspace switcher through Livewire', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public array $workspaces = [
            ['id' => 'design', 'name' => 'Design'],
        ];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::workspace-switcher :workspaces="$workspaces" :default-open="$open" wire:model="open" />
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = workspaceSwitcherOpeningTag($html, 'root');
    $popover = workspaceSwitcherOpeningTag($html, 'popover');

    expect($root)->toContain('wire:model="open"')
        ->and($root)->toContain('x-modelable="open"')
        ->and($root)->toContain('defaultOpen: true')
        ->and($popover)->not->toContain('x-cloak');
});
