<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderCodeBlock(
    array $props = [],
    string|Htmlable $slot = 'echo "Hello";',
): string {
    $language = $props['language'] ?? null;
    $lineNumbers = $props['lineNumbers'] ?? false;
    $wrap = $props['wrap'] ?? false;
    $copyLabel = $props['copyLabel'] ?? null;
    $copiedLabel = $props['copiedLabel'] ?? null;
    $copyText = $props['copyText'] ?? null;
    unset(
        $props['language'],
        $props['lineNumbers'],
        $props['wrap'],
        $props['copyLabel'],
        $props['copiedLabel'],
        $props['copyText'],
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
            '<x-lyra::code-block :language="$language" :line-numbers="$lineNumbers" :wrap="$wrap" :copy-label="$copyLabel" :copied-label="$copiedLabel" :copy-text="$copyText" %s>{{ $slot }}</x-lyra::code-block>',
            $attributes,
        ),
        compact('language', 'lineNumbers', 'wrap', 'copyLabel', 'copiedLabel', 'copyText', 'slot'),
    );
}

function codeBlockOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-code(?: [^"]*)?")[^>]*>/',
        'bar' => '/<div\b(?=[^>]*\bclass="lyra-code__bar")[^>]*>/',
        'copy' => '/<button\b(?=[^>]*\bclass="lyra-code__copy")[^>]*>/',
        'pre' => '/<pre\b(?=[^>]*\bclass="lyra-code__pre")[^>]*>/',
        'status' => '/<span\b(?=[^>]*\bclass="lyra-code__status")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function codeBlockClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-code(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('code block class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/code-block.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the code-block class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(codeBlockClass(renderCodeBlock($case['props'])))->toBe($case['expected_class']);
})->with('code block class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::code-block language="php">echo 1;</x-lyra::code-block>');
    $short = Blade::render('<lyra:code-block language="php">echo 1;</lyra:code-block>');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-code"');
});

it('renders the exact static-first structure without copying', function (): void {
    $html = renderCodeBlock();
    $root = codeBlockOpeningTag($html, 'root');
    $bar = codeBlockOpeningTag($html, 'bar');
    $pre = codeBlockOpeningTag($html, 'pre');

    expect($root)->not->toContain('x-data=')
        ->and($root)->not->toContain('data-copy-text=')
        ->and($bar)->toBe('<div class="lyra-code__bar">')
        ->and($html)->toMatch('/<div class="lyra-code__bar">\s*<span aria-hidden="true"><\/span>\s*<\/div>/')
        ->and($pre)->toContain('class="lyra-code__pre"')
        ->and($pre)->toContain('tabindex="0"')
        ->and($html)->toContain('echo &quot;Hello&quot;;')
        ->and($html)->not->toContain('lyra-code__copy')
        ->and($html)->not->toContain('lyra-code__status');
});

it('renders the language in the bar instead of the empty spacer', function (): void {
    $html = renderCodeBlock(['language' => 'TypeScript']);

    expect($html)->toMatch('/<div class="lyra-code__bar">\s*<span class="lyra-code__lang">TypeScript<\/span>\s*<\/div>/')
        ->and($html)->not->toContain('aria-hidden="true"');
});

it('requires both labels before rendering either copy element', function (array $props): void {
    $html = renderCodeBlock($props);

    expect($html)->not->toContain('lyra-code__copy')
        ->and($html)->not->toContain('lyra-code__status')
        ->and(codeBlockOpeningTag($html, 'root'))->not->toContain('x-data=');
})->with([
    'copy label only' => [['copyLabel' => 'Copy']],
    'copied label only' => [['copiedLabel' => 'Copied']],
]);

