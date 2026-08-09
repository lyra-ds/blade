<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderPopover(
    array $props = [],
    string|Htmlable $trigger = 'More details',
    string|Htmlable $content = 'Popover content',
): string {
    $defaultOpen = $props['defaultOpen'] ?? false;
    $side = $props['side'] ?? 'auto';
    $align = $props['align'] ?? null;
    $width = $props['width'] ?? null;
    $ariaLabel = $props['ariaLabel'] ?? 'Popover';
    $wrapTrigger = $props['wrapTrigger'] ?? true;
    unset(
        $props['defaultOpen'],
        $props['side'],
        $props['align'],
        $props['width'],
        $props['ariaLabel'],
        $props['wrapTrigger'],
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
            '<x-lyra::popover :default-open="$defaultOpen" :side="$side" :align="$align" :width="$width" :aria-label="$ariaLabel" :wrap-trigger="$wrapTrigger" %s><x-slot:trigger>{{ $trigger }}</x-slot:trigger>{{ $content }}</x-lyra::popover>',
            $attributes,
        ),
        compact('defaultOpen', 'side', 'align', 'width', 'ariaLabel', 'wrapTrigger', 'trigger', 'content'),
    );
}

function popoverOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<span\b(?=[^>]*\bx-data="lyraPopover\()[^>]*>/',
        'trigger' => '/<span\b(?=[^>]*\bx-bind="trigger")[^>]*>/',
        'panel' => '/<div\b(?=[^>]*\bx-bind="panel")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function popoverClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="(lyra-popover-anchor(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('popover class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/popover.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the popover class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(popoverClass(renderPopover($case['props'])))->toBe($case['expected_class']);
})->with('popover class emission');

it('emits the exact default Alpine literal and omits nullable options', function (): void {
    $root = popoverOpeningTag(renderPopover(), 'root');

    expect($root)->toContain("x-data=\"lyraPopover({ defaultOpen: false, side: 'auto', ariaLabel: 'Popover' })\"")
        ->and($root)->toContain('x-modelable="open"')
        ->and($root)->not->toContain('align:')
        ->and($root)->not->toContain('width:');
});

