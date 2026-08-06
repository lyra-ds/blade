@props([
    'columns',
    'rows',
    'hover' => false,
])

<div {{ $attributes->class('lyra-table-wrap') }}>
    <table @class([
        'lyra-table',
        'lyra-table--hover' => $hover,
    ])>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    @if (isset($column['align']))
                        <th style="text-align: {{ $column['align'] }}">{{ $column['label'] }}</th>
                    @else
                        <th>{{ $column['label'] }}</th>
                    @endif
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($columns as $column)
                        @if ($loop->first && isset($column['align']))
                            <td class="lyra-table__primary" style="text-align: {{ $column['align'] }}">{{ $row[$column['key']] ?? '' }}</td>
                        @elseif ($loop->first)
                            <td class="lyra-table__primary">{{ $row[$column['key']] ?? '' }}</td>
                        @elseif (isset($column['align']))
                            <td style="text-align: {{ $column['align'] }}">{{ $row[$column['key']] ?? '' }}</td>
                        @else
                            <td>{{ $row[$column['key']] ?? '' }}</td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
