<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderSlotPicker(array $props = [], bool $short = false, string $content = ''): string
{
    $slots = $props['slots'] ?? [];
    $date = $props['date'] ?? null;
    $timezone = $props['timezone'] ?? null;
    $detectedZone = $props['detectedZone'] ?? null;
    $holdExpiresAt = $props['holdExpiresAt'] ?? null;
    $nextAvailableDate = $props['nextAvailableDate'] ?? null;
    $loading = $props['loading'] ?? false;
    $locale = $props['locale'] ?? 'en-US';
    $min = $props['min'] ?? null;
    $max = $props['max'] ?? null;
    $labels = $props['labels'] ?? [];
    $tzLabels = $props['tzLabels'] ?? [];
    unset(
        $props['slots'],
        $props['date'],
        $props['timezone'],
        $props['detectedZone'],
        $props['holdExpiresAt'],
        $props['nextAvailableDate'],
        $props['loading'],
        $props['locale'],
        $props['min'],
        $props['max'],
        $props['labels'],
        $props['tzLabels'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');
    $component = $short ? 'lyra:slot-picker' : 'x-lyra::slot-picker';
    $summary = new HtmlString($content);

    return Blade::render(
        sprintf(
            '<%1$s :slots="$slots" :date="$date" :timezone="$timezone" :detected-zone="$detectedZone" :hold-expires-at="$holdExpiresAt" :next-available-date="$nextAvailableDate" :loading="$loading" :locale="$locale" :min="$min" :max="$max" :labels="$labels" :tz-labels="$tzLabels" %2$s>{{ $summary }}</%1$s>',
            $component,
            $attributes,
        ),
        compact(
            'slots',
            'date',
            'timezone',
            'detectedZone',
            'holdExpiresAt',
            'nextAvailableDate',
            'loading',
            'locale',
            'min',
            'max',
            'labels',
            'tzLabels',
            'summary',
        ),
    );
}

/** @return array{DOMDocument, DOMXPath} */
function slotPickerDocument(string $html): array
{
    $document = new DOMDocument;
    $loaded = @$document->loadHTML(
        '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$html.'</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING,
    );

    expect($loaded)->toBeTrue();

    return [$document, new DOMXPath($document)];
}

function slotPickerElement(string $html, string $target): DOMElement
{
    [, $xpath] = slotPickerDocument($html);
    $class = match ($target) {
        'root' => 'lyra-slotpicker',
        'side' => 'lyra-slotpicker__side',
        'timezone' => 'lyra-slotpicker__tz',
        'main' => 'lyra-slotpicker__main',
        'slots' => 'lyra-slotpicker__slots',
        'skeleton' => 'lyra-slotpicker__skeleton',
        'empty' => 'lyra-slotpicker__empty',
        'day-label' => 'lyra-slotpicker__daylabel',
        'pair' => 'lyra-slotpicker__pair',
        'slot' => 'lyra-slotpicker__slot',
        'hold' => 'lyra-slotpicker__hold',
        'day-marker' => 'lyra-cal__dot',
    };
    $element = $xpath->query(sprintf(
        '//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]',
        $class,
    ))?->item(0);

    expect($element)->toBeInstanceOf(DOMElement::class);

    return $element;
}

function slotPickerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'visible-option' => '/<button\b(?=[^>]*\brole="option")(?=[^>]*\bclass="lyra-slotpicker__slot")(?![^>]*\bx-cloak\b)[^>]*>/',
        'confirm' => '/<button\b(?=[^>]*\bx-bind="confirmButton")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function slotPickerAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/(?:^|\s)%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1
        ? html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : null;
}

/** @return array<string, mixed> */
function slotPickerOptions(string $html): array
{
    $expression = slotPickerElement($html, 'root')->getAttribute('x-data');

    expect($expression)->toStartWith('lyraSlotPicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraSlotPicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return list<DOMElement> */
function slotPickerVisibleElements(string $html, string $class): array
{
    [, $xpath] = slotPickerDocument($html);

    return collect($xpath->query(sprintf(
        '//*[contains(concat(" ", normalize-space(@class), " "), " %s ") and not(@x-cloak)]',
        $class,
    )))->map(fn (DOMElement $element): DOMElement => $element)->all();
}

/** @return array<string, mixed> */
function nestedTimeZonePickerOptions(string $html): array
{
    preg_match('/<div\b(?=[^>]*\bclass="[^"]*lyra-tzpicker)[^>]*\bx-data="([^"]*)"[^>]*>/', $html, $matches);
    $expression = html_entity_decode($matches[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect($expression)->toStartWith('lyraTimeZonePicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraTimeZonePicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

dataset('slot picker class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/slot-picker.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the slot-picker class-emission fixture.');
    }

    return collect(json_decode($contents, true, flags: JSON_THROW_ON_ERROR))
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])->all();
});

it('emits every React-derived class string from the independent fixture', function (array $case): void {
    $html = renderSlotPicker($case['props']);

    expect(slotPickerElement($html, $case['target'])->getAttribute('class'))
        ->toBe($case['expected_class']);
})->with('slot picker class emission');

it('renders the complete React topology and keeps consumer classes on the root', function (): void {
    $html = renderSlotPicker([
        'class' => 'booking utility',
        'id' => 'booking-slots',
        'data-track' => 'slot-picker',
    ], content: '<strong id="booking-summary">Choose a time</strong>');
    [, $xpath] = slotPickerDocument($html);
    $root = slotPickerElement($html, 'root');
    $children = [];

    foreach ($root->childNodes as $child) {
        if ($child instanceof DOMElement) {
            $children[] = [$child->tagName, $child->getAttribute('class')];
        }
    }

    expect($children)->toBe([
        ['div', 'lyra-slotpicker__side'],
        ['div', 'lyra-slotpicker__main'],
    ])->and($root->getAttribute('class'))->toBe('lyra-slotpicker booking utility')
        ->and($root->getAttribute('id'))->toBe('booking-slots')
        ->and($root->getAttribute('data-track'))->toBe('slot-picker')
        ->and($xpath->query('//*[@id="booking-summary"]')->length)->toBe(1)
        ->and(strpos($html, 'id="booking-summary"'))->toBeLessThan(strpos($html, 'class="lyra-cal'));
});

it('serves exactly six loading skeletons and hides the other initial states', function (): void {
    $html = renderSlotPicker([
        'loading' => true,
        'labels' => ['loading' => 'Loading appointments'],
    ]);
    $slots = slotPickerVisibleElements($html, 'lyra-slotpicker__slots');

    expect($slots)->toHaveCount(1)
        ->and($slots[0]->getAttribute('aria-label'))->toBe('Loading appointments')
        ->and(slotPickerVisibleElements($html, 'lyra-slotpicker__skeleton'))->toHaveCount(6)
        ->and(slotPickerVisibleElements($html, 'lyra-slotpicker__empty'))->toHaveCount(0)
        ->and(slotPickerVisibleElements($html, 'lyra-slotpicker__daylabel'))->toHaveCount(0);
});

it('serves the full-empty message when no slot exists', function (): void {
    $html = renderSlotPicker(['labels' => ['fullMessage' => 'Nothing bookable.']]);
    $empty = slotPickerVisibleElements($html, 'lyra-slotpicker__empty');

    expect($empty)->toHaveCount(1)
        ->and(trim($empty[0]->textContent))->toBe('Nothing bookable.')
        ->and($empty[0]->getAttribute('x-show'))->toContain('byDay().size === 0');
});

it('serves reactive next-date content and only reveals it when the target day has slots', function (): void {
    $slots = [[
        'start' => '2026-08-10T13:00:00Z',
        'end' => '2026-08-10T13:30:00Z',
    ]];
    $withoutNext = renderSlotPicker([
        'slots' => $slots,
        'date' => '2026-08-11',
        'timezone' => 'America/Sao_Paulo',
    ]);
    $unusableNext = renderSlotPicker([
        'slots' => $slots,
        'date' => '2026-08-11',
        'timezone' => 'America/Sao_Paulo',
        'nextAvailableDate' => '2026-08-12',
    ]);
    $usableNext = renderSlotPicker([
        'slots' => $slots,
        'date' => '2026-08-11',
        'timezone' => 'America/Sao_Paulo',
        'nextAvailableDate' => '2026-08-10',
    ]);
    [, $withoutNextXPath] = slotPickerDocument($withoutNext);
    [, $unusableNextXPath] = slotPickerDocument($unusableNext);
    [, $usableNextXPath] = slotPickerDocument($usableNext);
    $nextDateContent = static fn (DOMXPath $xpath): array => [
        $xpath->query('//span[contains(@x-text, "nextAvailable")]')->item(0),
        $xpath->query('//button[@x-bind="nextAvailable"]')->item(0),
    ];
    [$withoutNextLabel, $withoutNextButton] = $nextDateContent($withoutNextXPath);
    [$unusableNextLabel, $unusableNextButton] = $nextDateContent($unusableNextXPath);
    [$usableNextLabel, $usableNextButton] = $nextDateContent($usableNextXPath);
    $withoutNextEmptyMessage = slotPickerVisibleElements($withoutNext, 'lyra-slotpicker__empty')[0]
        ->getElementsByTagName('span')->item(0);
    $reactivePredicate = 'nextAvailableDate && byDay().has(nextAvailableDate)';

    expect(trim($withoutNextEmptyMessage->textContent))
        ->toBe('No available times on this day.')
        ->and($withoutNextLabel)->toBeInstanceOf(DOMElement::class)
        ->and($withoutNextButton)->toBeInstanceOf(DOMElement::class)
        ->and($withoutNextLabel->getAttribute('x-show'))->toBe($reactivePredicate)
        ->and($withoutNextButton->getAttribute('x-show'))->toBe($reactivePredicate)
        ->and($withoutNextLabel->hasAttribute('x-cloak'))->toBeTrue()
        ->and($withoutNextButton->hasAttribute('x-cloak'))->toBeTrue()
        ->and(trim($unusableNextLabel->textContent))->toBe('Next available time: Wednesday, August 12.')
        ->and(trim($unusableNextButton->textContent))->toBe('Go to Wednesday, August 12')
        ->and($unusableNextLabel->hasAttribute('x-cloak'))->toBeTrue()
        ->and($unusableNextButton->hasAttribute('x-cloak'))->toBeTrue()
        ->and(trim($usableNextLabel->textContent))->toBe('Next available time: Monday, August 10.')
        ->and(trim($usableNextButton->textContent))->toBe('Go to Monday, August 10')
        ->and($usableNextLabel->hasAttribute('x-cloak'))->toBeFalse()
        ->and($usableNextButton->hasAttribute('x-cloak'))->toBeFalse();
});

it('serves timezone-local sorted times including the UTC day rollover', function (): void {
    $slots = [
        ['start' => '2026-08-10T16:05:00Z', 'end' => '2026-08-10T16:35:00Z'],
        ['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z'],
        ['start' => '2026-08-10T12:00:00Z', 'end' => '2026-08-10T12:30:00Z'],
        ['start' => '2026-08-10T00:30:00Z', 'end' => '2026-08-10T01:00:00Z'],
        ['start' => '2026-08-10T02:00:00Z', 'end' => '2026-08-10T02:30:00Z'],
    ];
    $augustTen = renderSlotPicker([
        'slots' => $slots,
        'date' => '2026-08-10',
        'timezone' => 'America/Sao_Paulo',
    ]);
    $augustNine = renderSlotPicker([
        'slots' => $slots,
        'date' => '2026-08-09',
        'timezone' => 'America/Sao_Paulo',
    ]);
    [, $tenXPath] = slotPickerDocument($augustTen);
    [, $nineXPath] = slotPickerDocument($augustNine);
    $visibleTimes = static fn (DOMXPath $xpath): array => collect($xpath->query(
        '//button[contains(concat(" ", normalize-space(@class), " "), " lyra-slotpicker__slot ") and not(@x-cloak)]',
    ))->map(fn (DOMElement $button): string => trim($button->textContent))->all();

    expect($visibleTimes($tenXPath))->toBe(['09:00 AM', '10:00 AM', '01:05 PM'])
        ->and($visibleTimes($nineXPath))->toBe(['09:30 PM', '11:00 PM'])
        ->and(slotPickerElement($augustTen, 'day-label')->textContent)->toBe('Monday, August 10');
});

it('serves slots ordered by instant when ISO offsets differ', function (): void {
    $html = renderSlotPicker([
        'slots' => [
            ['start' => '2026-08-10T11:00:00Z', 'end' => '2026-08-10T11:30:00Z'],
            ['start' => '2026-08-10T13:00:00+03:00', 'end' => '2026-08-10T13:30:00+03:00'],
        ],
        'date' => '2026-08-10',
        'timezone' => 'UTC',
    ]);
    [, $xpath] = slotPickerDocument($html);
    $visibleTimes = collect($xpath->query(
        '//button[contains(concat(" ", normalize-space(@class), " "), " lyra-slotpicker__slot ") and not(@x-cloak)]',
    ))->map(fn (DOMElement $button): string => trim($button->textContent))->all();

    expect($visibleTimes)->toBe(['10:00 AM', '11:00 AM']);
});

it('serves listbox semantics, interpolated labels, and the unselected initial state', function (): void {
    $html = renderSlotPicker([
        'slots' => [
            ['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z'],
        ],
        'timezone' => 'America/Sao_Paulo',
        'labels' => ['availableTimes' => 'Bookable on {date}'],
    ]);
    [, $xpath] = slotPickerDocument($html);
    $listbox = $xpath->query('//*[@role="listbox" and not(@x-cloak)]')->item(0);
    $option = $xpath->query('.//button[@role="option" and not(@x-cloak)]', $listbox)->item(0);

    expect($listbox)->toBeInstanceOf(DOMElement::class)
        ->and($listbox->getAttribute('aria-label'))->toBe('Bookable on Monday, August 10')
        ->and($listbox->getAttribute(':aria-label'))->toContain("label('availableTimes'")
        ->and($option)->toBeInstanceOf(DOMElement::class)
        ->and($option->getAttribute('type'))->toBe('button')
        ->and($option->getAttribute('aria-selected'))->toBe('false')
        ->and(slotPickerAttribute(slotPickerOpeningTag($html, 'visible-option'), '@click'))->toContain('selectSlot(')
        ->and(slotPickerVisibleElements($html, 'lyra-slotpicker__pair'))->toHaveCount(0);
});

it('ships the selected pair and confirm control hidden until a slot is selected', function (): void {
    $html = renderSlotPicker([
        'slots' => [
            ['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z'],
        ],
        'timezone' => 'America/Sao_Paulo',
        'labels' => ['confirm' => 'Reserve'],
    ]);
    [, $xpath] = slotPickerDocument($html);
    $pair = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-slotpicker__pair ")]')->item(0);
    $selected = $xpath->query('.//*[@role="option" and @aria-selected="true"]', $pair)->item(0);
    $confirm = $xpath->query('.//button[@x-bind="confirmButton"]', $pair)->item(0);

    expect($pair->hasAttribute('x-cloak'))->toBeTrue()
        ->and($pair->getAttribute('x-show'))->toContain('selected?.start')
        ->and($selected)->toBeInstanceOf(DOMElement::class)
        ->and(trim($selected->textContent))->toBe('10:00 AM')
        ->and($confirm)->toBeInstanceOf(DOMElement::class)
        ->and($confirm->getAttribute('class'))->toBe('lyra-btn lyra-btn--primary lyra-btn--md')
        ->and(slotPickerAttribute(slotPickerOpeningTag($html, 'confirm'), '@click'))->toContain('confirm(')
        ->and(trim($confirm->textContent))->toBe('Reserve');
});

it('serves a ticking hold note only while the hold is alive', function (): void {
    Carbon::setTestNow('2026-08-10T13:00:00Z');

    try {
        $future = renderSlotPicker([
            'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
            'timezone' => 'UTC',
            'holdExpiresAt' => '2026-08-10T13:02:05Z',
        ]);
        $expired = renderSlotPicker([
            'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
            'timezone' => 'UTC',
            'holdExpiresAt' => '2026-08-10T12:59:59Z',
        ]);
        $loading = renderSlotPicker([
            'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
            'timezone' => 'UTC',
            'holdExpiresAt' => '2026-08-10T13:02:05Z',
            'loading' => true,
        ]);
        $emptyDay = renderSlotPicker([
            'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
            'date' => '2026-08-11',
            'timezone' => 'UTC',
            'holdExpiresAt' => '2026-08-10T13:02:05Z',
        ]);
        $withoutSlots = renderSlotPicker([
            'timezone' => 'UTC',
            'holdExpiresAt' => '2026-08-10T13:02:05Z',
        ]);
    } finally {
        Carbon::setTestNow();
    }

    $hold = slotPickerElement($future, 'hold');

    expect($hold->hasAttribute('x-cloak'))->toBeFalse()
        ->and(trim($hold->textContent))->toContain('Reserved for 2:05')
        ->and($hold->getAttribute('x-show'))->toBe('!loading && daySlots().length > 0 && holdLeft() !== null && holdLeft() > 0')
        ->and($hold->getElementsByTagName('svg')->item(0)?->getAttribute('width'))->toBe('14')
        ->and(slotPickerElement($expired, 'hold')->hasAttribute('x-cloak'))->toBeTrue()
        ->and(slotPickerElement($loading, 'hold')->hasAttribute('x-cloak'))->toBeTrue()
        ->and(slotPickerElement($emptyDay, 'hold')->hasAttribute('x-cloak'))->toBeTrue()
        ->and(slotPickerElement($withoutSlots, 'hold')->hasAttribute('x-cloak'))->toBeTrue();
});

it('serves the closed timezone control and forwards labels to the nested picker', function (): void {
    $html = renderSlotPicker([
        'timezone' => 'America/Sao_Paulo',
        'detectedZone' => 'America/Sao_Paulo',
        'locale' => 'pt-BR',
        'labels' => ['changeTimeZone' => 'alterar'],
        'tzLabels' => ['searchPlaceholder' => 'Buscar fuso…'],
    ]);
    [, $xpath] = slotPickerDocument($html);
    $timezone = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-slotpicker__tz ")]')->item(0);
    $globe = $xpath->query('.//svg[@width="15"]', $timezone)->item(0);
    $change = $xpath->query('.//button[@x-bind="changeTimeZone"]', $timezone)->item(0);
    $picker = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " lyra-tzpicker ")]', $timezone)->item(0);
    $pickerOptions = nestedTimeZonePickerOptions($html);

    expect($globe)->toBeInstanceOf(DOMElement::class)
        ->and($timezone->textContent)->toContain('São Paulo / Brasília')
        ->and($change)->toBeInstanceOf(DOMElement::class)
        ->and($change->getAttribute('type'))->toBe('button')
        ->and(trim($change->textContent))->toBe('alterar')
        ->and($picker)->toBeInstanceOf(DOMElement::class)
        ->and($picker->getAttribute('class'))->toContain('lyra-tzpicker')
        ->and($pickerOptions)->toMatchArray([
            'value' => 'America/Sao_Paulo',
            'detectedZone' => 'America/Sao_Paulo',
            'locale' => 'pt-BR',
        ])->and($pickerOptions['labels']['searchPlaceholder'])->toBe('Buscar fuso…');
});

