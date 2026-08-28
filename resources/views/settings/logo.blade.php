{{-- ============================================================================
     COMPANY SETTINGS — Logo
     Admin-only page (gated by the 'settings' permission).
     Lets the admin upload / change the company logo shown in the sidebar
     and on the login page.
     ============================================================================ --}}
@extends('layouts.app')

@section('title', 'Company Settings - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Company Settings"
    :breadcrumbs="[
        ['label' => 'Home'],
        ['label' => 'Company Settings'],
    ]"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-800">Company Logo</h3>
        <p class="text-xs text-gray-500 mt-0.5">
            This logo appears in the sidebar header and on the login page background.
        </p>
    </div>

    <div class="p-6">
        {{-- Current logo preview --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Current Logo</label>
            @if($logoPath)
                <div class="w-48 h-24 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center p-2">
                    <img src="{{ Storage::disk('public')->url($logoPath) }}"
                         alt="Current company logo"
                         class="max-h-full max-w-full object-contain">
                </div>
            @else
                <div class="w-48 h-24 bg-gray-50 border border-dashed border-gray-300 rounded-lg flex items-center justify-center text-xs text-gray-400">
                    No logo uploaded yet
                </div>
            @endif
        </div>

        {{-- Upload form --}}
        <form method="POST" action="{{ route('settings.logo.update') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Choose a new logo (PNG, JPG, SVG or WebP)</label>
                <input type="file" id="logo" name="logo" accept="image/*" required
                       class="block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('logo') border border-red-500 rounded-lg @enderror">
                @error('logo')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500 mt-2">Maximum size: 2MB.</p>
            </div>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
                Save Logo
            </button>
        </form>
    </div>
</div>
@endsection
