<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;

function renderOtpInput(array $props = [], array $data = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render("<x-lyra::otp-input {$attributes} />", $data);
}

function otpTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'field' => '/<div\b(?=[^>]*\bclass="lyra-field")[^>]*>/',
        'group' => '/<div\b(?=[^>]*\bclass="lyra-otp(?: [^"]*)?")[^>]*>/',
        'digit' => '/<input\b(?=[^>]*\blyra-otp__digit)[^>]*>/',
        'hidden' => '/<input\b(?=[^>]*\btype="hidden")[^>]*>/',
        'label' => '/<span\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'hint' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
    };

    expect(preg_match($pattern, $html, $matches))->toBe(1);

    return $matches[0];
}

dataset('otp input class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/otp-input.json');
    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)->mapWithKeys(fn (array $case, int $index): array => [
        sprintf('class parity case %02d', $index + 1) => [$case],
    ])->all();
});

it('emits the observed class strings', function (array $case): void {
    $tag = otpTag(renderOtpInput($case['props']), $case['target']);

    expect($tag)->toMatch('/\bclass="'.preg_quote($case['expected_class'], '/').'"/');
})->with('otp input class emission');

it('renders both component syntaxes identically', function (): void {
    $namespaced = Blade::render('<x-lyra::otp-input id="otp" label="Code" />');
    $short = Blade::render('<lyra:otp-input id="otp" label="Code" />');

    expect($short)->toBe($namespaced);
});

it('serves the Alpine digit contract and a named hidden form value', function (): void {
    $html = renderOtpInput(['id' => 'otp', 'label' => 'Code', 'name' => 'code', 'length' => 4, 'value' => '12-34']);

    expect(otpTag($html, 'field'))->toContain('x-modelable="code"')
        ->and(html_entity_decode(otpTag($html, 'field'), ENT_QUOTES))->toContain('"length":4')
        ->and(otpTag($html, 'group'))->toContain('id="otp"')->toContain('role="group"')->toContain('aria-labelledby="otp-label"')
        ->and(otpTag($html, 'label'))->toContain('id="otp-label"')
        ->and(otpTag($html, 'digit'))->toContain('inputmode="numeric"')->toContain('pattern="[0-9]*"')->toContain('maxlength="4"')->toContain('x-bind="digit"')
        ->and($html)->toContain(':autocomplete="index === 0 ? \'one-time-code\' : \'off\'"')
        ->and(otpTag($html, 'hidden'))->toContain('name="code"')->toContain('value="1234"')->toContain('x-bind:value="code"');
});

it('connects validation errors to the group and every digit', function (): void {
    View::share('errors', new MessageBag(['code' => ['Expired code']]));
    $html = renderOtpInput(['id' => 'otp', 'name' => 'code', 'label' => 'Code', 'hint' => 'Enter your code', 'aria-describedby' => 'outside']);
    View::share('errors', new MessageBag);

    expect(otpTag($html, 'group'))->toContain('aria-describedby="outside otp-message"')
        ->and(otpTag($html, 'digit'))->toContain('aria-invalid="true"')->toContain('aria-describedby="otp-message"')
        ->and($html)->toContain('id="otp-message" class="lyra-hint lyra-hint--error">Expired code')
        ->not->toContain('Enter your code');
});

it('preserves translated digit labels and disables the complete field', function (): void {
    $html = renderOtpInput(['digit-label' => 'Dígito', 'disabled' => 'disabled', 'name' => 'code']);

    expect(html_entity_decode(otpTag($html, 'field'), ENT_QUOTES))->toContain('"digitLabel":"Dígito"')
        ->and(otpTag($html, 'digit'))->toContain('disabled')
        ->and(otpTag($html, 'hidden'))->toContain('disabled');
});

it('prefers flashed old input over the supplied value', function (): void {
    session()->flashInput(['code' => '9-876']);
    request()->setLaravelSession(app('session')->driver());

    $html = renderOtpInput(['name' => 'code', 'value' => '1234', 'length' => 4]);

    expect(otpTag($html, 'hidden'))->toContain('value="9876"')
        ->and(html_entity_decode(otpTag($html, 'field'), ENT_QUOTES))->toContain('"defaultValue":"9876"');
});

it('marks every digit required instead of the group', function (): void {
    $html = Blade::render('<x-lyra::otp-input name="code" required />');
    preg_match_all('/<input\b(?=[^>]*\blyra-otp__digit)[^>]*>/', $html, $digits);

    expect($digits[0])->toHaveCount(1)
        ->and($digits[0][0])->toMatch('/\srequired(\s|>|=)/')
        ->and(otpTag($html, 'group'))->not->toMatch('/\srequired(\s|>|=)/')
        ->and(otpTag(renderOtpInput(['name' => 'code']), 'digit'))->not->toMatch('/\srequired(\s|>|=)/');
});

it('keeps an external aria-labelledby and combines it with the internal label', function (): void {
    $external = Blade::render('<x-lyra::otp-input id="otp" aria-labelledby="outside" />');
    $combined = Blade::render('<x-lyra::otp-input id="otp" label="Code" aria-labelledby="outside" />');

    expect(otpTag($external, 'group'))->toContain('aria-labelledby="outside"')
        ->and(otpTag($combined, 'group'))->toContain('aria-labelledby="otp-label outside"');
});
