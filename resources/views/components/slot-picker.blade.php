@props([
    'slots' => [],
    'date' => null,
    'timezone' => null,
    'detectedZone' => null,
    'holdExpiresAt' => null,
    'nextAvailableDate' => null,
    'loading' => false,
    'locale' => 'en-US',
    'min' => null,
    'max' => null,
    'labels' => [],
    'tzLabels' => [],
])

{{--
    React owns this component's structure and classes; lyraSlotPicker owns its browser behavior.
    Slots are UTC values and confirmation remains a lyra:confirm event for server revalidation.
    date is modelable by default; timezone is the only opt-in alternative through x-modelable.

    The nested calendar and time-zone picker use alias scopes so their x-model names never bind
    back to themselves. The calendar predicate is a package-owned whitelist name, never consumer
    JavaScript. selected starts null, so every served slot starts in its unselected state.

    Passing timezone explicitly is recommended so the calendar and slot panel stay aligned. Without
    it, the initial day and calendar month follow the server zone; on boot, only the slot-picker day
    is recalculated in the browser-detected zone while the calendar keeps its initial month. This
    matches React hydration. The hold text is served from the server clock and ticks in Alpine; an
    inherent one-second boundary drift is expected and the value is never hidden for that reason.
--}}
@php
    $jsonFlags = JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE;
    $normalizeDay = static function (mixed $value): ?string {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $value
                ? $value
                : null;
    };
    $normalizeInstant = static function (mixed $value): ?DateTimeImmutable {
        if (
            ! is_string($value)
            || preg_match('/(?:Z|[+-]\d{2}:\d{2})$/', $value) !== 1
        ) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    };
    $resolvedDate = $normalizeDay($date);
    $resolvedMin = $normalizeDay($min);
    $resolvedMax = $normalizeDay($max);
    $resolvedNextAvailableDate = $normalizeDay($nextAvailableDate);
    $resolvedLoading = is_bool($loading) ? $loading : false;
    $resolvedLocale = is_string($locale)
        && preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/', $locale) === 1
            ? $locale
            : 'en-US';
    $knownTimezones = array_fill_keys(DateTimeZone::listIdentifiers(), true);
    $explicitTimezone = is_string($timezone) && isset($knownTimezones[$timezone])
        ? $timezone
        : null;
    $phpTimezone = date_default_timezone_get();
    $serverTimezone = $explicitTimezone
        ?? (isset($knownTimezones[$phpTimezone]) ? $phpTimezone : 'UTC');
    $resolvedDetectedZone = is_string($detectedZone) && isset($knownTimezones[$detectedZone])
        ? $detectedZone
        : null;
    $resolvedHold = $normalizeInstant($holdExpiresAt);
    $resolvedSlots = [];

    if (is_array($slots)) {
        foreach ($slots as $slotValue) {
            if (
                ! is_array($slotValue)
                || ! array_key_exists('start', $slotValue)
                || ! array_key_exists('end', $slotValue)
                || ! is_string($slotValue['start'])
                || ! is_string($slotValue['end'])
                || $normalizeInstant($slotValue['start']) === null
                || $normalizeInstant($slotValue['end']) === null
            ) {
                continue;
            }

            $resolvedSlots[] = [
                'start' => $slotValue['start'],
                'end' => $slotValue['end'],
            ];
        }
    }

    usort(
        $resolvedSlots,
        static fn (array $left, array $right): int => (new DateTimeImmutable($left['start']))->getTimestamp()
            <=> (new DateTimeImmutable($right['start']))->getTimestamp(),
    );

    $zone = new DateTimeZone($serverTimezone);
    $byDay = [];

    foreach ($resolvedSlots as $resolvedSlot) {
        $instant = new DateTimeImmutable($resolvedSlot['start']);
        $localDay = $instant->setTimezone($zone)->format('Y-m-d');
        $byDay[$localDay][] = $resolvedSlot;
    }

    ksort($byDay);
    $firstDay = array_key_first($byDay);
    $serverDay = $resolvedDate ?? $firstDay;
    $serverDaySlots = $serverDay === null ? [] : ($byDay[$serverDay] ?? []);
    $timeFormatter = new IntlDateFormatter(
        $resolvedLocale,
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        $serverTimezone,
        null,
        'hh:mm a',
    );
    $datePattern = IntlDatePatternGenerator::create($resolvedLocale)->getBestPattern('EEEEMMMMd');
    $dateFormatter = new IntlDateFormatter(
        $resolvedLocale,
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        $serverTimezone,
        null,
        $datePattern,
    );
    $longDate = static function (?string $isoDay) use ($dateFormatter, $zone): string {
        if ($isoDay === null) {
            return '';
        }

        return (string) $dateFormatter->format(new DateTimeImmutable($isoDay.'T12:00:00', $zone));
    };
    $interpolate = static fn (string $template, array $values = []): string => (string) preg_replace_callback(
        '/\{(\w+)\}/',
        static fn (array $match): string => (string) ($values[$match[1]] ?? ''),
        $template,
    );
    $defaultLabels = [
        'confirm' => 'Confirm',
        'emptyMessage' => 'No available times on this day.',
        'fullMessage' => 'There are no available times right now.',
        'loading' => 'Loading available times',
        'availableTimes' => 'Available times for {date}',
        'nextAvailable' => 'Next available time: {date}.',
        'goToDate' => 'Go to {date}',
        'changeTimeZone' => 'change',
        'hold' => 'Reserved for {minutes}:{seconds}',
    ];
    $resolvedLabels = $defaultLabels;

    if (is_array($labels)) {
        foreach ($defaultLabels as $labelName => $defaultLabel) {
            if (array_key_exists($labelName, $labels) && is_string($labels[$labelName])) {
                $resolvedLabels[$labelName] = $labels[$labelName];
            }
        }
    }

    $resolvedTzLabels = is_array($tzLabels) ? $tzLabels : [];
    $options = [
        'slots' => $resolvedSlots,
        'loading' => $resolvedLoading,
        'locale' => $resolvedLocale,
        'labels' => $resolvedLabels,
        'tzLabels' => $resolvedTzLabels,
    ];

    if ($resolvedDate !== null) {
        $options['date'] = $resolvedDate;
    }

    if ($explicitTimezone !== null) {
        $options['timezone'] = $explicitTimezone;
    }

    if ($resolvedDetectedZone !== null) {
        $options['detectedZone'] = $resolvedDetectedZone;
    }

    if ($resolvedHold !== null) {
        $options['holdExpiresAt'] = $resolvedHold->format('Y-m-d\TH:i:sP');
    }

    if ($resolvedNextAvailableDate !== null) {
        $options['nextAvailableDate'] = $resolvedNextAvailableDate;
    }

    if ($resolvedMin !== null) {
        $options['min'] = $resolvedMin;
    }

    if ($resolvedMax !== null) {
        $options['max'] = $resolvedMax;
    }

    $optionsLiteral = json_encode($options, $jsonFlags);
    $serverSlots = [];

    foreach ($resolvedSlots as $resolvedSlot) {
        $instant = new DateTimeImmutable($resolvedSlot['start']);
        $slotDay = $instant->setTimezone($zone)->format('Y-m-d');
        $serverSlots[] = [
            'value' => $resolvedSlot,
            'literal' => json_encode($resolvedSlot, $jsonFlags),
            'startLiteral' => json_encode($resolvedSlot['start'], $jsonFlags),
            'day' => $slotDay,
            'time' => (string) $timeFormatter->format($instant),
        ];
    }

    $hasAnySlots = $byDay !== [];
    $showLoading = $resolvedLoading;
    $showFull = ! $resolvedLoading && ! $hasAnySlots;
    $showEmpty = ! $resolvedLoading && $hasAnySlots && $serverDaySlots === [];
    $showSlots = ! $resolvedLoading && $serverDaySlots !== [];
    $serverDayLabel = $longDate($serverDay);
    $nextDateLabel = $longDate($resolvedNextAvailableDate);
    $nextDateHasSlots = $resolvedNextAvailableDate !== null
        && array_key_exists($resolvedNextAvailableDate, $byDay);
    $holdLeft = $resolvedHold === null
        ? null
        : max(0, $resolvedHold->getTimestamp() - now()->getTimestamp());
    $showHold = $showSlots && $holdLeft !== null && $holdLeft > 0;
    $zoneLabels = [
        'America/Sao_Paulo' => 'São Paulo / Brasília',
        'America/Manaus' => 'Manaus',
        'America/Argentina/Buenos_Aires' => 'Buenos Aires',
        'America/Santiago' => 'Santiago',
        'America/Bogota' => 'Bogotá',
        'America/Lima' => 'Lima',
        'America/Mexico_City' => 'Mexico City',
        'America/New_York' => 'New York',
        'America/Chicago' => 'Chicago',
        'America/Denver' => 'Denver',
        'America/Los_Angeles' => 'Los Angeles',
        'Europe/Lisbon' => 'Lisbon',
        'Europe/London' => 'London',
        'Europe/Madrid' => 'Madrid',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Africa/Cairo' => 'Cairo',
        'Africa/Lagos' => 'Lagos',
        'Africa/Johannesburg' => 'Johannesburg',
        'Asia/Dubai' => 'Dubai',
        'Asia/Kolkata' => 'Mumbai / New Delhi',
        'Asia/Singapore' => 'Singapore',
        'Asia/Shanghai' => 'Beijing / Shanghai',
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Seoul' => 'Seoul',
        'Australia/Sydney' => 'Sydney',
        'Pacific/Auckland' => 'Auckland',
    ];
    $visibleTimezone = $zoneLabels[$serverTimezone] ?? $serverTimezone;
    $requestedModelable = $attributes->get('x-modelable');
    $resolvedModelable = is_string($requestedModelable)
        && in_array($requestedModelable, ['date', 'timezone'], true)
            ? $requestedModelable
            : 'date';
    $rootAttributes = $attributes
        ->except(['x-data', 'x-modelable'])
        ->class('lyra-slotpicker');
