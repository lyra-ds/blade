<?php

use Illuminate\Support\Facades\Blade;

function renderActionBar(array $props = [], string $slot = '', ?string $actions = null): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $actionsSlot = $actions === null ? '' : sprintf("<x-slot:actions>%s</x-slot:actions>\n", $actions);

    return Blade::render(sprintf(
        '<x-lyra::action-bar %s>%s%s</x-lyra::action-bar>',
        $attributes,
        $actionsSlot,
        $slot,
    ));
}

function actionBarRootClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function actionBarRootOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function actionBarClassEmissionCases(): array
{
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/action-bar.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the action-bar class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
}

dataset('action bar class emission', fn (): array => actionBarClassEmissionCases());

it('emits the exact React action bar class string', function (array $case): void {
    expect(actionBarRootClass(renderActionBar($case['props'])))->toBe($case['expected_class']);
})->with('action bar class emission');

it('renders nothing when closed or when count is explicitly zero', function (): void {
    expect(trim(Blade::render('<x-lyra::action-bar :open="false">Content</x-lyra::action-bar>')))->toBe('')
        ->and(trim(Blade::render('<x-lyra::action-bar :count="0">Content</x-lyra::action-bar>')))->toBe('');
});

it('renders without a count and always includes the actions span', function (): void {
    $html = renderActionBar(slot: 'Bulk');

    expect($html)->not->toContain('lyra-actionbar__count')
        ->and($html)->toContain('Bulk')
        ->and($html)->toContain('<span class="lyra-actionbar__actions"></span>');
});

it('renders the count structure with the default and custom labels', function (): void {
    $defaultLabel = Blade::render('<x-lyra::action-bar :count="3" />');
    $customLabel = Blade::render('<x-lyra::action-bar :count="3" label="items" />');

    expect($defaultLabel)->toContain('<span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>3</strong> selected</span>')
        ->and($customLabel)->toContain('<span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>3</strong> items</span>');
});

it('renders default content between the count and actions', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::action-bar :count="3">
            Bulk
            <x-slot:actions><button type="button">Apply</button></x-slot:actions>
        </x-lyra::action-bar>
        BLADE);
    $countPosition = strpos($html, 'lyra-actionbar__count');
    $contentPosition = strpos($html, 'Bulk');
    $actionsPosition = strpos($html, 'lyra-actionbar__actions');

    expect($countPosition)->toBeInt()
        ->and($contentPosition)->toBeInt()
        ->and($actionsPosition)->toBeInt()
        ->and($countPosition)->toBeLessThan($contentPosition)
        ->and($contentPosition)->toBeLessThan($actionsPosition)
        ->and($html)->toContain('<button type="button">Apply</button>');
});

it('omits the interactive clear button', function (): void {
    expect(renderActionBar(['count' => 2], slot: 'Bulk', actions: 'Apply'))
        ->not->toContain('lyra-actionbar__clear');
});

it('defaults to toolbar role while allowing a user role override and passing attributes through', function (): void {
    $defaultOpeningTag = actionBarRootOpeningTag(renderActionBar());
    $html = Blade::render('<x-lyra::action-bar class="x y" role="region" id="selection" data-track="bulk">Bulk</x-lyra::action-bar>');
    $openingTag = actionBarRootOpeningTag($html);

    expect($defaultOpeningTag)->toContain('role="toolbar"')
        ->and(actionBarRootClass($html))->toBe('lyra-actionbar x y')
        ->and($openingTag)->toContain('role="region"')
        ->and($openingTag)->not->toContain('role="toolbar"')
        ->and(substr_count($openingTag, 'role='))->toBe(1)
        ->and($openingTag)->toContain('id="selection"')
        ->and($openingTag)->toContain('data-track="bulk"');
});
