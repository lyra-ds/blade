<?php

use Illuminate\Support\Facades\Blade;

function renderPageHeader(array $props = [], array $slots = [], string $slot = ''): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
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
        '<x-lyra::page-header %s>%s%s</x-lyra::page-header>',
        $attributes,
        $slot,
        $namedSlots,
    ));
}

function pageHeaderClass(string $html): string
{
    $matched = preg_match('/<header\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function pageHeaderOpeningTag(string $html): string
{
    $matched = preg_match('/<header\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('page header class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/page-header.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the page-header class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(pageHeaderClass(renderPageHeader($case['props'])))->toBe($case['expected_class']);
})->with('page header class emission');

it('renders the default h1 inside the row text without optional wrappers', function (): void {
    $html = renderPageHeader(['title' => 'Reports']);

    expect($html)->toContain('<div class="lyra-pageheader__row">')
        ->and($html)->toContain('<div class="lyra-pageheader__text">')
        ->and($html)->toContain('<h1 class="lyra-pageheader__title">Reports</h1>')
        ->and($html)->not->toContain('lyra-pageheader__eyebrow')
        ->and($html)->not->toContain('lyra-pageheader__desc')
        ->and($html)->not->toContain('lyra-pageheader__actions');
});

it('renders the requested title element', function (string $titleAs): void {
    $html = renderPageHeader([
        'title' => 'Reports',
        'title-as' => $titleAs,
    ]);

    expect($html)->toContain(sprintf(
        '<%1$s class="lyra-pageheader__title">Reports</%1$s>',
        $titleAs,
    ));
})->with(['h2', 'h3']);

it('renders the eyebrow wrapper only when the eyebrow slot has content', function (): void {
    $html = renderPageHeader(
        ['title' => 'Reports'],
        ['eyebrow' => '<strong>Analytics</strong>'],
    );

    expect($html)->toContain('<span class="lyra-pageheader__eyebrow"><strong>Analytics</strong></span>')
        ->and($html)->not->toContain('lyra-pageheader__desc')
        ->and($html)->not->toContain('lyra-pageheader__actions');
});

it('renders the description wrapper only when the description slot has content', function (): void {
    $html = renderPageHeader(
        ['title' => 'Reports'],
        ['description' => 'Review performance'],
    );

    expect($html)->toContain('<p class="lyra-pageheader__desc">Review performance</p>')
        ->and($html)->not->toContain('lyra-pageheader__eyebrow')
        ->and($html)->not->toContain('lyra-pageheader__actions');
});

it('renders the actions wrapper only when the actions slot has content', function (): void {
    $html = renderPageHeader(
        ['title' => 'Reports'],
        ['actions' => '<button type="button">Export</button>'],
    );

    expect($html)->toContain('<div class="lyra-pageheader__actions"><button type="button">Export</button></div>')
        ->and($html)->not->toContain('lyra-pageheader__eyebrow')
        ->and($html)->not->toContain('lyra-pageheader__desc');
});

it('does not render wrappers for whitespace-only named slots', function (): void {
    $html = renderPageHeader(
        ['title' => 'Reports'],
        [
            'eyebrow' => '   ',
            'description' => "\n",
            'actions' => "\t",
        ],
    );

    expect($html)->not->toContain('lyra-pageheader__eyebrow')
        ->and($html)->not->toContain('lyra-pageheader__desc')
        ->and($html)->not->toContain('lyra-pageheader__actions');
});

it('renders default slot content after the row', function (): void {
    $html = renderPageHeader(
        ['title' => 'Reports'],
        slot: '<nav data-secondary>Sections</nav>',
    );
    $rowPosition = strpos($html, '<div class="lyra-pageheader__row">');
    $secondaryPosition = strpos($html, '<nav data-secondary>Sections</nav>');
    $rowClosePosition = $secondaryPosition === false
        ? false
        : strrpos(substr($html, 0, $secondaryPosition), '</div>');

    expect($rowPosition)->toBeInt()
        ->and($rowClosePosition)->toBeInt()
        ->and($secondaryPosition)->toBeInt()
        ->and($rowPosition)->toBeLessThan($rowClosePosition)
        ->and($rowClosePosition)->toBeLessThan($secondaryPosition);
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = renderPageHeader([
        'title' => 'Reports',
        'class' => 'x y',
        'id' => 'reports-header',
        'data-track' => 'page-header',
        'aria-label' => 'Reports overview',
    ]);
    $openingTag = pageHeaderOpeningTag($html);

    expect(pageHeaderClass($html))->toBe('lyra-pageheader x y')
        ->and($openingTag)->toContain('id="reports-header"')
        ->and($openingTag)->toContain('data-track="page-header"')
        ->and($openingTag)->toContain('aria-label="Reports overview"');
});
