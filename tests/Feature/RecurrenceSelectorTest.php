<?php

use Illuminate\Support\Facades\Blade;

function renderRecurrenceSelector(array $props = []): string
{
    $value = $props['value'] ?? null;
    $startDate = $props['startDate'] ?? '2026-08-03';
    $defaultEndCount = $props['defaultEndCount'] ?? null;
    $conflicts = $props['conflicts'] ?? [];
    $labels = $props['labels'] ?? [];
    unset(
        $props['value'],
        $props['startDate'],
        $props['defaultEndCount'],
        $props['conflicts'],
        $props['labels'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $attributeValue, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $attributeValue, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::recurrence-selector :value="$value" :start-date="$startDate" :default-end-count="$defaultEndCount" :conflicts="$conflicts" :labels="$labels" %s />',
            $attributes,
        ),
        compact('value', 'startDate', 'defaultEndCount', 'conflicts', 'labels'),
    );
}

/** @return array{DOMDocument, DOMXPath} */
function recurrenceSelectorDocument(string $html): array
{
    $document = new DOMDocument;
    $loaded = @$document->loadHTML(
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING,
    );

    expect($loaded)->toBeTrue();

    return [$document, new DOMXPath($document)];
}

function recurrenceSelectorElement(string $html, string $target): DOMElement
{
    [, $xpath] = recurrenceSelectorDocument($html);
    $root = '//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur ")]';
    $query = match ($target) {
        'root' => $root,
        'preset-wrap' => $root.'/span[contains(concat(" ", normalize-space(@class), " "), " lyra-select-wrap ")][1]',
        'preset-select' => $root.'/span[contains(concat(" ", normalize-space(@class), " "), " lyra-select-wrap ")][1]/select',
        'custom' => $root.'/div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__custom ")]',
        'freq-row' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__freqrow ")]',
        'interval' => $root.'//input[@x-bind="intervalInput"]',
        'freq-wrap' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__freqrow ")]/span[contains(concat(" ", normalize-space(@class), " "), " lyra-select-wrap ")]',
        'freq-select' => $root.'//select[@x-bind="freqSelect"]',
        'days' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__days ")]',
        'day' => $root.'//button[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__day ")][1]',
        'end-row' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__endrow ")]',
        'end-wrap' => $root.'//div[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__endrow ")]/span[contains(concat(" ", normalize-space(@class), " "), " lyra-select-wrap ")]',
        'end-select' => $root.'//select[@x-bind="endSelect"]',
        'count-input' => $root.'//input[@x-bind="countInput"]',
        'end-date' => $root.'//span[contains(concat(" ", normalize-space(@class), " "), " lyra-recur__enddate ")]',
        'summary' => $root.'/span[@aria-live="polite"]',
        'conflicts' => $root.'/span[@role="status"]',
    };
    $element = $xpath->query($query)?->item(0);

    expect($element)->toBeInstanceOf(DOMElement::class);

    return $element;
}

