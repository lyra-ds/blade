<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderCookieBanner(
    array $props = [],
    string|Htmlable $slot = '',
): string {
    $storageKey = $props['storageKey'] ?? 'lyra-cookie-consent';
    $policyHref = $props['policyHref'] ?? null;
    $essentialsLabel = $props['essentialsLabel'] ?? 'Only essentials';
    $acceptLabel = $props['acceptLabel'] ?? 'Accept all';
    $ariaLabel = $props['ariaLabel'] ?? 'Cookie notice';
    unset(
        $props['storageKey'],
        $props['policyHref'],
        $props['essentialsLabel'],
        $props['acceptLabel'],
        $props['ariaLabel'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::cookie-banner :storage-key="$storageKey" :policy-href="$policyHref" :essentials-label="$essentialsLabel" :accept-label="$acceptLabel" :aria-label="$ariaLabel" %s>{{ $slot }}</x-lyra::cookie-banner>',
            $attributes,
        ),
        compact('storageKey', 'policyHref', 'essentialsLabel', 'acceptLabel', 'ariaLabel', 'slot'),
    );
}

function cookieBannerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-cookies(?: [^"]*)?")[^>]*>/',
        'actions' => '/<div\b(?=[^>]*\bclass="lyra-cookies__actions")[^>]*>/',
        'essentials' => '/<button\b(?=[^>]*\bx-bind="essentials")[^>]*>/',
        'accept' => '/<button\b(?=[^>]*\bx-bind="accept")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function cookieBannerClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-cookies(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('cookie banner class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/cookie-banner.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the cookie-banner class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string without serving the runtime closing class', function (array $case): void {
    $html = renderCookieBanner($case['props']);

    expect(cookieBannerClass($html))->toBe($case['expected_class'])
        ->and(cookieBannerOpeningTag($html, 'root'))->not->toContain('lyra-cookies--closing');
})->with('cookie banner class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::cookie-banner>Custom notice</x-lyra::cookie-banner>');
    $short = Blade::render('<lyra:cookie-banner>Custom notice</lyra:cookie-banner>');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-cookies"');
});

it('serves the accessible region name with a consumer override and no duplication', function (): void {
    $defaultRoot = cookieBannerOpeningTag(renderCookieBanner(), 'root');
    $customRoot = cookieBannerOpeningTag(renderCookieBanner([
        'ariaLabel' => 'LGPD choices',
        'role' => 'presentation',
    ]), 'root');

    expect($defaultRoot)->toContain('role="region"')
        ->and($defaultRoot)->toContain('aria-label="Cookie notice"')
        ->and(substr_count($defaultRoot, 'aria-label='))->toBe(1)
        ->and(substr_count($customRoot, 'role='))->toBe(1)
        ->and($customRoot)->not->toContain('role="presentation"')
        ->and($customRoot)->toContain('aria-label="LGPD choices"')
        ->and(substr_count($customRoot, 'aria-label='))->toBe(1);
});

it('wires the private Alpine state machine and stays cloaked until storage is checked', function (): void {
    $root = cookieBannerOpeningTag(renderCookieBanner(), 'root');

    expect($root)->toContain("x-data=\"lyraCookieBanner({ storageKey: 'lyra-cookie-consent' })\"")
        ->and($root)->toContain('x-bind="root"')
        ->and($root)->toContain('x-cloak')
        ->and($root)->not->toContain('x-modelable');
});

it('safely escapes the storage key inside the Alpine argument', function (): void {
    $root = cookieBannerOpeningTag(renderCookieBanner([
        'storageKey' => "cookie's \\\"key\"\r\nnext",
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraCookieBanner({ storageKey: 'cookie\\'s \\\\&quot;key&quot;\\r\\nnext' })\"");
});

it('renders the byte-exact default copy and optional policy link', function (): void {
    $copy = 'We use cookies to improve your experience in accordance with LGPD. You can accept all cookies or keep only essential ones.';
    $withoutPolicy = renderCookieBanner();
    $withEmptyPolicy = renderCookieBanner(['policyHref' => '']);
    $withPolicy = renderCookieBanner(['policyHref' => '/privacy?from=cookies']);

    expect($withoutPolicy)->toContain('<p class="lyra-cookies__text">'.$copy.'</p>')
        ->and($withoutPolicy)->not->toContain('<a ')
        ->and($withEmptyPolicy)->not->toContain('<a ')
        ->and($withPolicy)->toContain('<p class="lyra-cookies__text">'.$copy.' <a href="/privacy?from=cookies">Privacy policy</a></p>');
});

it('lets the slot replace the entire default copy branch including the policy link', function (): void {
    $slot = new HtmlString('<strong>Choose carefully.</strong>');
    $html = renderCookieBanner(['policyHref' => '/privacy'], $slot);

    expect($html)->toContain('<p class="lyra-cookies__text"><strong>Choose carefully.</strong></p>')
        ->and($html)->not->toContain('We use cookies')
        ->and($html)->not->toContain('Privacy policy')
        ->and($html)->not->toContain('href="/privacy"');
});

it('composes exactly two small action buttons in React order with served button types', function (): void {
    $html = renderCookieBanner();
    $actionsStart = strpos($html, cookieBannerOpeningTag($html, 'actions'));
    $actionsEnd = strpos($html, '</div>', $actionsStart);
    $actions = substr($html, $actionsStart, $actionsEnd - $actionsStart + strlen('</div>'));
    $essentials = cookieBannerOpeningTag($html, 'essentials');
    $accept = cookieBannerOpeningTag($html, 'accept');

    expect(substr_count($actions, '<button'))->toBe(2)
        ->and(strpos($actions, 'x-bind="essentials"'))->toBeLessThan(strpos($actions, 'x-bind="accept"'))
        ->and($essentials)->toContain('class="lyra-btn lyra-btn--secondary lyra-btn--sm"')
        ->and($essentials)->toContain('type="button"')
        ->and(substr_count($essentials, 'type='))->toBe(1)
        ->and($accept)->toContain('class="lyra-btn lyra-btn--primary lyra-btn--sm"')
        ->and($accept)->toContain('type="button"')
        ->and(substr_count($accept, 'type='))->toBe(1)
        ->and($actions)->toMatch('/x-bind="essentials"[^>]*>\s*<span class="lyra-btn__label">Only essentials<\/span>/')
        ->and($actions)->toMatch('/x-bind="accept"[^>]*>\s*<span class="lyra-btn__label">Accept all<\/span>/');
});

it('overrides both visible action labels', function (): void {
    $html = renderCookieBanner([
        'essentialsLabel' => 'Necessary only',
        'acceptLabel' => 'Allow everything',
    ]);

    expect($html)->toContain('<span class="lyra-btn__label">Necessary only</span>')
        ->and($html)->toContain('<span class="lyra-btn__label">Allow everything</span>')
        ->and($html)->not->toContain('Only essentials')
        ->and($html)->not->toContain('Accept all');
});
