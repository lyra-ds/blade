<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Livewire;

function renderAccordion(array $props = []): string
{
    $items = $props['items'] ?? [
        ['id' => 'overview', 'title' => 'Overview', 'content' => 'Overview content'],
        ['id' => 'activity', 'title' => 'Activity', 'content' => 'Activity content'],
    ];
    $defaultOpen = array_key_exists('defaultOpen', $props) ? $props['defaultOpen'] : null;
    $multiple = $props['multiple'] ?? false;
    unset($props['items'], $props['defaultOpen'], $props['multiple']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::accordion :items="$items" :default-open="$defaultOpen" :multiple="$multiple" %s />',
            $attributes,
        ),
        compact('items', 'defaultOpen', 'multiple'),
    );
}

function accordionOpeningTag(string $html, string $target, ?string $value = null): string
{
    if ($target === 'root') {
        $pattern = '/<div\b(?=[^>]*\bx-data="lyraAccordion\()(?=[^>]*\bclass="lyra-accordion(?: [^"]*)?")[^>]*>/';
    } else {
        $itemPattern = sprintf(
            '/<div\b(?=[^>]*\bclass="lyra-acc__item(?: [^"]*)?")(?=[^>]*\bdata-value="%s")[^>]*>/',
            preg_quote((string) $value, '/'),
        );
        $itemMatched = preg_match($itemPattern, $html, $itemMatches, PREG_OFFSET_CAPTURE);

        expect($itemMatched)->toBe(1);

        $itemOffset = $itemMatches[0][1];
        $nextItemPattern = '/<div\b(?=[^>]*\bclass="lyra-acc__item(?: [^"]*)?")[^>]*>/';
        $nextItemMatched = preg_match(
            $nextItemPattern,
            $html,
            $nextItemMatches,
            PREG_OFFSET_CAPTURE,
            $itemOffset + strlen($itemMatches[0][0]),
        );
        $nextItemOffset = $nextItemMatched === 1 ? $nextItemMatches[0][1] : strlen($html);
        $itemMarkup = substr($html, $itemOffset, $nextItemOffset - $itemOffset);
        $pattern = match ($target) {
            'item' => $itemPattern,
            'trigger' => '/<button\b(?=[^>]*\bclass="lyra-acc__trigger")[^>]*>/',
            'panel-wrap' => '/<div\b(?=[^>]*\bclass="lyra-acc__panel-wrap")[^>]*>/',
            'panel' => '/<div\b(?=[^>]*\bclass="lyra-acc__panel")[^>]*>/',
        };

        if ($target !== 'item') {
            $html = $itemMarkup;
        }
    }

    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function accordionClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-accordion(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('accordion class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/accordion.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the accordion class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    $html = renderAccordion($case['props']);

    expect(accordionClass($html))->toBe($case['expected_class']);
})->with('accordion class emission');

it('emits the default Alpine state and passes attributes through on the root', function (): void {
    $root = accordionOpeningTag(renderAccordion([
        'id' => 'account-sections',
        'data-track' => 'accordion',
    ]), 'root');

    expect($root)->toContain('x-data="lyraAccordion({ multiple: false })"')
        ->and($root)->toContain('x-modelable="openItems"')
        ->and($root)->toContain('class="lyra-accordion"')
        ->and($root)->toContain('id="account-sections"')
        ->and($root)->toContain('data-track="accordion"');
});

it('emits multiple and defaultOpen in the exact Alpine literal', function (): void {
    $root = accordionOpeningTag(renderAccordion([
        'multiple' => true,
        'defaultOpen' => 'activity',
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraAccordion({ multiple: true, defaultOpen: 'activity' })\"")
        ->and($root)->toContain('x-modelable="openItems"');
});

it('omits the defaultOpen Alpine option when it is null', function (): void {
    $root = accordionOpeningTag(renderAccordion(['defaultOpen' => null]), 'root');

    expect($root)->toContain('x-data="lyraAccordion({ multiple: false })"')
        ->and($root)->not->toContain('defaultOpen');
});

it('substitutes malformed UTF-8 in the Alpine defaultOpen literal', function (): void {
    $defaultOpen = "\xC3\x28";
    $root = accordionOpeningTag(renderAccordion([
        'items' => [
            ['id' => $defaultOpen, 'title' => 'Malformed', 'content' => 'Content'],
        ],
        'defaultOpen' => $defaultOpen,
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraAccordion({ multiple: false, defaultOpen: '�(' })\"");
});

it('JavaScript-escapes quotes, slashes, and line breaks in the Alpine defaultOpen literal', function (): void {
    $defaultOpen = "a'b\\c\r\nd";
    $root = accordionOpeningTag(renderAccordion([
        'items' => [
            ['id' => $defaultOpen, 'title' => 'Escaped', 'content' => 'Content'],
        ],
        'defaultOpen' => $defaultOpen,
    ]), 'root');

    expect($root)->toContain("x-data=\"lyraAccordion({ multiple: false, defaultOpen: 'a\\'b\\\\c\\r\\nd' })\"");
});

it('keeps component model bindings ahead of consumer duplicate attributes', function (): void {
    $root = accordionOpeningTag(renderAccordion([
        'x-data' => 'consumerState',
        'x-modelable' => 'consumerModel',
    ]), 'root');
    $componentDataPosition = strpos($root, 'x-data="lyraAccordion({ multiple: false })"');
    $consumerDataPosition = strpos($root, 'x-data="consumerState"');
    $componentModelPosition = strpos($root, 'x-modelable="openItems"');
    $consumerModelPosition = strpos($root, 'x-modelable="consumerModel"');

    expect($componentDataPosition)->toBeInt()
        ->and($consumerDataPosition)->toBeInt()
        ->and($componentDataPosition)->toBeLessThan($consumerDataPosition)
        ->and($componentModelPosition)->toBeInt()
        ->and($consumerModelPosition)->toBeInt()
        ->and($componentModelPosition)->toBeLessThan($consumerModelPosition);
});

it('renders the complete open and closed item contracts', function (): void {
    $html = renderAccordion(['defaultOpen' => 'activity']);
    $overviewItem = accordionOpeningTag($html, 'item', 'overview');
    $overviewTrigger = accordionOpeningTag($html, 'trigger', 'overview');
    $overviewWrap = accordionOpeningTag($html, 'panel-wrap', 'overview');
    $overviewPanel = accordionOpeningTag($html, 'panel', 'overview');
    $activityItem = accordionOpeningTag($html, 'item', 'activity');
    $activityTrigger = accordionOpeningTag($html, 'trigger', 'activity');
    $activityWrap = accordionOpeningTag($html, 'panel-wrap', 'activity');
    $activityPanel = accordionOpeningTag($html, 'panel', 'activity');

    expect($overviewItem)->toContain('class="lyra-acc__item"')
        ->and($overviewItem)->toContain('data-value="overview"')
        ->and($overviewItem)->toContain('x-bind="item"')
        ->and($overviewTrigger)->toContain('type="button"')
        ->and($overviewTrigger)->toContain('class="lyra-acc__trigger"')
        ->and($overviewTrigger)->toContain('aria-expanded="false"')
        ->and($overviewTrigger)->toContain('x-bind="trigger"')
        ->and($overviewWrap)->toContain('class="lyra-acc__panel-wrap"')
        ->and($overviewWrap)->toContain(' inert')
        ->and($overviewWrap)->toContain('x-bind="panelWrap"')
        ->and($overviewPanel)->toContain('class="lyra-acc__panel"')
        ->and($overviewPanel)->toContain('x-bind="panel"')
        ->and($activityItem)->toContain('class="lyra-acc__item lyra-acc__item--open"')
        ->and($activityItem)->toContain('data-value="activity"')
        ->and($activityItem)->toContain('x-bind="item"')
        ->and($activityTrigger)->toContain('aria-expanded="true"')
        ->and($activityTrigger)->toContain('x-bind="trigger"')
        ->and($activityWrap)->not->toContain(' inert')
        ->and($activityWrap)->toContain('x-bind="panelWrap"')
        ->and($activityPanel)->toContain('x-bind="panel"');
});

it('renders all items closed when defaultOpen does not match', function (): void {
    $html = renderAccordion(['defaultOpen' => 'missing']);

    foreach (['overview', 'activity'] as $id) {
        expect(accordionOpeningTag($html, 'item', $id))->toContain('class="lyra-acc__item"')
            ->and(accordionOpeningTag($html, 'item', $id))->not->toContain('lyra-acc__item--open')
            ->and(accordionOpeningTag($html, 'trigger', $id))->toContain('aria-expanded="false"')
            ->and(accordionOpeningTag($html, 'panel-wrap', $id))->toContain(' inert');
    }
});

it('renders the exact chevron and panel nesting for each item', function (): void {
    $html = renderAccordion();

    expect(substr_count($html, '<span class="lyra-acc__chevron" aria-hidden="true"></span>'))->toBe(2)
        ->and($html)->toMatch('/<div\s+class="lyra-acc__panel-clip"\s*>\s*<div\s+class="lyra-acc__panel"\s+x-bind="panel"\s*>Overview content<\/div>\s*<\/div>/s');
});

it('renders Htmlable titles and content while escaping strings', function (): void {
    $html = renderAccordion([
        'items' => [
            [
                'id' => 'raw',
                'title' => new HtmlString('<strong>Raw title</strong>'),
                'content' => new HtmlString('<p>Raw content</p>'),
            ],
            [
                'id' => 'escaped',
                'title' => '<strong>Escaped title</strong>',
                'content' => '<p>Escaped content</p>',
            ],
        ],
    ]);

    expect($html)->toContain('<strong>Raw title</strong><span class="lyra-acc__chevron"')
        ->and($html)->toContain('<p>Raw content</p>')
        ->and($html)->toContain('&lt;strong&gt;Escaped title&lt;/strong&gt;<span class="lyra-acc__chevron"')
        ->and($html)->toContain('&lt;p&gt;Escaped content&lt;/p&gt;');
});

it('normalizes sparse item keys before rendering served state', function (): void {
    $html = renderAccordion([
        'items' => [
            3 => ['id' => 'overview', 'title' => 'Overview', 'content' => 'Overview content'],
            7 => ['id' => 'activity', 'title' => 'Activity', 'content' => 'Activity content'],
        ],
        'defaultOpen' => 'activity',
    ]);

    expect(accordionOpeningTag($html, 'item', 'overview'))->toContain('class="lyra-acc__item"')
        ->and(accordionOpeningTag($html, 'trigger', 'overview'))->toContain('aria-expanded="false"')
        ->and(accordionOpeningTag($html, 'panel-wrap', 'overview'))->toContain(' inert')
        ->and(accordionOpeningTag($html, 'item', 'activity'))->toContain('lyra-acc__item--open')
        ->and(accordionOpeningTag($html, 'trigger', 'activity'))->toContain('aria-expanded="true"')
        ->and(accordionOpeningTag($html, 'panel-wrap', 'activity'))->not->toContain(' inert');
});

it('serves no ids or trigger-panel relationship attributes', function (): void {
    $html = renderAccordion();

    expect($html)->not->toMatch('/\sid=/')
        ->and($html)->not->toContain('aria-controls=')
        ->and($html)->not->toContain('aria-labelledby=');
});

it('keeps model attributes and passthrough together on the root', function (): void {
    $root = accordionOpeningTag(renderAccordion([
        'wire:model.live' => 'openItems',
        'x-model' => 'selectedItems',
        'data-track' => 'accordion',
    ]), 'root');

    expect($root)->toContain('wire:model.live="openItems"')
        ->and($root)->toContain('x-model="selectedItems"')
        ->and($root)->toContain('data-track="accordion"');
});

it('seeds the open Livewire item and places wire model on the root', function (): void {
    $component = new class extends Component
    {
        public array $openItems = ['activity'];

        public ?string $defaultOpen = 'activity';

        public array $items = [
            ['id' => 'overview', 'title' => 'Overview', 'content' => 'Overview content'],
            ['id' => 'activity', 'title' => 'Activity', 'content' => 'Activity content'],
        ];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::accordion :items="$items" :default-open="$defaultOpen" wire:model="openItems" />
                BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = accordionOpeningTag($html, 'root');
    $activityItem = accordionOpeningTag($html, 'item', 'activity');
    $activityTrigger = accordionOpeningTag($html, 'trigger', 'activity');
    $activityWrap = accordionOpeningTag($html, 'panel-wrap', 'activity');

    expect($root)->toContain("x-data=\"lyraAccordion({ multiple: false, defaultOpen: 'activity' })\"")
        ->and($root)->toContain('x-modelable="openItems"')
        ->and($root)->toContain('wire:model="openItems"')
        ->and($activityItem)->toContain('lyra-acc__item--open')
        ->and($activityTrigger)->toContain('aria-expanded="true"')
        ->and($activityWrap)->not->toContain(' inert');
});
