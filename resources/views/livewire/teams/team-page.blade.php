<div class="px-4 sm:px-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Teams</h1>
            <p class="page-subtitle">Manage sales teams and their members</p>
        </div>
        @can('teams.create')
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Team
        </button>
        @endcan
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($this->teams as $team)
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-gray-900 truncate">{{ $team->name }}</h3>
                        @if($team->phoneNumber)
                            <span class="badge bg-green-100 text-green-700 text-xs">{{ $team->phoneNumber->name }}</span>
                        @endif
                        @if(!$team->is_active)
                            <span class="badge bg-gray-100 text-gray-500 text-xs">Inactive</span>
                        @endif
                    </div>
                    @if($team->description)
                        <p class="text-sm text-gray-500 mt-1 line-clamp-2">{{ $team->description }}</p>
                    @endif
                </div>
                <div class="flex gap-1 flex-shrink-0 ml-2">
                    @can('teams.edit')
                    <button wire:click="openEdit({{ $team->id }})" class="btn-icon" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    @endcan
                    @can('teams.delete')
                    <button wire:click="deleteTeam({{ $team->id }})" wire:confirm="Are you sure you want to delete this team?" class="btn-icon text-red-400 hover:text-red-600" title="Delete">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @endcan
                </div>
            </div>

            {{-- Leader --}}
            <div class="mt-4 pt-3 border-t border-gray-100">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Team Leader</p>
                @if($team->leader)
                    <div class="flex items-center gap-2">
                        <img src="{{ $team->leader->avatar_url }}" alt="" class="w-7 h-7 rounded-full">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $team->leader->name }}</p>
                            <p class="text-xs text-gray-400">{{ $team->leader->getRoleNames()->first() }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">No leader assigned</p>
                @endif
            </div>

            {{-- Members --}}
            <div class="mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Members</p>
                    <span class="text-xs text-gray-500 font-medium">{{ $team->members->count() }}</span>
                </div>

                @if($team->members->count() > 0)
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($team->members->take(8) as $member)
                            <div class="flex items-center gap-1.5 bg-gray-50 rounded-full pl-0.5 pr-2 py-0.5" title="{{ $member->name }}">
                                <img src="{{ $member->avatar_url }}" alt="" class="w-5 h-5 rounded-full">
                                <span class="text-xs text-gray-700">{{ Str::before($member->name, ' ') }}</span>
                            </div>
                        @endforeach
                        @if($team->members->count() > 8)
                            <span class="text-xs text-gray-400 py-1">+{{ $team->members->count() - 8 }} more</span>
                        @endif
                    </div>
                @else
                    <p class="text-xs text-gray-400 italic">No members yet</p>
                @endif

                @can('teams.edit')
                <button wire:click="openMembers({{ $team->id }})" class="mt-3 w-full btn-secondary btn-sm text-center">
                    Manage Members
                </button>
                @endcan
            </div>
        </div>
        @empty
            <div class="col-span-full text-center py-16">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No teams yet</p>
                <p class="text-xs text-gray-400 mt-1">Create a team to organize your sales force</p>
            </div>
        @endforelse
    </div>

    {{-- Create/Edit Team Modal --}}
    @if($showModal)
    <div class="modal-backdrop" wire:key="team-modal-{{ $editingTeamId ?? 'create' }}-{{ now()->timestamp }}">
        <div class="modal modal-sm mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">{{ $editingTeamId ? 'Edit Team' : 'Create Team' }}</h3>
                <button wire:click="$set('showModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div class="form-group">
                    <label class="label">Team Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="input @error('name') input-error @enderror" placeholder="e.g. Sales Team A">
                    @error('name') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Description</label>
                    <textarea wire:model="description" rows="2" class="input" placeholder="Optional description..."></textarea>
                </div>
                <div class="form-group">
                    <label class="label">Team Leader</label>
                    <select wire:model="leaderId" class="input">
                        <option value="">No leader</option>
                        @foreach($this->agents as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->getRoleNames()->first() }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="label">WhatsApp Number</label>
                    <select wire:model="phoneNumberId" class="input">
                        <option value="">No number linked</option>
                        @foreach($this->phoneNumbers as $pn)
                            <option value="{{ $pn->id }}">{{ $pn->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Conversations from this number will be visible to this team</p>
                </div>
                <div class="form-group">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input wire:model="isActive" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Active</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Members Management Modal --}}
    @if($showMembersModal)
    <div class="modal-backdrop" wire:key="members-modal-{{ $managingTeamId }}-{{ now()->timestamp }}">
        <div class="modal modal-md mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Manage Members</h3>
                <button wire:click="$set('showMembersModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-6 space-y-2 max-h-96 overflow-y-auto">
                @foreach($this->agents as $agent)
                <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors cursor-pointer">
                    <input
                        type="checkbox"
                        wire:click="toggleMember({{ $agent->id }})"
                        {{ in_array($agent->id, $selectedMembers) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <img src="{{ $agent->avatar_url }}" alt="" class="w-8 h-8 rounded-full">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $agent->name }}</p>
                        <p class="text-xs text-gray-400">{{ $agent->getRoleNames()->first() }} &middot; {{ $agent->email }}</p>
                    </div>
                </label>
                @endforeach
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                <p class="text-sm text-gray-500">{{ count($selectedMembers) }} members selected</p>
                <div class="flex gap-3">
                    <button wire:click="$set('showMembersModal', false)" class="btn-secondary">Cancel</button>
                    <button wire:click="saveMembers" class="btn-primary">Save Members</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
