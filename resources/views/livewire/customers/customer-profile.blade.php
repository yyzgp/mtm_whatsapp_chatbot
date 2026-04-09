<div class="px-4 sm:px-6 space-y-5">
    <!-- Top Bar -->
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('customers.index') }}" class="hover:text-indigo-600 transition-colors inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Customers
            </a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900 font-semibold">{{ $customer->full_name }}</span>
        </div>
        <span class="text-xs text-gray-400">Created {{ $customer->created_at->diffForHumans() }}</span>
    </div>

    <!-- Profile Header Card -->
    <div class="card overflow-hidden">
        <div class="h-24 bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-600"></div>
        <div class="px-6 pb-5 -mt-12">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                <img src="{{ $customer->avatar_url }}" alt="" class="w-24 h-24 rounded-2xl border-4 border-white shadow-lg">
                <div class="flex-1 sm:pb-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ $customer->full_name }}</h1>
                        <span class="badge {{ $customer->status->badgeClass() }}">{{ $customer->status->label() }}</span>
                        <span class="badge {{ $customer->priority->badgeClass() }}">{{ $customer->priority->label() }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $customer->job_title }}{{ $customer->job_title && $customer->company_name ? ' at ' : '' }}<span class="font-medium text-gray-700">{{ $customer->company_name }}</span>
                    </p>
                    @if($customer->expected_value)
                        <p class="text-sm font-semibold text-green-600 mt-1">${{ number_format($customer->expected_value, 2) }} expected value</p>
                    @endif
                </div>
                <div class="flex items-center gap-2 sm:pb-1">
                    <a href="{{ route('chat.index', ['customer' => $customer->id]) }}" class="btn-primary btn-sm" title="Chat on WhatsApp">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </a>
                    @if($customer->phone)
                    <a href="tel:{{ $customer->phone }}" class="btn-secondary btn-sm" title="Call">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </a>
                    @endif
                    @if($customer->email)
                    <a href="mailto:{{ $customer->email }}" class="btn-secondary btn-sm" title="Email">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Status Pipeline - Radio Timeline -->
        <div class="px-6 pb-5 pt-2 border-t border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Sales Pipeline</p>

            <div class="max-w-2xl mx-auto pb-2">
                <div class="flex items-start justify-between">
                    @foreach(\App\Enums\CustomerStatus::pipeline() as $i => $status)
                        @php
                            $isActive = $customer->status === $status;
                            $isPast = $customer->status->pipelineIndex() > $status->pipelineIndex();
                            $isFuture = !$isActive && !$isPast;
                            $isLast = $i === count(\App\Enums\CustomerStatus::pipeline()) - 1;
                        @endphp
                        <div wire:key="pipeline-{{ $status->value }}" class="flex flex-col items-center relative flex-1">
                            @if($i > 0)
                                <div class="absolute top-[13px] right-1/2 w-1/2 h-0.5 {{ $isPast || $isActive ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                            @endif
                            @if(!$isLast)
                                <div class="absolute top-[13px] left-1/2 w-1/2 h-0.5 {{ $isPast ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                            @endif

                            <button type="button"
                                wire:click="confirmStatusChange('{{ $status->value }}', '{{ $status->label() }}')"
                                class="relative z-10 w-7 h-7 rounded-full border-2 flex items-center justify-center transition-all cursor-pointer
                                    {{ $isActive ? 'border-indigo-500 bg-indigo-500 shadow-lg shadow-indigo-200 scale-110' : '' }}
                                    {{ $isPast ? 'border-green-400 bg-green-400' : '' }}
                                    {{ $isFuture ? 'border-gray-300 bg-white hover:border-indigo-300 hover:bg-indigo-50' : '' }}">
                                @if($isPast)
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @elseif($isActive)
                                    <span class="w-2.5 h-2.5 rounded-full bg-white"></span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                @endif
                            </button>

                            <span class="mt-2 text-[11px] font-semibold text-center leading-tight
                                {{ $isActive ? 'text-indigo-600' : '' }}
                                {{ $isPast ? 'text-green-600' : '' }}
                                {{ $isFuture ? 'text-gray-400' : '' }}">
                                {{ $status->label() }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center gap-3 mt-4 pt-3 border-t border-gray-100">
                    <button type="button"
                        wire:click="confirmStatusChange('lost', 'Lost')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border
                            {{ $customer->status->value === 'lost' ? 'border-red-200 bg-red-50 text-red-700' : 'border-gray-200 bg-white text-gray-400 hover:border-red-200 hover:bg-red-50 hover:text-red-500' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $customer->status->value === 'lost' ? 'bg-red-500' : 'bg-gray-300' }}"></span>
                        Lost
                    </button>
                    <button type="button"
                        wire:click="confirmStatusChange('on_hold', 'On Hold')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border
                            {{ $customer->status->value === 'on_hold' ? 'border-yellow-200 bg-yellow-50 text-yellow-700' : 'border-gray-200 bg-white text-gray-400 hover:border-yellow-200 hover:bg-yellow-50 hover:text-yellow-500' }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $customer->status->value === 'on_hold' ? 'bg-yellow-500' : 'bg-gray-300' }}"></span>
                        On Hold
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b border-gray-200">
        @foreach(['information' => 'Information', 'documents' => 'Documents', 'logs' => 'Activity Logs'] as $tab => $label)
            @php
                $count = match($tab) {
                    'documents' => $customer->documents->count(),
                    'logs' => $customer->activities->count(),
                    default => null,
                };
            @endphp
            <button wire:click="$set('activeTab', '{{ $tab }}')"
                class="px-5 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px inline-flex items-center gap-2
                    {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $label }}
                @if($count !== null)
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $activeTab === $tab ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ===== INFORMATION TAB ===== --}}
    @if($activeTab === 'information')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Contact + Assignment -->
        <div class="lg:col-span-2 space-y-5">
            @if(!$editingInfo)
            {{-- View Mode --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Customer Details</h3>
                    @can('customers.edit')
                    <button wire:click="startEditing" class="btn-secondary btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </button>
                    @endcan
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                        @php
                            $fields = [
                                ['label' => 'First Name', 'value' => $customer->first_name],
                                ['label' => 'Last Name', 'value' => $customer->last_name],
                                ['label' => 'Email', 'value' => $customer->email],
                                ['label' => 'Phone', 'value' => $customer->phone],
                                ['label' => 'Company', 'value' => $customer->company_name],
                                ['label' => 'Job Title', 'value' => $customer->job_title],
                                ['label' => 'City', 'value' => $customer->city],
                                ['label' => 'Country', 'value' => $customer->country],
                                ['label' => 'Source', 'value' => $customer->source?->name],
                                ['label' => 'Priority', 'value' => $customer->priority->label(), 'badge' => $customer->priority->badgeClass()],
                                ['label' => 'Expected Value', 'value' => $customer->expected_value ? '$' . number_format($customer->expected_value, 2) : null],
                                ['label' => 'Expected Close', 'value' => $customer->expected_close_date?->format('M d, Y')],
                            ];
                        @endphp
                        @foreach($fields as $field)
                            <div>
                                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $field['label'] }}</p>
                                @if(isset($field['badge']) && $field['value'])
                                    <span class="badge {{ $field['badge'] }} mt-1">{{ $field['value'] }}</span>
                                @elseif($field['value'])
                                    <p class="text-sm font-medium text-gray-900 mt-0.5">{{ $field['value'] }}</p>
                                @else
                                    <p class="text-sm text-gray-300 mt-0.5">&mdash;</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($customer->notes)
                    <div class="mt-6 pt-5 border-t border-gray-100">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1.5">Notes</p>
                        <div class="p-3 rounded-lg bg-amber-50 border border-amber-100">
                            <p class="text-sm text-amber-800 whitespace-pre-line">{{ $customer->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            @else
            {{-- Edit Mode --}}
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">Edit Customer</h3>
                    <button wire:click="cancelEditing" class="btn-icon">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form wire:submit="saveInfo" class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">First Name <span class="text-red-500">*</span></label>
                            <input wire:model="editFirstName" type="text" class="input @error('editFirstName') input-error @enderror">
                            @error('editFirstName') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="label">Last Name <span class="text-red-500">*</span></label>
                            <input wire:model="editLastName" type="text" class="input @error('editLastName') input-error @enderror">
                            @error('editLastName') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="label">Email</label>
                            <input wire:model="editEmail" type="email" class="input @error('editEmail') input-error @enderror">
                            @error('editEmail') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="label">Phone <span class="text-red-500">*</span></label>
                            <input wire:model="editPhone" type="text" class="input @error('editPhone') input-error @enderror">
                            @error('editPhone') <p class="error-msg">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-group">
                            <label class="label">Company</label>
                            <input wire:model="editCompany" type="text" class="input">
                        </div>
                        <div class="form-group">
                            <label class="label">Job Title</label>
                            <input wire:model="editJobTitle" type="text" class="input">
                        </div>
                        <div class="form-group">
                            <label class="label">City</label>
                            <input wire:model="editCity" type="text" class="input">
                        </div>
                        <div class="form-group">
                            <label class="label">Country</label>
                            <input wire:model="editCountry" type="text" class="input">
                        </div>
                        <div class="form-group">
                            <label class="label">Expected Value ($)</label>
                            <input wire:model="editExpectedValue" type="number" step="0.01" min="0" class="input">
                        </div>
                        <div class="form-group">
                            <label class="label">Priority</label>
                            <select wire:model="editPriority" class="input">
                                @foreach(\App\Enums\CustomerPriority::cases() as $p)
                                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group sm:col-span-2">
                            <label class="label">Notes</label>
                            <textarea wire:model="editNotes" rows="3" class="input" placeholder="Internal notes about this customer..."></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cancelEditing" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-5">
            <!-- Assigned Agent -->
            @can('customers.assign')
            <div class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Assigned Agent</h3></div>
                <div class="p-4">
                    @if($customer->assignedAgent)
                        <div class="flex items-center gap-3 mb-3">
                            <img src="{{ $customer->assignedAgent->avatar_url }}" class="w-10 h-10 rounded-full border-2 border-white shadow">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $customer->assignedAgent->name }}</p>
                                <p class="text-xs text-gray-400">{{ $customer->assignedAgent->email }}</p>
                            </div>
                        </div>
                    @endif
                    <select wire:change="assignTo($event.target.value)" class="input w-full text-sm">
                        <option value="">-- Unassigned --</option>
                        @foreach($this->agents as $agent)
                            <option value="{{ $agent->id }}" {{ $customer->assigned_to == $agent->id ? 'selected' : '' }}>{{ $agent->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endcan

            <!-- Tags -->
            @if($customer->tags->count())
            <div class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Tags</h3></div>
                <div class="p-4 flex flex-wrap gap-1.5">
                    @foreach($customer->tags as $tag)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium" style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $tag->color }}"></span>
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Quick Info</h3></div>
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span class="badge {{ $customer->status->badgeClass() }}">{{ $customer->status->label() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Source</span>
                        <span class="font-medium text-gray-900">{{ $customer->source?->name ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Documents</span>
                        <span class="font-medium text-gray-900">{{ $customer->documents->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Activities</span>
                        <span class="font-medium text-gray-900">{{ $customer->activities->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Created</span>
                        <span class="font-medium text-gray-900">{{ $customer->created_at->format('M d, Y') }}</span>
                    </div>
                    @if($customer->closed_at)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Closed</span>
                        <span class="font-medium text-gray-900">{{ $customer->closed_at->format('M d, Y') }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ===== DOCUMENTS TAB ===== --}}
    @elseif($activeTab === 'documents')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Document List -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900">All Documents</h3>
                    <span class="text-xs text-gray-400">{{ $customer->documents->count() }} files</span>
                </div>
                @if($customer->documents->count())
                <div class="divide-y divide-gray-50">
                    @foreach($customer->documents as $doc)
                    <div wire:key="doc-{{ $doc->id }}" class="px-5 py-3.5 flex items-center gap-4 hover:bg-gray-50/50 transition-colors group">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0
                            {{ str_starts_with($doc->mime_type ?? '', 'image') ? 'bg-blue-50' : (str_contains($doc->mime_type ?? '', 'pdf') ? 'bg-red-50' : 'bg-gray-50') }}">
                            @if(str_starts_with($doc->mime_type ?? '', 'image'))
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @elseif(str_contains($doc->mime_type ?? '', 'pdf'))
                                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ $doc->url }}" target="_blank" class="text-sm font-medium text-gray-900 hover:text-indigo-600 truncate block transition-colors">{{ $doc->name }}</a>
                            <p class="text-xs text-gray-400 mt-0.5">{{ $doc->file_size_human }} &middot; Uploaded {{ $doc->created_at->diffForHumans() }} by {{ $doc->uploadedBy?->name ?? 'Unknown' }}</p>
                        </div>
                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ $doc->url }}" target="_blank" class="p-2 rounded-lg hover:bg-indigo-50 text-gray-400 hover:text-indigo-600 transition-colors" title="Download">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            </a>
                            <button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Delete this document?" class="p-2 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm font-medium text-gray-500">No documents yet</p>
                    <p class="text-xs text-gray-400 mt-1">Upload your first document using the panel on the right</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Upload Panel -->
        <div>
            <div class="card">
                <div class="card-header"><h3 class="text-sm font-semibold text-gray-900">Upload Document</h3></div>
                <form wire:submit="uploadDocument" class="p-5 space-y-4">
                    <div class="form-group">
                        <label class="label">Document Name <span class="text-red-500">*</span></label>
                        <input wire:model="documentName" type="text" class="input" placeholder="e.g. Contract, Proposal...">
                        @error('documentName') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label">File <span class="text-red-500">*</span></label>
                        <div x-data="{ dragging: false }"
                             x-on:dragover.prevent="dragging = true"
                             x-on:dragleave.prevent="dragging = false"
                             x-on:drop.prevent="dragging = false"
                             class="relative mt-1">
                            <label :class="dragging ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 hover:border-gray-300 bg-white'"
                                class="flex flex-col items-center justify-center p-6 border-2 border-dashed rounded-xl cursor-pointer transition-colors">
                                @if($documentFile)
                                    <svg class="w-8 h-8 text-green-500 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-sm font-medium text-green-700">{{ $documentFile->getClientOriginalName() }}</span>
                                    <span class="text-xs text-gray-400 mt-0.5">Click to change file</span>
                                @else
                                    <svg class="w-8 h-8 text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                    <span class="text-sm font-medium text-gray-600">Click to upload</span>
                                    <span class="text-xs text-gray-400 mt-0.5">or drag and drop</span>
                                @endif
                                <input wire:model="documentFile" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            </label>
                        </div>
                        @error('documentFile') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>

                    <div wire:loading wire:target="documentFile" class="flex items-center gap-2 text-sm text-indigo-600">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Uploading file...
                    </div>

                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="uploadDocument,documentFile">
                        <svg wire:loading.remove wire:target="uploadDocument" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <svg wire:loading wire:target="uploadDocument" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Upload Document
                    </button>

                    <p class="text-[11px] text-gray-400 text-center">Max file size: 10MB</p>
                </form>
            </div>
        </div>
    </div>

    {{-- ===== ACTIVITY LOGS TAB ===== --}}
    @elseif($activeTab === 'logs')
    <div>
        <!-- Actions bar -->
        <div class="flex items-center justify-between mb-4">
            <p class="text-sm text-gray-500">{{ $customer->activities->count() }} activities recorded</p>
            <button wire:click="$set('showActivityModal', true)" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Log Activity
            </button>
        </div>

        @if($customer->notes)
        <div class="flex items-start gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200 mb-4">
            <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            <div>
                <p class="text-xs font-semibold text-amber-700 uppercase tracking-wider">Pinned Notes</p>
                <p class="text-sm text-amber-800 mt-1 whitespace-pre-line">{{ $customer->notes }}</p>
            </div>
        </div>
        @endif

        <div class="card overflow-hidden max-h-[700px] overflow-y-auto">
            @forelse($customer->activities->sortByDesc('created_at') as $activity)
                <div wire:key="activity-{{ $activity->id }}" class="px-5 py-4 flex items-start gap-4 border-b border-gray-50 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 {{ $activity->type->color() }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $activity->type->icon() }}"/>
                            </svg>
                        </div>
                        @if(!$loop->last)
                            <div class="absolute top-10 left-1/2 -translate-x-1/2 w-0.5 h-[calc(100%+0.5rem)] bg-gray-100"></div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 pb-1">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ $activity->subject }}</p>
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider mt-0.5 {{ $activity->type->color() }}">{{ $activity->type->label() }}</span>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">{{ $activity->created_at->diffForHumans() }}</span>
                        </div>
                        @if($activity->description)
                            <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">{{ $activity->description }}</p>
                        @endif
                        @if($activity->outcome)
                            <div class="flex items-center gap-1.5 mt-2 text-xs text-green-600 font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $activity->outcome }}
                            </div>
                        @endif
                        <p class="text-xs text-gray-400 mt-2">by {{ $activity->user?->name ?? 'System' }} &middot; {{ $activity->created_at->format('M d, Y \a\t h:i A') }}</p>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center">
                    <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-medium text-gray-500">No activities yet</p>
                    <p class="text-xs text-gray-400 mt-1">Log the first interaction with this customer</p>
                    <button wire:click="$set('showActivityModal', true)" class="btn-primary btn-sm mt-4">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Log Activity
                    </button>
                </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- Activity Modal -->
    @if($showActivityModal)
    <div class="modal-backdrop" @keydown.escape.window="$wire.set('showActivityModal', false)">
        <div class="modal modal-md mx-4" @click.outside="$wire.set('showActivityModal', false)">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Log Activity</h3>
                <button wire:click="$set('showActivityModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit="logActivity" class="p-6 space-y-5">
                <div class="form-group">
                    <label class="label">Activity Type</label>
                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        @foreach($this->activityTypes as $type)
                        <button type="button"
                            wire:click="$set('activityType', '{{ $type->value }}')"
                            class="flex flex-col items-center gap-1.5 p-2.5 rounded-xl border-2 text-xs font-medium transition-all {{ $activityType === $type->value ? 'border-indigo-500 bg-indigo-50 text-indigo-700 shadow-sm' : 'border-gray-100 hover:border-gray-200 text-gray-600' }}">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $type->color() }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $type->icon() }}"/></svg>
                            </span>
                            {{ $type->label() }}
                        </button>
                        @endforeach
                    </div>
                </div>
                <div class="form-group">
                    <label class="label">Subject <span class="text-red-500">*</span></label>
                    <input wire:model="activitySubject" type="text" class="input @error('activitySubject') input-error @enderror" placeholder="Brief summary of the interaction">
                    @error('activitySubject') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Description</label>
                    <textarea wire:model="activityDescription" rows="3" class="input" placeholder="Detailed notes..."></textarea>
                </div>
                <div class="form-group">
                    <label class="label">Outcome / Next Step</label>
                    <input wire:model="activityOutcome" type="text" class="input" placeholder="e.g. Scheduled follow-up for Monday">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="$set('showActivityModal', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Log Activity
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Status Change Confirmation Modal --}}
    @if($showStatusModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $pendingStatus === 'lost' ? 'bg-red-100' : ($pendingStatus === 'on_hold' ? 'bg-yellow-100' : 'bg-indigo-100') }}">
                        <svg class="w-6 h-6 {{ $pendingStatus === 'lost' ? 'text-red-600' : ($pendingStatus === 'on_hold' ? 'text-yellow-600' : 'text-indigo-600') }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Change Status</h3>
                        <p class="text-sm text-gray-500">
                            <span class="font-medium text-gray-700">{{ $customer->status->label() }}</span>
                            <svg class="w-4 h-4 inline text-gray-400 mx-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <span class="font-medium {{ $pendingStatus === 'lost' ? 'text-red-600' : ($pendingStatus === 'on_hold' ? 'text-yellow-600' : 'text-indigo-600') }}">{{ $pendingLabel }}</span>
                        </p>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea
                        wire:model="statusNotes"
                        rows="3"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        placeholder="Reason for this change, next steps..."
                        autofocus
                    ></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button type="button" wire:click="cancelStatusChange"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="applyStatusChange"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white rounded-lg transition-colors
                        {{ $pendingStatus === 'lost' ? 'bg-red-600 hover:bg-red-700' : ($pendingStatus === 'on_hold' ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-indigo-600 hover:bg-indigo-700') }}">
                    Confirm Change
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
