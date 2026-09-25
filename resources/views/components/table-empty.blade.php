@props([
    'colspan' => 1,
    'icon' => 'inbox',
    'message' => 'No records found.',
    'description' => null,
])

<tr>
    <td colspan="{{ $colspan }}" class="px-4 py-16 text-center">
        <div class="flex flex-col items-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                @if($icon === 'inbox')
                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                @elseif($icon === 'search')
                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                @endif
            </div>
            <p class="text-sm font-medium text-gray-500">{{ $message }}</p>
            @if($description)
                <p class="text-xs text-gray-400 mt-1">{{ $description }}</p>
            @endif
            {{ $slot }}
        </div>
    </td>
</tr>
