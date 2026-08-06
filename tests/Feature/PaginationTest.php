<?php

use Illuminate\Support\Facades\Blade;

function renderPagination(array $props = []): string
{
    $page = $props['page'] ?? 5;
    $total = $props['total'] ?? 10;
    $url = $props['url'] ?? '/p?page={page}';
    unset($props['page'], $props['total'], $props['url']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf('<x-lyra::pagination :page="$page" :total="$total" :url="$url" %s />', $attributes),
        compact('page', 'total', 'url'),
    );
}

function paginationClass(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function paginationOpeningTag(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function paginationItems(string $html): array
{
    preg_match_all('/<(?:a|span)\b[^>]*>([^<]*)<\/(?:a|span)>/', $html, $matches);

    return array_map('trim', $matches[1]);
}

dataset('pagination class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/pagination.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the pagination class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

dataset('pagination windows', [
    'all pages when total is at most seven' => [5, 5, ['‹', '1', '2', '3', '4', '5', '›']],
    'leading window' => [2, 10, ['‹', '1', '2', '3', '4', '5', '…', '10', '›']],
    'trailing window' => [8, 10, ['‹', '1', '…', '6', '7', '8', '9', '10', '›']],
    'middle window' => [5, 10, ['‹', '1', '…', '4', '5', '6', '…', '10', '›']],
]);

it('emits the exact React root class string', function (array $case): void {
    expect(paginationClass(renderPagination($case['props'])))->toBe($case['expected_class']);
})->with('pagination class emission');

it('ports each React page-window branch exactly', function (int $page, int $total, array $expected): void {
    expect(paginationItems(renderPagination(compact('page', 'total'))))->toBe($expected);
})->with('pagination windows');

it('renders current, gap, previous, and next link markup with substituted urls', function (): void {
    $html = renderPagination();

    expect($html)->toContain('<a class="lyra-page" href="/p?page=4" aria-label="Previous page">‹</a>')
        ->and($html)->toContain('<a class="lyra-page lyra-page--active" href="/p?page=5" aria-current="page">5</a>')
        ->and($html)->toContain('<a class="lyra-page" href="/p?page=6">6</a>')
        ->and($html)->toContain('<span class="lyra-page lyra-page--gap" aria-hidden="true">…</span>')
        ->and($html)->toContain('<a class="lyra-page" href="/p?page=6" aria-label="Next page">›</a>')
        ->and(substr_count($html, 'class="lyra-page lyra-page--gap"'))->toBe(2)
        ->and($html)->not->toContain('{page}');
});

it('renders boundary controls as disabled spans and omits gaps for a short range', function (): void {
    $first = renderPagination(['page' => 1, 'total' => 3, 'class' => 'x']);
    $last = renderPagination(['page' => 3, 'total' => 3]);

    expect($first)->toContain('<span class="lyra-page" aria-disabled="true">‹</span>')
        ->and($first)->toContain('<a class="lyra-page lyra-page--active" href="/p?page=1" aria-current="page">1</a>')
        ->and($first)->not->toContain('lyra-page--gap')
        ->and(paginationClass($first))->toBe('lyra-pagination x')
        ->and($last)->toContain('<span class="lyra-page" aria-disabled="true">›</span>');
});

it('uses default accessible labels and allows every label to be overridden', function (): void {
    $defaultHtml = renderPagination(['page' => 2, 'total' => 3]);
    $overrideHtml = Blade::render(<<<'BLADE'
        <x-lyra::pagination
            :page="2"
            :total="3"
            url="/p?page={page}"
            aria-label="Paginação"
            previous-label="Página anterior"
            next-label="Próxima página"
        />
        BLADE);

    expect(paginationOpeningTag($defaultHtml))->toContain('aria-label="Pagination"')
        ->and($defaultHtml)->toContain('aria-label="Previous page"')
        ->and($defaultHtml)->toContain('aria-label="Next page"')
        ->and(paginationOpeningTag($overrideHtml))->toContain('aria-label="Paginação"')
        ->and(paginationOpeningTag($overrideHtml))->not->toContain('aria-label="Pagination"')
        ->and(substr_count(paginationOpeningTag($overrideHtml), 'aria-label='))->toBe(1)
        ->and($overrideHtml)->toContain('aria-label="Página anterior"')
        ->and($overrideHtml)->toContain('aria-label="Próxima página"');
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = renderPagination([
        'class' => 'first second',
        'id' => 'post-pages',
        'data-track' => 'pagination',
    ]);
    $openingTag = paginationOpeningTag($html);

    expect(paginationClass($html))->toBe('lyra-pagination first second')
        ->and($openingTag)->toContain('id="post-pages"')
        ->and($openingTag)->toContain('data-track="pagination"');
});
