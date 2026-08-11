<?php

use LyraDs\Blade\BladePropParser;

it('reads names, defaults and required props from a @props directive', function (): void {
    $props = (new BladePropParser)->parse(<<<'BLADE'
        @props([
            'label',
            'variant' => 'primary',
            'loading' => false,
        ])
        <div></div>
        BLADE, 'sample.blade.php');

    expect($props)->toHaveCount(3)
        ->and($props[0]['name'])->toBe('label')
        ->and($props[0]['hasDefault'])->toBeFalse()
        ->and($props[1]['name'])->toBe('variant')
        ->and($props[1]['value'])->toBe('primary')
        ->and($props[2]['value'])->toBeFalse();
});

it('ignores a @props directive that only appears inside a Blade comment', function (): void {
    $props = (new BladePropParser)->parse(<<<'BLADE'
        {{-- @props(['ghost' => true]) --}}
        @props(['real' => 1])
        <div></div>
        BLADE, 'sample.blade.php');

    expect($props)->toHaveCount(1)->and($props[0]['name'])->toBe('real');
});

it('refuses a template with two @props directives', function (): void {
    expect(fn (): array => (new BladePropParser)->parse(
        "@props(['a' => 1])\n@props(['b' => 2])\n<div></div>",
        'sample.blade.php',
    ))->toThrow(RuntimeException::class);
});

it('returns no props when the template has no directive', function (): void {
    expect((new BladePropParser)->parse('<div></div>', 'sample.blade.php'))->toBe([]);
});
