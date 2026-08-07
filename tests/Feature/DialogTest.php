<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderDialog(array $props = [], string $body = 'Dialog body', ?string $footer = null): string
{
    $title = $props['title'] ?? 'Dialog title';
    $closable = $props['closable'] ?? true;
    $closeLabel = $props['closeLabel'] ?? 'Close';
    $defaultOpen = $props['defaultOpen'] ?? false;
    $closeOnEsc = $props['closeOnEsc'] ?? true;
    $closeOnOverlayClick = $props['closeOnOverlayClick'] ?? true;
    $labelId = $props['labelId'] ?? null;
    unset(
        $props['title'],
        $props['closable'],
        $props['closeLabel'],
        $props['defaultOpen'],
        $props['closeOnEsc'],
        $props['closeOnOverlayClick'],
        $props['labelId'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');
    $footerSlot = $footer === null ? '' : sprintf('<x-slot:footer>%s</x-slot:footer>', $footer);

    return Blade::render(
        sprintf(
            '<x-lyra::dialog :title="$title" :closable="$closable" :close-label="$closeLabel" :default-open="$defaultOpen" :close-on-esc="$closeOnEsc" :close-on-overlay-click="$closeOnOverlayClick" :label-id="$labelId" %s>%s%s</x-lyra::dialog>',
            $attributes,
            $body,
            $footerSlot,
        ),
        compact('title', 'closable', 'closeLabel', 'defaultOpen', 'closeOnEsc', 'closeOnOverlayClick', 'labelId'),
    );
}

function dialogClass(string $html, string $target): string
{
    $class = match ($target) {
        'overlay' => 'lyra-dialog-overlay',
        'panel' => 'lyra-dialog',
    };
    $matched = preg_match(sprintf('/<div\b[^>]*\bclass="(%s(?: [^"]*)?)"/', $class), $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function dialogOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'overlay' => '/<div\b(?=[^>]*\bclass="lyra-dialog-overlay")[^>]*>/',
        'panel' => '/<div\b(?=[^>]*\bclass="lyra-dialog(?: [^"]*)?")[^>]*>/',
        'header' => '/<div\b(?=[^>]*\bclass="lyra-dialog__header")[^>]*>/',
        'title' => '/<h2\b(?=[^>]*\bclass="lyra-dialog__title")[^>]*>/',
        'close' => '/<button\b(?=[^>]*\bclass="lyra-dialog__close")[^>]*>/',
        'body' => '/<div\b(?=[^>]*\bclass="lyra-dialog__body")[^>]*>/',
        'footer' => '/<div\b(?=[^>]*\bclass="lyra-dialog__footer")[^>]*>/',
        'svg' => '/<svg\b(?=[^>]*\bviewBox="0 0 24 24")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('dialog class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/dialog.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the dialog class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact fixed overlay class string', function (array $case): void {
    $html = renderDialog($case['props']);

    expect(dialogClass($html, 'overlay'))->toBe($case['expected_class']);
})->with('dialog class emission');

it('emits the closed default Alpine state and cloaks the overlay', function (): void {
    $openingTag = dialogOpeningTag(renderDialog(), 'overlay');

    expect($openingTag)->toContain('class="lyra-dialog-overlay"')
        ->and($openingTag)->toContain('x-data="lyraDialog({ defaultOpen: false, closeOnEsc: true, closeOnOverlayClick: true })"')
        ->and($openingTag)->toContain('x-modelable="open"')
        ->and($openingTag)->toContain('x-bind="overlay"')
        ->and($openingTag)->toContain('x-cloak');
});

it('emits configured Alpine booleans without cloaking an open overlay', function (): void {
    $openingTag = dialogOpeningTag(renderDialog([
        'defaultOpen' => true,
        'closeOnEsc' => false,
        'closeOnOverlayClick' => false,
    ]), 'overlay');

    expect($openingTag)->toContain('x-data="lyraDialog({ defaultOpen: true, closeOnEsc: false, closeOnOverlayClick: false })"')
        ->and($openingTag)->not->toContain('x-cloak');
});

it('keeps passthrough attributes on the panel and appends user classes', function (): void {
    $html = renderDialog([
        'class' => 'wide elevated',
        'id' => 'account-dialog',
        'data-track' => 'dialog',
    ]);
    $overlayTag = dialogOpeningTag($html, 'overlay');
    $panelTag = dialogOpeningTag($html, 'panel');

    expect(dialogClass($html, 'overlay'))->toBe('lyra-dialog-overlay')
        ->and($overlayTag)->not->toContain('wide elevated')
        ->and($overlayTag)->not->toContain('id="account-dialog"')
        ->and($overlayTag)->not->toContain('data-track="dialog"')
        ->and(dialogClass($html, 'panel'))->toBe('lyra-dialog wide elevated')
        ->and($panelTag)->toContain('id="account-dialog"')
        ->and($panelTag)->toContain('data-track="dialog"')
        ->and($panelTag)->toContain('role="dialog"')
        ->and($panelTag)->toContain('aria-modal="true"')
        ->and($panelTag)->toContain('tabindex="-1"')
        ->and($panelTag)->toContain('x-bind="panel"');
});

it('keeps the component panel role ahead of a consumer role attribute', function (): void {
    $panelTag = dialogOpeningTag(renderDialog([
        'role' => 'alert',
    ]), 'panel');
    $componentRolePosition = strpos($panelTag, 'role="dialog"');
    $consumerRolePosition = strpos($panelTag, 'role="alert"');

    expect($componentRolePosition)->toBeInt()
        ->and($consumerRolePosition)->toBeInt()
        ->and($componentRolePosition)->toBeLessThan($consumerRolePosition);
});

it('moves model attributes to the modelable overlay and leaves other attributes on the panel', function (): void {
    $html = renderDialog([
        'wire:model.live' => 'open',
        'x-model.number' => 'dialogOpen',
        'data-track' => 'dialog',
    ]);
    $overlayTag = dialogOpeningTag($html, 'overlay');
    $panelTag = dialogOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('wire:model.live="open"')
        ->and($overlayTag)->toContain('x-model.number="dialogOpen"')
        ->and($overlayTag)->not->toContain('data-track="dialog"')
        ->and($panelTag)->toContain('data-track="dialog"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->not->toContain('x-model');
});

it('omits server label ids by default and renders an explicit label id throughout', function (): void {
    $withoutId = renderDialog();
    $withId = renderDialog(['labelId' => 'my-title']);
    $withIdOverlayTag = dialogOpeningTag($withId, 'overlay');
    $withIdPanelTag = dialogOpeningTag($withId, 'panel');
    $withIdTitleTag = dialogOpeningTag($withId, 'title');

    expect($withoutId)->not->toContain('aria-labelledby=')
        ->and($withoutId)->not->toContain(' id=')
        ->and($withIdOverlayTag)->toContain("labelId: 'my-title'")
        ->and($withIdPanelTag)->toContain('aria-labelledby="my-title"')
        ->and($withIdTitleTag)->toContain('id="my-title"');
});

it('substitutes malformed UTF-8 in the Alpine label id option', function (): void {
    $overlayTag = dialogOpeningTag(renderDialog([
        'labelId' => "\xC3\x28",
    ]), 'overlay');

    expect($overlayTag)->toContain("labelId: '�('");
});

it('renders the header and Htmlable-aware title contract', function (): void {
    $raw = renderDialog(['title' => new HtmlString('<strong>Raw title</strong>')]);
    $escaped = renderDialog(['title' => '<strong>Escaped title</strong>']);
    $rawTitleTag = dialogOpeningTag($raw, 'title');

    expect(dialogOpeningTag($raw, 'header'))->toContain('class="lyra-dialog__header"')
        ->and($rawTitleTag)->toContain('class="lyra-dialog__title"')
        ->and($rawTitleTag)->toContain('x-bind="title"')
        ->and($raw)->toContain('<strong>Raw title</strong>')
        ->and($escaped)->toContain('&lt;strong&gt;Escaped title&lt;/strong&gt;')
        ->and($escaped)->not->toContain('<strong>Escaped title</strong>');
});

it('renders the default close control and exact React icon', function (): void {
    $html = renderDialog();
    $buttonTag = dialogOpeningTag($html, 'close');
    $svgTag = dialogOpeningTag($html, 'svg');

    expect($buttonTag)->toContain('type="button"')
        ->and($buttonTag)->toContain('class="lyra-dialog__close"')
        ->and($buttonTag)->toContain('aria-label="Close"')
        ->and($buttonTag)->toContain('x-bind="close"')
        ->and($svgTag)->toContain('width="14"')
        ->and($svgTag)->toContain('height="14"')
        ->and($svgTag)->toContain('viewBox="0 0 24 24"')
        ->and($svgTag)->toContain('fill="none"')
        ->and($svgTag)->toContain('stroke="currentColor"')
        ->and($svgTag)->toContain('stroke-width="2.5"')
        ->and($svgTag)->toContain('stroke-linecap="round"')
        ->and($html)->toContain('<path d="M18 6 6 18M6 6l12 12" />');
});

it('overrides the close label and omits the close control when not closable', function (): void {
    $customLabelTag = dialogOpeningTag(renderDialog(['closeLabel' => 'Dismiss dialog']), 'close');
    $notClosable = renderDialog(['closable' => false]);

    expect($customLabelTag)->toContain('aria-label="Dismiss dialog"')
        ->and($notClosable)->not->toContain('lyra-dialog__close')
        ->and($notClosable)->not->toContain('x-bind="close"')
        ->and($notClosable)->not->toContain('M18 6 6 18M6 6l12 12');
});

it('wraps the body and renders a footer only when its slot is present', function (): void {
    $withoutFooter = renderDialog(body: '<p>Body content</p>');
    $withFooter = renderDialog(
        body: '<p>Body content</p>',
        footer: '<button type="button">Save</button>',
    );

    expect(dialogOpeningTag($withoutFooter, 'body'))->toContain('class="lyra-dialog__body"')
        ->and($withoutFooter)->toContain('<p>Body content</p>')
        ->and($withoutFooter)->not->toContain('lyra-dialog__footer')
        ->and(dialogOpeningTag($withFooter, 'footer'))->toContain('class="lyra-dialog__footer"')
        ->and($withFooter)->toContain('<button type="button">Save</button>');
});

it('seeds an open Livewire dialog and places wire model on the modelable overlay', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::dialog title="Account" :default-open="$open" wire:model="open" data-track="dialog">
                    Dialog body
                </x-lyra::dialog>
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $overlayTag = dialogOpeningTag($html, 'overlay');
    $panelTag = dialogOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('x-data="lyraDialog({ defaultOpen: true, closeOnEsc: true, closeOnOverlayClick: true })"')
        ->and($overlayTag)->toContain('x-modelable="open"')
        ->and($overlayTag)->toContain('wire:model="open"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->toContain('data-track="dialog"');
});
