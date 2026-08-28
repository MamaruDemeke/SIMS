@extends('layouts.app')

@section('title', 'Permissions - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Role Permissions"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'System'],
        ['label' => 'Permissions'],
    ]"
/>

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('permissions.save') }}" method="POST">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-6 py-3 font-semibold text-gray-600">Module / Page</th>
                        @foreach($roles as $role)
                            <th class="text-center px-4 py-3 font-semibold text-gray-600">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $key => $label)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-800">{{ $label }}</td>
                            @foreach($roles as $role)
                                <td class="text-center px-4 py-3">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="permissions[{{ $role->id }}][]"
                                               value="{{ $key }}"
                                               class="sr-only peer"
                                               {{ $role->permissions->contains('module', $key) ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-between">
        <p class="text-sm text-gray-500">Toggle the permissions you want, then click Save to apply permanently.</p>
        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Save Permissions
        </button>
    </div>
</form>
@endsection
