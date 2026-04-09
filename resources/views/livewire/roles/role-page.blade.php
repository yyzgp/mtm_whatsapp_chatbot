<div class="px-4 sm:px-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Roles & Permissions</h1>
            <p class="page-subtitle">Define what each role can access</p>
        </div>
        @can('roles.create')
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Role
        </button>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($this->roles as $role)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="font-semibold text-gray-900">{{ $role->display_name ?? $role->name }}</h3>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $role->name }}</p>
                    @if($role->description)
                        <p class="text-sm text-gray-600 mt-1">{{ $role->description }}</p>
                    @endif
                </div>
                <div class="flex gap-1">
                    @can('roles.edit')
                    <button wire:click="openEdit({{ $role->id }})" class="btn-icon" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    @endcan
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    <span class="font-medium text-gray-700">{{ $role->permissions_count }}</span> permissions
                    &middot;
                    <span class="font-medium text-gray-700">{{ $role->users_count }}</span> users
                </div>
                @can('roles.edit')
                <button wire:click="openPermissions({{ $role->id }})" class="btn-secondary btn-sm">
                    Manage Permissions
                </button>
                @endcan
            </div>

            @if($role->permissions->count() > 0)
            <div class="mt-3 flex flex-wrap gap-1">
                @foreach($role->permissions->take(6) as $perm)
                    <span class="badge bg-gray-100 text-gray-600 text-xs">{{ $perm->display_name ?? $perm->name }}</span>
                @endforeach
                @if($role->permissions->count() > 6)
                    <span class="badge bg-gray-100 text-gray-500 text-xs">+{{ $role->permissions->count() - 6 }} more</span>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Role Create/Edit Modal -->
    @if($showModal)
    <div class="modal-backdrop">
        <div class="modal modal-sm mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">{{ $editingRoleId ? 'Edit Role' : 'Create Role' }}</h3>
                <button wire:click="$set('showModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div class="form-group">
                    <label class="label">Role Name (slug) <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror" placeholder="sales_agent">
                    <p class="text-xs text-gray-400 mt-1">Lowercase letters, numbers, and underscores only</p>
                    @error('name') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Display Name <span class="text-red-500">*</span></label>
                    <input wire:model="display_name" type="text" class="input @error('display_name') input-error @enderror" placeholder="Sales Agent">
                    @error('display_name') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Description</label>
                    <textarea wire:model="description" rows="2" class="input" placeholder="What does this role do?"></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Permission Matrix Modal -->
    @if($showPermissionMatrix)
    <div class="modal-backdrop">
        <div class="modal modal-xl mx-4 max-h-screen">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="font-semibold text-gray-900">Manage Permissions</h3>
                <button wire:click="$set('showPermissionMatrix', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-6 overflow-y-auto">
                @foreach($this->permissionsByModule as $module => $permissions)
                <div class="border border-gray-100 rounded-xl overflow-hidden">
                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-100">
                        <h4 class="font-semibold text-gray-700 uppercase text-sm tracking-wide">{{ $module }}</h4>
                        <button wire:click="toggleModule('{{ $module }}')"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                            Toggle All
                        </button>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 p-4">
                        @foreach($permissions as $perm)
                        <label class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-gray-50 transition-colors">
                            <input
                                type="checkbox"
                                wire:click="togglePermission('{{ $perm['name'] }}')"
                                {{ in_array($perm['name'], $selectedPermissions) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span class="text-sm text-gray-700">{{ $perm['display_name'] ?? $perm['name'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center sticky bottom-0 bg-white">
                <p class="text-sm text-gray-500">{{ count($selectedPermissions) }} permissions selected</p>
                <div class="flex gap-3">
                    <button wire:click="$set('showPermissionMatrix', false)" class="btn-secondary">Cancel</button>
                    <button wire:click="savePermissions" class="btn-primary">Save Permissions</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