it('composes calendar and timezone picker through predicate, marker, and alias scopes', function (): void {
    $html = renderSlotPicker([
        'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
        'date' => '2026-08-10',
        'timezone' => 'America/Sao_Paulo',
        'min' => '2026-08-01',
        'max' => '2026-08-31',
    ]);
    [, $xpath] = slotPickerDocument($html);
    $calendar = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-cal ")]')->item(0);
    $picker = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " lyra-tzpicker ")]')->item(0);

    expect($calendar)->toBeInstanceOf(DOMElement::class)
        ->and($calendar->getAttribute('class'))->toBe('lyra-cal lyra-cal--md')
        ->and($calendar->getAttribute('x-data'))->toContain('isDateDisabled: (date) => !hasSlots(date)')
        ->and($calendar->getAttribute('x-model'))->toBe('slotPickerDate')
        ->and($html)->toContain('get slotPickerDate() { return calendarValue() }')
        ->and(slotPickerElement($html, 'day-marker')->getAttribute('x-show'))->toBe('hasSlots(date)')
        ->and($picker)->toBeInstanceOf(DOMElement::class)
        ->and($picker->getAttribute('x-model'))->toBe('slotPickerTimeZone')
        ->and($html)->toContain('get slotPickerTimeZone() { return timeZoneValue() }');
});

