<?php

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderCalendar(
    array $props = [],
    string|Htmlable|null $dayMarker = null,
): string {
    $range = $props['range'] ?? false;
    $defaultValue = $props['defaultValue'] ?? null;
    $min = $props['min'] ?? null;
    $max = $props['max'] ?? null;
    $disabledDates = $props['disabledDates'] ?? [];
    $weekStartsOn = $props['weekStartsOn'] ?? 0;
    $size = $props['size'] ?? 'sm';
    $todayButton = $props['todayButton'] ?? false;
    $locale = $props['locale'] ?? 'en-US';
    $labels = $props['labels'] ?? [];
    $dateDisabledPredicate = $props['dateDisabledPredicate'] ?? null;
    unset(
        $props['range'],
        $props['defaultValue'],
        $props['min'],
        $props['max'],
        $props['disabledDates'],
        $props['weekStartsOn'],
        $props['size'],
        $props['todayButton'],
        $props['locale'],
        $props['labels'],
        $props['dateDisabledPredicate'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');
    $markerSlot = $dayMarker === null
        ? ''
        : '<x-slot:dayMarker>{{ $dayMarker }}</x-slot:dayMarker>';

    return Blade::render(
        sprintf(
            '<x-lyra::calendar :range="$range" :default-value="$defaultValue" :min="$min" :max="$max" :disabled-dates="$disabledDates" :week-starts-on="$weekStartsOn" :size="$size" :today-button="$todayButton" :locale="$locale" :labels="$labels" :date-disabled-predicate="$dateDisabledPredicate" %s>%s</x-lyra::calendar>',
            $attributes,
            $markerSlot,
        ),
        compact(
            'range',
            'defaultValue',
            'min',
            'max',
            'disabledDates',
            'weekStartsOn',
            'size',
            'todayButton',
            'locale',
            'labels',
            'dateDisabledPredicate',
            'dayMarker',
        ),
    );
}

function calendarOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-cal(?: [^"]*)?")[^>]*>/',
        'head' => '/<div\b(?=[^>]*\bclass="lyra-cal__head")[^>]*>/',
        'label' => '/<button\b(?=[^>]*\bclass="lyra-cal__label")[^>]*>/',
        'day' => '/<button\b(?=[^>]*\bclass="lyra-cal__day")[^>]*>/',
        'today' => '/<button\b(?=[^>]*\bclass="lyra-cal__today")[^>]*>/',
        'month' => '/<button\b(?=[^>]*\bclass="lyra-cal__mcell")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function calendarClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-cal(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function calendarOptions(string $html): array
{
    $root = calendarOpeningTag($html, 'root');
    $matched = preg_match('/\bx-data="([^"]*)"/', $root, $matches);

    expect($matched)->toBe(1);

    $expression = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    expect($expression)->toStartWith('lyraCalendar(')->toEndWith(')');
    $json = substr($expression, strlen('lyraCalendar('), -1);

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

dataset('calendar class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/calendar.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the calendar class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(calendarClass(renderCalendar($case['props'])))->toBe($case['expected_class']);
})->with('calendar class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::calendar id="schedule" size="md" />');
    $short = Blade::render('<lyra:calendar id="schedule" size="md" />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-cal lyra-cal--md"');
});

it('serializes every supplied Alpine option as valid JSON', function (): void {
    $props = [
        'range' => true,
        'defaultValue' => ['start' => '2026-08-09', 'end' => '2026-08-12'],
        'min' => '2026-08-01',
        'max' => '2026-08-31',
        'disabledDates' => ['2026-08-15', '2026-08-16'],
        'weekStartsOn' => 1,
        'locale' => 'pt-BR',
        'labels' => [
            'previousMonth' => 'Mês anterior',
            'today' => 'Hoje',
        ],
    ];
    $html = renderCalendar($props);
    $root = calendarOpeningTag($html, 'root');

    expect($root)->toContain('x-data="lyraCalendar(')
        ->and($root)->toContain('x-modelable="selected"')
        ->and(calendarOptions($html))->toBe($props);
});

it('delegates omitted option defaults to the Alpine binding', function (): void {
    $html = renderCalendar();
    $root = html_entity_decode(calendarOpeningTag($html, 'root'), ENT_QUOTES | ENT_HTML5);

    expect($root)->toContain('x-data="lyraCalendar({})"')
        ->and(calendarOptions($html))->toBe([])
        ->and($root)->not->toContain('defaultValue')
        ->and($root)->not->toContain('weekStartsOn')
        ->and($root)->not->toContain('en-US')
        ->and($root)->not->toContain('Previous month');
});

it('keeps hostile props inside the JSON literal', function (): void {
    $props = [
        'locale' => "pt-BR'); window.pwned=1; //",
        'defaultValue' => '2026-08-09"\\</script>',
        'min' => "2026-01-01'\\\"</script>",
        'labels' => [
            'today' => "Hoje'); window.pwned=2; //\\\"</script>",
        ],
    ];
    $html = renderCalendar($props);
    $root = calendarOpeningTag($html, 'root');

    expect(calendarOptions($html))->toMatchArray($props)
        ->and(substr_count($root, 'x-data='))->toBe(1)
        ->and($root)->not->toContain("'); window.pwned")
        ->and($root)->not->toContain('</script>')
        ->and($root)->toContain('\\u003C/script\\u003E');
});

it('renders the React header controls and exact chevrons', function (): void {
    $html = renderCalendar();
    preg_match_all('/<button\b(?=[^>]*\bclass="lyra-cal__nav")[^>]*>.*?<\/button>/s', $html, $navs);

    expect($navs[0])->toHaveCount(2)
        ->and($navs[0][0])->toContain('type="button"')
        ->and($navs[0][0])->toContain('x-bind="prev"')
        ->and($navs[0][0])->toMatch('/<svg\s+width="14"\s+height="14"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2\.5"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s+aria-hidden="true"\s*>\s*<path d="m15 18-6-6 6-6" \/>\s*<\/svg>/s')
        ->and($navs[0][1])->toContain('type="button"')
        ->and($navs[0][1])->toContain('x-bind="next"')
        ->and($navs[0][1])->toContain('<path d="m9 18 6-6-6-6" />')
        ->and(calendarOpeningTag($html, 'label'))->toContain('type="button"')
        ->and(calendarOpeningTag($html, 'label'))->toContain('x-bind="viewButton"')
        ->and(calendarOpeningTag($html, 'label'))->toContain('x-text="headerLabel()"')
        ->and($html)->toMatch('/<button\b(?=[^>]*\bclass="lyra-cal__label")[^>]*>\s*<\/button>/s');
});

it('serves all view templates in React DOM order', function (): void {
    $html = renderCalendar(['todayButton' => true]);
    $days = strpos($html, '<template x-if="mode === \'days\'">');
    $foot = strpos($html, 'class="lyra-cal__foot"');
    $months = strpos($html, '<template x-if="mode === \'months\'">');
    $years = strpos($html, '<template x-if="mode === \'years\'">');

    expect($days)->not->toBeFalse()
        ->and($foot)->not->toBeFalse()
        ->and($months)->not->toBeFalse()
        ->and($years)->not->toBeFalse()
        ->and($days)->toBeLessThan($foot)
        ->and($foot)->toBeLessThan($months)
        ->and($months)->toBeLessThan($years)
        ->and($html)->toContain('<div class="lyra-cal__grid">')
        ->and(substr_count($html, '<div class="lyra-cal__mgrid">'))->toBe(2);
});

it('renders localized weekday and day loops with the authoritative bindings', function (): void {
    $html = renderCalendar();
    $day = calendarOpeningTag($html, 'day');

    expect($html)->toContain('<template x-for="weekday in weekdays()" :key="weekday.key">')
        ->and($html)->toContain('<span class="lyra-cal__wd" :aria-label="weekday.long" x-text="weekday.narrow"></span>')
        ->and($html)->toContain('<template x-for="date in days()" :key="dayKey(date)">')
        ->and($day)->toContain('type="button"')
        ->and($day)->toContain(':class="dayClass(date)"')
        ->and($day)->toContain(':aria-disabled="dayDisabled(date)"')
        ->and($day)->toContain(':tabindex="dayTabindex(date)"')
        ->and($day)->toContain(':aria-label="dayLabel(date)"')
        ->and($day)->toContain(':aria-pressed="dayPressed(date)"')
        ->and($day)->toContain(':data-key="dayKey(date)"')
        ->and($day)->toContain('@click="selectDate(date)"')
        ->and($day)->toContain('@focus="onDayFocus(date)"')
        ->and($day)->toContain('@keydown="onDayKeydown($event, date)"')
        ->and($html)->toContain('<span x-text="date.getDate()"></span>');
});

it('renders the month and year loops with the authoritative bindings', function (): void {
    $html = renderCalendar();
    $month = calendarOpeningTag($html, 'month');

    expect($html)->toContain('<template x-for="month in months()" :key="month.getMonth()">')
        ->and($month)->toContain('type="button"')
        ->and($month)->toContain(':class="monthClass(month)"')
        ->and($month)->toContain('@click="pickMonth(month)"')
        ->and($month)->toContain('x-text="monthName(month)"')
        ->and($html)->toContain('<template x-for="year in years()" :key="year">')
        ->and($html)->toContain(':class="yearClass(year)"')
        ->and($html)->toContain('@click="pickYear(year)"')
        ->and($html)->toContain('x-text="year"');
});

it('omits the today control by default and nests it in the days view when enabled', function (): void {
    $plain = renderCalendar();
    $withToday = renderCalendar(['todayButton' => true]);
    $today = calendarOpeningTag($withToday, 'today');
    $footPosition = strpos($withToday, 'class="lyra-cal__foot"');
    $daysBeforeFoot = strrpos(substr($withToday, 0, $footPosition), '<template x-if="mode === \'days\'">');
    $templateEnd = strpos($withToday, '</template>', $footPosition);

    expect($plain)->not->toContain('lyra-cal__foot')
        ->and($plain)->not->toContain('lyra-cal__today')
        ->and($daysBeforeFoot)->not->toBeFalse()
        ->and($templateEnd)->not->toBeFalse()
        ->and($today)->toContain('type="button"')
        ->and($today)->toContain('x-bind="today"')
        ->and($withToday)->toMatch('/<button\b(?=[^>]*\bclass="lyra-cal__today")[^>]*>\s*<\/button>/s');
});

it('renders the day marker slot after the day number', function (): void {
    $html = renderCalendar(
        [],
        new HtmlString('<span class="lyra-cal__dot" x-show="date.getDate() === 9"></span>'),
    );
    $number = strpos($html, '<span x-text="date.getDate()"></span>');
    $marker = strpos($html, '<span class="lyra-cal__dot"');
    $dayEnd = strpos($html, '</button>', $marker);

    expect($number)->not->toBeFalse()
        ->and($marker)->not->toBeFalse()
        ->and($dayEnd)->not->toBeFalse()
        ->and($number)->toBeLessThan($marker)
        ->and($marker)->toBeLessThan($dayEnd)
        ->and($html)->toContain('x-show="date.getDate() === 9"');
});

it('appends consumer attributes and classes after fixed root attributes', function (): void {
    $root = calendarOpeningTag(renderCalendar([
        'class' => 'consumer utility',
        'id' => 'booking-calendar',
        'data-track' => 'calendar',
    ]), 'root');

    expect($root)->toContain('class="lyra-cal consumer utility"')
        ->and($root)->toContain('id="booking-calendar"')
        ->and($root)->toContain('data-track="calendar"')
        ->and(strpos($root, 'x-modelable="selected"'))->toBeLessThan(strpos($root, 'class="lyra-cal'));
});

it('emits the whitelisted internal date-disabled predicate', function (): void {
    $root = html_entity_decode(calendarOpeningTag(renderCalendar([
        'dateDisabledPredicate' => 'slot-picker',
    ]), 'root'), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect($root)->toContain('isDateDisabled: (date) => !hasSlots(date)');
});

it('omits unknown internal date-disabled predicate names', function (): void {
    $html = renderCalendar(['dateDisabledPredicate' => 'another-composite']);

    expect(calendarOptions($html))->toBe([])
        ->and(calendarOpeningTag($html, 'root'))->not->toContain('isDateDisabled');
});

it('never injects a consumer expression through the internal predicate prop', function (): void {
    $payload = "slot-picker'); window.calendarPwned = true; //";
    $root = calendarOpeningTag(renderCalendar([
        'dateDisabledPredicate' => $payload,
    ]), 'root');

    expect($root)->not->toContain($payload)
        ->and($root)->not->toContain('calendarPwned')
        ->and(calendarOptions(renderCalendar(['dateDisabledPredicate' => $payload])))->toBe([]);
});
