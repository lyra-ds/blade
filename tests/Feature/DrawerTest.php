<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderDrawer(array $props = [], string $body = 'Drawer body', ?string $footer = null): string
{
    $title = $props['title'] ?? 'Drawer title';
    $closable = $props['closable'] ?? true;
    $closeLabel = $props['closeLabel'] ?? 'Close';
    $defaultOpen = $props['defaultOpen'] ?? false;
    $labelId = $props['labelId'] ?? null;
    unset(
        $props['title'],
        $props['closable'],
        $props['closeLabel'],
        $props['defaultOpen'],
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
            '<x-lyra::drawer :title="$title" :closable="$closable" :close-label="$closeLabel" :default-open="$defaultOpen" :label-id="$labelId" %s>%s%s</x-lyra::drawer>',
            $attributes,
            $body,
            $footerSlot,
        ),
        compact('title', 'closable', 'closeLabel', 'defaultOpen', 'labelId'),
    );
}

function drawerClass(string $html, string $target): string
{
    $class = match ($target) {
        'overlay' => 'lyra-drawer-overlay',
        'panel' => 'lyra-drawer',
    };
    $matched = preg_match(sprintf('/<div\b[^>]*\bclass="(%s(?: [^"]*)?)"/', $class), $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function drawerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'overlay' => '/<div\b(?=[^>]*\bclass="lyra-drawer-overlay")[^>]*>/',
        'panel' => '/<div\b(?=[^>]*\bclass="lyra-drawer(?: [^"]*)?")[^>]*>/',
        'header' => '/<div\b(?=[^>]*\bclass="lyra-drawer__header")[^>]*>/',
        'title' => '/<h2\b(?=[^>]*\bclass="lyra-drawer__title")[^>]*>/',
        'close' => '/<button\b(?=[^>]*\bclass="lyra-drawer__close")[^>]*>/',
        'body' => '/<div\b(?=[^>]*\bclass="lyra-drawer__body")[^>]*>/',
        'footer' => '/<div\b(?=[^>]*\bclass="lyra-drawer__footer")[^>]*>/',
        'svg' => '/<svg\b(?=[^>]*\bviewBox="0 0 24 24")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('drawer class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/drawer.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the drawer class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact fixed overlay class string', function (array $case): void {
    $html = renderDrawer($case['props']);

    expect(drawerClass($html, 'overlay'))->toBe($case['expected_class']);
})->with('drawer class emission');

it('emits the closed default Alpine state and cloaks the overlay', function (): void {
    $openingTag = drawerOpeningTag(renderDrawer(), 'overlay');

    expect($openingTag)->toContain('class="lyra-drawer-overlay"')
        ->and($openingTag)->toContain('x-data="lyraDrawer({ defaultOpen: false })"')
        ->and($openingTag)->toContain('x-modelable="open"')
        ->and($openingTag)->toContain('x-bind="overlay"')
        ->and($openingTag)->toContain('x-cloak');
});

it('emits an open Alpine state without cloaking the overlay', function (): void {
    $openingTag = drawerOpeningTag(renderDrawer([
        'defaultOpen' => true,
    ]), 'overlay');

    expect($openingTag)->toContain('x-data="lyraDrawer({ defaultOpen: true })"')
        ->and($openingTag)->not->toContain('x-cloak');
});

it('keeps passthrough attributes on the panel and appends user classes', function (): void {
    $html = renderDrawer([
        'class' => 'wide elevated',
        'id' => 'account-drawer',
        'data-track' => 'drawer',
    ]);
    $overlayTag = drawerOpeningTag($html, 'overlay');
    $panelTag = drawerOpeningTag($html, 'panel');

    expect(drawerClass($html, 'overlay'))->toBe('lyra-drawer-overlay')
        ->and($overlayTag)->not->toContain('wide elevated')
        ->and($overlayTag)->not->toContain('id="account-drawer"')
        ->and($overlayTag)->not->toContain('data-track="drawer"')
        ->and(drawerClass($html, 'panel'))->toBe('lyra-drawer wide elevated')
        ->and($panelTag)->toContain('id="account-drawer"')
        ->and($panelTag)->toContain('data-track="drawer"')
        ->and($panelTag)->toContain('role="dialog"')
        ->and($panelTag)->toContain('aria-modal="true"')
        ->and($panelTag)->toContain('tabindex="-1"')
        ->and($panelTag)->toContain('x-bind="panel"');
});

it('keeps the component panel role ahead of a consumer role attribute', function (): void {
    $panelTag = drawerOpeningTag(renderDrawer([
        'role' => 'alert',
    ]), 'panel');
    $componentRolePosition = strpos($panelTag, 'role="dialog"');
    $consumerRolePosition = strpos($panelTag, 'role="alert"');

    expect($componentRolePosition)->toBeInt()
        ->and($consumerRolePosition)->toBeInt()
        ->and($componentRolePosition)->toBeLessThan($consumerRolePosition);
});