/** @return array<string, mixed> */
function recurrenceSelectorOptions(string $html): array
{
    $expression = recurrenceSelectorElement($html, 'root')->getAttribute('x-data');

    expect($expression)->toStartWith('lyraRecurrenceSelector(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraRecurrenceSelector('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return list<DOMElement> */
function recurrenceSelectorOptionsElements(string $html): array
{
    [, $xpath] = recurrenceSelectorDocument($html);

    return collect($xpath->query('//select[@x-bind="presetSelect"]/option'))
        ->map(fn (DOMElement $option): DOMElement => $option)
        ->all();
}

/** @return list<array<string, mixed>> */
function recurrenceSelectorCalendarOptions(string $html): array
{
    preg_match_all('/<div\b(?=[^>]*\bx-data="lyraCalendar\()[^>]*>/', $html, $matches);

    return array_map(function (string $tag): array {
        preg_match('/\bx-data="([^"]*)"/', $tag, $attribute);
        $expression = html_entity_decode($attribute[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        expect($expression)->toStartWith('lyraCalendar(')->toEndWith(')');

        return json_decode(
            substr($expression, strlen('lyraCalendar('), -1),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }, $matches[0]);
}

dataset('recurrence selector class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/recurrence-selector.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the recurrence-selector class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

dataset('recurrence summaries', [
    'none' => [null, 'Does not repeat'],
    'explicit none' => [['freq' => 'none'], 'Does not repeat'],
    'weekly defaults' => [['freq' => 'weekly'], 'Repeats every Wednesday'],
    'monthly defaults' => [['freq' => 'monthly'], 'Repeats every month on the 1st Wednesday'],
    'weekly every week never' => [[
        'freq' => 'weekly', 'interval' => 1, 'byWeekday' => [3, 1, 2], 'end' => ['type' => 'never'],
    ], 'Repeats every Monday, Tuesday, and Wednesday'],
    'weekly every week count' => [[
        'freq' => 'weekly', 'interval' => 1, 'byWeekday' => [1, 2], 'end' => ['type' => 'count', 'count' => 4],
    ], 'Repeats every Monday and Tuesday, 4 times'],
    'weekly every week date' => [[
        'freq' => 'weekly', 'interval' => 1, 'byWeekday' => [1], 'end' => ['type' => 'date', 'date' => '2026-08-31'],
    ], 'Repeats every Monday, until Aug 31, 2026'],
    'weekly interval never' => [[
        'freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3], 'end' => ['type' => 'never'],
    ], 'Repeats every 3 weeks on Wednesday'],
    'weekly interval count' => [[
        'freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3], 'end' => ['type' => 'count', 'count' => 6],
    ], 'Repeats every 3 weeks on Wednesday, 6 times'],
    'weekly interval date' => [[
        'freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3], 'end' => ['type' => 'date', 'date' => '2026-09-15'],
    ], 'Repeats every 3 weeks on Wednesday, until Sep 15, 2026'],
    'monthly every month never' => [[
        'freq' => 'monthly', 'interval' => 1, 'byWeekday' => [3], 'end' => ['type' => 'never'],
    ], 'Repeats every month on the 1st Wednesday'],
    'monthly every month count' => [[
        'freq' => 'monthly', 'interval' => 1, 'byWeekday' => [3], 'end' => ['type' => 'count', 'count' => 5],
    ], 'Repeats every month on the 1st Wednesday, 5 times'],
    'monthly every month date' => [[
        'freq' => 'monthly', 'interval' => 1, 'byWeekday' => [3], 'end' => ['type' => 'date', 'date' => '2027-01-09'],
    ], 'Repeats every month on the 1st Wednesday, until Jan 9, 2027'],
    'monthly interval never' => [[
        'freq' => 'monthly', 'interval' => 2, 'byWeekday' => [3], 'end' => ['type' => 'never'],
    ], 'Repeats every 2 months on the 1st Wednesday'],
    'monthly interval count' => [[
        'freq' => 'monthly', 'interval' => 2, 'byWeekday' => [3], 'end' => ['type' => 'count', 'count' => 7],
    ], 'Repeats every 2 months on the 1st Wednesday, 7 times'],
    'monthly interval date' => [[
        'freq' => 'monthly', 'interval' => 2, 'byWeekday' => [3], 'end' => ['type' => 'date', 'date' => '2027-02-14'],
    ], 'Repeats every 2 months on the 1st Wednesday, until Feb 14, 2027'],
]);

dataset('preset selection', [
    'none' => [null, 'none', true],
    'weekly' => [['freq' => 'weekly', 'interval' => 1, 'byWeekday' => [3]], 'weekly', true],
    'weekly float interval' => [['freq' => 'weekly', 'interval' => 1.0, 'byWeekday' => [3]], 'weekly', true],
    'biweekly' => [['freq' => 'weekly', 'interval' => 2, 'byWeekday' => [3]], 'biweekly', true],
    'monthly' => [['freq' => 'monthly', 'interval' => 1, 'byWeekday' => [3]], 'monthly', true],
    'custom interval' => [['freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3]], 'custom', false],
    'custom weekday set' => [['freq' => 'weekly', 'interval' => 1, 'byWeekday' => [1, 3]], 'custom', false],
]);

it('emits every React-derived class string from the independent fixture', function (array $case): void {
    $html = renderRecurrenceSelector($case['props']);
    $element = recurrenceSelectorElement($html, $case['target']);

    expect($element->getAttribute('class'))->toBe($case['expected_class']);
})->with('recurrence selector class emission');

it('renders the complete React topology with the consumer class only on the root', function (): void {
    $html = renderRecurrenceSelector([
        'class' => 'booking-rule',
        'id' => 'repeat-rule',
        'data-track' => 'recurrence',
    ]);
    [, $xpath] = recurrenceSelectorDocument($html);
    $root = recurrenceSelectorElement($html, 'root');
    $children = [];

    foreach ($root->childNodes as $child) {
        if ($child instanceof DOMElement) {
            $children[] = [$child->tagName, $child->getAttribute('class')];
        }
    }

    expect($children)->toBe([
        ['span', 'lyra-select-wrap'],
        ['div', 'lyra-recur__custom'],
        ['span', 'lyra-recur__summary'],
    ])->and($root->getAttribute('class'))->toBe('lyra-recur booking-rule')
        ->and($root->getAttribute('id'))->toBe('repeat-rule')
        ->and($root->getAttribute('data-track'))->toBe('recurrence')
        ->and($xpath->query('//div[contains(@class, "lyra-recur__custom")]/div')->length)->toBeGreaterThanOrEqual(3)
        ->and($html)->not->toContain('lyra-recur__conflict');
});

it('serves the exact complete recurrence sentence for every frequency interval and end combination', function (?array $rule, string $expected): void {
    $html = renderRecurrenceSelector([
        'value' => $rule,
        'startDate' => '2026-08-05',
    ]);
    $summary = recurrenceSelectorElement($html, 'summary');

    expect(trim($summary->textContent))->toBe($expected)
        ->and($summary->getAttribute('x-bind'))->toBe('summary')
        ->and($summary->getAttribute('aria-live'))->toBe('polite');
})->with('recurrence summaries');

it('uses the start weekday when a weekly rule has no explicit weekdays', function (): void {
    $html = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 1, 'byWeekday' => [], 'end' => ['type' => 'never']],
        'startDate' => '2026-08-05',
    ]);

    expect(trim(recurrenceSelectorElement($html, 'summary')->textContent))->toBe('Repeats every Wednesday');
});

it('interpolates preset labels with short and long weekday labels and the ordinal', function (): void {
    $html = renderRecurrenceSelector(['startDate' => '2026-08-12']);
    $options = recurrenceSelectorOptionsElements($html);

    expect(collect($options)->map(fn (DOMElement $option): string => trim($option->textContent))->all())->toBe([
        'Does not repeat',
        'Every week (Wed)',
        'Every 2 weeks (Wed)',
        'Every month (2nd Wednesday)',
        'Custom…',
    ])->and(collect($options)->map(fn (DOMElement $option): string => $option->getAttribute('value'))->all())->toBe([
        'none', 'weekly', 'biweekly', 'monthly', 'custom',
    ]);
});

it('serves the preset selected state and custom section visibility exactly like binding boot', function (?array $rule, string $selected, bool $customCloaked): void {
    $html = renderRecurrenceSelector([
        'value' => $rule,
        'startDate' => '2026-08-05',
    ]);
    $options = recurrenceSelectorOptionsElements($html);
    $selectedOptions = collect($options)
        ->filter(fn (DOMElement $option): bool => $option->hasAttribute('selected'))
        ->values();
    $custom = recurrenceSelectorElement($html, 'custom');

    expect($selectedOptions)->toHaveCount(1)
        ->and($selectedOptions[0]->getAttribute('value'))->toBe($selected)
        ->and(recurrenceSelectorElement($html, 'preset-select')->getAttribute('x-bind'))->toBe('presetSelect')
        ->and(recurrenceSelectorElement($html, 'preset-select')->hasAttribute('value'))->toBeFalse()
        ->and(recurrenceSelectorElement($html, 'preset-select')->getAttribute('aria-label'))->toBe('Recurrence')
        ->and($custom->getAttribute('x-bind'))->toBe('customSection')
        ->and($custom->hasAttribute('x-cloak'))->toBe($customCloaked);
})->with('preset selection');

it('normalizes integer and float biweekly intervals to the same served and modelable state', function (): void {
    $integer = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 2, 'byWeekday' => [3]],
        'startDate' => '2026-08-05',
    ]);
    $float = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 2.0, 'byWeekday' => [3]],
        'startDate' => '2026-08-05',
    ]);

    foreach ([$integer, $float] as $html) {
        $selected = collect(recurrenceSelectorOptionsElements($html))
            ->filter(fn (DOMElement $option): bool => $option->hasAttribute('selected'))
            ->values();

        expect(recurrenceSelectorOptions($html)['value']['interval'])->toBe(2)
            ->and($selected)->toHaveCount(1)
            ->and($selected[0]->getAttribute('value'))->toBe('biweekly')
            ->and(recurrenceSelectorElement($html, 'custom')->hasAttribute('x-cloak'))->toBeTrue();
    }

    expect(recurrenceSelectorElement($integer, 'root')->getAttribute('x-data'))
        ->toBe(recurrenceSelectorElement($float, 'root')->getAttribute('x-data'));
});

