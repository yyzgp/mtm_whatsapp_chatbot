<div class="flex h-full overflow-hidden" wire:poll.30s>
    <!-- Conversation List (Left Panel) -->
    <div class="w-80 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="p-4 border-b border-gray-100 space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-gray-900">Messages</h2>
                <button wire:click="$set('showNewConversation', true)"
                    class="w-8 h-8 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-colors" title="New conversation">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
            </div>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search..."
                    class="input w-full text-sm pl-9">
            </div>
            <div class="flex gap-1 bg-gray-100 rounded-lg p-0.5">
                @foreach(['open' => 'Open', 'pending' => 'Pending', 'resolved' => 'Resolved'] as $val => $label)
                <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="flex-1 text-xs py-1.5 rounded-md transition-all font-medium
                        {{ $statusFilter === $val ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
                @endforeach
            </div>
            <div class="flex gap-2">
                <select wire:model.live="phoneFilter" class="input w-full text-xs">
                    <option value="">All Numbers</option>
                    @foreach($this->filterablePhoneNumbers as $pn)
                        <option value="{{ $pn->id }}">{{ $pn->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="agentFilter" class="input w-full text-xs">
                    <option value="">All Agents</option>
                    <option value="0">Unassigned</option>
                    @foreach($this->filterableAgents as $agent)
                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Conversation List -->
        <div class="flex-1 overflow-y-auto">
            @forelse($this->conversations as $conv)
                <button wire:key="conv-{{ $conv->id }}" wire:click="selectConversation({{ $conv->id }})"
                    class="w-full text-left px-4 py-3 transition-colors border-b border-gray-50
                        {{ $selectedConversationId === $conv->id ? 'bg-indigo-50 border-l-3 border-l-indigo-500' : 'hover:bg-gray-50' }}">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-shrink-0">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                                {{ strtoupper(substr($conv->contact_name ?? $conv->contact_phone, 0, 2)) }}
                            </div>
                            @if($conv->isAiActive())
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 rounded-full border-2 border-white" title="AI Active"></span>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 truncate">
                                    {{ $conv->contact_name ?? $conv->contact_phone }}
                                </p>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    @if($conv->phoneNumber)
                                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">{{ $conv->phoneNumber->name }}</span>
                                    @endif
                                    <span class="text-[10px] text-gray-400">
                                        {{ $conv->last_message_at?->diffForHumans(short: true) ?? '' }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $conv->last_message_preview ?? 'No messages yet' }}</p>
                        </div>
                        @if($conv->unread_count > 0)
                            <span class="bg-indigo-600 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">{{ $conv->unread_count > 9 ? '9+' : $conv->unread_count }}</span>
                        @endif
                    </div>
                </button>
            @empty
                <div class="flex flex-col items-center justify-center h-full py-16 text-center px-6">
                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500">No conversations</p>
                    <p class="text-xs text-gray-400 mt-1">Start a new one below</p>
                    <button wire:click="$set('showNewConversation', true)" class="mt-3 text-xs font-medium text-indigo-600 hover:text-indigo-700">+ New Conversation</button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Chat Area -->
    @if($this->selectedConversation)
        @php
            $conv = $this->selectedConversation;
            $windowOpen = $this->isWindowOpen;
        @endphp
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Chat Header -->
            <div class="bg-white border-b border-gray-200 px-5 py-3 flex items-center gap-3 flex-shrink-0">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-bold">
                            {{ strtoupper(substr($conv->contact_name ?? $conv->contact_phone, 0, 2)) }}
                        </div>
                        @if($conv->isAiActive())
                            <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-emerald-500 rounded-full border-2 border-white"></span>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 truncate">{{ $conv->contact_name ?? $conv->contact_phone }}</p>
                            @if(!$windowOpen)
                                <span class="inline-flex items-center gap-1 text-[10px] font-medium px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    24hr expired
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">{{ $conv->contact_phone }}@if($conv->customer && $conv->customer->assignedAgent) &middot; {{ $conv->customer->assignedAgent->name }}@endif</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @can('chat.manage_ai')
                    <button wire:click="toggleAi"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors
                            {{ $conv->isAiActive() ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $conv->isAiActive() ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                        AI {{ $conv->isAiActive() ? 'On' : 'Off' }}
                        @if($conv->ai_paused_remaining) <span class="text-[10px] opacity-75">({{ $conv->ai_paused_remaining }}m)</span> @endif
                    </button>
                    @endcan

                    @if($conv->customer && $conv->customer->assignedAgent)
                        <span class="text-xs text-gray-500 bg-gray-50 px-2.5 py-1.5 rounded-lg">{{ $conv->customer->assignedAgent->name }}</span>
                    @else
                        <span class="text-xs text-gray-400 bg-gray-50 px-2.5 py-1.5 rounded-lg">Unassigned</span>
                    @endif

                    <button wire:click="resolveConversation"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Resolve
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#f0f2f5]" id="messageArea"
                x-data="{ scrollToBottom() { $nextTick(() => { $el.scrollTop = $el.scrollHeight; }); } }"
                x-init="scrollToBottom(); Livewire.hook('morph.updated', ({ el }) => { if (el.id === 'messageArea') scrollToBottom(); })"
                @scroll-chat.window="scrollToBottom()"
                @if($this->messages->isEmpty())
                    <div class="flex flex-col items-center justify-center h-full text-center">
                        <div class="w-14 h-14 rounded-full bg-white/80 flex items-center justify-center mb-3">
                            <svg class="w-7 h-7 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <p class="text-sm text-gray-500">No messages yet</p>
                        <p class="text-xs text-gray-400 mt-1">Send a template to start the conversation</p>
                    </div>
                @endif

                @foreach($this->messages as $msg)
                    @if($msg->sender_type->value === 'system')
                        <div class="flex justify-center">
                            <span class="bg-white/80 text-gray-500 text-[11px] px-3 py-1 rounded-full shadow-sm">{{ $msg->content }}</span>
                        </div>
                    @else
                        @php
                            $isOutgoing = in_array($msg->sender_type->value, ['agent', 'ai']);
                        @endphp
                        @php
                            $quickEmojis = ['👍','❤️','😂','😮','😢','🙏'];
                        @endphp
                        <div x-data="{ showPicker: false }" class="flex items-end gap-1 {{ $isOutgoing ? 'justify-end' : 'justify-start' }} group">

                            {{-- Reaction button: left of bubble for outgoing, right for incoming --}}
                            @if($isOutgoing)
                            <div class="relative flex-shrink-0 self-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="showPicker = !showPicker" class="text-base leading-none p-1 rounded-full hover:bg-gray-200/70 transition-colors">😊</button>
                                <div x-show="showPicker" x-cloak @click.outside="showPicker = false"
                                    class="absolute bottom-8 right-0 bg-white border border-gray-100 rounded-2xl shadow-xl px-2 py-1.5 flex gap-1 z-20"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-90"
                                    x-transition:enter-end="opacity-100 scale-100">
                                    @foreach($quickEmojis as $em)
                                        <button wire:click="reactToMessage({{ $msg->id }}, '{{ $em }}')"
                                            @click="showPicker = false"
                                            class="text-xl hover:scale-125 transition-transform leading-none p-0.5 rounded
                                                {{ $msg->reaction === $em ? 'bg-indigo-100 ring-1 ring-indigo-300' : '' }}">{{ $em }}</button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="max-w-xs lg:max-w-md xl:max-w-lg">
                                @if($msg->sender_type->value === 'ai')
                                    <p class="text-[10px] text-emerald-600 mb-0.5 {{ $isOutgoing ? 'text-right mr-1' : 'ml-1' }} font-medium">AI Assistant</p>
                                @elseif($msg->sender_type->value === 'agent' && $msg->sender)
                                    <p class="text-[10px] text-gray-400 mb-0.5 text-right mr-1">{{ $msg->sender->name }}</p>
                                @endif

                                <div class="px-3.5 py-2 rounded-2xl text-sm shadow-sm
                                    @if($msg->sender_type->value === 'customer')
                                        bg-white text-gray-800 rounded-bl-md
                                    @elseif($msg->sender_type->value === 'ai')
                                        bg-emerald-100 text-emerald-900 rounded-br-md
                                    @else
                                        bg-indigo-600 text-white rounded-br-md
                                    @endif
                                    @if($msg->message_type === 'template') border-2 border-dashed {{ $isOutgoing ? 'border-indigo-300' : 'border-gray-200' }} @endif">

                                    @if($msg->message_type === 'template')
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            <span class="text-[10px] font-medium opacity-70">Template</span>
                                        </div>
                                    @endif

                                    @if($msg->message_type === 'text' || $msg->message_type === 'template')
                                        <p class="whitespace-pre-wrap leading-relaxed">{{ $msg->content }}</p>

                                    @elseif($msg->message_type === 'image' || $msg->message_type === 'sticker')
                                        @if($msg->media_url)
                                            <img src="{{ asset($msg->media_url) }}" class="rounded-lg max-w-full max-h-64 object-contain cursor-pointer" alt="" onclick="window.open(this.src)">
                                        @else
                                            <div class="flex items-center gap-2 py-1">
                                                <svg class="w-5 h-5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                <span class="text-xs opacity-60">{{ $msg->message_type === 'sticker' ? 'Sticker' : 'Photo' }}</span>
                                            </div>
                                        @endif
                                        @if($msg->media_caption) <p class="text-xs mt-1">{{ $msg->media_caption }}</p> @endif

                                    @elseif($msg->message_type === 'video')
                                        @if($msg->media_url)
                                            <video src="{{ asset($msg->media_url) }}" controls class="rounded-lg max-w-full max-h-64"></video>
                                        @else
                                            <div class="flex items-center gap-2 py-1">
                                                <svg class="w-5 h-5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                <span class="text-xs opacity-60">Video</span>
                                            </div>
                                        @endif
                                        @if($msg->media_caption) <p class="text-xs mt-1">{{ $msg->media_caption }}</p> @endif

                                    @elseif($msg->message_type === 'audio')
                                        @if($msg->media_url)
                                            <audio src="{{ asset($msg->media_url) }}" controls class="max-w-full"></audio>
                                        @else
                                            <div class="flex items-center gap-2 py-1">
                                                <svg class="w-5 h-5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg>
                                                <span class="text-xs opacity-60">Voice message</span>
                                            </div>
                                        @endif

                                    @elseif($msg->message_type === 'document')
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5 opacity-60 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            @if($msg->media_url)
                                                <a href="{{ asset($msg->media_url) }}" target="_blank" class="text-xs underline hover:opacity-80">{{ $msg->content ?? 'Document' }}</a>
                                            @else
                                                <span class="text-xs">{{ $msg->content ?? 'Document' }}</span>
                                            @endif
                                        </div>

                                    @elseif($msg->message_type === 'location')
                                        @php $loc = json_decode($msg->content, true) @endphp
                                        <a href="https://maps.google.com/?q={{ $loc['latitude'] ?? 0 }},{{ $loc['longitude'] ?? 0 }}" target="_blank" class="flex items-center gap-2 hover:opacity-80">
                                            <svg class="w-4 h-4 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            <span class="text-xs underline">{{ $loc['name'] ?? 'Location' }}</span>
                                        </a>

                                    @else
                                        <p class="text-xs italic opacity-60">{{ $msg->content ?? 'Message' }}</p>
                                    @endif
                                </div>

                                @if($msg->reaction)
                                <div class="flex {{ $isOutgoing ? 'justify-end -mr-1' : 'justify-start -ml-1' }} -mt-2 mb-1 relative z-10">
                                    <span class="text-sm bg-white border border-gray-100 rounded-full px-1.5 py-0.5 shadow-sm leading-none" title="{{ $msg->reaction_by === 'customer' ? 'Customer reacted' : 'Agent reacted' }}">{{ $msg->reaction }}</span>
                                </div>
                                @endif
                                <div class="flex items-center gap-1 mt-0.5 {{ $isOutgoing ? 'justify-end pr-1' : 'justify-start pl-1' }}">
                                    <span class="text-[10px] text-gray-400">{{ $msg->created_at->format('H:i') }}</span>
                                    @if($msg->sender_type->value === 'agent')
                                        @if($msg->status->value === 'read')
                                            <svg class="w-3 h-3 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M1.83 8.58L6 12.75l4.17-4.17 1.41 1.42L6 15.59 0.42 10l1.41-1.42zM6.58 8.58l4.17 4.17 8-8 1.42 1.42-9.42 9.41-5.58-5.58 1.41-1.42z"/></svg>
                                        @elseif($msg->status->value === 'delivered')
                                            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M1.83 8.58L6 12.75l4.17-4.17 1.41 1.42L6 15.59 0.42 10l1.41-1.42zM6.58 8.58l4.17 4.17 8-8 1.42 1.42-9.42 9.41-5.58-5.58 1.41-1.42z"/></svg>
                                        @elseif($msg->status->value === 'sent')
                                            <svg class="w-3 h-3 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                        @elseif($msg->status->value === 'pending')
                                            <svg class="w-3 h-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @elseif($msg->status->value === 'failed')
                                            <svg class="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            {{-- Reaction button: right of bubble for incoming messages --}}
                            @if(!$isOutgoing)
                            <div class="relative flex-shrink-0 self-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button @click="showPicker = !showPicker" class="text-base leading-none p-1 rounded-full hover:bg-gray-200/70 transition-colors">😊</button>
                                <div x-show="showPicker" x-cloak @click.outside="showPicker = false"
                                    class="absolute bottom-8 left-0 bg-white border border-gray-100 rounded-2xl shadow-xl px-2 py-1.5 flex gap-1 z-20"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 scale-90"
                                    x-transition:enter-end="opacity-100 scale-100">
                                    @foreach($quickEmojis as $em)
                                        <button wire:click="reactToMessage({{ $msg->id }}, '{{ $em }}')"
                                            @click="showPicker = false"
                                            class="text-xl hover:scale-125 transition-transform leading-none p-0.5 rounded
                                                {{ $msg->reaction === $em ? 'bg-gray-100 ring-1 ring-gray-300' : '' }}">{{ $em }}</button>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Message Input -->
            @can('chat.reply')
            <div class="bg-white border-t border-gray-200 px-4 py-3 flex-shrink-0">
                @if(!$conv->isAiActive() && $conv->ai_paused_remaining)
                    <div class="mb-2 flex items-center gap-2 text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        AI paused - resumes in {{ $conv->ai_paused_remaining }}m
                    </div>
                @endif

                @if($windowOpen)
                    {{-- Normal message input --}}
                    <form wire:submit="sendMessage" class="flex items-end gap-2">
                        <button type="button" wire:click="openTemplateModal"
                            class="flex-shrink-0 w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:border-indigo-200 transition-colors" title="Send Template">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </button>
                        <div class="flex-1 relative">
                            <textarea
                                wire:model="messageText"
                                placeholder="Type a message..."
                                rows="1"
                                class="input w-full resize-none min-h-[36px] max-h-28 pr-12 text-sm"
                                @keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                            ></textarea>
                        </div>
                        <button type="submit" class="flex-shrink-0 w-9 h-9 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                @else
                    {{-- 24hr window expired - template only --}}
                    <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-amber-800">24-hour messaging window expired</p>
                            <p class="text-xs text-amber-600 mt-0.5">You can only send a pre-approved template message to re-open the conversation.</p>
                        </div>
                        <button type="button" wire:click="openTemplateModal"
                            class="flex-shrink-0 px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold transition-colors">
                            Send Template
                        </button>
                    </div>
                @endif
            </div>
            @endcan
        </div>

        <!-- Customer Info Sidebar -->
        @if($conv->customer)
        <div class="w-72 flex-shrink-0 bg-white border-l border-gray-200 overflow-y-auto hidden xl:flex xl:flex-col">
            <div class="p-5 text-center border-b border-gray-100">
                <img src="{{ $conv->customer->avatar_url }}" alt="" class="w-16 h-16 rounded-full mx-auto ring-2 ring-gray-100">
                <p class="font-semibold text-gray-900 mt-3">{{ $conv->customer->full_name }}</p>
                @if($conv->customer->company_name)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $conv->customer->job_title ?? '' }}{{ $conv->customer->job_title && $conv->customer->company_name ? ' at ' : '' }}{{ $conv->customer->company_name }}</p>
                @endif
                <div class="flex items-center justify-center gap-2 mt-2">
                    <span class="badge {{ $conv->customer->status->badgeClass() }}">{{ $conv->customer->status->label() }}</span>
                    <span class="badge {{ $conv->customer->priority->badgeClass() }}">{{ $conv->customer->priority->label() }}</span>
                </div>
            </div>

            <div class="p-5 space-y-3 text-sm flex-1">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span class="text-gray-700">{{ $conv->customer->phone }}</span>
                </div>
                @if($conv->customer->email)
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-gray-700 truncate">{{ $conv->customer->email }}</span>
                </div>
                @endif
                @if($conv->customer->expected_value)
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="text-green-600 font-semibold">${{ number_format($conv->customer->expected_value, 2) }}</span>
                </div>
                @endif

                {{-- 24hr window status --}}
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Message Window</p>
                    @if($windowOpen)
                        <div class="flex items-center gap-2 text-xs text-green-600">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                            Open - free messaging
                        </div>
                    @else
                        <div class="flex items-center gap-2 text-xs text-amber-600">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Closed - templates only
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-4 border-t border-gray-100">
                <a href="{{ route('customers.show', $conv->customer) }}"
                    class="block text-center text-xs font-medium text-indigo-600 hover:text-indigo-700 py-2 rounded-lg hover:bg-indigo-50 transition-colors">
                    View Full Profile
                </a>
            </div>
        </div>
        @endif

    @else
        <!-- Empty State -->
        <div class="flex-1 flex flex-col items-center justify-center bg-[#f0f2f5] text-center px-6">
            <div class="w-24 h-24 rounded-full bg-white/80 flex items-center justify-center mb-5">
                <svg class="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <p class="text-lg font-semibold text-gray-600">Select a conversation</p>
            <p class="text-sm text-gray-400 mt-1 max-w-xs">Choose from your existing conversations or start a new one with a customer</p>
            <button wire:click="$set('showNewConversation', true)"
                class="mt-5 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Conversation
            </button>
        </div>
    @endif

    {{-- New Conversation Modal --}}
    @if($showNewConversation)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">New Conversation</h3>
                        <p class="text-sm text-gray-500">Select a customer to start chatting</p>
                    </div>
                </div>

                @if($this->selectedCustomer)
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-indigo-50 border border-indigo-100">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr($this->selectedCustomer->first_name, 0, 1) . substr($this->selectedCustomer->last_name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $this->selectedCustomer->full_name }}</p>
                            <p class="text-xs text-gray-500">{{ $this->selectedCustomer->phone }}</p>
                        </div>
                        <button wire:click="$set('selectedCustomerId', null)" class="text-gray-400 hover:text-red-500 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @else
                    <div class="space-y-2">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input wire:model.live.debounce.300ms="customerSearch" type="text"
                                placeholder="Search by name, phone, or company..."
                                class="input w-full text-sm pl-9" autofocus>
                        </div>

                        @if(strlen($customerSearch) >= 2)
                            <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-xl divide-y divide-gray-50">
                                @forelse($this->customerResults as $cust)
                                    <button wire:key="cust-{{ $cust->id }}"
                                        wire:click="pickCustomer({{ $cust->id }})"
                                        class="w-full text-left px-3 py-2.5 hover:bg-indigo-50 transition-colors flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                            {{ strtoupper(substr($cust->first_name, 0, 1) . substr($cust->last_name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $cust->full_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $cust->phone }}@if($cust->company_name) &middot; {{ $cust->company_name }}@endif</p>
                                        </div>
                                    </button>
                                @empty
                                    <div class="px-3 py-6 text-center text-sm text-gray-400">No customers found</div>
                                @endforelse
                            </div>
                        @elseif(strlen($customerSearch) > 0)
                            <p class="text-xs text-gray-400 text-center py-3">Type at least 2 characters to search</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <button type="button" wire:click="cancelNewConversation"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="startNewConversation"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ !$selectedCustomerId ? 'disabled' : '' }}>
                    Start Chat
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Template Modal --}}
    @if($showTemplateModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Send Template</h3>
                        <p class="text-sm text-gray-500">Select an approved template to send</p>
                    </div>
                </div>

                @if($this->templates->isEmpty())
                    <div class="text-center py-8">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-gray-500">No approved templates</p>
                        <p class="text-xs text-gray-400 mt-1">Configure templates in WhatsApp Settings</p>
                    </div>
                @else
                    <div class="max-h-72 overflow-y-auto space-y-2">
                        @foreach($this->templates as $tpl)
                            <button wire:key="tpl-{{ $tpl->id }}"
                                wire:click="$set('selectedTemplateId', {{ $tpl->id }})"
                                class="w-full text-left p-3.5 rounded-xl border-2 transition-all
                                    {{ $selectedTemplateId == $tpl->id ? 'border-green-500 bg-green-50 shadow-sm' : 'border-gray-100 hover:border-green-200 hover:bg-green-50/30' }}">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-gray-900">{{ $tpl->name }}</p>
                                    @if($selectedTemplateId == $tpl->id)
                                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ strtoupper($tpl->language ?? 'en') }}</span>
                                    <span class="text-[10px] font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ ucfirst($tpl->category ?? 'utility') }}</span>
                                </div>
                                @if($tpl->components)
                                    @foreach($tpl->components as $comp)
                                        @if(($comp['type'] ?? '') === 'BODY')
                                            <p class="text-xs text-gray-400 mt-2 line-clamp-2 leading-relaxed">{{ $comp['text'] ?? '' }}</p>
                                        @endif
                                    @endforeach
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            {{-- Variable inputs (shown once a template with variables is selected) --}}
            @if($selectedTemplateId && count($this->selectedTemplateVars) > 0)
            <div class="px-6 pb-2 space-y-4 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Fill in template variables</p>
                @foreach($this->selectedTemplateVars as $section)
                    <div>
                        <p class="text-xs font-medium text-gray-400 mb-2 flex items-center gap-1">
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-green-400"></span>
                            {{ $section['label'] }}
                            <span class="text-gray-300 font-normal ml-1 truncate max-w-[200px]" title="{{ $section['text'] }}">— {{ $section['text'] }}</span>
                        </p>
                        @foreach($section['vars'] as $varNum)
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-bold text-green-600 bg-green-50 border border-green-200 rounded px-1.5 py-0.5 shrink-0">&#123;&#123;{{ $varNum }}&#125;&#125;</span>
                                <input
                                    type="text"
                                    wire:model="templateParams.{{ $section['type'] }}.{{ $varNum - 1 }}"
                                    placeholder="Value for &#123;&#123;{{ $varNum }}&#125;&#125;"
                                    class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-green-300 focus:border-green-400"
                                />
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
            @endif

            <div class="flex gap-3 px-6 pb-6 pt-4">
                <button type="button" wire:click="$set('showTemplateModal', false)"
                    class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="button" wire:click="sendTemplate"
                    class="flex-1 px-4 py-2.5 text-sm font-semibold text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ !$selectedTemplateId ? 'disabled' : '' }}>
                    Send Template
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
