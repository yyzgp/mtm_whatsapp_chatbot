<div class="px-4 sm:px-6 space-y-4">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="page-title">Customers</h1>
            <p class="page-subtitle">Manage your customer pipeline</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- View toggle -->
            <div class="flex rounded-lg border border-gray-300 overflow-hidden">
                <button wire:click="$set('view', 'table')"
                    class="px-3 py-1.5 text-sm {{ $view === 'table' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Table
                </button>
                <button wire:click="$set('view', 'kanban')"
                    class="px-3 py-1.5 text-sm {{ $view === 'kanban' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">
                    Kanban
                </button>
            </div>
            @can('customers.create')
            <button wire:click="openCreate" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Customer
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-3">
        <div class="flex-1 min-w-48">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search customers..."
                class="input w-full">
        </div>
        <select wire:model.live="statusFilter" class="input w-auto">
            <option value="">All Statuses</option>
            @foreach($this->statusOptions as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="priorityFilter" class="input w-auto">
            <option value="">All Priorities</option>
            @foreach($this->priorityOptions as $p)
                <option value="{{ $p->value }}">{{ $p->label() }}</option>
            @endforeach
        </select>
        @can('customers.view_all')
        <select wire:model.live="agentFilter" class="input w-auto">
            <option value="">All Agents</option>
            @foreach($this->agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
            @endforeach
        </select>
        @endcan
    </div>

    @if($view === 'table')
        <!-- Table View -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>
                            <button wire:click="sort('first_name')" class="flex items-center gap-1 hover:text-gray-700">
                                Customer
                                @if($sortBy === 'first_name')
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Source</th>
                        <th>Assigned To</th>
                        <th>
                            <button wire:click="sort('created_at')" class="flex items-center gap-1 hover:text-gray-700">
                                Created
                                @if($sortBy === 'created_at')
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($this->customers as $customer)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $customer->avatar_url }}" alt="" class="w-8 h-8 rounded-full">
                                    <div>
                                        <a href="{{ route('customers.show', $customer) }}"
                                            class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ $customer->full_name }}
                                        </a>
                                        @if($customer->company_name)
                                            <p class="text-xs text-gray-400">{{ $customer->company_name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="text-sm">{{ $customer->phone }}</p>
                                @if($customer->email)
                                    <p class="text-xs text-gray-400">{{ $customer->email }}</p>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $customer->status->badgeClass() }}">{{ $customer->status->label() }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $customer->priority->badgeClass() }}">{{ $customer->priority->label() }}</span>
                            </td>
                            <td>{{ $customer->source?->name ?? '—' }}</td>
                            <td>{{ $customer->assignedAgent?->name ?? '—' }}</td>
                            <td class="text-gray-400 text-xs">{{ $customer->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn-icon" title="View">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    @can('customers.edit')
                                    <button wire:click="openEdit({{ $customer->id }})" class="btn-icon" title="Edit">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    @endcan
                                    @can('customers.delete')
                                    <button wire:click="confirmDelete({{ $customer->id }})" class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50" title="Delete">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center">
                                <div class="empty-state">
                                    <svg class="empty-state-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <p class="empty-state-title">No customers found</p>
                                    <p class="empty-state-text">Add your first customer to get started.</p>
                                    @can('customers.create')
                                    <button wire:click="openCreate" class="btn-primary">Add Customer</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $this->customers->links() }}</div>

    @else
        <!-- Kanban View -->
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($this->kanbanColumns as $column)
            <div class="flex-shrink-0 w-72 bg-gray-100 rounded-xl p-3">
                <div class="flex items-center justify-between mb-3">
                    <span class="badge {{ $column['color'] }}">{{ $column['label'] }}</span>
                    <span class="text-xs text-gray-500 font-medium">{{ $column['customers']->count() }}</span>
                </div>
                <div class="space-y-2 min-h-8">
                    @foreach($column['customers'] as $customer)
                    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between">
                            <div>
                                <a href="{{ route('customers.show', $customer) }}"
                                    class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                    {{ $customer->full_name }}
                                </a>
                                @if($customer->company_name)
                                    <p class="text-xs text-gray-400">{{ $customer->company_name }}</p>
                                @endif
                            </div>
                            <span class="badge {{ $customer->priority->badgeClass() }} text-xs">{{ $customer->priority->label() }}</span>
                        </div>
                        @if($customer->expected_value)
                            <p class="text-xs text-green-600 font-medium mt-1">${{ number_format($customer->expected_value) }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-1">{{ $customer->phone }}</p>
                        @if($customer->assignedAgent)
                            <div class="flex items-center gap-1 mt-2">
                                <img src="{{ $customer->assignedAgent->avatar_url }}" class="w-5 h-5 rounded-full" alt="">
                                <span class="text-xs text-gray-500">{{ $customer->assignedAgent->name }}</span>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    @endif

    <!-- Create/Edit Modal -->
    @if($showModal)
    <div class="modal-backdrop" wire:keydown.escape="$set('showModal', false)">
        <div class="modal modal-lg mx-4" @click.outside="$wire.set('showModal', false)">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $editMode ? 'Edit Customer' : 'Add Customer' }}
                </h3>
                <button wire:click="$set('showModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="save" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="label">First Name <span class="text-red-500">*</span></label>
                        <input wire:model="first_name" type="text" class="input @error('first_name') input-error @enderror" placeholder="John">
                        @error('first_name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">Last Name <span class="text-red-500">*</span></label>
                        <input wire:model="last_name" type="text" class="input @error('last_name') input-error @enderror" placeholder="Doe">
                        @error('last_name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">Phone <span class="text-red-500">*</span></label>
                        <input wire:model="phone" type="tel" class="input @error('phone') input-error @enderror" placeholder="+60123456789">
                        @error('phone') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">Email</label>
                        <input wire:model="email" type="email" class="input @error('email') input-error @enderror" placeholder="john@example.com">
                        @error('email') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">WhatsApp Number</label>
                        <input wire:model="phone_whatsapp" type="tel" class="input" placeholder="If different from phone">
                    </div>
                    <div class="form-group">
                        <label class="label">Company</label>
                        <input wire:model="company_name" type="text" class="input" placeholder="Company name">
                    </div>
                    <div class="form-group">
                        <label class="label">Job Title</label>
                        <input wire:model="job_title" type="text" class="input" placeholder="CEO, Manager...">
                    </div>
                    <div class="form-group">
                        <label class="label">Source</label>
                        <select wire:model="source_id" class="input">
                            <option value="">Select source</option>
                            @foreach($this->sources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Status</label>
                        <select wire:model="status" class="input">
                            @foreach($this->statusOptions as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Priority</label>
                        <select wire:model="priority" class="input">
                            @foreach($this->priorityOptions as $p)
                                <option value="{{ $p->value }}">{{ $p->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    @can('customers.assign')
                    <div class="form-group">
                        <label class="label">Assign to Agent</label>
                        <select wire:model="assigned_to" class="input">
                            <option value="">Unassigned</option>
                            @foreach($this->agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endcan
                    <div class="form-group">
                        <label class="label">Expected Value</label>
                        <input wire:model="expected_value" type="number" step="0.01" class="input" placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label class="label">City</label>
                        <input wire:model="city" type="text" class="input" placeholder="Kuala Lumpur">
                    </div>
                    <div class="form-group">
                        <label class="label">Country</label>
                        <input wire:model="country" type="text" class="input" placeholder="Malaysia">
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Notes</label>
                    <textarea wire:model="notes" rows="3" class="input" placeholder="Add notes..."></textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">{{ $editMode ? 'Update' : 'Create' }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Delete Confirm -->
    @if($showDeleteConfirm)
    <div class="modal-backdrop">
        <div class="modal modal-sm mx-4 p-6 text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Customer?</h3>
            <p class="text-sm text-gray-500 mb-6">This action cannot be undone. All associated data will be removed.</p>
            <div class="flex gap-3 justify-center">
                <button wire:click="$set('showDeleteConfirm', false)" class="btn-secondary">Cancel</button>
                <button wire:click="delete" class="btn-danger">Delete</button>
            </div>
        </div>
    </div>
    @endif
</div>
