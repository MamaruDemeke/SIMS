@props([
    'columns' => [],
    'actions' => null,
    'index' => 0,
])

<tr class="{{ $index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
    @foreach($columns as $column)
        <td class="px-4 py-3.5 whitespace-nowrap {{ $column['class'] ?? '' }}">
            {{ $column['slot'] ?? $column['value'] ?? '' }}
        </td>
    @endforeach

    @if($actions)
        <td class="px-4 py-3.5 whitespace-nowrap text-right">
            <div class="flex items-center justify-end gap-1">
                {{ $actions }}
            </div>
        </td>
    @endif
</tr>