it('delegates copy semantics to Alpine bindings while serving usable initial labels', function (): void {
    $html = renderCodeBlock([
        'copyLabel' => 'Copy',
        'copiedLabel' => 'Copied',
    ]);
    $root = codeBlockOpeningTag($html, 'root');
    $button = codeBlockOpeningTag($html, 'copy');
    $status = codeBlockOpeningTag($html, 'status');

    expect($root)->toContain('x-data="lyraCodeBlock()"')
        ->and($root)->not->toContain('x-modelable')
        ->and($button)->toContain('x-bind="copyButton"')
        ->and($button)->toContain('x-text="copied ? \'Copied\' : \'Copy\'"')
        ->and($button)->toContain('type="button"')
        ->and(substr_count($button, 'type='))->toBe(1)
        ->and($status)->toContain('x-bind="status"')
        ->and($status)->toContain('class="lyra-code__status"')
        ->and($status)->toContain('x-text="copied ? \'Copied\' : \'\'"')
        ->and($status)->not->toContain('role=')
        ->and($status)->not->toContain('aria-live=')
        ->and($status)->not->toContain('aria-atomic=')
        ->and($html)->toMatch('/<button\b[^>]*>\s*Copy\s*<\/button>/')
        ->and($html)->toMatch('/<span\b[^>]*class="lyra-code__status"[^>]*>\s*<\/span>/');
});

it('keeps the Alpine state first and consumer attributes last without duplication', function (): void {
    $root = codeBlockOpeningTag(renderCodeBlock([
        'copyLabel' => 'Copy',
        'copiedLabel' => 'Copied',
        'copyText' => 'override',
        'class' => 'extra',
        'id' => 'sample',
        'data-track' => 'code',
        'x-data' => 'consumerState',
    ]), 'root');
    $componentDataPosition = strpos($root, 'x-data="lyraCodeBlock()"');
    $copyTextPosition = strpos($root, 'data-copy-text="override"');
    $classPosition = strpos($root, 'class="lyra-code extra"');
    $consumerDataPosition = strpos($root, 'x-data="consumerState"');

    expect($componentDataPosition)->toBeInt()
        ->and($copyTextPosition)->toBeInt()
        ->and($classPosition)->toBeInt()
        ->and($consumerDataPosition)->toBeInt()
        ->and($componentDataPosition)->toBeLessThan($copyTextPosition)
        ->and($copyTextPosition)->toBeLessThan($classPosition)
        ->and($copyTextPosition)->toBeLessThan($consumerDataPosition)
        ->and($root)->toContain('id="sample"')
        ->and($root)->toContain('data-track="code"')
        ->and(substr_count($root, 'data-copy-text='))->toBe(1);
});

it('removes the native pre tab stop only when wrapping is enabled', function (): void {
    $scrolling = codeBlockOpeningTag(renderCodeBlock(), 'pre');
    $wrapped = codeBlockOpeningTag(renderCodeBlock(['wrap' => true]), 'pre');

    expect($scrolling)->toContain('tabindex="0"')
        ->and($wrapped)->not->toContain('tabindex=');
});

it('emits a copy text override only on the root', function (): void {
    $withOverride = renderCodeBlock(['copyText' => "one\ntwo"]);
    $withoutOverride = renderCodeBlock();

    expect(codeBlockOpeningTag($withOverride, 'root'))->toContain('data-copy-text="one&#10;two"')
        ->and(codeBlockOpeningTag($withOverride, 'pre'))->not->toContain('data-copy-text=')
        ->and(codeBlockOpeningTag($withoutOverride, 'root'))->not->toContain('data-copy-text=');
});

it('preserves consumer-authored highlighted markup in the pre', function (): void {
    $html = renderCodeBlock(
        slot: new HtmlString('<code><span class="line"><mark>echo</mark> 1;</span></code>'),
    );
    $pre = codeBlockOpeningTag($html, 'pre');

    expect($pre)->toContain('class="lyra-code__pre"')
        ->and($pre)->toContain('tabindex="0"')
        ->and($html)->toContain('<code><span class="line"><mark>echo</mark> 1;</span></code>')
        ->and($html)->not->toContain('&lt;code&gt;');
});