it('defaults date modelability and whitelists timezone as the only alternative', function (): void {
    $date = renderSlotPicker(['wire:model.live' => 'bookingDate']);
    $timezone = renderSlotPicker([
        'x-modelable' => 'timezone',
        'x-model' => 'bookingZone',
        'x-data' => 'window.pwned = true',
    ]);
    $unknown = renderSlotPicker(['x-modelable' => 'selected']);

    expect(slotPickerElement($date, 'root')->getAttribute('x-modelable'))->toBe('date')
        ->and(slotPickerElement($date, 'root')->getAttribute('wire:model.live'))->toBe('bookingDate')
        ->and(slotPickerElement($timezone, 'root')->getAttribute('x-modelable'))->toBe('timezone')
        ->and(slotPickerElement($timezone, 'root')->getAttribute('x-model'))->toBe('bookingZone')
        ->and(slotPickerElement($unknown, 'root')->getAttribute('x-modelable'))->toBe('date')
        ->and(substr_count($timezone, 'x-data="lyraSlotPicker('))->toBe(1)
        ->and($timezone)->not->toContain('window.pwned');
});

it('hardens the root JSON literal and normalizes malformed server inputs', function (): void {
    $payload = "'); window.slotPickerPwned = true; //\"</script><img onerror=alert(1)>";
    $validSlot = [
        'start' => '2026-08-10T13:00:00Z',
        'end' => '2026-08-10T13:30:00Z',
    ];
    $html = renderSlotPicker([
        'slots' => [
            $validSlot,
            ['start' => $payload, 'end' => '2026-08-10T14:00:00Z'],
            ['start' => '2026-08-10T14:00:00Z'],
            $payload,
        ],
        'date' => '10/08/2026',
        'timezone' => $payload,
        'min' => '2026-08-00',
        'max' => $payload,
        'loading' => 'true',
        'labels' => ['confirm' => $payload],
    ]);
    $options = slotPickerOptions($html);

    expect($options['slots'])->toBe([$validSlot])
        ->and($options)->not->toHaveKeys(['date', 'timezone', 'min', 'max'])
        ->and($options['loading'])->toBeFalse()
        ->and($options['labels']['confirm'])->toBe($payload)
        ->and(substr_count($html, 'x-data="lyraSlotPicker('))->toBe(1)
        ->and(slotPickerElement($html, 'root')->getAttribute('x-data'))->toContain('\\u003C/script\\u003E')
        ->and($html)->not->toContain('</script>')
        ->and($html)->not->toContain('<img')
        ->and(slotPickerDocument($html)[0]->getElementsByTagName('img')->length)->toBe(0);
});

it('renders namespaced and short syntax identically', function (): void {
    $props = [
        'slots' => [['start' => '2026-08-10T13:00:00Z', 'end' => '2026-08-10T13:30:00Z']],
        'date' => '2026-08-10',
        'timezone' => 'America/Sao_Paulo',
        'class' => 'booking',
    ];

    $normalizeGeneratedIds = static fn (string $html): string => preg_replace(
        '/lyra-combobox-[a-f0-9]+/',
        'lyra-combobox-generated',
        $html,
    );

    expect($normalizeGeneratedIds(renderSlotPicker($props, short: true)))
        ->toBe($normalizeGeneratedIds(renderSlotPicker($props)));
});
