@props(['columns' => []])

<thead class="bg-gray-50 border-b border-gray-200">
    <tr>
        @foreach($columns as $column)
            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider {{ $column['class'] ?? '' }}">
                {{ $column['label'] }}
            </th>
        @endforeach
    </tr>
</thead>