it('serves weekday pressed state and modifier classes and cloaks the group for monthly rules', function (): void {
    $weekly = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 3, 'byWeekday' => [6, 0, 2], 'end' => ['type' => 'never']],
        'startDate' => '2026-08-05',
    ]);
    $monthly = renderRecurrenceSelector([
        'value' => ['freq' => 'monthly', 'interval' => 2, 'byWeekday' => [3], 'end' => ['type' => 'never']],
        'startDate' => '2026-08-05',
    ]);
    [, $xpath] = recurrenceSelectorDocument($weekly);
    $days = collect($xpath->query('//div[@x-bind="weekdayGroup"]/button'))
        ->map(fn (DOMElement $day): DOMElement => $day)
        ->all();

    expect($days)->toHaveCount(7)
        ->and(collect($days)->map(fn (DOMElement $day): string => $day->getAttribute('aria-pressed'))->all())->toBe([
            'true', 'false', 'true', 'false', 'false', 'false', 'true',
        ])->and(collect($days)->map(fn (DOMElement $day): string => $day->getAttribute('class'))->all())->toBe([
            'lyra-recur__day lyra-recur__day--on',
            'lyra-recur__day',
            'lyra-recur__day lyra-recur__day--on',
            'lyra-recur__day',
            'lyra-recur__day',
            'lyra-recur__day',
            'lyra-recur__day lyra-recur__day--on',
        ])->and($days[0]->getAttribute(':aria-pressed'))->toBe('dayPressed(0)')
        ->and($days[0]->getAttribute(':class'))->toBe('dayClass(0)')
        ->and($days[0]->getAttribute('@click'))->toBe('toggleDay(0)')
        ->and($days[0]->getAttribute('type'))->toBe('button')
        ->and(recurrenceSelectorElement($weekly, 'days')->hasAttribute('x-cloak'))->toBeFalse()
        ->and(recurrenceSelectorElement($monthly, 'days')->hasAttribute('x-cloak'))->toBeTrue();
});

