<?php

use Illuminate\Support\Facades\Blade;

function renderFooter(array $props = [], array $slots = [], string $slot = ''): string
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
        '<x-lyra::footer %s>%s%s</x-lyra::footer>',
        $attributes,
        $slot,
        $namedSlots,
    ));
}

function footerClass(string $html): string
{
    $matched = preg_match('/<footer\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function footerOpeningTag(string $html): string
{
    $matched = preg_match('/<footer\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('footer class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/footer.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the footer class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(footerClass(renderFooter($case['props'])))->toBe($case['expected_class']);
})->with('footer class emission');

it('renders only the container when no named slots are provided', function (): void {
    $html = renderFooter(slot: '<span data-default>Ignored</span>');

    expect($html)->toContain('<footer class="lyra-footer">')
        ->and($html)->toContain('<div class="lyra-container lyra-footer__inner">')
        ->and($html)->not->toContain('lyra-footer__brand')
        ->and($html)->not->toContain('lyra-footer__note')
        ->and($html)->not->toContain('lyra-footer__links')
        ->and($html)->not->toContain('data-default');
});

it('renders the brand wrapper only when the brand slot is provided', function (): void {
    $html = renderFooter(slots: ['brand' => '<strong>Lyra</strong>']);

    expect($html)->toContain('<div class="lyra-footer__brand"><strong>Lyra</strong></div>')
        ->and($html)->not->toContain('lyra-footer__note')
        ->and($html)->not->toContain('lyra-footer__links');
});

it('renders the note wrapper only when the note slot is provided', function (): void {
    $html = renderFooter(slots: ['note' => '<small>Fine print</small>']);

    expect($html)->toContain('<div class="lyra-footer__note"><small>Fine print</small></div>')
        ->and($html)->not->toContain('lyra-footer__brand')
        ->and($html)->not->toContain('lyra-footer__links');
});

it('renders the links wrapper only when the links slot is provided', function (): void {
    $html = renderFooter(slots: ['links' => '<a href="/docs">Docs</a>']);

    expect($html)->toContain('<nav class="lyra-footer__links"><a href="/docs">Docs</a></nav>')
        ->and($html)->not->toContain('lyra-footer__brand')
        ->and($html)->not->toContain('lyra-footer__note')
        ->and($html)->not->toContain('aria-label=');
});

it('renders named slots in React order regardless of authoring order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::footer links-label="Resources">
            <x-slot:links><a href="/docs">Docs</a></x-slot:links>
            <x-slot:note><small>Fine print</small></x-slot:note>
            <x-slot:brand><strong>Lyra</strong></x-slot:brand>
        </x-lyra::footer>
        BLADE);

    $brandPosition = strpos($html, 'lyra-footer__brand');
    $notePosition = strpos($html, 'lyra-footer__note');
    $linksPosition = strpos($html, 'lyra-footer__links');

    expect($brandPosition)->toBeInt()
        ->and($notePosition)->toBeInt()
        ->and($linksPosition)->toBeInt()
        ->and($brandPosition)->toBeLessThan($notePosition)
        ->and($notePosition)->toBeLessThan($linksPosition)
        ->and($html)->toContain('<nav class="lyra-footer__links" aria-label="Resources"><a href="/docs">Docs</a></nav>');
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::footer class="x y" id="site-footer" data-id="1" aria-describedby="footer-note" />');
    $openingTag = footerOpeningTag($html);

    expect(footerClass($html))->toBe('lyra-footer x y')
        ->and($openingTag)->toContain('id="site-footer"')
        ->and($openingTag)->toContain('data-id="1"')
        ->and($openingTag)->toContain('aria-describedby="footer-note"');
});
