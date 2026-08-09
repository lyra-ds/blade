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

it('always emits the Alpine toast stack wiring while keeping consumer attributes last', function (): void {
    $root = dynamicToastStackRoot(renderDynamicToastStack(
        'class="first second" id="notifications" data-track="stack" x-data="consumerState"',
    ));
    $bindingPosition = strpos($root, 'x-data="lyraToastStack()"');
    $classPosition = strpos($root, 'class="lyra-toast-stack first second"');
    $consumerPosition = strpos($root, 'x-data="consumerState"');

    expect($bindingPosition)->toBeInt()
        ->and($classPosition)->toBeInt()
        ->and($consumerPosition)->toBeInt()
        ->and($bindingPosition)->toBeLessThan($classPosition)
        ->and($classPosition)->toBeLessThan($consumerPosition)
        ->and($root)->toContain('id="notifications"')
        ->and($root)->toContain('data-track="stack"');
});

it('serves the dynamic queue template with static toast class parity', function (): void {
    $html = renderDynamicToastStack();

    expect($html)->toMatch('/<template\s+x-for="toast in toasts"\s+:key="toast\.id"\s*>/s')
        ->and($html)->toMatch('/<div\s+class="lyra-toast"\s+role="status"\s*>/s')
        ->and($html)->toMatch('/<span\s+class="lyra-toast__icon"\s+:class="toneClass\(toast\.tone\)"\s*>/s')
        ->and($html)->toContain('<span x-text="toast.message"></span>')
        ->and($html)->toMatch('/<button\s+class="lyra-toast__close"\s+:data-toast-id="toast\.id"\s+x-bind="closeButton"\s*>×<\/button>/s')
        ->and($html)->not->toContain('x-html=');
});

it('inlines the exact success danger and info tone icons', function (): void {
    $html = renderDynamicToastStack();
    $svgContract = 'aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

    expect(substr_count($html, '<svg'))->toBe(3)
        ->and(substr_count($html, $svgContract))->toBe(3)
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'success\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<path d="m9 12 2 2 4-4"\s*\/>\s*<\/svg>/s')
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'danger\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<line x1="12" x2="12" y1="8" y2="12"\s*\/>\s*<line x1="12" x2="12\.01" y1="16" y2="16"\s*\/>\s*<\/svg>/s')
        ->and($html)->toMatch('/<svg[^>]*x-show="toast\.tone === \'info\'"[^>]*>\s*<circle cx="12" cy="12" r="10"\s*\/>\s*<path d="M12 16v-4"\s*\/>\s*<path d="M12 8h\.01"\s*\/>\s*<\/svg>/s');
});

it('preserves statically served toast children unchanged', function (): void {
    $html = renderDynamicToastStack(slot: '<x-lyra::toast class="static-toast">Saved</x-lyra::toast>');

    expect($html)->toContain('class="lyra-toast static-toast"')
        ->and($html)->toContain('role="status"')
        ->and($html)->toContain('<span>Saved</span>')
        ->and(substr_count($html, 'x-data="lyraToastStack()"'))->toBe(1)
        ->and(substr_count($html, 'x-bind="closeButton"'))->toBe(1);
});
