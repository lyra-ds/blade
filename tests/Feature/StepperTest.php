<?php

use Illuminate\Support\Facades\Blade;

function renderStepper(array $props = []): string
{
    $steps = $props['steps'] ?? ['Account', 'Profile', 'Confirm'];
    $active = $props['active'] ?? 1;
    unset($props['steps'], $props['active']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf('<x-lyra::stepper :steps="$steps" :active="$active" %s />', $attributes),
        compact('steps', 'active'),
    );
}

function stepperClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function stepperOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function stepperElementClasses(string $html, string $baseClass): array
{
    $matched = preg_match_all(
        sprintf('/<span\b[^>]*\bclass="(%s(?: [^"]*)?)"/', preg_quote($baseClass, '/')),
        $html,
        $matches,
    );

    expect($matched)->not->toBeFalse();

    return $matches[1];
}

dataset('stepper class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/stepper.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the stepper class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(stepperClass(renderStepper($case['props'])))->toBe($case['expected_class']);
})->with('stepper class emission');

it('renders done, active, and pending steps with React markup and ordering', function (): void {
    $html = renderStepper();

    expect(stepperElementClasses($html, 'lyra-step'))->toBe([
        'lyra-step lyra-step--done',
        'lyra-step lyra-step--active',
        'lyra-step',
    ])->and(stepperElementClasses($html, 'lyra-step__line'))->toBe([
        'lyra-step__line lyra-step__line--done',
        'lyra-step__line',
    ])->and($html)
        ->toContain('<svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>')
        ->toMatch('/<span\s+class="lyra-step lyra-step--done"\s*>\s*<span class="lyra-step__dot">\s*<svg[^>]*>.*?<\/svg>\s*<\/span>\s*<span class="lyra-step__label">Account<\/span>/s')
        ->not->toMatch('/<span\s+class="lyra-step lyra-step--done"\s*>\s*<span class="lyra-step__dot">\s*1\s*<\/span>/s')
        ->toMatch('/<span\s+class="lyra-step lyra-step--active"\s+aria-current="step"\s*>\s*<span class="lyra-step__dot">\s*2\s*<\/span>\s*<span class="lyra-step__label">Profile<\/span>/s')
        ->toMatch('/<span\s+class="lyra-step"\s*>\s*<span class="lyra-step__dot">\s*3\s*<\/span>\s*<span class="lyra-step__label">Confirm<\/span>/s');

    expect(substr_count($html, 'class="lyra-step__line'))->toBe(2)
        ->and(substr_count($html, 'class="lyra-step__line lyra-step__line--done"'))->toBe(1)
        ->and(substr_count($html, 'aria-current="step"'))->toBe(1);

    $firstStep = strpos($html, 'class="lyra-step lyra-step--done"');
    $firstLine = strpos($html, 'class="lyra-step__line lyra-step__line--done"');
    $activeStep = strpos($html, 'class="lyra-step lyra-step--active"');
    $secondLine = strpos($html, 'class="lyra-step__line"', $firstLine + 1);
    $pendingStep = strpos($html, 'class="lyra-step"', $activeStep + 1);

    expect($firstStep)->toBeInt()
        ->and($firstLine)->toBeInt()
        ->and($activeStep)->toBeInt()
        ->and($secondLine)->toBeInt()
        ->and($pendingStep)->toBeInt()
        ->and($firstStep)->toBeLessThan($firstLine)
        ->and($firstLine)->toBeLessThan($activeStep)
        ->and($activeStep)->toBeLessThan($secondLine)
        ->and($secondLine)->toBeLessThan($pendingStep);
});

it('renders no done steps or lines when the first step is active', function (): void {
    $html = renderStepper(['active' => 0]);

    expect(substr_count($html, 'lyra-step--active'))->toBe(1)
        ->and($html)->not->toContain('lyra-step--done')
        ->and($html)->not->toContain('lyra-step__line--done');
});

it('renders every step and connector as done when active is beyond the last step', function (): void {
    $html = renderStepper(['active' => 3]);

    expect(substr_count($html, 'lyra-step--done'))->toBe(3)
        ->and(substr_count($html, 'lyra-step__line--done'))->toBe(2)
        ->and($html)->not->toContain('lyra-step--active')
        ->and($html)->not->toContain('aria-current="step"')
        ->and(substr_count($html, '<svg aria-hidden="true"'))->toBe(3);
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = renderStepper([
        'class' => 'x y',
        'id' => 'checkout-progress',
        'data-track' => 'stepper',
    ]);
    $openingTag = stepperOpeningTag($html);

    expect(stepperClass($html))->toBe('lyra-stepper x y')
        ->and($openingTag)->toContain('id="checkout-progress"')
        ->and($openingTag)->toContain('data-track="stepper"');
});
