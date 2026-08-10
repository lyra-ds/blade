<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;

function renderWeeklyScheduleEditor(array $props = [], bool $short = false): string
{
    $value = $props['value'] ?? null;
    $exceptions = $props['exceptions'] ?? [];
    $defaultRange = $props['defaultRange'] ?? null;
    $weekStartsOn = $props['weekStartsOn'] ?? 1;
    $showExceptions = $props['showExceptions'] ?? true;
    $labels = $props['labels'] ?? [];
    $name = $props['name'] ?? null;
    unset(
        $props['value'],
        $props['exceptions'],
        $props['defaultRange'],
        $props['weekStartsOn'],
        $props['showExceptions'],
        $props['labels'],
        $props['name'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');
    $component = $short ? 'lyra:weekly-schedule-editor' : 'x-lyra::weekly-schedule-editor';

    return Blade::render(
        sprintf(
            '<%1$s :value="$value" :exceptions="$exceptions" :default-range="$defaultRange" :week-starts-on="$weekStartsOn" :show-exceptions="$showExceptions" :labels="$labels" :name="$name" %2$s />',
            $component,
            $attributes,
        ),
        compact('value', 'exceptions', 'defaultRange', 'weekStartsOn', 'showExceptions', 'labels', 'name'),
    );
}

/** @return array{DOMDocument, DOMXPath} */
function weeklyScheduleDocument(string $html): array
{
    $document = new DOMDocument;
    $loaded = @$document->loadHTML(
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING,
    );

    expect($loaded)->toBeTrue();

    return [$document, new DOMXPath($document)];
}

function weeklyScheduleElement(string $html, string $target): DOMElement
{
    [, $xpath] = weeklyScheduleDocument($html);
    $root = '//*[contains(concat(" ", normalize-space(@class), " "), " lyra-sched ")]';
    $query = match ($target) {
        'root' => $root,
        'row' => $root.'/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__row ")][1]',
        'daycell' => $root.'/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__row ")][1]/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__daycell ")]',
        'ranges' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__ranges ")][1]',
        'range' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__range ")][1]',
        'dash' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__dash ")][1]',
        'ghost' => $root.'//button[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__ghostbtn ")][1]',
        'error' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__error ")][1]',
        'add-range' => $root.'//button[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__addrange ")][1]',
        'off' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__off ")][1]',
        'actions' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__actions ")][1]',
        'copy' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__copy ")][1]',
        'copy-title' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__copy-title ")][1]',
        'check-row' => $root.'//label[contains(concat(" ", normalize-space(@class), " "), " lyra-check-row ")][1]',
        'checkbox' => $root.'//input[contains(concat(" ", normalize-space(@class), " "), " lyra-checkbox ")][1]',
        'exceptions' => $root.'/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc ")]',
        'exception-row' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc-row ")][1]',
        'exception-date' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc-date ")][1]',
    };
    $element = $xpath->query($query)?->item(0);

    expect($element)->toBeInstanceOf(DOMElement::class);

    return $element;
}

/** @return array<string, mixed> */
function weeklyScheduleOptions(string $html): array
{
    $expression = weeklyScheduleElement($html, 'root')->getAttribute('x-data');

    expect($expression)->toStartWith('lyraWeeklyScheduleEditor(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraWeeklyScheduleEditor('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return list<DOMElement> */
function weeklyScheduleRows(string $html): array
{
    [, $xpath] = weeklyScheduleDocument($html);

    return collect($xpath->query(
        '//*[contains(concat(" ", normalize-space(@class), " "), " lyra-sched ")]/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__row ")]',
    ))->map(fn (DOMElement $element): DOMElement => $element)->all();
}

dataset('weekly schedule class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/weekly-schedule-editor.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the weekly-schedule-editor class-emission fixture.');
    }

    return collect(json_decode($contents, true, flags: JSON_THROW_ON_ERROR))
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])->all();
});

