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