it('serves interval frequency and end controls with binding defaults and initial values', function (): void {
    $html = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 4, 'byWeekday' => [1, 3], 'end' => ['type' => 'count']],
        'startDate' => '2026-08-05',
    ]);
    $interval = recurrenceSelectorElement($html, 'interval');
    $frequency = recurrenceSelectorElement($html, 'freq-select');
    $end = recurrenceSelectorElement($html, 'end-select');
    $count = recurrenceSelectorElement($html, 'count-input');
    [, $xpath] = recurrenceSelectorDocument($html);
    $suffix = $xpath->query('//span[@x-bind="countSuffix"]')->item(0);
    $selectedFrequency = $xpath->query('//select[@x-bind="freqSelect"]/option[@selected]');
    $selectedEnd = $xpath->query('//select[@x-bind="endSelect"]/option[@selected]');

    expect($interval->getAttribute('type'))->toBe('number')
        ->and($interval->getAttribute('min'))->toBe('1')
        ->and($interval->getAttribute('max'))->toBe('12')
        ->and($interval->getAttribute('value'))->toBe('4')
        ->and($interval->getAttribute('aria-label'))->toBe('Interval')
        ->and($frequency->hasAttribute('value'))->toBeFalse()
        ->and($frequency->getAttribute('aria-label'))->toBe('Frequency')
        ->and($selectedFrequency)->toHaveLength(1)
        ->and($selectedFrequency->item(0)?->getAttribute('value'))->toBe('weekly')
        ->and($end->hasAttribute('value'))->toBeFalse()
        ->and($end->getAttribute('aria-label'))->toBe('Ends')
        ->and($selectedEnd)->toHaveLength(1)
        ->and($selectedEnd->item(0)?->getAttribute('value'))->toBe('count')
        ->and($count->getAttribute('value'))->toBe('8')
        ->and($count->getAttribute('aria-label'))->toBe('Occurrences')
        ->and($count->hasAttribute('x-cloak'))->toBeFalse()
        ->and($suffix)->toBeInstanceOf(DOMElement::class)
        ->and($suffix->hasAttribute('x-cloak'))->toBeFalse()
        ->and(trim($suffix->textContent))->toBe('times');
});

