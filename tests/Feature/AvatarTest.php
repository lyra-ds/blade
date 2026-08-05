<?php

use Illuminate\Support\Facades\Blade;

function renderAvatar(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::avatar %s />', $attributes));
}

function avatarRootClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function avatarOpeningTag(string $html): string
{
    $matched = preg_match('/<span\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('avatar class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/avatar.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the avatar class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(avatarRootClass(renderAvatar($case['props'])))->toBe($case['expected_class']);
})->with('avatar class emission');

it('renders initials and a title by default when a name is present', function (): void {
    $html = renderAvatar(['name' => 'Ana Souza']);
    $openingTag = avatarOpeningTag($html);

    expect(avatarRootClass($html))->toBe('lyra-avatar lyra-avatar--md')
        ->and($openingTag)->toContain('title="Ana Souza"')
        ->and($html)->toContain('<span aria-hidden="true">AS</span>')
        ->and($html)->not->toContain('<img')
        ->and($html)->not->toContain('lyra-avatar__status');
});

it('renders an image with src and alt instead of initials', function (): void {
    $html = renderAvatar([
        'src' => '/avatars/ana.png',
        'name' => 'Ana',
    ]);

    expect($html)->toContain('<img src="/avatars/ana.png" alt="Ana">')
        ->and($html)->not->toContain('aria-hidden="true"');
});

it('always renders an empty alt when an image has no name', function (): void {
    $html = renderAvatar(['src' => '/avatars/anonymous.png']);

    expect($html)->toContain('<img src="/avatars/anonymous.png" alt="">')
        ->and(avatarOpeningTag($html))->not->toContain('title=');
});

it('matches the React initials algorithm', function (?string $name, string $expected): void {
    $props = $name === null ? [] : ['name' => $name];
    $html = renderAvatar($props);

    expect($html)->toContain(sprintf('<span aria-hidden="true">%s</span>', $expected));
})->with([
    'single word' => ['ana', 'A'],
    'first two of three words' => ['Ana Maria Souza', 'AM'],
    'whitespace runs and empty parts' => ['  Ana   Souza  ', 'AS'],
    'no name' => [null, ''],
]);

it('omits the title when no name is provided', function (): void {
    expect(avatarOpeningTag(renderAvatar()))->not->toContain('title=');
});

it('renders the status after the image with the fallback label', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::avatar src="/a.png" name="Ana" size="xl" shape="square" status="online" class="x" />
        BLADE);
    $imagePosition = strpos($html, '<img');
    $statusPosition = strpos($html, 'lyra-avatar__status');

    expect(avatarRootClass($html))->toBe('lyra-avatar lyra-avatar--xl lyra-avatar--square x')
        ->and($html)->toContain('<img src="/a.png" alt="Ana">')
        ->and($html)->toContain('<span class="lyra-avatar__status lyra-avatar__status--online" role="status" aria-label="online"></span>')
        ->and($imagePosition)->toBeInt()
        ->and($statusPosition)->toBeInt()
        ->and($imagePosition)->toBeLessThan($statusPosition);
});

it('uses a custom status label and renders the status after initials', function (): void {
    $html = Blade::render('<x-lyra::avatar name="Ana Souza" status="busy" status-label="In a meeting" />');
    $initialsPosition = strpos($html, '<span aria-hidden="true">AS</span>');
    $statusPosition = strpos($html, 'lyra-avatar__status');

    expect($html)->toContain('<span class="lyra-avatar__status lyra-avatar__status--busy" role="status" aria-label="In a meeting"></span>')
        ->and($initialsPosition)->toBeInt()
        ->and($statusPosition)->toBeInt()
        ->and($initialsPosition)->toBeLessThan($statusPosition);
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::avatar name="Ana" shape="square" class="first second" id="account" data-track="avatar" aria-live="polite" />
        BLADE);
    $openingTag = avatarOpeningTag($html);

    expect(avatarRootClass($html))->toBe('lyra-avatar lyra-avatar--md lyra-avatar--square first second')
        ->and($openingTag)->toContain('id="account"')
        ->and($openingTag)->toContain('data-track="avatar"')
        ->and($openingTag)->toContain('aria-live="polite"');
});
