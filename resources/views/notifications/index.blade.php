@extends('layouts.app')

@section('title', 'Notifications - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Notifications"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Notifications'],
    ]"
/>

@if($notifications->count() > 0)
    <div class="mb-4 flex justify-end">
        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Mark All as Read
            </button>
        </form>
    </div>
@endif

<div class="space-y-3">
    @forelse($notifications as $notification)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 {{ $notification->is_read ? 'opacity-60' : '' }}">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 mt-0.5">
                    @if($notification->type === 'out_of_stock')
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    @elseif($notification->type === 'low_stock')
                        <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    @else
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">{{ $notification->title }}</h3>
                            <p class="text-sm text-gray-600 mt-0.5">{{ $notification->message }}</p>
                            @if($notification->product)
                                <p class="text-xs text-gray-400 mt-1">
                                    Product: {{ $notification->product->product_code }} | Stock: {{ $notification->product->name }}
                                </p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-xs text-gray-400 whitespace-nowrap">{{ $notification->created_at->diffForHumans() }}</span>
                            @if(!$notification->is_read)
                                <form method="POST" action="{{ route('notifications.markRead', $notification) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 whitespace-nowrap">
                                        Mark read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
            </svg>
            <h3 class="text-sm font-medium text-gray-700">No notifications</h3>
            <p class="text-xs text-gray-500 mt-1">You're all caught up!</p>
        </div>
    @endforelse
</div>

@if($notifications->hasPages())
    <x-pagination :paginator="$notifications" label="notifications" />
@endif
@endsection
