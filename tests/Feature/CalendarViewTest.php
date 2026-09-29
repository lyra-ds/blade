<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

function renderCalendarView(array $props = [], string $slots = ''): string
{
    $view = $props['view'] ?? null;
    $date = $props['date'] ?? null;
    $events = $props['events'] ?? [];
    $availability = $props['availability'] ?? [];
    $labels = $props['labels'] ?? [];
    unset($props['view'], $props['date'], $props['events'], $props['availability'], $props['labels']);

    $attributes = collect($props)->map(fn (mixed $value, string $name): string => sprintf(
        '%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES),
    ))->implode(' ');

    return Blade::render(
        '<x-lyra::calendar-view :view="$view" :date="$date" :events="$events" :availability="$availability" :labels="$labels" '.$attributes.'>'.$slots.'</x-lyra::calendar-view>',
        compact('view', 'date', 'events', 'availability', 'labels'),
    );
}

function calendarViewRoot(string $html): DOMElement
{
    $document = new DOMDocument;
    @$document->loadHTML('<!doctype html><html><body>'.$html.'</body></html>');
    $root = (new DOMXPath($document))->query('//*[@class and contains(concat(" ", normalize-space(@class), " "), " lyra-calview ")]')->item(0);
    expect($root)->toBeInstanceOf(DOMElement::class);

    return $root;
}

dataset('calendar view class emission', function (): array {
    $cases = json_decode((string) file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/calendar-view.json'), true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)->mapWithKeys(fn (array $case, int $index): array => ["class parity {$index}" => [$case]])->all();
});

it('emits the React root class', function (array $case): void {
    expect(calendarViewRoot(renderCalendarView($case['props']))->getAttribute('class'))->toBe($case['expected_class']);
})->with('calendar view class emission');

it('maps initial state and data to the Alpine binding', function (): void {
    $events = [['id' => 1, 'start' => '2026-08-12T10:00:00', 'end' => '2026-08-12T11:00:00', 'title' => 'Session']];
    $availability = [3 => [['start' => '08:00', 'end' => '17:00']]];
    $html = renderCalendarView(['view' => 'week', 'date' => '2026-08-12', 'events' => $events, 'availability' => $availability]);
    $root = calendarViewRoot($html);
    $expression = $root->getAttribute('x-data');
    expect($expression)->toStartWith('lyraCalendarView(')->toEndWith(')');
    expect(json_decode(substr($expression, strlen('lyraCalendarView('), -1), true, flags: JSON_THROW_ON_ERROR))->toMatchArray([
        'defaultView' => 'week', 'defaultDate' => '2026-08-12', 'events' => $events, 'availability' => $availability,
    ]);
    expect($root->getAttribute('x-modelable'))->toBe('date');
});

it('serves the calendar binding structure and named slots', function (): void {
    $html = renderCalendarView([], '<x-slot:toolbarActions><button id="new-event">New</button></x-slot:toolbarActions><x-slot:popover><span x-text="popover?.event.title"></span></x-slot:popover>');

    foreach (['lyra-calview__toolbar', 'lyra-calview__head', 'lyra-calview__scroll', 'lyra-calview__grid', 'lyra-calview__col', 'lyra-calview__avail', 'lyra-calview__evt', 'lyra-calview__mgrid', 'lyra-calview__mcell', 'lyra-calview__pop'] as $class) {
        expect($html)->toContain($class);
    }
    expect($html)->toContain('x-on:click="navigate(-1)"')
        ->toContain('x-on:click="setView(\'month\')"')
        ->toContain('x-on:click="createSlot($event, day)"')
        ->toContain('x-on:click="openEvent($event, item)"')
        ->toContain('id="new-event"')
        ->toContain('x-text="popover?.event.title"');
});

it('accepts the toolbar-actions slot name', function (): void {
    $html = renderCalendarView([], '<x-slot:toolbar-actions><button id="slot-action">Add</button></x-slot:toolbar-actions>');

    expect($html)->toContain('id="slot-action"');
});

it('translates controls and supports view modelling', function (): void {
    $html = renderCalendarView(['labels' => ['today' => 'Hoje', 'view' => 'Visualização do calendário', 'week' => 'Semana'], 'x-modelable' => 'view', 'x-model' => 'selectedView']);
    $root = calendarViewRoot($html);

    expect($root->getAttribute('x-modelable'))->toBe('view')
        ->and($root->getAttribute('x-model'))->toBe('selectedView')
        ->and($html)->toContain('aria-label="Hoje"')
        ->toContain('aria-label="Visualização do calendário"')
        ->toContain('>Semana</button>');
});

it('guards the owned Alpine root and escapes options', function (): void {
    expect(fn () => renderCalendarView(['x-data' => '{}']))->toThrow(ViewException::class, 'owns x-data');
    $root = calendarViewRoot(renderCalendarView(['labels' => ['today' => "Hoje'); window.pwned=1; //"]]));
    expect($root->getAttribute('x-data'))->not->toContain("'); window.pwned")
        ->and($root->getAttribute('x-data'))->toContain('\\u0027');
});

it('renders the same result in short and namespaced syntax', function (): void {
    expect(Blade::render('<lyra:calendar-view date="2026-08-12" />'))
        ->toBe(Blade::render('<x-lyra::calendar-view date="2026-08-12" />'));
});