it('has a weekly schedule editor component view', function (): void {
    expect(file_exists(dirname(__DIR__, 2).'/resources/views/components/weekly-schedule-editor.blade.php'))->toBeTrue();
});

it('emits every React-derived class string from the independent fixture', function (array $case): void {
    $html = renderWeeklyScheduleEditor($case['props']);

    expect(weeklyScheduleElement($html, $case['target'])->getAttribute('class'))
        ->toBe($case['expected_class']);
})->with('weekly schedule class emission');

it('serves seven React-shaped rows with enabled and unavailable first-render states', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [
            1 => [['start' => '09:00', 'end' => '17:00']],
        ],
        'class' => 'booking utility',
        'id' => 'working-hours',
    ]);
    $rows = weeklyScheduleRows($html);

    expect($rows)->toHaveCount(7)
        ->and(weeklyScheduleElement($html, 'root')->getAttribute('class'))->toBe('lyra-sched booking utility')
        ->and(weeklyScheduleElement($html, 'root')->getAttribute('id'))->toBe('working-hours');

    $monday = $rows[0];
    $tuesday = $rows[1];
    $mondaySwitch = $monday->getElementsByTagName('input')->item(0);
    $mondayRanges = (new DOMXPath($monday->ownerDocument))->query(
        './/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__ranges ")]',
        $monday,
    )->item(0);
    $mondayOff = (new DOMXPath($monday->ownerDocument))->query(
        './/span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__off ")]',
        $monday,
    )->item(0);
    $tuesdayRanges = (new DOMXPath($tuesday->ownerDocument))->query(
        './/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__ranges ")]',
        $tuesday,
    )->item(0);
    $tuesdayOff = (new DOMXPath($tuesday->ownerDocument))->query(
        './/span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__off ")]',
        $tuesday,
    )->item(0);

    expect(trim($monday->getElementsByTagName('label')->item(0)->textContent))->toBe('Monday')
        ->and($mondaySwitch->hasAttribute('checked'))->toBeTrue()
        ->and($mondaySwitch->getAttribute('x-bind:checked'))->toBe('enabled(day)')
        ->and($mondayRanges->hasAttribute('x-cloak'))->toBeFalse()
        ->and($mondayOff->hasAttribute('x-cloak'))->toBeTrue()
        ->and(trim($tuesday->getElementsByTagName('label')->item(0)->textContent))->toBe('Tuesday')
        ->and($tuesdayRanges->hasAttribute('x-cloak'))->toBeTrue()
        ->and($tuesdayOff->hasAttribute('x-cloak'))->toBeFalse();
});

it('orders all seven served rows from the configured first weekday', function (): void {
    $labels = static fn (string $html): array => collect(weeklyScheduleRows($html))
        ->map(fn (DOMElement $row): string => trim($row->getElementsByTagName('label')->item(0)->textContent))
        ->all();

    expect($labels(renderWeeklyScheduleEditor(['weekStartsOn' => 0])))->toBe([
        'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',
    ])->and($labels(renderWeeklyScheduleEditor(['weekStartsOn' => 1])))->toBe([
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
    ]);
});