it('cloaks count controls and date composition according to the served end type', function (): void {
    $never = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3], 'end' => ['type' => 'never']],
        'startDate' => '2026-08-05',
    ]);
    $date = renderRecurrenceSelector([
        'value' => ['freq' => 'weekly', 'interval' => 3, 'byWeekday' => [3], 'end' => ['type' => 'date', 'date' => '2026-09-18']],
        'startDate' => '2026-08-05',
        'labels' => ['endDate' => 'Choose last date'],
    ]);
    [, $neverXpath] = recurrenceSelectorDocument($never);
    [, $dateXpath] = recurrenceSelectorDocument($date);
    $dateSpan = recurrenceSelectorElement($date, 'end-date');
    $alias = $dateXpath->query('//span[contains(@class, "lyra-recur__enddate")]/div[@x-data]')->item(0);
    $picker = $dateXpath->query('//span[contains(@class, "lyra-recur__enddate")]//div[starts-with(@x-data, "lyraDatePicker(")]')->item(0);

    expect(recurrenceSelectorElement($never, 'count-input')->hasAttribute('x-cloak'))->toBeTrue()
        ->and($neverXpath->query('//span[@x-bind="countSuffix"]')->item(0)->hasAttribute('x-cloak'))->toBeTrue()
        ->and(recurrenceSelectorElement($never, 'end-date')->hasAttribute('x-cloak'))->toBeTrue()
        ->and(recurrenceSelectorElement($date, 'count-input')->hasAttribute('x-cloak'))->toBeTrue()
        ->and($dateXpath->query('//span[@x-bind="countSuffix"]')->item(0)->hasAttribute('x-cloak'))->toBeTrue()
        ->and($dateSpan->hasAttribute('x-cloak'))->toBeFalse()
        ->and($dateSpan->getAttribute('x-show'))->toBe("endType() === 'date'")
        ->and($alias)->toBeInstanceOf(DOMElement::class)
        ->and($alias->getAttribute('x-data'))->toBe('{ get recurrenceEndDate() { return endDate() }, set recurrenceEndDate(v) { setEndDate(v) } }')
        ->and($picker)->toBeInstanceOf(DOMElement::class)
        ->and($picker->getAttribute('x-model'))->toBe('recurrenceEndDate')
        ->and(recurrenceSelectorCalendarOptions($date))->each->toMatchArray(['min' => '2026-08-05']);

    $pickerExpression = $picker->getAttribute('x-data');
    $pickerOptions = json_decode(substr($pickerExpression, strlen('lyraDatePicker('), -1), true, flags: JSON_THROW_ON_ERROR);

    expect($pickerOptions)->toMatchArray([
        'defaultValue' => '2026-09-18',
        'placeholder' => 'Choose last date',
    ]);
});

it('serves no conflict note for zero and the correct whole template for one or many', function (): void {
    $zero = renderRecurrenceSelector();
    $one = renderRecurrenceSelector([
        'conflicts' => [['date' => '2026-08-10']],
        'labels' => ['conflictsOne' => 'One conflict.'],
    ]);
    $many = renderRecurrenceSelector([
        'conflicts' => [
            ['date' => '2026-08-10'],
            ['date' => '2026-08-17'],
            ['date' => '2026-08-24'],
        ],
        'labels' => ['conflictsMany' => '{count} conflicts.'],
    ]);
    [, $zeroXpath] = recurrenceSelectorDocument($zero);

    expect($zeroXpath->query('//span[@role="status"]')->length)->toBe(0)
        ->and(trim(recurrenceSelectorElement($one, 'conflicts')->textContent))->toBe('One conflict.')
        ->and(recurrenceSelectorElement($one, 'conflicts')->getAttribute('x-bind'))->toBe('conflictsNote')
        ->and(trim(recurrenceSelectorElement($many, 'conflicts')->textContent))->toBe('3 conflicts.')
        ->and(recurrenceSelectorElement($many, 'conflicts')->getAttribute('class'))->toBe('lyra-recur__summary');
});

