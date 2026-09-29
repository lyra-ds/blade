{{--
    The calendar root dispatches four bubbling events. event.detail is the value itself, not a wrapper object:
    - lyra:view-change: the new view string, 'day', 'week' or 'month'.
    - lyra:change: the new local anchor date as a 'YYYY-MM-DD' string (toolbar previous/today/next or a month-day click).
    - lyra:event-open: the clicked event object with every field you passed (id, kind, start, end, title and any extra keys),
      plus startDate and endDate as local Date objects; kind defaults to 'session'.
    - lyra:slot-create: a local Date for the empty time-grid slot that was clicked, snapped down to slot-step minutes.
    Keep your own state on a wrapper x-data and listen there; the component owns its x-data.
--}}
<div
    x-data="{ view: 'week', date: '2026-08-12', openedEventId: null, slotAt: null }"
    @lyra:view-change="view = $event.detail"
    @lyra:change="date = $event.detail"
    @lyra:event-open="openedEventId = $event.detail.id"
    @lyra:slot-create="slotAt = $event.detail"
>
    <lyra:calendar-view
        date="2026-08-12"
        view="week"
        locale="pt-BR"
        :events="[
            ['id' => 'consultation', 'kind' => 'session', 'start' => '2026-08-12T10:00:00', 'end' => '2026-08-12T11:00:00', 'title' => 'Consultation'],
        ]"
        :availability="[3 => [['start' => '08:00', 'end' => '17:00']]]"
        :labels="[
            'previous' => 'Período anterior', 'today' => 'Hoje', 'next' => 'Próximo período',
            'view' => 'Visualização do calendário', 'day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês',
            'hours' => 'Horas do calendário',
        ]"
    >
        <x-slot:toolbar-actions><button type="button">New event</button></x-slot:toolbar-actions>
        <x-slot:popover>
            <strong x-text="popover?.event.title"></strong>
            <button type="button" x-on:click="closePopover()">Close</button>
        </x-slot:popover>
    </lyra:calendar-view>
    <p x-text="`View: ${view} · Date: ${date}`"></p>
    <p x-show="openedEventId" x-text="`Opened: ${openedEventId}`"></p>
    <p x-show="slotAt" x-text="`New slot: ${slotAt?.toLocaleString('pt-BR')}`"></p>
</div>
