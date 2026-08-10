@props([
    'value' => null,
    'exceptions' => [],
    'defaultRange' => null,
    'weekStartsOn' => 1,
    'showExceptions' => true,
    'labels' => [],
    'name' => null,
])

{{--
    React owns the structure and class strings; lyraWeeklyScheduleEditor owns interaction.
    Range rows are deliberately rendered through x-for because users can add and remove them at
    runtime. As with the combobox's options, no range controls exist without Alpine.

    React's formatDate and formatRanges labels are functions and cannot cross a Blade prop. The
    server mirrors their binding-owned en-US defaults so exception text is correct before boot.

    The exception picker's min follows the server's date at render time. Time-zone-sensitive
    applications must validate submitted exception dates server-side because they arrive through
    a JSON hidden input.

    Each nested modelable has an alias scope. The copy trigger uses wrapTrigger=false because the
    React trigger is itself an interactive button carrying x-bind="trigger"; wrapping it would add
    a second interactive element and change the normative markup.

    When name is present, POST contains name[value] and name[exceptions], each as JSON for the app
    to json_decode. Two JSON hiddens avoid duplicating dynamic range names between SSR and x-for.
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
        'weekdays' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        'unavailable' => 'Unavailable',
        'copyToOtherDays' => 'Copy {day} to other days',
        'copySchedule' => 'Copy schedule',
        'copyFrom' => 'Copy {day} to…',
        'apply' => 'Apply',
        'addInterval' => '+ Add interval',
        'removeInterval' => 'Remove interval',
        'startTime' => '{day} — start',
        'endTime' => '{day} — end',
        'invalidRange' => 'End time must be after start time.',
        'exceptions' => 'Exceptions',
        'unavailableAllDay' => 'Unavailable all day',
        'removeException' => 'Remove exception',
        'addException' => 'Add exception…',
    ];
    $resolvedLabels = $defaultLabels;

    if (is_array($labels)) {
        foreach ($defaultLabels as $labelName => $defaultLabel) {
            if ($labelName === 'weekdays') {
                continue;
            }

            if (array_key_exists($labelName, $labels) && is_string($labels[$labelName])) {
                $resolvedLabels[$labelName] = $labels[$labelName];
            }
        }

        if (
            array_key_exists('weekdays', $labels)
            && is_array($labels['weekdays'])
            && count($labels['weekdays']) === 7
            && collect($labels['weekdays'])->every(static fn (mixed $day): bool => is_string($day))
        ) {
            $resolvedLabels['weekdays'] = array_values($labels['weekdays']);
        }
    }

    $normalizeTime = static function (mixed $time): ?string {
        if (! is_string($time) || preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            return null;
        }

        return $time;
    };
    $normalizeRange = static function (mixed $range) use ($normalizeTime): ?array {
        if (! is_array($range)) {
            return null;
        }

        $start = $normalizeTime($range['start'] ?? null);
        $end = $normalizeTime($range['end'] ?? null);

        return $start !== null && $end !== null
            ? ['start' => $start, 'end' => $end]
            : null;
    };
    $normalizeRanges = static function (mixed $ranges) use ($normalizeRange): array {
        if (! is_array($ranges)) {
            return [];
        }

        $normalized = [];

        foreach ($ranges as $range) {
            $resolved = $normalizeRange($range);

            if ($resolved !== null) {
                $normalized[] = $resolved;
            }
        }

        return $normalized;
    };
    $normalizeDate = static function (mixed $date): ?string {
        if (! is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsed !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsed->format('Y-m-d') === $date
                ? $date
                : null;
    };
    $resolvedSchedule = [0 => [], 1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => []];

    if (is_array($value)) {
        foreach (array_keys($resolvedSchedule) as $day) {
            if (array_key_exists($day, $value)) {
                $resolvedSchedule[$day] = $normalizeRanges($value[$day]);
            }
        }
    }

    $resolvedDefaultRange = $normalizeRange($defaultRange)
        ?? ['start' => '09:00', 'end' => '17:00'];
    $resolvedWeekStartsOn = is_int($weekStartsOn) && $weekStartsOn >= 0 && $weekStartsOn <= 6
        ? $weekStartsOn
        : 1;
    $resolvedShowExceptions = is_bool($showExceptions) ? $showExceptions : true;
    $resolvedExceptions = [];

    if (is_array($exceptions)) {
        foreach ($exceptions as $exception) {
            if (! is_array($exception)) {
                continue;
            }

            $date = $normalizeDate($exception['date'] ?? null);

            if ($date === null) {
                continue;
            }

            $resolvedExceptions[] = [
                'date' => $date,
                'ranges' => $normalizeRanges($exception['ranges'] ?? []),
            ];
        }
    }

    $interpolate = static fn (string $template, array $values = []): string => (string) preg_replace_callback(
        '/\{(\w+)\}/',
        static fn (array $match): string => (string) ($values[$match[1]] ?? ''),
        $template,
    );
    $formatDate = static fn (string $date): string => (new DateTimeImmutable($date.'T12:00:00'))
        ->format('M d, Y');
    $formatRanges = static fn (array $ranges): string => collect($ranges)
        ->map(static fn (array $range): string => $range['start'].'–'.$range['end'])
        ->implode(', ');
    $options = [
        'value' => (object) $resolvedSchedule,
        'exceptions' => $resolvedExceptions,
        'defaultRange' => $resolvedDefaultRange,
        'weekStartsOn' => $resolvedWeekStartsOn,
        'showExceptions' => $resolvedShowExceptions,
        'labels' => $resolvedLabels,
    ];
    $optionsLiteral = json_encode($options, $jsonFlags);
    $scheduleLiteral = json_encode((object) $resolvedSchedule, $jsonFlags);
    $exceptionsLiteral = json_encode($resolvedExceptions, $jsonFlags);
    $order = array_map(
        static fn (int $index): int => ($index + $resolvedWeekStartsOn) % 7,
        range(0, 6),
    );
    $today = now()->toDateString();
    $requestedModelable = $attributes->get('x-modelable');
    $resolvedModelable = is_string($requestedModelable)
        && in_array($requestedModelable, ['value', 'exceptions'], true)
            ? $requestedModelable
            : 'value';
    $resolvedName = is_string($name) && $name !== '' ? $name : null;
    $rootAttributes = $attributes
        ->except(['x-data', 'x-modelable'])
        ->class('lyra-sched');
@endphp

<div
    x-data="{{ 'lyraWeeklyScheduleEditor('.$optionsLiteral.')' }}"
    x-modelable="{{ $resolvedModelable }}"
    {{ $rootAttributes }}
>
    @foreach ($order as $day)
        @php
            $dayEnabled = $resolvedSchedule[$day] !== [];
            $dayLabel = $resolvedLabels['weekdays'][$day];
            $startLabel = $interpolate($resolvedLabels['startTime'], ['day' => $dayLabel]);
            $endLabel = $interpolate($resolvedLabels['endTime'], ['day' => $dayLabel]);
            $copyLabel = $interpolate($resolvedLabels['copyToOtherDays'], ['day' => $dayLabel]);
            $copyTitle = $interpolate($resolvedLabels['copyFrom'], ['day' => $dayLabel]);
        @endphp
        <div class="lyra-sched__row" x-data="{ day: {{ $day }} }">
            <div class="lyra-sched__daycell">
                <x-lyra::switch
                    :label="$dayLabel"
                    :checked="$dayEnabled"
                    x-bind:checked="enabled(day)"
                    @change="setEnabled(day, $event.currentTarget.checked)"
                />
            </div>

            <div
                class="lyra-sched__ranges"
                x-show="enabled(day)"
                @unless ($dayEnabled)
                    x-cloak
                @endunless
            >
                <template x-for="(range, index) in rangesFor(day)" :key="index">
                    <div>
                        <div class="lyra-sched__range">
                            <div x-data="{ get t() { return rangesFor(day)[index].start }, set t(v) { setRangeStart(day, index, v) } }">
                                <x-lyra::time-input
                                    x-model="t"
                                    :aria-label="$startLabel"
                                    x-bind:aria-label="label('startTime', { day: dayLabel(day) })"
                                />
                            </div>
                            <span class="lyra-sched__dash">–</span>
                            <div x-data="{ get t() { return rangesFor(day)[index].end }, set t(v) { setRangeEnd(day, index, v) } }">
                                <x-lyra::time-input
                                    x-model="t"
                                    :aria-label="$endLabel"
                                    x-bind:aria-label="label('endTime', { day: dayLabel(day) })"
                                    x-bind:aria-invalid="invalid(range) ? true : false"
                                    x-bind:class="{ 'lyra-input--error': invalid(range) }"
                                />
                            </div>
                            <template x-if="rangesFor(day).length > 1">
                                <button
                                    type="button"
                                    class="lyra-sched__ghostbtn"
                                    aria-label="{{ $resolvedLabels['removeInterval'] }}"
                                    :aria-label="label('removeInterval')"
                                    @click="removeRange(day, index)"
                                >
                                    <x-lyra::icon name="x" :size="15" />
                                </button>
                            </template>
                        </div>
                        <span
                            class="lyra-sched__error"
                            x-show="invalid(range)"
                            x-text="label('invalidRange')"
                        >{{ $resolvedLabels['invalidRange'] }}</span>
                    </div>
                </template>
                <button
                    type="button"
                    class="lyra-sched__addrange"
                    @click="addRange(day)"
                    x-text="label('addInterval')"
                >{{ $resolvedLabels['addInterval'] }}</button>
            </div>

            <span
                class="lyra-sched__off"
                x-show="!enabled(day)"
                x-text="label('unavailable')"
                @if ($dayEnabled)
                    x-cloak
                @endif
            >{{ $resolvedLabels['unavailable'] }}</span>

            <div class="lyra-sched__actions">
                <div
                    x-show="enabled(day)"
                    @unless ($dayEnabled)
                        x-cloak
                    @endunless
                    x-data="{ get copyOpen() { return copyOpenFor(day) }, set copyOpen(v) { setCopyOpen(day, v) } }"
                >
                    <x-lyra::popover
                        :aria-label="$resolvedLabels['copySchedule']"
                        :wrap-trigger="false"
                        x-model="copyOpen"
                    >
                        <x-slot:trigger>
                            <button
                                type="button"
                                class="lyra-sched__ghostbtn"
                                aria-label="{{ $copyLabel }}"
                                :aria-label="label('copyToOtherDays', { day: dayLabel(day) })"
                                title="{{ $copyTitle }}"
                                :title="label('copyFrom', { day: dayLabel(day) })"
                                x-bind="trigger"
                            >
                                <x-lyra::icon name="copy" :size="15" />
                            </button>
                        </x-slot:trigger>

                        <div class="lyra-sched__copy">
                            <span
                                class="lyra-sched__copy-title"
                                x-text="label('copyFrom', { day: dayLabel(day) })"
                            >{{ $copyTitle }}</span>
                            @foreach (range(0, 6) as $target)
                                @continue($target === $day)
                                <label class="lyra-check-row">
                                    <input
                                        type="checkbox"
                                        class="lyra-checkbox"
                                        :checked="picked(day).includes({{ $target }})"
                                        @change="togglePicked(day, {{ $target }})"
                                    >
                                    <span x-text="dayLabel({{ $target }})">{{ $resolvedLabels['weekdays'][$target] }}</span>
                                </label>
                            @endforeach
                            <x-lyra::button
                                size="sm"
                                disabled
                                x-bind:disabled="picked(day).length === 0"
                                @click="applyCopy(day)"
                                x-text="label('apply')"
                            >{{ $resolvedLabels['apply'] }}</x-lyra::button>
                        </div>
                    </x-lyra::popover>
                </div>
            </div>
        </div>
    @endforeach

    @if ($resolvedShowExceptions)
        <div class="lyra-sched__exc">
            <span class="lyra-label" x-text="label('exceptions')">{{ $resolvedLabels['exceptions'] }}</span>

            {{--
                Initial rows remain useful without JS. Alpine hides these fallbacks and the x-for
                takes ownership so additions, removals, and external model reordering stay exact.
            --}}
            @foreach ($resolvedExceptions as $exception)
                <div
                    class="lyra-sched__exc-row"
                    x-show="false"
                >
                    <span class="lyra-sched__exc-date">{{ $formatDate($exception['date']) }}</span>
                    <span>{{ $exception['ranges'] !== [] ? $formatRanges($exception['ranges']) : $resolvedLabels['unavailableAllDay'] }}</span>
                    <button
                        type="button"
                        class="lyra-sched__ghostbtn"
                        aria-label="{{ $resolvedLabels['removeException'] }}"
                    >
                        <x-lyra::icon name="x" :size="15" />
                    </button>
                </div>
            @endforeach

            <template
                x-for="(exception, index) in exceptions"
                :key="exception.date"
            >
                <div class="lyra-sched__exc-row">
                    <span class="lyra-sched__exc-date" x-text="formatDate(exception.date)"></span>
                    <span x-text="exceptionText(exception)"></span>
                    <button
                        type="button"
                        class="lyra-sched__ghostbtn"
                        :aria-label="label('removeException')"
                        @click="removeException(index)"
                    >
                        <x-lyra::icon name="x" :size="15" />
                    </button>
                </div>
            </template>

            <div x-data="{ get exceptionDate() { return null }, set exceptionDate(v) { addException(v) } }">
                <x-lyra::date-picker
                    :placeholder="$resolvedLabels['addException']"
                    :min="$today"
                    x-model="exceptionDate"
                />
            </div>
        </div>
    @endif

    @if ($resolvedName !== null)
        <input
            type="hidden"
            name="{{ $resolvedName }}[value]"
            value="{{ $scheduleLiteral }}"
            :value="JSON.stringify(value)"
        >
        <input
            type="hidden"
            name="{{ $resolvedName }}[exceptions]"
            value="{{ $exceptionsLiteral }}"
            :value="JSON.stringify(exceptions)"
        >
    @endif
</div>
