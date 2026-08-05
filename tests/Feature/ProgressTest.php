<?php

use Illuminate\Support\Facades\Blade;

function renderProgress(array $props): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::progress %s />', $attributes));
}

function progressClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function progressOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function progressFillTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="lyra-progress__fill"[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('progress class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/progress.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the progress class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(progressClass(renderProgress($case['props'])))->toBe($case['expected_class']);
})->with('progress class emission');

it('renders the progressbar aria contract and fill width', function (): void {
    $html = Blade::render('<x-lyra::progress :value="30" />');
    $openingTag = progressOpeningTag($html);

    expect(progressClass($html))->toBe('lyra-progress')
        ->and($openingTag)->toContain('role="progressbar"')
        ->and($openingTag)->toContain('aria-valuenow="30"')
        ->and($openingTag)->toContain('aria-valuemin="0"')
        ->and($openingTag)->toContain('aria-valuemax="100"')
        ->and(progressFillTag($html))->toContain('style="width: 30%"');
});

it('clamps values over 100', function (): void {
    $html = Blade::render('<x-lyra::progress :value="150" tone="danger" class="x" />');

    expect(progressClass($html))->toBe('lyra-progress lyra-progress--danger x')
        ->and(progressOpeningTag($html))->toContain('aria-valuenow="100"')
        ->and(progressFillTag($html))->toContain('style="width: 100%"');
});

it('clamps values below zero', function (): void {
    $html = Blade::render('<x-lyra::progress :value="-5" />');

    expect(progressOpeningTag($html))->toContain('aria-valuenow="0"')
        ->and(progressFillTag($html))->toContain('style="width: 0%"');
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::progress :value="45" tone="success" class="first second" id="upload" data-track="progress" />
        BLADE);
    $openingTag = progressOpeningTag($html);

    expect(progressClass($html))->toBe('lyra-progress lyra-progress--success first second')
        ->and($openingTag)->toContain('id="upload"')
        ->and($openingTag)->toContain('data-track="progress"');
});
