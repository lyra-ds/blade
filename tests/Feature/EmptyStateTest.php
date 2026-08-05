<?php

use Illuminate\Support\Facades\Blade;

function renderEmptyState(array $props = []): string
{
    $icon = $props['icon'] ?? null;
    $title = $props['title'] ?? 'Nada aqui';
    $description = $props['description'] ?? null;
    $action = $props['action'] ?? null;
    unset($props['icon'], $props['title'], $props['description'], $props['action']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $slots = collect([
        'icon' => $icon,
        'title' => $title,
        'description' => $description,
        'action' => $action,
    ])->filter(fn (?string $content): bool => $content !== null)
        ->map(fn (string $content, string $name): string => sprintf(
            '<x-slot:%s>%s</x-slot:%s>',
            $name,
            $content,
            $name,
        ))
        ->implode('');

    return Blade::render(sprintf(
        '<x-lyra::empty-state %s>%s</x-lyra::empty-state>',
        $attributes,
        $slots,
    ));
}

function emptyStateOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function emptyStateRootClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('empty-state class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/empty-state.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the empty-state class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    expect(emptyStateRootClass(renderEmptyState($case['props'])))->toBe($case['expected_class']);
})->with('empty-state class emission');

it('always renders the title heading and omits absent optional content', function (): void {
    $html = renderEmptyState();
    $innerHtml = preg_replace('/^.*?<div\b[^>]*>|<\/div>\s*$/s', '', $html);

    expect($innerHtml)->not->toBeNull()
        ->and(preg_replace('/\s+/', '', $innerHtml))->toBe(
            '<h3class="lyra-empty__title">Nadaaqui</h3>',
        )
        ->and($html)->not->toContain('lyra-empty__icon')
        ->not->toContain('lyra-empty__desc')
        ->not->toContain('lyra-empty__action');
});

it('renders all content in the fixed contract order', function (): void {
    $html = renderEmptyState([
        'icon' => '<svg></svg>',
        'title' => 'T',
        'description' => 'D',
        'action' => '<button>A</button>',
    ]);
    $iconPosition = strpos($html, '<div class="lyra-empty__icon"><svg></svg></div>');
    $titlePosition = strpos($html, '<h3 class="lyra-empty__title">T</h3>');
    $descriptionPosition = strpos($html, '<p class="lyra-empty__desc">D</p>');
    $actionPosition = strpos($html, '<div class="lyra-empty__action"><button>A</button></div>');

    expect($iconPosition)->toBeInt()
        ->and($titlePosition)->toBeInt()->toBeGreaterThan($iconPosition)
        ->and($descriptionPosition)->toBeInt()->toBeGreaterThan($titlePosition)
        ->and($actionPosition)->toBeInt()->toBeGreaterThan($descriptionPosition);
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = renderEmptyState([
        'class' => 'x y',
        'id' => 'nothing-here',
        'data-track' => 'empty-state',
        'aria-live' => 'polite',
    ]);
    $openingTag = emptyStateOpeningTag($html);

    expect(emptyStateRootClass($html))->toBe('lyra-empty x y')
        ->and($openingTag)->toContain('id="nothing-here"')
        ->and($openingTag)->toContain('data-track="empty-state"')
        ->and($openingTag)->toContain('aria-live="polite"');
});