it('emits every configured Alpine option in deterministic order', function (): void {
    $root = popoverOpeningTag(renderPopover([
        'defaultOpen' => true,
        'side' => 'top',
        'align' => 'end',
        'width' => 320,
        'ariaLabel' => 'Account "menu"',
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraPopover({ defaultOpen: true, side: 'top', align: 'end', width: 320, ariaLabel: 'Account &quot;menu&quot;' })\"");
});

it('substitutes malformed UTF-8 in the Alpine aria label literal', function (): void {
    $root = popoverOpeningTag(renderPopover([
        'ariaLabel' => "\xC3\x28",
    ]), 'root');

    expect($root)->toContain("ariaLabel: '�('");
});

it('JavaScript-escapes quotes, slashes, and line breaks in the Alpine aria label literal', function (): void {
    $root = popoverOpeningTag(renderPopover([
        'ariaLabel' => "a'b\\c\r\nd",
    ]), 'root');

    expect($root)->toContain("ariaLabel: 'a\\'b\\\\c\\r\\nd'");
});

it('splits model attributes onto the root before passthrough attributes', function (): void {
    $root = popoverOpeningTag(renderPopover([
        'wire:model.live' => 'open',
        'x-model.number' => 'popoverOpen',
        'data-track' => 'popover',
    ]), 'root');
    $wireModelPosition = strpos($root, 'wire:model.live="open"');
    $xModelPosition = strpos($root, 'x-model.number="popoverOpen"');
    $passthroughPosition = strpos($root, 'data-track="popover"');

    expect($wireModelPosition)->toBeInt()
        ->and($xModelPosition)->toBeInt()
        ->and($passthroughPosition)->toBeInt()
        ->and($wireModelPosition)->toBeLessThan($passthroughPosition)
        ->and($xModelPosition)->toBeLessThan($passthroughPosition)
        ->and(substr_count($root, 'wire:model.live='))->toBe(1)
        ->and(substr_count($root, 'x-model.number='))->toBe(1);
});

it('keeps component-owned Alpine state ahead of a consumer x-data attribute', function (): void {
    $root = popoverOpeningTag(renderPopover([
        'x-data' => 'consumerState',
    ]), 'root');
    $componentDataPosition = strpos($root, "x-data=\"lyraPopover({ defaultOpen: false, side: 'auto', ariaLabel: 'Popover' })\"");
    $consumerDataPosition = strpos($root, 'x-data="consumerState"');

    expect($componentDataPosition)->toBeInt()
        ->and($consumerDataPosition)->toBeInt()
        ->and($componentDataPosition)->toBeLessThan($consumerDataPosition);
});

it('keeps passthrough on the root and appends the user class last', function (): void {
    $html = renderPopover([
        'class' => 'wide elevated',
        'id' => 'account-popover',
        'data-track' => 'popover',
    ]);
    $root = popoverOpeningTag($html, 'root');
    $trigger = popoverOpeningTag($html, 'trigger');
    $panel = popoverOpeningTag($html, 'panel');

    expect(popoverClass($html))->toBe('lyra-popover-anchor wide elevated')
        ->and($root)->toContain('id="account-popover"')
        ->and($root)->toContain('data-track="popover"')
        ->and($trigger)->not->toContain('id="account-popover"')
        ->and($panel)->not->toContain('id="account-popover"')
        ->and($panel)->not->toContain('data-track="popover"');
});

it('wraps trigger content with the complete trigger contract by default', function (): void {
    $closed = renderPopover(trigger: new HtmlString('<svg data-trigger></svg>Actions'));
    $open = renderPopover(['defaultOpen' => true]);
    $closedTag = popoverOpeningTag($closed, 'trigger');
    $openTag = popoverOpeningTag($open, 'trigger');

    expect($closedTag)->toContain('role="button"')
        ->and($closedTag)->toContain('tabindex="0"')
        ->and($closedTag)->toContain('aria-haspopup="dialog"')
        ->and($closedTag)->toContain('aria-expanded="false"')
        ->and($closedTag)->toContain('x-bind="trigger"')
        ->and($closed)->toContain('<svg data-trigger></svg>Actions</span>')
        ->and($openTag)->toContain('aria-expanded="true"');
});

it('renders trigger slot content directly when wrapping is disabled', function (): void {
    $html = renderPopover(
        ['wrapTrigger' => false],
        new HtmlString('<button type="button" x-bind="trigger">Actions</button>'),
    );

    expect($html)->toContain('<button type="button" x-bind="trigger">Actions</button>')
        ->and($html)->not->toContain('role="button"')
        ->and($html)->not->toContain('tabindex="0"')
        ->and(substr_count($html, 'x-bind="trigger"'))->toBe(1);
});

it('renders the initial panel placement, alignment, and accessibility contract', function (): void {
    $automatic = popoverOpeningTag(renderPopover(), 'panel');
    $bottomCenter = popoverOpeningTag(renderPopover([
        'side' => 'bottom',
        'align' => 'center',
    ]), 'panel');
    $topEnd = popoverOpeningTag(renderPopover([
        'side' => 'top',
        'align' => 'end',
    ]), 'panel');

    expect($automatic)->toContain('class="lyra-popover lyra-popover--bottom lyra-popover--align-start"')
        ->and($bottomCenter)->toContain('class="lyra-popover lyra-popover--bottom lyra-popover--align-center"')
        ->and($topEnd)->toContain('class="lyra-popover lyra-popover--top lyra-popover--align-end"')
        ->and($automatic)->toContain('role="dialog"')
        ->and($automatic)->toContain('aria-label="Popover"')
        ->and($automatic)->toContain('x-bind="panel"');
});

it('renders escaped panel labels and width styling only when configured', function (): void {
    $default = popoverOpeningTag(renderPopover(), 'panel');
    $configured = popoverOpeningTag(renderPopover([
        'width' => 320,
        'ariaLabel' => 'Account "menu" & tools',
    ]), 'panel');

    expect($default)->not->toContain(' style=')
        ->and($configured)->toContain('aria-label="Account &quot;menu&quot; &amp; tools"')
        ->and($configured)->toContain('style="width: 320px"');
});

it('cloaks only a closed panel and always serves the panel binding and content', function (): void {
    $closed = renderPopover(content: new HtmlString('<strong>Raw content</strong>'));
    $open = renderPopover(['defaultOpen' => true], content: '<strong>Escaped content</strong>');
    $closedTag = popoverOpeningTag($closed, 'panel');
    $openTag = popoverOpeningTag($open, 'panel');

    expect($closedTag)->toContain('x-bind="panel"')
        ->and($closedTag)->toContain('x-cloak')
        ->and($closed)->toContain('<strong>Raw content</strong>')
        ->and($openTag)->toContain('x-bind="panel"')
        ->and($openTag)->not->toContain('x-cloak')
        ->and($open)->toContain('&lt;strong&gt;Escaped content&lt;/strong&gt;')
        ->and($open)->not->toContain('<strong>Escaped content</strong>');
});

it('coerces unknown side and align values to their safe defaults', function (): void {
    $html = renderPopover([
        'side' => 'left',
        'align' => 'stretch',
    ]);
    $root = popoverOpeningTag($html, 'root');
    $panel = popoverOpeningTag($html, 'panel');

    expect($root)->toContain("x-data=\"lyraPopover({ defaultOpen: false, side: 'auto', ariaLabel: 'Popover' })\"")
        ->and($root)->not->toContain('align:')
        ->and($panel)->toContain('class="lyra-popover lyra-popover--bottom lyra-popover--align-start"');
});

it('emits no server ids or aria controls', function (): void {
    $html = renderPopover();
    $root = popoverOpeningTag($html, 'root');
    $trigger = popoverOpeningTag($html, 'trigger');
    $panel = popoverOpeningTag($html, 'panel');

    expect($root)->not->toContain(' id=')
        ->and($trigger)->not->toContain(' id=')
        ->and($trigger)->not->toContain('aria-controls=')
        ->and($panel)->not->toContain(' id=');
});

it('renders a server-seeded open wire-modelled popover through Livewire', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::popover :default-open="$open" wire:model="open">
                    <x-slot:trigger>More details</x-slot:trigger>
                    Account details
                </x-lyra::popover>
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = popoverOpeningTag($html, 'root');
    $trigger = popoverOpeningTag($html, 'trigger');
    $panel = popoverOpeningTag($html, 'panel');

    expect($root)->toContain("x-data=\"lyraPopover({ defaultOpen: true, side: 'auto', ariaLabel: 'Popover' })\"")
        ->and($root)->toContain('wire:model="open"')
        ->and($trigger)->toContain('aria-expanded="true"')
        ->and($panel)->not->toContain('x-cloak');
});
