@props([
    'value' => null,
    'startDate' => null,
    'defaultEndCount' => null,
    'conflicts' => [],
    'labels' => [],
])

{{--
    React's formatWeekdays, formatOrdinal, and formatDate labels are functions, so they cannot
    cross Blade props. The server mirrors the binding's en-US defaults for the served summary;
    custom formatter locales remain an upstream concern. conflictsOne/conflictsMany are the
    binding's JSON-safe replacement for React's conflicts(count) function.
--}}
@php
    $jsonFlags = JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE;

    $defaultLabels = [
        'weekdaysShort' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'weekdaysLong' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'none' => 'Does not repeat',
        'weekly' => 'Repeats every {days}',
        'weeklyCount' => 'Repeats every {days}, {count} times',
        'weeklyDate' => 'Repeats every {days}, until {date}',
        'weeklyInterval' => 'Repeats every {interval} weeks on {days}',
        'weeklyIntervalCount' => 'Repeats every {interval} weeks on {days}, {count} times',
        'weeklyIntervalDate' => 'Repeats every {interval} weeks on {days}, until {date}',
        'monthly' => 'Repeats every month on the {ordinal} {weekday}',
        'monthlyCount' => 'Repeats every month on the {ordinal} {weekday}, {count} times',
        'monthlyDate' => 'Repeats every month on the {ordinal} {weekday}, until {date}',
        'monthlyInterval' => 'Repeats every {interval} months on the {ordinal} {weekday}',
        'monthlyIntervalCount' => 'Repeats every {interval} months on the {ordinal} {weekday}, {count} times',
        'monthlyIntervalDate' => 'Repeats every {interval} months on the {ordinal} {weekday}, until {date}',
        'recurrence' => 'Recurrence',
        'noRepeat' => 'Does not repeat',
        'everyWeek' => 'Every week ({weekday})',
        'everyTwoWeeks' => 'Every 2 weeks ({weekday})',
        'everyMonth' => 'Every month ({ordinal} {weekday})',
        'custom' => 'Custom…',
        'repeatEvery' => 'Repeat every',
        'interval' => 'Interval',
        'frequency' => 'Frequency',
        'weeks' => 'week(s)',
        'months' => 'month(s)',
        'weekdays' => 'Days of the week',
        'ends' => 'Ends',
        'neverEnds' => 'Never ends',
        'afterOccurrences' => 'After N occurrences',
        'onDate' => 'On a date',
        'occurrences' => 'Occurrences',
        'times' => 'times',
        'endDate' => 'End date',
        'conflictsOne' => '1 occurrence falls in unavailable time; you can adjust it later.',
        'conflictsMany' => '{count} occurrences fall in unavailable time; you can adjust them later.',
    ];
    $resolvedLabels = $defaultLabels;

    if (is_array($labels)) {
        foreach (array_keys($defaultLabels) as $labelKey) {
            if (! array_key_exists($labelKey, $labels)) {
                continue;
            }

            if (in_array($labelKey, ['weekdaysShort', 'weekdaysLong'], true)) {
                $candidate = $labels[$labelKey];

                if (
                    is_array($candidate)
                    && count($candidate) === 7
                    && collect($candidate)->every(fn (mixed $item): bool => is_string($item))
                ) {
                    $resolvedLabels[$labelKey] = array_values($candidate);
                }

                continue;
            }

            if (is_string($labels[$labelKey])) {
                $resolvedLabels[$labelKey] = $labels[$labelKey];
            }
        }
    }

    $isoDate = static function (mixed $candidate): ?string {
        if ($candidate instanceof \DateTimeInterface) {
            return $candidate->format('Y-m-d');
        }

        if (! is_string($candidate) || trim($candidate) === '') {
            return null;
        }

        $trimmed = trim($candidate);

        if (preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', $trimmed, $parts) === 1) {
            return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])
                ? $trimmed
                : null;
        }

        try {
            return (new \DateTimeImmutable($trimmed))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    };

    $resolvedStartDate = $isoDate($startDate) ?? (new \DateTimeImmutable('today'))->format('Y-m-d');
    $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $resolvedStartDate);

    if (! $start instanceof \DateTimeImmutable) {
        $start = new \DateTimeImmutable('today');
        $resolvedStartDate = $start->format('Y-m-d');
    }

    $resolvedDefaultEndCount = null;

    if (
        (is_int($defaultEndCount) || is_float($defaultEndCount))
        && is_finite((float) $defaultEndCount)
        && $defaultEndCount > 0
    ) {
        $resolvedDefaultEndCount = max(1, (int) floor($defaultEndCount));
    }

    $noneRule = [
        'freq' => 'none',
        'interval' => 1,
        'byWeekday' => [],
        'end' => ['type' => 'never'],
    ];
    $normalizeRule = static function (mixed $candidate) use ($isoDate, $noneRule): array {
        if (! is_array($candidate) || ! in_array($candidate['freq'] ?? null, ['none', 'weekly', 'monthly'], true)) {
            return $noneRule;
        }

        // This server-rendered trust boundary keeps only known keys instead of spreading arbitrary app data.
        // Keep server-side clamping and sanitization for intervals, weekdays, counts, and ISO dates.
        $normalized = ['freq' => $candidate['freq']];

        if (array_key_exists('interval', $candidate)) {
            $interval = $candidate['interval'];

            if ((is_int($interval) || is_float($interval)) && is_finite((float) $interval)) {
                $normalized['interval'] = max(1, (int) floor($interval));
            } else {
                $normalized['interval'] = 1;
            }
        }

        if (array_key_exists('byWeekday', $candidate)) {
            $normalized['byWeekday'] = [];

            if (is_array($candidate['byWeekday'])) {
                foreach ($candidate['byWeekday'] as $day) {
                    if (
                        is_int($day)
                        && $day >= 0
                        && $day <= 6
                        && ! in_array($day, $normalized['byWeekday'], true)
                    ) {
                        $normalized['byWeekday'][] = $day;
                    }
                }
            }
        }

        if (array_key_exists('end', $candidate)) {
            $end = $candidate['end'];

            if ($end === null) {
                $normalized['end'] = null;
            } elseif (! is_array($end) || ! in_array($end['type'] ?? null, ['never', 'count', 'date'], true)) {
                $normalized['end'] = ['type' => 'never'];
            } elseif ($end['type'] === 'count') {
                $normalized['end'] = ['type' => 'count'];
                $count = $end['count'] ?? null;

                if ((is_int($count) || is_float($count)) && is_finite((float) $count)) {
                    $normalized['end']['count'] = max(1, (int) floor($count));
                }
            } elseif ($end['type'] === 'date') {
                $normalized['end'] = [
                    'type' => 'date',
                    'date' => $isoDate($end['date'] ?? null),
                ];
            } else {
                $normalized['end'] = ['type' => 'never'];
            }
        }

        return $normalized;
    };
    $resolvedValue = $normalizeRule($value);
    $resolvedConflicts = [];

    if (is_array($conflicts)) {
        foreach ($conflicts as $conflict) {
            if (! is_array($conflict)) {
                continue;
            }

            $resolvedConflict = ['date' => $isoDate($conflict['date'] ?? null) ?? ''];

            if (is_string($conflict['reason'] ?? null)) {
                $resolvedConflict['reason'] = $conflict['reason'];
            }

            $resolvedConflicts[] = $resolvedConflict;
        }
    }

    $interpolate = static fn (string $template, array $values): string => preg_replace_callback(
        '/\{(\w+)\}/',
        static fn (array $match): string => (string) ($values[$match[1]] ?? ''),
        $template,
    );
    $formatWeekdays = static function (array $days): string {
        $count = count($days);

        return match (true) {
            $count === 0 => '',
            $count === 1 => $days[0],
            $count === 2 => $days[0].' and '.$days[1],
            default => implode(', ', array_slice($days, 0, -1)).', and '.$days[$count - 1],
        };
    };
    $formatOrdinal = static function (int $position): string {
        $moduloHundred = $position % 100;
        $suffix = in_array($moduloHundred, [11, 12, 13], true)
            ? 'th'
            : match ($position % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };

        return $position.$suffix;
    };
    $formatDate = static function (?string $date): string {
        if ($date === null) {
            return '';
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable ? $parsed->format('M j, Y') : '';
    };
    $weekday = (int) $start->format('w');
    $ordinal = (int) floor(((int) $start->format('j') - 1) / 7) + 1;
    $interval = max(1, (int) floor($resolvedValue['interval'] ?? 1));
    $ruleDays = $resolvedValue['byWeekday'] ?? [];
    $summaryDays = $ruleDays === [] ? [$weekday] : $ruleDays;
    sort($summaryDays);
    $weekdayNames = array_map(
        fn (int $day): string => $resolvedLabels['weekdaysLong'][$day],
        $summaryDays,
    );
    $end = is_array($resolvedValue['end'] ?? null) ? $resolvedValue['end'] : [];
    $endType = in_array($end['type'] ?? null, ['count', 'date'], true) ? $end['type'] : 'never';
    $resolvedEndDate = $endType === 'date' ? ($end['date'] ?? null) : null;
    $summaryValues = [
        'days' => $formatWeekdays($weekdayNames),
        'interval' => $interval,
        'ordinal' => $formatOrdinal($ordinal),
        'weekday' => $resolvedLabels['weekdaysLong'][$weekday],
        'count' => $end['count'] ?? 1,
        'date' => $formatDate($resolvedEndDate),
    ];

    if ($resolvedValue['freq'] === 'none') {
        $summaryText = $resolvedLabels['none'];
    } else {
        $templateKey = match (true) {
            $resolvedValue['freq'] === 'monthly' && $interval === 1 && $endType === 'count' => 'monthlyCount',
            $resolvedValue['freq'] === 'monthly' && $interval === 1 && $endType === 'date' => 'monthlyDate',
            $resolvedValue['freq'] === 'monthly' && $interval === 1 => 'monthly',
            $resolvedValue['freq'] === 'monthly' && $endType === 'count' => 'monthlyIntervalCount',
            $resolvedValue['freq'] === 'monthly' && $endType === 'date' => 'monthlyIntervalDate',
            $resolvedValue['freq'] === 'monthly' => 'monthlyInterval',
            $interval === 1 && $endType === 'count' => 'weeklyCount',
            $interval === 1 && $endType === 'date' => 'weeklyDate',
            $interval === 1 => 'weekly',
            $endType === 'count' => 'weeklyIntervalCount',
            $endType === 'date' => 'weeklyIntervalDate',
            default => 'weeklyInterval',
        };
        $summaryText = $interpolate($resolvedLabels[$templateKey], $summaryValues);
    }

    $defaultEnd = $resolvedDefaultEndCount !== null
        ? ['type' => 'count', 'count' => $resolvedDefaultEndCount]
        : ['type' => 'never'];
    $presets = [
        [
            'id' => 'none',
            'label' => $resolvedLabels['noRepeat'],
            'rule' => $noneRule,
        ],
        [
            'id' => 'weekly',
            'label' => $interpolate($resolvedLabels['everyWeek'], [
                'weekday' => $resolvedLabels['weekdaysShort'][$weekday],
            ]),
            'rule' => ['freq' => 'weekly', 'interval' => 1, 'byWeekday' => [$weekday], 'end' => $defaultEnd],
        ],
        [
            'id' => 'biweekly',
            'label' => $interpolate($resolvedLabels['everyTwoWeeks'], [
                'weekday' => $resolvedLabels['weekdaysShort'][$weekday],
            ]),
            'rule' => ['freq' => 'weekly', 'interval' => 2, 'byWeekday' => [$weekday], 'end' => $defaultEnd],
        ],
        [
            'id' => 'monthly',
            'label' => $interpolate($resolvedLabels['everyMonth'], [
                'ordinal' => $formatOrdinal($ordinal),
                'weekday' => $resolvedLabels['weekdaysLong'][$weekday],
            ]),
            'rule' => ['freq' => 'monthly', 'interval' => 1, 'byWeekday' => [$weekday], 'end' => $defaultEnd],
        ],
    ];
    $matchedPreset = null;

    foreach ($presets as $preset) {
        $presetDays = $preset['rule']['byWeekday'] ?? [];
        $valueDays = $resolvedValue['byWeekday'] ?? [];
        sort($presetDays);
        sort($valueDays);

        if (
            $preset['rule']['freq'] === $resolvedValue['freq']
            && $preset['rule']['interval'] === $interval
            && $presetDays === $valueDays
        ) {
            $matchedPreset = $preset;

            break;
        }
    }

    $selectedPreset = $matchedPreset['id'] ?? 'custom';
    $showCustom = $matchedPreset === null && $resolvedValue['freq'] !== 'none';
    $frequencyValue = $resolvedValue['freq'] === 'monthly' ? 'monthly' : 'weekly';
    $countValue = $end['count'] ?? 8;
    $conflictCount = count($resolvedConflicts);
    $conflictText = $conflictCount === 1
        ? $resolvedLabels['conflictsOne']
        : $interpolate($resolvedLabels['conflictsMany'], ['count' => $conflictCount]);
    $bindingOptions = [
        'value' => $resolvedValue,
        'startDate' => $resolvedStartDate,
        'defaultEndCount' => $resolvedDefaultEndCount,
        'conflicts' => $resolvedConflicts,
        'labels' => $resolvedLabels,
    ];
    $optionsLiteral = json_encode($bindingOptions, $jsonFlags);
    $rootAttributes = $attributes
        ->except(['x-data', 'x-modelable'])
        ->class('lyra-recur');
@endphp

<div
    x-data="{{ 'lyraRecurrenceSelector('.$optionsLiteral.')' }}"
    x-modelable="value"
    {{ $rootAttributes }}
>
    <span class="lyra-select-wrap">
        <select
            class="lyra-input"
            aria-label="{{ $resolvedLabels['recurrence'] }}"
            x-bind="presetSelect"
        >
            @foreach ($presets as $presetIndex => $preset)
                <option
                    value="{{ $preset['id'] }}"
                    @selected($selectedPreset === $preset['id'])
                    x-text="presetEntries()[{{ $presetIndex }}].label"
                >{{ $preset['label'] }}</option>
            @endforeach
            <option
                value="custom"
                @selected($selectedPreset === 'custom')
                x-text="label('custom')"
            >{{ $resolvedLabels['custom'] }}</option>
        </select>
    </span>

    <div
        class="lyra-recur__custom"
        x-bind="customSection"
        @if (! $showCustom)
            x-cloak
        @endif
    >
        <div class="lyra-recur__freqrow">
            <span x-text="label('repeatEvery')">{{ $resolvedLabels['repeatEvery'] }}</span>
            <input
                type="number"
                min="1"
                max="12"
                class="lyra-input"
                aria-label="{{ $resolvedLabels['interval'] }}"
                value="{{ $interval }}"
                x-bind="intervalInput"
            >
            <span class="lyra-select-wrap">
                <select
                    class="lyra-input"
                    aria-label="{{ $resolvedLabels['frequency'] }}"
                    x-bind="freqSelect"
                >
                    <option
                        value="weekly"
                        @selected($frequencyValue === 'weekly')
                        x-text="label('weeks')"
                    >{{ $resolvedLabels['weeks'] }}</option>
                    <option
                        value="monthly"
                        @selected($frequencyValue === 'monthly')
                        x-text="label('months')"
                    >{{ $resolvedLabels['months'] }}</option>
                </select>
            </span>
        </div>

        <div
            class="lyra-recur__days"
            role="group"
            aria-label="{{ $resolvedLabels['weekdays'] }}"
            x-bind="weekdayGroup"
            @if ($resolvedValue['freq'] === 'monthly')
                x-cloak
            @endif
        >
            @foreach ($resolvedLabels['weekdaysShort'] as $dayIndex => $dayLabel)
                @php($dayIsPressed = in_array($dayIndex, $ruleDays, true))
                <button
                    type="button"
                    aria-pressed="{{ $dayIsPressed ? 'true' : 'false' }}"
                    @class([
                        'lyra-recur__day',
                        'lyra-recur__day--on' => $dayIsPressed,
                    ])
                    :class="dayClass({{ $dayIndex }})"
                    :aria-pressed="dayPressed({{ $dayIndex }})"
                    x-on:click="toggleDay({{ $dayIndex }})"
                    x-text="weekdayEntries()[{{ $dayIndex }}].label"
                >{{ $dayLabel }}</button>
            @endforeach
        </div>

        <div class="lyra-recur__endrow">
            <span class="lyra-select-wrap">
                <select
                    class="lyra-input"
                    aria-label="{{ $resolvedLabels['ends'] }}"
                    x-bind="endSelect"
                >
                    <option
                        value="never"
                        @selected($endType === 'never')
                        x-text="label('neverEnds')"
                    >{{ $resolvedLabels['neverEnds'] }}</option>
                    <option
                        value="count"
                        @selected($endType === 'count')
                        x-text="label('afterOccurrences')"
                    >{{ $resolvedLabels['afterOccurrences'] }}</option>
                    <option
                        value="date"
                        @selected($endType === 'date')
                        x-text="label('onDate')"
                    >{{ $resolvedLabels['onDate'] }}</option>
                </select>
            </span>
            <input
                type="number"
                min="1"
                max="99"
                class="lyra-input"
                aria-label="{{ $resolvedLabels['occurrences'] }}"
                value="{{ $countValue }}"
                x-bind="countInput"
                @if ($endType !== 'count')
                    x-cloak
                @endif
            >
            <span
                x-bind="countSuffix"
                @if ($endType !== 'count')
                    x-cloak
                @endif
            >{{ $resolvedLabels['times'] }}</span>
            <span
                class="lyra-recur__enddate"
                x-show="endType() === 'date'"
                @if ($endType !== 'date')
                    x-cloak
                @endif
            >
                <div x-data="{ get recurrenceEndDate() { return endDate() }, set recurrenceEndDate(v) { setEndDate(v) } }">
                    <x-lyra::date-picker
                        :default-value="$resolvedEndDate"
                        :placeholder="$resolvedLabels['endDate']"
                        :min="$resolvedStartDate"
                        x-model="recurrenceEndDate"
                    />
                </div>
            </span>
        </div>
    </div>

    <span
        class="lyra-recur__summary"
        aria-live="polite"
        x-bind="summary"
    >{{ $summaryText }}</span>

    @if ($conflictCount > 0)
        <span
            class="lyra-recur__summary"
            role="status"
            x-bind="conflictsNote"
        >{{ $conflictText }}</span>
    @endif
</div>