@endphp

<div
    x-data="{{ 'lyraSlotPicker('.$optionsLiteral.')' }}"
    x-modelable="{{ $resolvedModelable }}"
    {{ $rootAttributes }}
>
    <div class="lyra-slotpicker__side">
        {{ $slot }}

        <div x-data="{ get slotPickerDate() { return calendarValue() }, set slotPickerDate(v) { setDay(v) } }">
            <x-lyra::calendar
                size="md"
                :default-value="$serverDay"
                :min="$resolvedMin"
                :max="$resolvedMax"
                :locale="$resolvedLocale"
                date-disabled-predicate="slot-picker"
                x-model="slotPickerDate"
            >
                <x-slot:dayMarker>
                    <span class="lyra-cal__dot" x-show="hasSlots(date)"></span>
                </x-slot:dayMarker>
            </x-lyra::calendar>
        </div>

        <div class="lyra-slotpicker__tz">
            <x-lyra::icon name="globe" :size="15" />
            <span x-show="!tzOpen" x-text="visibleTimeZone()">{{ $visibleTimezone }}</span>
            <button
                type="button"
                x-show="!tzOpen"
                x-bind="changeTimeZone"
                x-text="label('changeTimeZone')"
            >{{ $resolvedLabels['changeTimeZone'] }}</button>
            <div
                x-show="tzOpen"
                x-cloak
                x-data="{ get slotPickerTimeZone() { return timeZoneValue() }, set slotPickerTimeZone(v) { setTimeZone(v) } }"
            >
                <x-lyra::time-zone-picker
                    :value="$serverTimezone"
                    :detected-zone="$resolvedDetectedZone"
                    :locale="$resolvedLocale"
                    :labels="$resolvedTzLabels"
                    x-model="slotPickerTimeZone"
                />
            </div>
        </div>
    </div>

    <div class="lyra-slotpicker__main" aria-live="polite" x-bind="main">
        <div
            class="lyra-slotpicker__slots"
            aria-label="{{ $resolvedLabels['loading'] }}"
            :aria-label="label('loading')"
            x-show="loading"
            @unless ($showLoading)
                x-cloak
            @endunless
        >
            @for ($index = 0; $index < 6; $index++)
                <span class="lyra-slotpicker__skeleton"></span>
            @endfor
        </div>

        <div
            class="lyra-slotpicker__empty"
            x-show="!loading && byDay().size === 0"
            @unless ($showFull)
                x-cloak
            @endunless
        >
            <span x-text="label('fullMessage')">{{ $resolvedLabels['fullMessage'] }}</span>
        </div>

        <div
            class="lyra-slotpicker__empty"
            x-show="!loading && byDay().size > 0 && daySlots().length === 0"
            @unless ($showEmpty)
                x-cloak
            @endunless
        >
            <span x-text="label('emptyMessage')">{{ $resolvedLabels['emptyMessage'] }}</span>
            <span
                x-show="nextAvailableDate && byDay().has(nextAvailableDate)"
                x-text="label('nextAvailable', { date: longDate(nextAvailableDate) })"
                @unless ($nextDateHasSlots)
                    x-cloak
                @endunless
            >{{ $interpolate($resolvedLabels['nextAvailable'], ['date' => $nextDateLabel]) }}</span>
            <x-lyra::button
                variant="secondary"
                size="sm"
                x-show="nextAvailableDate && byDay().has(nextAvailableDate)"
                x-bind="nextAvailable"
                x-text="label('goToDate', { date: longDate(nextAvailableDate) })"
                :x-cloak="! $nextDateHasSlots"
            >{{ $interpolate($resolvedLabels['goToDate'], ['date' => $nextDateLabel]) }}</x-lyra::button>
        </div>

        <span
            class="lyra-slotpicker__daylabel"
            x-show="!loading && daySlots().length > 0"
            x-text="dayLabel()"
            @unless ($showSlots)
                x-cloak
            @endunless
        >{{ $serverDayLabel }}</span>
        <div
            class="lyra-slotpicker__slots"
            role="listbox"
            aria-label="{{ $interpolate($resolvedLabels['availableTimes'], ['date' => $serverDayLabel]) }}"
            :aria-label="label('availableTimes', { date: dayLabel() })"
            x-show="!loading && daySlots().length > 0"
            @unless ($showSlots)
                x-cloak
            @endunless
        >
            @foreach ($serverSlots as $serverSlot)
                @php
                    $inDayExpression = 'daySlots().some((candidate) => candidate.start === '
                        .$serverSlot['startLiteral'].')';
                    $selectedExpression = 'selected?.start === '.$serverSlot['startLiteral'];
                    $slotInitiallyVisible = $showSlots && $serverSlot['day'] === $serverDay;
                @endphp
                <span
                    class="lyra-slotpicker__pair"
                    x-show="{{ $inDayExpression.' && '.$selectedExpression }}"
                    x-cloak
                >
                    <span
                        class="lyra-slotpicker__slot"
                        role="option"
                        aria-selected="true"
                        x-text="{{ 'timeOf('.$serverSlot['startLiteral'].')' }}"
                    >{{ $serverSlot['time'] }}</span>
                    <x-lyra::button
                        x-bind="confirmButton"
                        x-on:click="{{ 'confirm('.$serverSlot['literal'].')' }}"
                        x-text="label('confirm')"
                    >{{ $resolvedLabels['confirm'] }}</x-lyra::button>
                </span>
                <button
                    type="button"
                    role="option"
                    aria-selected="false"
                    class="lyra-slotpicker__slot"
                    x-show="{{ $inDayExpression.' && !('.$selectedExpression.')' }}"
                    x-on:click="{{ 'selectSlot('.$serverSlot['literal'].')' }}"
                    x-text="{{ 'timeOf('.$serverSlot['startLiteral'].')' }}"
                    @unless ($slotInitiallyVisible)
                        x-cloak
                    @endunless
                >{{ $serverSlot['time'] }}</button>
            @endforeach
        </div>

        <span
            class="lyra-slotpicker__hold"
            x-show="!loading && daySlots().length > 0 && holdLeft() !== null && holdLeft() > 0"
            @unless ($showHold)
                x-cloak
            @endunless
        >
            <x-lyra::icon name="timer" :size="14" />
            <span
                x-text="label('hold', { minutes: Math.floor(holdLeft() / 60), seconds: String(holdLeft() % 60).padStart(2, '0') })"
            >@if ($holdLeft !== null){{ $interpolate($resolvedLabels['hold'], [
                'minutes' => intdiv($holdLeft, 60),
                'seconds' => str_pad((string) ($holdLeft % 60), 2, '0', STR_PAD_LEFT),
            ]) }}@endif</span>
        </span>
    </div>
</div>
