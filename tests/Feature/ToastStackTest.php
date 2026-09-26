<?php

use Illuminate\Support\Facades\Blade;

function renderDynamicToastStack(string $attributes = '', string $slot = ''): string
{
    return Blade::render(sprintf(
        '<x-lyra::toast-stack %s>%s</x-lyra::toast-stack>',
        $attributes,
        $slot,
    ));
}

function dynamicToastStackRoot(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

it('owns the stack x-data and does not forward a consumer x-data', function (): void {
    app()->detectEnvironment(fn () => 'production');
    $root = dynamicToastStackRoot(renderDynamicToastStack(
        'class="first second" id="notifications" data-track="stack" x-data="consumerState"',
    ));
    expect(substr_count($root, 'x-data='))->toBe(1)
        ->and($root)->not->toContain('consumerState')
        ->and($root)->toContain('x-data="lyraToastStack()"')
        ->and($root)->toContain('class="lyra-toast-stack first second"')
        ->and($root)->toContain('id="notifications"')
        ->and($root)->toContain('data-track="stack"');
});

it('serves two persistent live regions before any toast exists', function (): void {
    $html = renderDynamicToastStack();
    $root = dynamicToastStackRoot($html);

    expect(substr_count($html, 'data-lyra-toast-region='))->toBe(2)
        ->and($root)->not->toContain('aria-live')
        ->and($html)->toMatch('/<div data-lyra-toast-region="polite" aria-live="polite" aria-relevant="additions" style="display: contents">\s*<template x-for="toast in politeToasts" :key="toast\.id">/s')
        ->and($html)->toMatch('/<div data-lyra-toast-region="assertive" aria-live="assertive" aria-relevant="additions" style="display: contents">\s*<template x-for="toast in assertiveToasts" :key="toast\.id">/s')
        ->and($html)->not->toContain('x-for="toast in toasts"');
});

it('serves the dynamic queue template with static toast class parity and no row role', function (): void {
    $html = renderDynamicToastStack();

    expect(substr_count($html, '<template'))->toBe(2)
        ->and(substr_count($html, '<div class="lyra-toast">'))->toBe(2)
        ->and($html)->not->toContain('role=')
        ->and($html)->toMatch('/<span\s+class="lyra-toast__icon"\s+:class="toneClass\(toast\.tone\)"\s*>/s')
        ->and($html)->toContain('<span x-text="toast.message"></span>')
        ->and($html)->toMatch('/<button\s+class="lyra-toast__close"\s+:data-toast-id="toast\.id"\s+x-bind="closeButton"\s*>×<\/button>/s')
        ->and($html)->not->toContain('x-html=');
});

it('inlines the exact success danger and info tone icons', function (): void {
    $html = renderDynamicToastStack();
    $svgContract = 'aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

    expect(substr_count($html, '<svg'))->toBe(6)
        ->and(substr_count($html, $svgContract))->toBe(6)
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'success\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<path d="m9 12 2 2 4-4"\s*\/>\s*<\/svg>/s')
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'danger\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<line x1="12" x2="12" y1="8" y2="12"\s*\/>\s*<line x1="12" x2="12\.01" y1="16" y2="16"\s*\/>\s*<\/svg>/s')
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'info\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<path d="M12 16v-4"\s*\/>\s*<path d="M12 8h\.01"\s*\/>\s*<\/svg>/s');
});

it('preserves statically served toast children unchanged', function (): void {
    $html = renderDynamicToastStack(slot: '<x-lyra::toast class="static-toast">Saved</x-lyra::toast>');

    expect($html)->toContain('class="lyra-toast static-toast"')
        ->and($html)->not->toContain('role=')
        ->and($html)->toContain('<span>Saved</span>')
        ->and(substr_count($html, 'x-data="lyraToastStack()"'))->toBe(1)
        ->and(substr_count($html, 'x-bind="closeButton"'))->toBe(2);

    // An explicit non-status role from the consumer is kept.
    expect(renderDynamicToastStack(slot: '<x-lyra::toast role="alert">Boom</x-lyra::toast>'))->toContain('role="alert"');

    // Static slot toasts land inside the polite region, which announces them.
    expect($html)->toMatch('/data-lyra-toast-region="polite".*static-toast.*data-lyra-toast-region="assertive"/s');
});