it('renders runtime ranges through x-for with aliases validation controls and the en dash', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [
            1 => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '13:00', 'end' => '13:00'],
            ],
        ],
    ]);
    [, $xpath] = weeklyScheduleDocument($html);
    $rangeTemplate = $xpath->query('//template[contains(@x-for, "rangesFor(day)")]')->item(0);
    $removeTemplate = $xpath->query('//template[@x-if="rangesFor(day).length > 1"]')->item(0);
    $timeInputs = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " lyra-timeinput ")]', $rangeTemplate);
    $endInput = collect($xpath->query('.//input', $rangeTemplate))
        ->first(fn (DOMElement $input): bool => $input->hasAttribute('x-bind:aria-invalid'));
    $remove = $xpath->query('.//button[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__ghostbtn ")]', $removeTemplate)->item(0);
    $error = weeklyScheduleElement($html, 'error');
    $add = weeklyScheduleElement($html, 'add-range');

    expect($rangeTemplate)->toBeInstanceOf(DOMElement::class)
        ->and($rangeTemplate->getAttribute('x-for'))->toBe('(range, index) in rangesFor(day)')
        ->and($rangeTemplate->childNodes->length)->toBeGreaterThan(0)
        ->and($html)->toContain('get t() { return rangesFor(day)[index].start }')
        ->and($html)->toContain('set t(v) { setRangeStart(day, index, v) }')
        ->and($html)->toContain('get t() { return rangesFor(day)[index].end }')
        ->and($html)->toContain('set t(v) { setRangeEnd(day, index, v) }')
        ->and($timeInputs->length)->toBe(2)
        ->and(trim(weeklyScheduleElement($html, 'dash')->textContent))->toBe('–')
        ->and($endInput->getAttribute('x-bind:aria-invalid'))->toContain('invalid(range)')
        ->and($removeTemplate)->toBeInstanceOf(DOMElement::class)
        ->and($remove->getAttribute('type'))->toBe('button')
        ->and($remove->getAttribute('x-on:click'))->toBe('removeRange(day, index)')
        ->and($error->getAttribute('x-show'))->toBe('invalid(range)')
        ->and(trim($error->textContent))->toBe('End time must be after start time.')
        ->and($add->getAttribute('x-on:click'))->toBe('addRange(day)')
        ->and(trim($add->textContent))->toBe('+ Add interval')
        ->and(weeklyScheduleOptions($html)['value'][1][1])->toBe(['start' => '13:00', 'end' => '13:00']);
});

it('renders x and copy icons at size 15 in their React locations', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [1 => [
            ['start' => '09:00', 'end' => '12:00'],
            ['start' => '13:00', 'end' => '17:00'],
        ]],
        'exceptions' => [['date' => '2026-08-10', 'ranges' => []]],
    ]);
    [, $xpath] = weeklyScheduleDocument($html);
    $removeRange = $xpath->query('//button[@aria-label="Remove interval"]//svg')->item(0);
    $copy = $xpath->query('//button[@aria-label="Copy Monday to other days"]//svg')->item(0);
    $removeException = $xpath->query('//button[@aria-label="Remove exception"]//svg')->item(0);

    foreach ([$removeRange, $copy, $removeException] as $icon) {
        expect($icon)->toBeInstanceOf(DOMElement::class)
            ->and($icon->getAttribute('width'))->toBe('15')
            ->and($icon->getAttribute('height'))->toBe('15');
    }
});

it('serves and reactively interpolates all four day templates', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [1 => [['start' => '09:00', 'end' => '17:00']]],
        'labels' => [
            'weekdays' => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
            'copyToOtherDays' => 'Dupliquer {day}',
            'copyFrom' => 'Depuis {day}',
            'startTime' => 'Début {day}',
            'endTime' => 'Fin {day}',
        ],
    ]);
    [, $xpath] = weeklyScheduleDocument($html);
    $copy = $xpath->query('//button[@aria-label="Dupliquer Lun"]')->item(0);
    $start = $xpath->query('//input[@aria-label="Début Lun"]')->item(0);
    $end = $xpath->query('//input[@aria-label="Fin Lun"]')->item(0);

    expect($copy)->toBeInstanceOf(DOMElement::class)
        ->and($copy->getAttribute(':aria-label'))->toContain("label('copyToOtherDays'")
        ->and($copy->getAttribute('title'))->toBe('Depuis Lun')
        ->and($copy->getAttribute(':title'))->toContain("label('copyFrom'")
        ->and($start)->toBeInstanceOf(DOMElement::class)
        ->and($start->getAttribute('x-bind:aria-label'))->toContain("label('startTime'")
        ->and($end)->toBeInstanceOf(DOMElement::class)
        ->and($end->getAttribute('x-bind:aria-label'))->toContain("label('endTime'");
});

