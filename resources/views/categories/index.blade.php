@extends('layouts.app')

@section('title', 'Categories - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Categories"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Inventory'],
        ['label' => 'Categories'],
    ]"
    action-label="Add Category"
    :action-href="route('categories.create')"
/>

<x-search-filter
    search="{{ request('search') }}"
    search-placeholder="Search categories..."
    :filters="[
        [
            'label' => 'Status',
            'name' => 'status',
            'options' => [
                ['label' => 'All', 'value' => ''],
                ['label' => 'Active', 'value' => '1'],
                ['label' => 'Inactive', 'value' => '0'],
            ],
        ],
    ]"
/>

<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">#</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $categories->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $category->name }}</td>
                        <td class="px-4 py-3.5 text-gray-500">{{ $category->description ?? '—' }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($category->status)
                                <x-status-badge label="Active" variant="success" />
                            @else
                                <x-status-badge label="Inactive" variant="danger" />
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-table-action :href="route('categories.edit', $category)" icon="pencil" variant="primary" tooltip="Edit" />
                                <x-table-action :href="route('categories.destroy', $category)" icon="trash" variant="danger" method="DELETE" confirm confirm-message="Delete this category?" tooltip="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" message="No categories found." description="Create your first category to get started." icon="search">
                        <a href="{{ route('categories.create') }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add Category
                        </a>
                    </x-table-empty>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $categories->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $categories->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $categories->total() }}</span> categories
        </p>
    </div>
</div>

<x-pagination :paginator="$categories" label="categories" />
@endsection
