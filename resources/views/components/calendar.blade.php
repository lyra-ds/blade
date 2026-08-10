@props([
    'range' => false,
    'defaultValue' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
    'weekStartsOn' => 0,
    'size' => 'sm',
    'todayButton' => false,
    'locale' => 'en-US',
    'labels' => [],
    'dateDisabledPredicate' => null,
])

{{--
    Calendar is a documented runtime-rendered exception: locale-dependent headings and cells come
    from Intl through these served x-for templates. The selected Date or {start, end} object is
    modelable; consumers that need ISO values should listen for the lyra:change event instead.

    dateDisabledPredicate exists for internal package composition. Its whitelist deliberately maps
    package-owned names to expressions; unsupported names are omitted instead of injecting a
    consumer-provided x-data expression.
--}}
@php
    $options = [];
    $allowedDateDisabledPredicates = [
        'slot-picker' => '(date) => !hasSlots(date)',
    ];

    if ($range) {
        $options['range'] = true;
    }

    if ($defaultValue !== null) {
        $options['defaultValue'] = $defaultValue;
    }

    if ($min !== null) {
        $options['min'] = $min;
    }

    if ($max !== null) {
        $options['max'] = $max;
    }

    if ($disabledDates !== []) {
        $options['disabledDates'] = $disabledDates;
    }

    if ((int) $weekStartsOn !== 0) {
        $options['weekStartsOn'] = (int) $weekStartsOn;
    }

    if ($locale !== 'en-US') {
        $options['locale'] = $locale;
    }

    if ($labels !== []) {
        $options['labels'] = $labels;
    }

    $optionsLiteral = json_encode(
        $options === [] ? (object) [] : $options,
        JSON_THROW_ON_ERROR
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE,
    );
    $predicateExpression = is_string($dateDisabledPredicate)
        ? ($allowedDateDisabledPredicates[$dateDisabledPredicate] ?? null)
        : null;

    if ($predicateExpression !== null) {
        $optionsLiteral = substr($optionsLiteral, 0, -1)
            .($options === [] ? '' : ',')
            .'isDateDisabled: '.$predicateExpression.'}';
    }
@endphp

<div
    x-data="{{ 'lyraCalendar('.$optionsLiteral.')' }}"
    x-modelable="selected"
    {{ $attributes->class([
        'lyra-cal',
        'lyra-cal--md' => $size === 'md',
    ]) }}
>
    <div class="lyra-cal__head">
        <button
            type="button"
            class="lyra-cal__nav"
            x-bind="prev"
        >
            <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>
        <button
            type="button"
            class="lyra-cal__label"
            x-bind="viewButton"
            x-text="headerLabel()"
        ></button>
        <button
            type="button"
            class="lyra-cal__nav"
            x-bind="next"
        >
            <svg
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </div>

    <template x-if="mode === 'days'">
        <div class="lyra-cal__grid">
            <template x-for="weekday in weekdays()" :key="weekday.key">
                <span class="lyra-cal__wd" :aria-label="weekday.long" x-text="weekday.narrow"></span>
            </template>
            <template x-for="date in days()" :key="dayKey(date)">
                <button
                    type="button"
                    class="lyra-cal__day"
                    :class="dayClass(date)"
                    :aria-disabled="dayDisabled(date)"
                    :tabindex="dayTabindex(date)"
                    :aria-label="dayLabel(date)"
                    :aria-pressed="dayPressed(date)"
                    :data-key="dayKey(date)"
                    @click="selectDate(date)"
                    @focus="onDayFocus(date)"
                    @keydown="onDayKeydown($event, date)"
                >
                    <span x-text="date.getDate()"></span>
                    @isset($dayMarker)
                        {{ $dayMarker }}
                    @endisset
                </button>
            </template>
        </div>
    </template>

    @if ($todayButton)
        <template x-if="mode === 'days'">
            <div class="lyra-cal__foot">
                <button
                    type="button"
                    class="lyra-cal__today"
                    x-bind="today"
                ></button>
            </div>
        </template>
    @endif

    <template x-if="mode === 'months'">
        <div class="lyra-cal__mgrid">
            <template x-for="month in months()" :key="month.getMonth()">
                <button
                    type="button"
                    class="lyra-cal__mcell"
                    :class="monthClass(month)"
                    @click="pickMonth(month)"
                    x-text="monthName(month)"
                ></button>
            </template>
        </div>
    </template>

    <template x-if="mode === 'years'">
        <div class="lyra-cal__mgrid">
            <template x-for="year in years()" :key="year">
                <button
                    type="button"
                    class="lyra-cal__mcell"
                    :class="yearClass(year)"
                    @click="pickYear(year)"
                    x-text="year"
                ></button>
            </template>
        </div>
    </template>
</div>
