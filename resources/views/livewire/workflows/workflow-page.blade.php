<div class="px-4 sm:px-6 space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Workflows</h1>
            <p class="page-subtitle">Automate conversations with triggers, conditions, and actions</p>
        </div>
        @can('settings.edit')
        <button wire:click="openCreate" class="btn-primary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New Workflow
        </button>
        @endcan
    </div>

    {{-- Workflow List --}}
    @if($this->workflows->isEmpty())
        <div class="card p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <p class="text-gray-500 font-medium">No workflows configured</p>
            <p class="text-gray-400 text-sm mt-1">Create your first automation to handle chats automatically</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($this->workflows as $workflow)
                <div class="card p-4">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <button wire:click="toggleActive({{ $workflow->id }})" type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $workflow->is_active ? 'bg-green-500' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $workflow->is_active ? 'true' : 'false' }}">
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $workflow->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ $workflow->name }}</span>
                                    <span class="badge bg-indigo-100 text-indigo-700">{{ \App\Models\ChatWorkflow::TRIGGERS[$workflow->trigger_type] ?? $workflow->trigger_type }}</span>
                                    @if($workflow->priority > 0)
                                        <span class="text-xs text-gray-400">P{{ $workflow->priority }}</span>
                                    @endif
                                </div>
                                @if($workflow->description)
                                    <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $workflow->description }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                                    @if($workflow->conditions && count($workflow->conditions) > 0)
                                        <span>{{ count($workflow->conditions) }} condition(s)</span>
                                    @endif
                                    <span>{{ $workflow->actions->count() }} action(s)</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            @can('settings.edit')
                            <button wire:click="openEdit({{ $workflow->id }})" class="btn-icon" title="Edit">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button wire:click="deleteWorkflow({{ $workflow->id }})" wire:confirm="Delete this workflow?" class="btn-icon text-red-400 hover:text-red-600" title="Delete">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-start justify-center bg-gray-900/60 backdrop-blur-sm pt-8 pb-8 overflow-y-auto">
        <div class="card w-full max-w-2xl mx-4 overflow-hidden">
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-bold text-gray-900">{{ $editingId ? 'Edit Workflow' : 'New Workflow' }}</h3>
                <button wire:click="$set('showModal', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-6 max-h-[70vh] overflow-y-auto">
                {{-- Basic Info --}}
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <label class="label">Name *</label>
                            <input wire:model="name" type="text" class="input" placeholder="e.g. Auto-assign inquiry">
                        </div>
                        <div>
                            <label class="label">Priority</label>
                            <input wire:model="priority" type="number" min="0" class="input" placeholder="0">
                        </div>
                    </div>
                    <div>
                        <label class="label">Description</label>
                        <input wire:model="description" type="text" class="input" placeholder="What does this workflow do?">
                    </div>
                </div>

                {{-- Trigger --}}
                <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span class="text-sm font-semibold text-blue-800">When (Trigger)</span>
                    </div>
                    <select wire:model.live="trigger_type" class="input">
                        @foreach(\App\Models\ChatWorkflow::TRIGGERS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>

                    @if($trigger_type === 'keyword_match')
                        <div class="space-y-2">
                            <label class="label">Keywords</label>
                            <div class="flex gap-2">
                                <input wire:model="newKeyword" type="text" class="input flex-1" placeholder="Enter keyword" wire:keydown.enter="addKeyword">
                                <button wire:click="addKeyword" class="btn-primary btn-sm">Add</button>
                            </div>
                            @if(!empty($trigger_config['keywords'] ?? []))
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($trigger_config['keywords'] as $i => $kw)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                            {{ $kw }}
                                            <button wire:click="removeKeyword({{ $i }})" class="hover:text-red-600 ml-0.5">&times;</button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                            <select wire:model="trigger_config.match_mode" class="input w-48 text-xs">
                                <option value="any">Match ANY keyword</option>
                                <option value="all">Match ALL keywords</option>
                            </select>
                        </div>
                    @endif

                    @if($trigger_type === 'no_reply_timeout')
                        <div>
                            <label class="label">Timeout (minutes)</label>
                            <input wire:model="trigger_config.timeout_minutes" type="number" min="1" class="input w-32" placeholder="30">
                        </div>
                    @endif
                </div>

                {{-- Conditions --}}
                <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-amber-500 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-amber-800">If (Conditions)</span>
                        </div>
                        <button wire:click="addCondition" class="btn-secondary btn-sm">+ Add</button>
                    </div>
                    @if(empty($conditions))
                        <p class="text-xs text-amber-600">No conditions — runs for all matching triggers</p>
                    @else
                        <div class="space-y-2">
                            @foreach($conditions as $i => $cond)
                                @php
                                    $field = $cond['field'] ?? 'message_content';
                                    $isBool = in_array($field, ['is_new_conversation', 'ai_active', 'has_customer']);
                                    $isStatus = in_array($field, ['customer_status', 'conversation_status']);
                                @endphp
                                <div class="bg-white rounded-lg p-2.5 border border-amber-100">
                                    <div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-2 items-center">
                                        <select wire:model.live="conditions.{{ $i }}.field" class="input text-xs">
                                            <option value="message_content">Message Content</option>
                                            <option value="customer_status">Customer Status</option>
                                            <option value="conversation_status">Chat Status</option>
                                            <option value="assigned_to">Assigned Agent</option>
                                            <option value="ai_active">AI Active</option>
                                            <option value="is_new_conversation">New Conversation</option>
                                            <option value="has_customer">Has Customer</option>
                                        </select>

                                        @if($isBool)
                                            <select wire:model="conditions.{{ $i }}.value" class="input text-xs col-span-2">
                                                <option value="1">Yes</option>
                                                <option value="">No</option>
                                            </select>
                                        @elseif($isStatus && $field === 'customer_status')
                                            <select wire:model="conditions.{{ $i }}.operator" class="input text-xs">
                                                <option value="equals">Is</option>
                                                <option value="not_equals">Is Not</option>
                                            </select>
                                            <select wire:model="conditions.{{ $i }}.value" class="input text-xs">
                                                <option value="inquiry">Inquiry</option>
                                                <option value="contacted">Contacted</option>
                                                <option value="qualified">Qualified</option>
                                                <option value="proposal">Proposal</option>
                                                <option value="negotiation">Negotiation</option>
                                                <option value="converted">Converted</option>
                                                <option value="lost">Lost</option>
                                                <option value="on_hold">On Hold</option>
                                            </select>
                                        @elseif($isStatus && $field === 'conversation_status')
                                            <select wire:model="conditions.{{ $i }}.operator" class="input text-xs">
                                                <option value="equals">Is</option>
                                                <option value="not_equals">Is Not</option>
                                            </select>
                                            <select wire:model="conditions.{{ $i }}.value" class="input text-xs">
                                                <option value="open">Open</option>
                                                <option value="pending">Pending</option>
                                                <option value="resolved">Resolved</option>
                                                <option value="spam">Spam</option>
                                            </select>
                                        @elseif($field === 'assigned_to')
                                            <select wire:model.live="conditions.{{ $i }}.operator" class="input text-xs">
                                                <option value="is_null">Is Unassigned</option>
                                                <option value="is_not_null">Is Assigned</option>
                                                <option value="equals">Is Agent</option>
                                            </select>
                                            @if(($cond['operator'] ?? '') === 'equals')
                                                <select wire:model="conditions.{{ $i }}.value" class="input text-xs">
                                                    <option value="">Select agent</option>
                                                    @foreach($this->agents as $agent)
                                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <div></div>
                                            @endif
                                        @else
                                            <select wire:model="conditions.{{ $i }}.operator" class="input text-xs">
                                                <option value="contains">Contains</option>
                                                <option value="not_contains">Not Contains</option>
                                                <option value="equals">Equals</option>
                                                <option value="not_equals">Not Equals</option>
                                            </select>
                                            <input wire:model="conditions.{{ $i }}.value" type="text" class="input text-xs" placeholder="Value">
                                        @endif

                                        <button wire:click="removeCondition({{ $i }})" class="btn-icon text-red-400 hover:text-red-600 flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="rounded-xl border border-green-200 bg-green-50/50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-sm font-semibold text-green-800">Then (Actions)</span>
                        </div>
                        <button wire:click="addAction" class="btn-secondary btn-sm">+ Add</button>
                    </div>
                    @if(empty($actions))
                        <p class="text-xs text-green-600">No actions configured</p>
                    @else
                        <div class="space-y-2">
                            @foreach($actions as $i => $action)
                                <div class="bg-white rounded-lg border border-green-100 p-3 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-[10px] font-bold flex-shrink-0">{{ $i + 1 }}</span>
                                        <select wire:model.live="actions.{{ $i }}.action_type" class="input text-xs flex-1 font-medium">
                                            @foreach(\App\Models\ChatWorkflowAction::TYPES as $val => $lbl)
                                                <option value="{{ $val }}">{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                        <div class="flex gap-0.5 flex-shrink-0">
                                            <button wire:click="moveActionUp({{ $i }})" class="btn-icon p-1" @if($i === 0) disabled @endif>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                            </button>
                                            <button wire:click="moveActionDown({{ $i }})" class="btn-icon p-1" @if($i === count($actions) - 1) disabled @endif>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                            <button wire:click="removeAction({{ $i }})" class="btn-icon p-1 text-red-400 hover:text-red-600">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    @if($action['action_type'] === 'send_message')
                                        <textarea wire:model="actions.{{ $i }}.action_config.message" rows="2" class="input text-sm" placeholder="Message to send..."></textarea>
                                    @elseif($action['action_type'] === 'send_template')
                                        <select wire:model="actions.{{ $i }}.action_config.template_id" class="input text-sm">
                                            <option value="">Select template</option>
                                            @foreach($this->templates as $tpl)
                                                <option value="{{ $tpl->id }}">{{ $tpl->name }} ({{ $tpl->language }})</option>
                                            @endforeach
                                        </select>
                                    @elseif($action['action_type'] === 'assign_agent')
                                        <div class="grid grid-cols-2 gap-2">
                                            <select wire:model.live="actions.{{ $i }}.action_config.strategy" class="input text-sm">
                                                <option value="specific">Specific Agent</option>
                                                <option value="round_robin">Round Robin (Team)</option>
                                            </select>
                                            @if(($action['action_config']['strategy'] ?? 'specific') === 'specific')
                                                <select wire:model="actions.{{ $i }}.action_config.user_id" class="input text-sm">
                                                    <option value="">Select agent</option>
                                                    @foreach($this->agents as $agent)
                                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select wire:model="actions.{{ $i }}.action_config.team_id" class="input text-sm">
                                                    <option value="">Select team</option>
                                                    @foreach($this->teams as $team)
                                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    @elseif($action['action_type'] === 'assign_team')
                                        <select wire:model="actions.{{ $i }}.action_config.team_id" class="input text-sm">
                                            <option value="">Select team</option>
                                            @foreach($this->teams as $team)
                                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($action['action_type'] === 'change_status')
                                        <select wire:model="actions.{{ $i }}.action_config.status" class="input text-sm">
                                            <option value="open">Open</option>
                                            <option value="pending">Pending</option>
                                            <option value="resolved">Resolved</option>
                                        </select>
                                    @elseif($action['action_type'] === 'add_tag')
                                        <select wire:model="actions.{{ $i }}.action_config.tag_id" class="input text-sm">
                                            <option value="">Select tag</option>
                                            @foreach($this->tags as $tag)
                                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($action['action_type'] === 'set_ai_prompt')
                                        <textarea wire:model="actions.{{ $i }}.action_config.prompt" rows="3" class="input text-sm" placeholder="Custom AI prompt..."></textarea>
                                    @elseif($action['action_type'] === 'wait')
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <span>Wait</span>
                                            <input wire:model="actions.{{ $i }}.action_config.minutes" type="number" min="1" class="input w-20 text-sm" placeholder="5">
                                            <span>minutes</span>
                                        </div>
                                    @elseif($action['action_type'] === 'notify_agent')
                                        <input wire:model="actions.{{ $i }}.action_config.message" type="text" class="input text-sm" placeholder="Notification message...">
                                    @elseif(in_array($action['action_type'], ['enable_ai', 'disable_ai']))
                                        <p class="text-xs text-gray-400 italic">No configuration needed</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Active toggle --}}
                <div class="flex items-center gap-3">
                    <button wire:click="$toggle('is_active')" type="button" class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none {{ $is_active ? 'bg-green-500' : 'bg-gray-300' }}" role="switch" aria-checked="{{ $is_active ? 'true' : 'false' }}">
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                    <span class="text-sm font-medium text-gray-700">{{ $is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <button wire:click="$set('showModal', false)" class="btn-secondary">Cancel</button>
                <button wire:click="save" class="btn-primary">{{ $editingId ? 'Update' : 'Create' }} Workflow</button>
            </div>
        </div>
    </div>
    @endif
</div>
