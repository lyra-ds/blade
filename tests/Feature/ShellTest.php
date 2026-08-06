<?php

use Illuminate\Support\Facades\Blade;

function renderShell(array $props = [], array $slots = [], string $slot = 'Body'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            str($name)->kebab(),
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $namedSlots = collect($slots)
        ->map(fn (string $value, string $name): string => sprintf(
            '<x-slot:%s>%s</x-slot:%s>',
            $name,
            $value,
            $name,
        ))
        ->implode('');

    return Blade::render(sprintf(
        '<x-lyra::shell %s>%s%s</x-lyra::shell>',
        $attributes,
        $slot,
        $namedSlots,
    ));
}

function shellClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function shellOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('shell class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/shell.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the shell class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(shellClass(renderShell($case['props'])))->toBe($case['expected_class']);
})->with('shell class emission');

it('renders the base app frame without optional rails topbar or style', function (): void {
    $html = renderShell();

    expect(shellOpeningTag($html))->toBe('<div class="lyra-shell lyra-shell--page">')
        ->and($html)->toContain('<main class="lyra-shell__main">')
        ->and($html)->toContain('<div class="lyra-shell__content">Body</div>')
        ->and($html)->not->toContain('lyra-shell__sidebar')
        ->and($html)->not->toContain('lyra-shell__aside')
        ->and($html)->not->toContain('lyra-shell__topbar')
        ->and($html)->not->toContain('style=');
});

it('adds rail modifiers only for non-empty rail slots', function (): void {
    $withRails = renderShell(slots: [
        'sidebar' => '<span>Menu</span>',
        'aside' => '<span>Details</span>',
    ]);
    $withEmptyRails = renderShell(slots: [
        'sidebar' => " \n",
        'aside' => "\t",
    ]);

    expect(shellClass($withRails))->toBe(
        'lyra-shell lyra-shell--page lyra-shell--has-sidebar lyra-shell--has-aside',
    )
        ->and(shellClass($withEmptyRails))->toBe('lyra-shell lyra-shell--page')
        ->and($withEmptyRails)->not->toContain('lyra-shell__sidebar')
        ->and($withEmptyRails)->not->toContain('lyra-shell__aside');
});

it('renders rail elements as aside by default and nav when configured', function (): void {
    $default = renderShell(slots: [
        'sidebar' => 'Menu',
        'aside' => 'Details',
    ]);
    $nav = renderShell(
        ['sidebarAs' => 'nav', 'asideAs' => 'nav'],
        ['sidebar' => 'Menu', 'aside' => 'Details'],
    );

    expect($default)->toContain('<aside class="lyra-shell__sidebar">Menu</aside>')
        ->and($default)->toContain('<aside class="lyra-shell__aside">Details</aside>')
        ->and($nav)->toContain('<nav class="lyra-shell__sidebar">Menu</nav>')
        ->and($nav)->toContain('<nav class="lyra-shell__aside">Details</nav>');
});

it('adds rail aria labels only when supplied', function (): void {
    $withoutLabels = renderShell(slots: [
        'sidebar' => 'Menu',
        'aside' => 'Details',
    ]);
    $withLabels = renderShell(
        ['sidebarLabel' => 'Primary', 'asideLabel' => 'Context'],
        ['sidebar' => 'Menu', 'aside' => 'Details'],
    );

    expect($withoutLabels)->not->toContain('aria-label=')
        ->and($withLabels)->toContain('<aside class="lyra-shell__sidebar" aria-label="Primary">')
        ->and($withLabels)->toContain('<aside class="lyra-shell__aside" aria-label="Context">');
});

it('renders the main region as main by default and div when configured', function (): void {
    expect(renderShell())->toContain('<main class="lyra-shell__main">')
        ->and(renderShell(['mainAs' => 'div']))->toContain('<div class="lyra-shell__main">');
});

it('renders a topbar wrapper only for a non-empty topbar slot', function (): void {
    $withTopbar = renderShell(slots: ['topbar' => '<header>Tools</header>']);
    $withEmptyTopbar = renderShell(slots: ['topbar' => " \n"]);

    expect($withTopbar)->toContain('<div class="lyra-shell__topbar"><header>Tools</header></div>')
        ->and($withEmptyTopbar)->not->toContain('lyra-shell__topbar');
});

it('always wraps the default slot in the content region', function (): void {
    expect(renderShell(slot: '<article>Content</article>'))
        ->toContain('<div class="lyra-shell__content"><article>Content</article></div>');
});

it('emits CSS variables in fixed order without a user style', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::shell :sidebar-width="280" :aside-width="320" :top="64">Body</x-lyra::shell>
        BLADE);

    expect(shellOpeningTag($html))->toContain(
        'style="--shell-sidebar: 280px; --shell-aside: 320px; --shell-top: 64px"',
    );
});

it('appends user style after the ordered CSS variables', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::shell :sidebar-width="280" :top="64" style="color:red">Body</x-lyra::shell>
        BLADE);

    expect(shellOpeningTag($html))->toMatch(
        '/style="--shell-sidebar: 280px; --shell-top: 64px;?\s*color:red;?"/',
    );
});

it('preserves a user style when no CSS variables are configured', function (): void {
    expect(shellOpeningTag(renderShell(['style' => 'color:red'])))
        ->toContain('style="color:red;"');
});

it('renders rails in sidebar main aside order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::shell>
            Body
            <x-slot:aside>Details</x-slot:aside>
            <x-slot:sidebar>Menu</x-slot:sidebar>
        </x-lyra::shell>
        BLADE);
    $sidebarPosition = strpos($html, 'lyra-shell__sidebar');
    $mainPosition = strpos($html, 'lyra-shell__main');
    $asidePosition = strpos($html, 'lyra-shell__aside');

    expect($sidebarPosition)->toBeInt()
        ->and($mainPosition)->toBeInt()
        ->and($asidePosition)->toBeInt()
        ->and($sidebarPosition)->toBeLessThan($mainPosition)
        ->and($mainPosition)->toBeLessThan($asidePosition);
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = renderShell([
        'scroll' => 'content',
        'class' => 'x y',
        'id' => 'app-shell',
        'data-track' => 'shell',
        'aria-describedby' => 'shell-note',
    ], [
        'sidebar' => 'Menu',
        'aside' => 'Details',
    ]);
    $openingTag = shellOpeningTag($html);

    expect(shellClass($html))->toBe(
        'lyra-shell lyra-shell--content lyra-shell--has-sidebar lyra-shell--has-aside x y',
    )
        ->and($openingTag)->toContain('id="app-shell"')
        ->and($openingTag)->toContain('data-track="shell"')
        ->and($openingTag)->toContain('aria-describedby="shell-note"');
});
