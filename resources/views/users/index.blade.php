@extends('layouts.app')

@section('title', 'Users - YEGNA TRADING PLC')

@section('content')
<x-page-header
    title="Users"
    :breadcrumbs="[
        ['label' => 'Home', 'href' => route('dashboard')],
        ['label' => 'Settings'],
        ['label' => 'Users'],
    ]"
    action-label="Add User"
    :action-href="route('users.create')"
/>

<x-search-filter
    search="{{ request('search') }}"
    search-placeholder="Search users..."
    :filters="[
        [
            'label' => 'Role',
            'name' => 'role_id',
            'options' => $roles->map(fn ($role) => ['label' => $role->name, 'value' => (string) $role->id])->prepend(['label' => 'All', 'value' => ''])->values()->all(),
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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="{{ $loop->index % 2 === 1 ? 'bg-gray-50/50' : 'bg-white' }} hover:bg-blue-50/30 transition-colors border-b border-gray-100 last:border-0">
                        <td class="px-4 py-3.5 whitespace-nowrap">{{ $users->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap font-medium text-gray-800">{{ $user->name }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-gray-500">{{ $user->email }}</td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            @if($user->role)
                                <x-status-badge :label="$user->role->name" variant="info" />
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            {{-- Show whether the user is Active or Deactivated. --}}
                            @if($user->is_active)
                                <x-status-badge label="Active" variant="success" />
                            @else
                                <x-status-badge label="Deactivated" variant="danger" />
                            @endif
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end gap-1">
                                <x-table-action :href="route('users.edit', $user)" icon="pencil" variant="primary" tooltip="Edit" />
                                {{-- Toggle active/deactivate without entering the edit page. --}}
                                @if($user->is_active)
                                    <x-table-action :href="route('users.toggleActive', $user)" icon="ban" variant="danger" method="POST" confirm confirm-message="Deactivate this user? They will no longer be able to log in." tooltip="Deactivate" />
                                @else
                                    <x-table-action :href="route('users.toggleActive', $user)" icon="check" variant="success" method="POST" tooltip="Activate" />
                                @endif
                                <x-table-action :href="route('users.destroy', $user)" icon="trash" variant="danger" method="DELETE" confirm confirm-message="Delete this user?" tooltip="Delete" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" message="No users found." description="Create your first user to get started." icon="search">
                        <a href="{{ route('users.create') }}" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Add User
                        </a>
                    </x-table-empty>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50/50">
        <p class="text-xs text-gray-500">
            Showing <span class="font-medium text-gray-700">{{ $users->firstItem() ?? 0 }}</span>
            to <span class="font-medium text-gray-700">{{ $users->lastItem() ?? 0 }}</span>
            of <span class="font-medium text-gray-700">{{ $users->total() }}</span> users
        </p>
    </div>
</div>

<x-pagination :paginator="$users" label="users" />
@endsection
