<div class="px-4 sm:px-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Users</h1>
            <p class="page-subtitle">Manage system users and their roles</p>
        </div>
        @can('users.create')
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add User
        </button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-3">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search users..." class="input flex-1 min-w-48">
        <select wire:model.live="roleFilter" class="input w-auto">
            <option value="">All Roles</option>
            @foreach($this->roles as $role)
                <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($this->users as $user)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <img src="{{ $user->avatar_url }}" alt="" class="w-8 h-8 rounded-full">
                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone ?? '—' }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            <span class="badge bg-indigo-100 text-indigo-700">{{ $role->display_name ?? $role->name }}</span>
                        @endforeach
                    </td>
                    <td>
                        <button wire:click="toggleActive({{ $user->id }})"
                            class="badge {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }} cursor-pointer">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="text-gray-400 text-xs">{{ $user->last_login_at?->format('d M Y H:i') ?? 'Never' }}</td>
                    <td>
                        @can('users.edit')
                        <button wire:click="openEdit({{ $user->id }})" class="btn-icon">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-gray-400">No users found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $this->users->links() }}</div>

    @if($showModal)
    <div class="modal-backdrop">
        <div class="modal modal-md mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">{{ $editMode ? 'Edit User' : 'Add User' }}</h3>
                <button wire:click="$set('showModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div class="form-group">
                    <label class="label">Full Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror">
                    @error('name') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Email <span class="text-red-500">*</span></label>
                    <input wire:model="email" type="email" class="input @error('email') input-error @enderror">
                    @error('email') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Password {{ $editMode ? '(leave blank to keep)' : '' }} <span class="text-red-500">{{ $editMode ? '' : '*' }}</span></label>
                    <input wire:model="password" type="password" class="input @error('password') input-error @enderror" placeholder="Min. 8 characters">
                    @error('password') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Phone</label>
                    <input wire:model="phone" type="tel" class="input">
                </div>
                <div class="form-group">
                    <label class="label">Role</label>
                    <select wire:model="role_id" class="input">
                        <option value="">No role</option>
                        @foreach($this->roles as $role)
                            <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <input wire:model="is_active" type="checkbox" id="is_active" class="rounded border-gray-300 text-indigo-600">
                    <label for="is_active" class="text-sm text-gray-700">Active</label>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Create' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
