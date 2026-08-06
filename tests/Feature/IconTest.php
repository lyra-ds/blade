<?php

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\IconRegistry;

function renderIcon(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::icon %s />', $attributes));
}

function iconOpeningTag(string $html): string
{
    $matched = preg_match('/<svg\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function iconClass(string $html): string
{
    $openingTag = iconOpeningTag($html);
    $matched = preg_match('/\bclass="([^"]*)"/', $openingTag, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('icon class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/icon.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the icon class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(iconClass(renderIcon($case['props'])))->toBe($case['expected_class']);
})->with('icon class emission');

it('renders the default icon contract', function (): void {
    $html = renderIcon(['name' => 'check']);
    $openingTag = iconOpeningTag($html);

    expect(iconClass($html))->toBe('lyra-icon')
        ->and($openingTag)->toContain('width="20"')
        ->and($openingTag)->toContain('height="20"')
        ->and($openingTag)->toContain('stroke="currentColor"')
        ->and($openingTag)->toContain('aria-hidden="true"')
        ->and($openingTag)->not->toContain('role=')
        ->and($openingTag)->not->toContain('aria-label=');
});

it('renders the accessible title contract', function (): void {
    $openingTag = iconOpeningTag(renderIcon([
        'name' => 'check',
        'title' => 'Approved',
    ]));

    expect($openingTag)->toContain('role="img"')
        ->and($openingTag)->toContain('aria-label="Approved"')
        ->and($openingTag)->not->toContain('aria-hidden=');
});

it('treats an empty title as decorative', function (string $title): void {
    $openingTag = iconOpeningTag(renderIcon([
        'name' => 'check',
        'title' => $title,
    ]));

    expect($openingTag)->toContain('aria-hidden="true"')
        ->and($openingTag)->not->toContain('role=')
        ->and($openingTag)->not->toContain('aria-label=');
})->with([
    'empty' => '',
    'whitespace only' => '   ',
]);

it('reflects size and color on the svg', function (): void {
    $html = Blade::render('<x-lyra::icon name="check" :size="16" color="red" />');
    $openingTag = iconOpeningTag($html);

    expect($openingTag)->toContain('width="16"')
        ->and($openingTag)->toContain('height="16"')
        ->and($openingTag)->toContain('stroke="red"')
        ->and(substr_count($openingTag, 'stroke='))->toBe(1);
});

it('renders nothing for unknown and absent names', function (?string $name): void {
    $props = $name === null ? [] : ['name' => $name];

    expect(renderIcon($props))->toBe('');
})->with([
    'prototype key' => 'constructor',
    'unknown name' => 'not-real',
    'unlisted lucide icon' => 'activity',
    'absent name' => null,
]);

it('renders the github icon', function (): void {
    expect(iconOpeningTag(renderIcon(['name' => 'github'])))->toStartWith('<svg');
});

it('keeps user classes after the base class', function (): void {
    expect(iconClass(renderIcon([
        'name' => 'check',
        'class' => 'size-4 text-success',
    ])))->toBe('lyra-icon size-4 text-success');
});

it('omits false passthrough attributes while preserving true and string values', function (): void {
    $falseTag = iconOpeningTag(Blade::render(
        '<x-lyra::icon name="check" :focusable="false" />',
    ));
    $trueTag = iconOpeningTag(Blade::render(
        '<x-lyra::icon name="check" :focusable="true" />',
    ));
    $stringTag = iconOpeningTag(Blade::render(
        '<x-lyra::icon name="check" focusable="auto" />',
    ));

    expect($falseTag)->not->toContain('focusable=')
        ->and($trueTag)->toContain('focusable=')
        ->and($stringTag)->toContain('focusable="auto"');
});

it('defines the exact curated React registry', function (): void {
    expect(IconRegistry::NAMES)->toBe([
        'archive', 'arrow-left', 'arrow-right', 'arrow-up-right', 'bell', 'book-open',
        'calendar', 'chart-line', 'check', 'chevron-down', 'chevron-left', 'chevron-right',
        'chevrons-left', 'chevrons-right', 'chevrons-up-down', 'circle', 'circle-alert',
        'circle-check', 'circle-dot', 'circle-x', 'cloud-upload', 'code', 'copy', 'credit-card',
        'download', 'ellipsis', 'external-link', 'eye', 'file', 'file-archive', 'file-plus',
        'file-spreadsheet', 'file-text', 'film', 'filter', 'folder', 'folder-open', 'github',
        'globe', 'hard-drive', 'heart', 'house', 'image', 'inbox', 'info', 'layout-dashboard',
        'layout-grid', 'link', 'list', 'lock', 'log-out', 'mail', 'message-circle', 'minus',
        'moon', 'music', 'package', 'pencil', 'plus', 'rocket', 'scale', 'search', 'send',
        'settings', 'shield', 'sliders-horizontal', 'sparkles', 'star', 'sun', 'terminal',
        'timer', 'trash-2', 'triangle-alert', 'upload', 'user', 'user-plus', 'users', 'x', 'zap',
    ]);
});