it('serves the measured default exception date and range formatting', function (): void {
    $html = renderWeeklyScheduleEditor([
        'exceptions' => [[
            'date' => '2026-08-10',
            'ranges' => [
                ['start' => '09:00', 'end' => '12:00'],
                ['start' => '13:00', 'end' => '17:00'],
            ],
        ]],
    ]);
    $row = weeklyScheduleElement($html, 'exception-row');
    $spans = $row->getElementsByTagName('span');
    [, $xpath] = weeklyScheduleDocument($html);
    $runtimeRow = $xpath->query('//template[@x-for="(exception, index) in exceptions"]')->item(0);

    expect(trim($spans->item(0)->textContent))->toBe('Aug 10, 2026')
        ->and(trim($spans->item(1)->textContent))->toBe('09:00–12:00, 13:00–17:00')
        ->and($row->getAttribute('x-show'))->toBe('false')
        ->and($runtimeRow)->toBeInstanceOf(DOMElement::class)
        ->and($xpath->query('.//span[@x-text="formatDate(exception.date)"]', $runtimeRow)->length)->toBe(1)
        ->and($xpath->query('.//span[@x-text="exceptionText(exception)"]', $runtimeRow)->length)->toBe(1);
});

it('serves each copy popover closed with six targets and a disabled Apply button', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [1 => [['start' => '09:00', 'end' => '17:00']]],
    ]);
    [, $xpath] = weeklyScheduleDocument($html);
    $monday = $xpath->query(
        '//*[contains(concat(" ", normalize-space(@class), " "), " lyra-sched ")]/div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__row ")][1]',
    )->item(0);
    $panel = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " lyra-popover ")]', $monday)->item(0);
    $title = $xpath->query('.//span[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__copy-title ")]', $monday)->item(0);
    $checks = $xpath->query('.//label[contains(concat(" ", normalize-space(@class), " "), " lyra-check-row ")]', $monday);
    $apply = $xpath->query('.//button[contains(concat(" ", normalize-space(@class), " "), " lyra-btn ")]', $monday)->item(0);
    $targetLabels = collect($checks)->map(fn (DOMElement $label): string => trim($label->textContent))->all();

    expect($html)->toContain('get copyOpen() { return copyOpenFor(day) }')
        ->and($html)->toContain('set copyOpen(v) { setCopyOpen(day, v) }')
        ->and($panel->hasAttribute('x-cloak'))->toBeTrue()
        ->and(trim($title->textContent))->toBe('Copy Monday to…')
        ->and($targetLabels)->toBe(['Sunday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])
        ->and($apply->hasAttribute('disabled'))->toBeTrue()
        ->and($apply->getAttribute('x-bind:disabled'))->toBe('picked(day).length === 0')
        ->and($apply->getAttribute('x-on:click'))->toBe('applyCopy(day)')
        ->and(trim($apply->textContent))->toBe('Apply');
});

it('serves exception rows full-day text and the date-picker composition point', function (): void {
    Carbon::setTestNow('2026-08-10');

    try {
        $html = renderWeeklyScheduleEditor([
            'exceptions' => [
                ['date' => '2026-08-12', 'ranges' => []],
                ['date' => '2026-08-10', 'ranges' => []],
            ],
        ]);
    } finally {
        Carbon::setTestNow();
    }

    [, $xpath] = weeklyScheduleDocument($html);
    $rows = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc-row ") and not(ancestor::template)]');
    $dates = collect($rows)->map(fn (DOMElement $row): string => trim($row->getElementsByTagName('span')->item(0)->textContent))->all();
    $picker = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-datepicker ")]/ancestor::*[@x-data][contains(@x-data, "lyraDatePicker")][1]')->item(0)
        ?? $xpath->query('//*[@x-data and starts-with(@x-data, "lyraDatePicker(")]')->item(0);

    expect($dates)->toBe(['Aug 12, 2026', 'Aug 10, 2026'])
        ->and($rows->item(0)->textContent)->toContain('Unavailable all day')
        ->and($html)->toContain('get exceptionDate() { return null }')
        ->and($html)->toContain('set exceptionDate(v) { addException(v) }')
        ->and($picker)->toBeInstanceOf(DOMElement::class)
        ->and($picker->getAttribute('x-model'))->toBe('exceptionDate')
        ->and($picker->getAttribute('x-data'))->toContain('Add exception');
});