it('moves model attributes to the modelable overlay and leaves other attributes on the panel', function (): void {
    $html = renderDrawer([
        'wire:model.live' => 'open',
        'x-model.number' => 'drawerOpen',
        'data-track' => 'drawer',
    ]);
    $overlayTag = drawerOpeningTag($html, 'overlay');
    $panelTag = drawerOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('wire:model.live="open"')
        ->and($overlayTag)->toContain('x-model.number="drawerOpen"')
        ->and($overlayTag)->not->toContain('data-track="drawer"')
        ->and($panelTag)->toContain('data-track="drawer"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->not->toContain('x-model');
});

it('omits server label ids by default and renders an explicit label id throughout', function (): void {
    $withoutId = renderDrawer();
    $withId = renderDrawer(['labelId' => 'my-title']);
    $withIdOverlayTag = drawerOpeningTag($withId, 'overlay');
    $withIdPanelTag = drawerOpeningTag($withId, 'panel');
    $withIdTitleTag = drawerOpeningTag($withId, 'title');

    expect($withoutId)->not->toContain('aria-labelledby=')
        ->and($withoutId)->not->toContain(' id=')
        ->and($withIdOverlayTag)->toContain("labelId: 'my-title'")
        ->and($withIdPanelTag)->toContain('aria-labelledby="my-title"')
        ->and($withIdTitleTag)->toContain('id="my-title"');
});

it('substitutes malformed UTF-8 in the Alpine label id option', function (): void {
    $overlayTag = drawerOpeningTag(renderDrawer([
        'labelId' => "\xC3\x28",
    ]), 'overlay');

    expect($overlayTag)->toContain("labelId: '�('");
});

it('renders the header and Htmlable-aware title contract', function (): void {
    $raw = renderDrawer(['title' => new HtmlString('<strong>Raw title</strong>')]);
    $escaped = renderDrawer(['title' => '<strong>Escaped title</strong>']);
    $rawTitleTag = drawerOpeningTag($raw, 'title');

    expect(drawerOpeningTag($raw, 'header'))->toContain('class="lyra-drawer__header"')
        ->and($rawTitleTag)->toContain('class="lyra-drawer__title"')
        ->and($rawTitleTag)->toContain('x-bind="title"')
        ->and($raw)->toContain('<strong>Raw title</strong>')
        ->and($escaped)->toContain('&lt;strong&gt;Escaped title&lt;/strong&gt;')
        ->and($escaped)->not->toContain('<strong>Escaped title</strong>');
});

it('renders the default close control and exact React icon', function (): void {
    $html = renderDrawer();
    $buttonTag = drawerOpeningTag($html, 'close');
    $svgTag = drawerOpeningTag($html, 'svg');

    expect($buttonTag)->toContain('type="button"')
        ->and($buttonTag)->toContain('class="lyra-drawer__close"')
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
    $customLabelTag = drawerOpeningTag(renderDrawer(['closeLabel' => 'Dismiss drawer']), 'close');
    $notClosable = renderDrawer(['closable' => false]);

    expect($customLabelTag)->toContain('aria-label="Dismiss drawer"')
        ->and($notClosable)->not->toContain('lyra-drawer__close')
        ->and($notClosable)->not->toContain('x-bind="close"')
        ->and($notClosable)->not->toContain('M18 6 6 18M6 6l12 12');
});

it('wraps the body and renders a footer only when its slot is present', function (): void {
    $withoutFooter = renderDrawer(body: '<p>Body content</p>');
    $withFooter = renderDrawer(
        body: '<p>Body content</p>',
        footer: '<button type="button">Save</button>',
    );

    expect(drawerOpeningTag($withoutFooter, 'body'))->toContain('class="lyra-drawer__body"')
        ->and($withoutFooter)->toContain('<p>Body content</p>')
        ->and($withoutFooter)->not->toContain('lyra-drawer__footer')
        ->and(drawerOpeningTag($withFooter, 'footer'))->toContain('class="lyra-drawer__footer"')
        ->and($withFooter)->toContain('<button type="button">Save</button>');
});

it('seeds an open Livewire drawer and places wire model on the modelable overlay', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::drawer title="Account" :default-open="$open" wire:model="open" data-track="drawer">
                    Drawer body
                </x-lyra::drawer>
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $overlayTag = drawerOpeningTag($html, 'overlay');
    $panelTag = drawerOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('x-data="lyraDrawer({ defaultOpen: true })"')
        ->and($overlayTag)->toContain('x-modelable="open"')
        ->and($overlayTag)->toContain('wire:model="open"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->toContain('data-track="drawer"');
});
