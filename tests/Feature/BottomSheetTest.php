<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderBottomSheet(array $props = [], string $body = 'Bottom sheet body'): string
{
    $title = array_key_exists('title', $props) ? $props['title'] : 'Bottom sheet title';
    $ariaLabel = $props['ariaLabel'] ?? null;
    $closable = $props['closable'] ?? true;
    $closeLabel = $props['closeLabel'] ?? 'Close';
    $defaultOpen = $props['defaultOpen'] ?? false;
    unset(
        $props['title'],
        $props['ariaLabel'],
        $props['closable'],
        $props['closeLabel'],
        $props['defaultOpen'],
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
            '<x-lyra::bottom-sheet :title="$title" :aria-label="$ariaLabel" :closable="$closable" :close-label="$closeLabel" :default-open="$defaultOpen" %s>%s</x-lyra::bottom-sheet>',
            $attributes,
            $body,
        ),
        compact('title', 'ariaLabel', 'closable', 'closeLabel', 'defaultOpen'),
    );
}

function bottomSheetOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'overlay' => '/<div\b(?=[^>]*\bclass="lyra-bottomsheet-overlay")[^>]*>/',
        'panel' => '/<div\b(?=[^>]*\bclass="lyra-bottomsheet(?: [^"]*)?")[^>]*>/',
        'header' => '/<div\b(?=[^>]*\bclass="lyra-bottomsheet__header")[^>]*>/',
        'title' => '/<h2\b(?=[^>]*\bclass="lyra-bottomsheet__title")[^>]*>/',
        'close' => '/<button\b(?=[^>]*\bclass="lyra-bottomsheet__close")[^>]*>/',
        'body' => '/<div\b(?=[^>]*\bclass="lyra-bottomsheet__body")[^>]*>/',
        'svg' => '/<svg\b(?=[^>]*\bviewBox="0 0 24 24")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function bottomSheetClass(string $html, string $target): string
{
    $tag = bottomSheetOpeningTag($html, $target);
    $matched = preg_match('/\bclass="([^"]+)"/', $tag, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('bottom sheet class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/bottom-sheet.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the bottom-sheet class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits every exact React anatomy class string', function (array $case): void {
    $html = renderBottomSheet($case['props']);

    foreach ($case['expected_classes'] as $target => $expectedClass) {
        expect(bottomSheetClass($html, $target))->toBe($expectedClass);
    }
})->with('bottom sheet class emission');

it('emits the closed default Alpine state and cloaks the overlay', function (): void {
    $overlayTag = bottomSheetOpeningTag(renderBottomSheet(), 'overlay');

    expect($overlayTag)->toContain('class="lyra-bottomsheet-overlay"')
        ->and($overlayTag)->toContain('x-data="lyraBottomSheet({ defaultOpen: false })"')
        ->and($overlayTag)->toContain('x-modelable="open"')
        ->and($overlayTag)->toContain('x-bind="overlay"')
        ->and($overlayTag)->toContain('x-cloak');
});

it('emits an open Alpine state without cloaking the overlay', function (): void {
    $overlayTag = bottomSheetOpeningTag(renderBottomSheet(['defaultOpen' => true]), 'overlay');

    expect($overlayTag)->toContain('x-data="lyraBottomSheet({ defaultOpen: true })"')
        ->and($overlayTag)->not->toContain('x-cloak');
});

it('serves the modal panel contract and appends user classes', function (): void {
    $html = renderBottomSheet([
        'class' => 'tall elevated',
        'id' => 'filters-sheet',
        'data-track' => 'bottom-sheet',
    ]);
    $overlayTag = bottomSheetOpeningTag($html, 'overlay');
    $panelTag = bottomSheetOpeningTag($html, 'panel');

    expect(bottomSheetClass($html, 'overlay'))->toBe('lyra-bottomsheet-overlay')
        ->and($overlayTag)->not->toContain('tall elevated')
        ->and($overlayTag)->not->toContain('id="filters-sheet"')
        ->and($overlayTag)->not->toContain('data-track="bottom-sheet"')
        ->and(bottomSheetClass($html, 'panel'))->toBe('lyra-bottomsheet tall elevated')
        ->and($panelTag)->toContain('id="filters-sheet"')
        ->and($panelTag)->toContain('data-track="bottom-sheet"')
        ->and($panelTag)->toContain('role="dialog"')
        ->and($panelTag)->toContain('aria-modal="true"')
        ->and($panelTag)->toContain('tabindex="-1"')
        ->and($panelTag)->toContain('x-bind="panel"');
});

it('routes model attributes to the modelable overlay', function (): void {
    $html = renderBottomSheet([
        'wire:model.live' => 'open',
        'x-model.number' => 'sheetOpen',
        'data-track' => 'bottom-sheet',
    ]);
    $overlayTag = bottomSheetOpeningTag($html, 'overlay');
    $panelTag = bottomSheetOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('wire:model.live="open"')
        ->and($overlayTag)->toContain('x-model.number="sheetOpen"')
        ->and($overlayTag)->not->toContain('data-track="bottom-sheet"')
        ->and($panelTag)->toContain('data-track="bottom-sheet"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->not->toContain('x-model');
});

it('uses a generated served title id as the exclusive accessible name', function (): void {
    $html = renderBottomSheet([
        'title' => 'Filters',
        'ariaLabel' => 'Ignored fallback',
    ]);
    $panelTag = bottomSheetOpeningTag($html, 'panel');
    $titleTag = bottomSheetOpeningTag($html, 'title');

    expect($panelTag)->toMatch('/aria-labelledby="(lyra-bottom-sheet-title-[^"]+)"/')
        ->and($panelTag)->not->toContain('aria-label=')
        ->and($titleTag)->toMatch('/id="(lyra-bottom-sheet-title-[^"]+)"/')
        ->and($titleTag)->not->toContain('x-bind="title"');

    preg_match('/aria-labelledby="([^"]+)"/', $panelTag, $panelMatch);
    preg_match('/id="([^"]+)"/', $titleTag, $titleMatch);

    expect($panelMatch[1])->toBe($titleMatch[1]);
});

it('uses aria-label and omits the title when no title exists', function (): void {
    $html = renderBottomSheet([
        'title' => null,
        'ariaLabel' => 'Filter options',
    ]);
    $panelTag = bottomSheetOpeningTag($html, 'panel');

    expect($panelTag)->toContain('aria-label="Filter options"')
        ->and($panelTag)->not->toContain('aria-labelledby=')
        ->and($html)->not->toContain('lyra-bottomsheet__title')
        ->and($html)->not->toContain('lyra-bottom-sheet-title-');
});

it('renders Htmlable titles without adding a dynamic title binding', function (): void {
    $raw = renderBottomSheet(['title' => new HtmlString('<strong>Raw title</strong>')]);
    $escaped = renderBottomSheet(['title' => '<strong>Escaped title</strong>']);
    $rawTitleTag = bottomSheetOpeningTag($raw, 'title');

    expect(bottomSheetOpeningTag($raw, 'header'))->toContain('class="lyra-bottomsheet__header"')
        ->and($rawTitleTag)->toContain('class="lyra-bottomsheet__title"')
        ->and($rawTitleTag)->not->toContain('x-bind=')
        ->and($raw)->toContain('<strong>Raw title</strong>')
        ->and($escaped)->toContain('&lt;strong&gt;Escaped title&lt;/strong&gt;')
        ->and($escaped)->not->toContain('<strong>Escaped title</strong>');
});

it('renders the default close control and exact React icon', function (): void {
    $html = renderBottomSheet();
    $buttonTag = bottomSheetOpeningTag($html, 'close');
    $svgTag = bottomSheetOpeningTag($html, 'svg');

    expect($buttonTag)->toContain('type="button"')
        ->and($buttonTag)->toContain('class="lyra-bottomsheet__close"')
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

it('overrides the close label and omits an empty header', function (): void {
    $customLabelTag = bottomSheetOpeningTag(renderBottomSheet([
        'closeLabel' => 'Dismiss filters',
    ]), 'close');
    $withoutHeader = renderBottomSheet([
        'title' => null,
        'ariaLabel' => 'Filter options',
        'closable' => false,
    ]);

    expect($customLabelTag)->toContain('aria-label="Dismiss filters"')
        ->and($withoutHeader)->not->toContain('lyra-bottomsheet__header')
        ->and($withoutHeader)->not->toContain('lyra-bottomsheet__close')
        ->and($withoutHeader)->not->toContain('x-bind="close"');
});

it('wraps the default slot in the exact body anatomy', function (): void {
    $html = renderBottomSheet(body: '<p>Filter controls</p>');

    expect(bottomSheetOpeningTag($html, 'body'))->toContain('class="lyra-bottomsheet__body"')
        ->and($html)->toContain('<p>Filter controls</p>');
});

it('seeds an open Livewire sheet and keeps wire model on the overlay', function (): void {
    $component = new class extends Component
    {
        public bool $open = true;

        public function render(): string
        {
            return <<<'BLADE'
                <lyra:bottom-sheet title="Filters" :default-open="$open" wire:model="open" data-track="bottom-sheet">
                    Filter controls
                </lyra:bottom-sheet>
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $overlayTag = bottomSheetOpeningTag($html, 'overlay');
    $panelTag = bottomSheetOpeningTag($html, 'panel');

    expect($overlayTag)->toContain('x-data="lyraBottomSheet({ defaultOpen: true })"')
        ->and($overlayTag)->toContain('x-modelable="open"')
        ->and($overlayTag)->toContain('wire:model="open"')
        ->and($panelTag)->not->toContain('wire:model')
        ->and($panelTag)->toContain('data-track="bottom-sheet"');
});