it('preserves consumer exception order in SSR state and form submission', function (): void {
    $exceptions = [
        ['date' => '2026-09-01', 'ranges' => [['start' => '09:00', 'end' => '12:00']]],
        ['date' => '2026-08-10', 'ranges' => []],
    ];
    $html = renderWeeklyScheduleEditor([
        'exceptions' => $exceptions,
        'name' => 'availability',
    ]);
    [, $xpath] = weeklyScheduleDocument($html);
    $rows = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc-row ") and not(ancestor::template)]');
    $dates = collect($rows)->map(fn (DOMElement $row): string => trim($row->getElementsByTagName('span')->item(0)->textContent))->all();
    $hidden = $xpath->query('//input[@name="availability[exceptions]"]')->item(0);

    expect($dates)->toBe(['Sep 01, 2026', 'Aug 10, 2026'])
        ->and(weeklyScheduleOptions($html)['exceptions'])->toBe($exceptions)
        ->and($hidden)->toBeInstanceOf(DOMElement::class)
        ->and(json_decode($hidden->getAttribute('value'), true, flags: JSON_THROW_ON_ERROR))->toBe($exceptions);
});

it('omits the exception block when showExceptions is false', function (): void {
    $html = renderWeeklyScheduleEditor([
        'showExceptions' => false,
        'exceptions' => [['date' => '2026-08-10', 'ranges' => []]],
    ]);
    [, $xpath] = weeklyScheduleDocument($html);

    expect($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-sched__exc ")]')->length)->toBe(0)
        ->and(weeklyScheduleOptions($html)['showExceptions'])->toBeFalse();
});

it('emits exactly two JSON hidden inputs only for a consumer name prefix', function (): void {
    $value = [1 => [['start' => '09:00', 'end' => '17:00']]];
    $exceptions = [['date' => '2026-08-10', 'ranges' => []]];
    $withoutName = renderWeeklyScheduleEditor(['value' => $value, 'exceptions' => $exceptions]);
    $withName = renderWeeklyScheduleEditor([
        'value' => $value,
        'exceptions' => $exceptions,
        'name' => 'availability',
    ]);
    [, $withoutXPath] = weeklyScheduleDocument($withoutName);
    [, $withXPath] = weeklyScheduleDocument($withName);
    $hidden = $withXPath->query('//input[@type="hidden"]');

    expect($withoutXPath->query('//input[@type="hidden"]')->length)->toBe(0)
        ->and($hidden->length)->toBe(2)
        ->and($hidden->item(0)->getAttribute('name'))->toBe('availability[value]')
        ->and($hidden->item(0)->getAttribute(':value'))->toBe('JSON.stringify(value)')
        ->and(json_decode($hidden->item(0)->getAttribute('value'), true, flags: JSON_THROW_ON_ERROR))->toBe(weeklyScheduleOptions($withName)['value'])
        ->and($hidden->item(1)->getAttribute('name'))->toBe('availability[exceptions]')
        ->and($hidden->item(1)->getAttribute(':value'))->toBe('JSON.stringify(exceptions)')
        ->and(json_decode($hidden->item(1)->getAttribute('value'), true, flags: JSON_THROW_ON_ERROR))->toBe($exceptions);
});

it('defaults value modelability and whitelists exceptions as the only opt-in alternative', function (): void {
    $default = renderWeeklyScheduleEditor(['wire:model.live' => 'schedule']);
    $exceptions = renderWeeklyScheduleEditor([
        'x-modelable' => 'exceptions',
        'x-model' => 'specialDates',
        'x-data' => 'window.pwned = true',
    ]);
    $unknown = renderWeeklyScheduleEditor(['x-modelable' => 'invented']);

    expect(weeklyScheduleElement($default, 'root')->getAttribute('x-modelable'))->toBe('value')
        ->and(weeklyScheduleElement($default, 'root')->getAttribute('wire:model.live'))->toBe('schedule')
        ->and(weeklyScheduleElement($exceptions, 'root')->getAttribute('x-modelable'))->toBe('exceptions')
        ->and(weeklyScheduleElement($exceptions, 'root')->getAttribute('x-model'))->toBe('specialDates')
        ->and(weeklyScheduleElement($unknown, 'root')->getAttribute('x-modelable'))->toBe('value')
        ->and(substr_count($exceptions, 'x-data="lyraWeeklyScheduleEditor('))->toBe(1)
        ->and($exceptions)->not->toContain('window.pwned');
});

it('normalizes partial schedules malformed ranges weekdays flags and exceptions', function (): void {
    $html = renderWeeklyScheduleEditor([
        'value' => [
            1 => [
                ['start' => '09:00', 'end' => '17:00'],
                ['start' => 'nope', 'end' => '17:00'],
                ['start' => '09:00'],
                'bad',
            ],
            8 => [['start' => '10:00', 'end' => '11:00']],
        ],
        'defaultRange' => ['start' => 'bad', 'end' => '17:00'],
        'weekStartsOn' => 9,
        'showExceptions' => 'false',
        'exceptions' => [
            ['date' => '2026-08-12', 'ranges' => []],
            ['ranges' => []],
            ['date' => 'not-a-date', 'ranges' => []],
            ['date' => '2026-08-10', 'ranges' => [['start' => '08:00', 'end' => '09:00']]],
        ],
    ]);
    $options = weeklyScheduleOptions($html);

    expect(array_keys($options['value']))->toBe([0, 1, 2, 3, 4, 5, 6])
        ->and($options['value'][1])->toBe([['start' => '09:00', 'end' => '17:00']])
        ->and($options['value'][0])->toBe([])
        ->and($options['defaultRange'])->toBe(['start' => '09:00', 'end' => '17:00'])
        ->and($options['weekStartsOn'])->toBe(1)
        ->and($options['showExceptions'])->toBeTrue()
        ->and(array_column($options['exceptions'], 'date'))->toBe(['2026-08-12', '2026-08-10']);
});

it('hardens labels weekdays and time data in the root JSON literal', function (): void {
    $payload = "'); window.schedulePwned = true; //\"</script><img onerror=alert(1)>";
    $weekdays = [$payload, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $html = renderWeeklyScheduleEditor([
        'value' => [0 => [['start' => '09:00'.$payload, 'end' => '17:00']]],
        'labels' => [
            'weekdays' => $weekdays,
            'unavailable' => $payload,
        ],
    ]);
    $options = weeklyScheduleOptions($html);

    expect($options['labels']['weekdays'])->toBe($weekdays)
        ->and($options['labels']['unavailable'])->toBe($payload)
        ->and($options['value'][0])->toBe([])
        ->and(weeklyScheduleElement($html, 'root')->getAttribute('x-data'))->toContain('\\u003C/script\\u003E')
        ->and($html)->not->toContain('</script>')
        ->and($html)->not->toContain('<img')
        ->and(weeklyScheduleDocument($html)[0]->getElementsByTagName('img')->length)->toBe(0);
});

it('renders namespaced and short syntax identically', function (): void {
    $props = [
        'value' => [1 => [['start' => '09:00', 'end' => '17:00']]],
        'exceptions' => [['date' => '2026-08-10', 'ranges' => []]],
        'name' => 'availability',
        'class' => 'booking',
    ];
    $normalizeGeneratedIds = static fn (string $html): string => preg_replace(
        '/lyra-(?:time-input|date-picker|calendar|bottom-sheet(?:-title)?)-[a-f0-9-]+/',
        'lyra-generated-id',
        $html,
    );

    expect($normalizeGeneratedIds(renderWeeklyScheduleEditor($props, short: true)))
        ->toBe($normalizeGeneratedIds(renderWeeklyScheduleEditor($props)));
});