it('merges every JSON-safe label type over the English defaults without exposing formatter props', function (): void {
    $short = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
    $long = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $html = renderRecurrenceSelector([
        'startDate' => '2026-08-12',
        'labels' => [
            'weekdaysShort' => $short,
            'weekdaysLong' => $long,
            'everyWeek' => 'Cada semana ({weekday})',
            'everyMonth' => 'Cada mes ({ordinal} {weekday})',
            'none' => 'Sin repetición',
            'formatWeekdays' => 'hostile formatter',
            'formatOrdinal' => 'hostile formatter',
            'formatDate' => 'hostile formatter',
        ],
    ]);
    $bindingLabels = recurrenceSelectorOptions($html)['labels'];
    $options = recurrenceSelectorOptionsElements($html);

    expect(trim($options[1]->textContent))->toBe('Cada semana (Mi)')
        ->and(trim($options[3]->textContent))->toBe('Cada mes (2nd Miércoles)')
        ->and($bindingLabels['weekdaysShort'])->toBe($short)
        ->and($bindingLabels['weekdaysLong'])->toBe($long)
        ->and($bindingLabels)->not->toHaveKeys(['formatWeekdays', 'formatOrdinal', 'formatDate']);
});

it('normalizes malformed rules weekdays intervals ends and PHP dates into JSON-safe binding state', function (): void {
    $malformed = renderRecurrenceSelector(['value' => 'not-a-rule']);
    $weekly = renderRecurrenceSelector([
        'value' => [
            'freq' => 'weekly',
            'interval' => -4,
            'byWeekday' => [-1, 0, 2, 2, 7, '3', null],
        ],
        'startDate' => new DateTimeImmutable('2026-08-05'),
    ]);
    $dated = renderRecurrenceSelector([
        'value' => [
            'freq' => 'monthly',
            'interval' => 2,
            'byWeekday' => [3],
            'end' => ['type' => 'date', 'date' => new DateTimeImmutable('2027-02-14 18:30:00')],
        ],
        'startDate' => new DateTimeImmutable('2026-08-05 09:00:00'),
    ]);
    $missingCount = renderRecurrenceSelector([
        'value' => [
            'freq' => 'weekly',
            'byWeekday' => [3],
            'end' => ['type' => 'count'],
        ],
        'startDate' => '2026-08-05',
    ]);
    $noneWithFields = renderRecurrenceSelector([
        'value' => [
            'freq' => 'none',
            'interval' => 2.9,
            'byWeekday' => [3, 3, 7, '2'],
            'end' => ['type' => 'count', 'count' => 4.8],
            'unknown' => 'not emitted',
        ],
    ]);

    expect(recurrenceSelectorOptions($malformed)['value'])->toBe([
        'freq' => 'none',
        'interval' => 1,
        'byWeekday' => [],
        'end' => ['type' => 'never'],
    ])->and(recurrenceSelectorOptions($weekly)['value'])->toBe([
        'freq' => 'weekly',
        'interval' => 1,
        'byWeekday' => [0, 2],
    ])->and(recurrenceSelectorOptions($weekly)['startDate'])->toBe('2026-08-05')
        ->and(recurrenceSelectorOptions($dated)['value']['end'])->toBe([
            'type' => 'date',
            'date' => '2027-02-14',
        ])->and(recurrenceSelectorOptions($dated)['startDate'])->toBe('2026-08-05')
        ->and(recurrenceSelectorOptions($missingCount)['value'])->toBe([
            'freq' => 'weekly',
            'byWeekday' => [3],
            'end' => ['type' => 'count'],
        ])->and(recurrenceSelectorElement($missingCount, 'count-input')->getAttribute('value'))->toBe('8')
        ->and(recurrenceSelectorOptions($noneWithFields)['value'])->toBe([
            'freq' => 'none',
            'interval' => 2,
            'byWeekday' => [3],
            'end' => ['type' => 'count', 'count' => 4],
        ]);
});

