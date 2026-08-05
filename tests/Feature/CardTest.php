<?php

use Illuminate\Support\Facades\Blade;

function renderCard(array $props = [], string $slot = 'Body'): string
{
    $title = $props['title'] ?? null;
    $actions = $props['actions'] ?? null;
    $footer = $props['footer'] ?? null;
    unset($props['title'], $props['actions'], $props['footer']);

    $attributes = collect($props)
        ->map(function (mixed $value, string $name): string {
            if (is_bool($value)) {
                return $value ? $name : sprintf(':%s="false"', $name);
            }

            return sprintf('%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES));
        })
        ->implode(' ');

    $slots = collect([
        'title' => $title,
        'actions' => $actions,
        'footer' => $footer,
    ])->filter(fn (?string $value): bool => $value !== null)
        ->map(fn (string $value, string $name): string => sprintf(
            '<x-slot:%s>%s</x-slot:%s>',
            $name,
            $value,
            $name,
        ))
        ->implode('');

    return Blade::render(sprintf(
        '<x-lyra::card %s>%s%s</x-lyra::card>',
        $attributes,
        $slot,
        $slots,
    ));
}

function cardClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function cardOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('card class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/card.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the card class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(cardClass(renderCard($case['props'])))->toBe($case['expected_class']);
})->with('card class emission');

it('renders body directly inside the root in the branch without title or footer', function (): void {
    $html = renderCard(slot: '<strong>Body</strong>');

    expect(trim($html))->toBe('<div class="lyra-card lyra-card--padded"><strong>Body</strong></div>')
        ->and($html)->not->toContain('lyra-card__body')
        ->and($html)->not->toContain('lyra-card__header')
        ->and($html)->not->toContain('lyra-card__footer');
});

it('renders title actions body and footer in React order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::card>
            <x-slot:title><span data-part="title">Title</span></x-slot:title>
            <x-slot:actions><button data-part="actions">Actions</button></x-slot:actions>
            <span data-part="body">Body</span>
            <x-slot:footer><span data-part="footer">Footer</span></x-slot:footer>
        </x-lyra::card>
        BLADE);

    $headerPosition = strpos($html, 'lyra-card__header');
    $titlePosition = strpos($html, 'lyra-card__title');
    $actionsPosition = strpos($html, 'lyra-card__actions');
    $bodyPosition = strpos($html, 'lyra-card__body');
    $footerPosition = strpos($html, 'lyra-card__footer');

    expect(cardClass($html))->toBe('lyra-card')
        ->and($html)->toContain('<h3 class="lyra-card__title"><span data-part="title">Title</span></h3>')
        ->and($html)->toContain('<div class="lyra-card__actions"><button data-part="actions">Actions</button></div>')
        ->and($html)->toContain('<div class="lyra-card__footer"><span data-part="footer">Footer</span></div>')
        ->and($headerPosition)->toBeInt()
        ->and($titlePosition)->toBeInt()
        ->and($actionsPosition)->toBeInt()
        ->and($bodyPosition)->toBeInt()
        ->and($footerPosition)->toBeInt()
        ->and($headerPosition)->toBeLessThan($titlePosition)
        ->and($titlePosition)->toBeLessThan($actionsPosition)
        ->and($actionsPosition)->toBeLessThan($bodyPosition)
        ->and($bodyPosition)->toBeLessThan($footerPosition);
});

it('always renders the structured body wrapper and gates its class on padded', function (): void {
    $padded = renderCard(['title' => 'Title']);
    $unpadded = renderCard(['footer' => 'Footer', 'padded' => false]);

    expect($padded)->toContain('<div class="lyra-card__body">Body</div>')
        ->and($unpadded)->toContain('<div>Body</div>')
        ->and($unpadded)->not->toMatch('/<div[^>]*class="[^"]*lyra-card__body/');
});

it('ignores actions when title is absent', function (): void {
    $html = renderCard(['actions' => '<button>Ignored</button>', 'footer' => 'Footer']);

    expect($html)->not->toContain('lyra-card__header')
        ->and($html)->not->toContain('lyra-card__actions')
        ->and($html)->not->toContain('Ignored')
        ->and($html)->toContain('lyra-card__footer');
});

it('treats empty title and footer slots as absent', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::card>
            <x-slot:title></x-slot:title>
            <x-slot:footer></x-slot:footer>
            Body
        </x-lyra::card>
        BLADE);

    expect(cardClass($html))->toBe('lyra-card lyra-card--padded')
        ->and($html)->not->toContain('lyra-card__header')
        ->and($html)->not->toContain('lyra-card__body')
        ->and($html)->not->toContain('lyra-card__footer');
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::card :padded="false" interactive class="x y" id="account-card" data-track="card" aria-label="Account">Body</x-lyra::card>');
    $openingTag = cardOpeningTag($html);

    expect(cardClass($html))->toBe('lyra-card lyra-card--interactive x y')
        ->and($openingTag)->toContain('id="account-card"')
        ->and($openingTag)->toContain('data-track="card"')
        ->and($openingTag)->toContain('aria-label="Account"');
});