it('preserves a sparse none rule while deriving the binding fallbacks for served state', function (): void {
    $html = renderRecurrenceSelector([
        'value' => ['freq' => 'none'],
        'startDate' => '2026-08-05',
    ]);
    [, $xpath] = recurrenceSelectorDocument($html);
    $selectedPreset = $xpath->query('//select[@x-bind="presetSelect"]/option[@selected]');
    $selectedFrequency = $xpath->query('//select[@x-bind="freqSelect"]/option[@selected]');
    $selectedEnd = $xpath->query('//select[@x-bind="endSelect"]/option[@selected]');
    $pressedDays = $xpath->query('//div[@x-bind="weekdayGroup"]/button[@aria-pressed="true"]');

    expect(recurrenceSelectorOptions($html)['value'])->toBe(['freq' => 'none'])
        ->and($selectedPreset)->toHaveLength(1)
        ->and($selectedPreset->item(0)?->getAttribute('value'))->toBe('none')
        ->and(recurrenceSelectorElement($html, 'custom')->hasAttribute('x-cloak'))->toBeTrue()
        ->and(trim(recurrenceSelectorElement($html, 'summary')->textContent))->toBe('Does not repeat')
        ->and(recurrenceSelectorElement($html, 'interval')->getAttribute('value'))->toBe('1')
        ->and($selectedFrequency)->toHaveLength(1)
        ->and($selectedFrequency->item(0)?->getAttribute('value'))->toBe('weekly')
        ->and($pressedDays)->toHaveLength(0)
        ->and($selectedEnd)->toHaveLength(1)
        ->and($selectedEnd->item(0)?->getAttribute('value'))->toBe('never');
});

it('hardens every binding option against markup breakout while preserving decoded values', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script><img src=x onerror=alert(1)>";
    $html = renderRecurrenceSelector([
        'value' => [
            'freq' => 'weekly',
            'interval' => 2,
            'byWeekday' => [1],
            'end' => ['type' => 'date', 'date' => '2026-08-31'],
        ],
        'conflicts' => [['date' => '2026-08-10', 'reason' => $payload]],
        'labels' => [
            'none' => $payload,
            'everyWeek' => $payload.' {weekday}',
            'conflictsOne' => $payload,
        ],
        'x-data' => 'consumerOverride()',
        'x-modelable' => 'invented',
        'wire:model.live' => 'rule',
    ]);
    [$document] = recurrenceSelectorDocument($html);
    $root = recurrenceSelectorElement($html, 'root');
    $options = recurrenceSelectorOptions($html);

    expect($options['labels']['none'])->toBe($payload)
        ->and($options['labels']['everyWeek'])->toBe($payload.' {weekday}')
        ->and($options['conflicts'][0]['reason'])->toBe($payload)
        ->and($root->getAttribute('x-data'))->toContain('\\u003C/script\\u003E')
        ->and($root->getAttribute('x-data'))->not->toContain('</script>')
        ->and($root->getAttribute('x-modelable'))->toBe('value')
        ->and($root->getAttribute('wire:model.live'))->toBe('rule')
        ->and(substr_count($html, 'x-data='))->toBeGreaterThan(1)
        ->and(substr_count($root->getAttribute('x-data'), 'lyraRecurrenceSelector('))->toBe(1)
        ->and($document->getElementsByTagName('script')->length)->toBe(0)
        ->and($document->getElementsByTagName('img')->length)->toBe(0)
        ->and($html)->not->toContain('</script>')
        ->and($html)->not->toContain('x-modelable="invented"')
        ->and($html)->not->toContain('consumerOverride()');
});

it('renders namespaced and short syntax identically', function (): void {
    $value = ['freq' => 'weekly', 'interval' => 2, 'byWeekday' => [3], 'end' => ['type' => 'never']];
    $labels = ['recurrence' => 'Repeat'];
    $namespaced = Blade::render(
        '<x-lyra::recurrence-selector :value="$value" start-date="2026-08-05" :labels="$labels" class="schedule" />',
        compact('value', 'labels'),
    );
    $short = Blade::render(
        '<lyra:recurrence-selector :value="$value" start-date="2026-08-05" :labels="$labels" class="schedule" />',
        compact('value', 'labels'),
    );

    $normalizeGeneratedIds = static fn (string $html): string => preg_replace(
        '/(lyra-(?:date-picker|bottom-sheet-title)-)[a-f0-9]+/',
        '$1generated',
        $html,
    );

    expect($normalizeGeneratedIds($short))->toBe($normalizeGeneratedIds($namespaced))
        ->and($short)->toContain('lyraRecurrenceSelector(');
});
