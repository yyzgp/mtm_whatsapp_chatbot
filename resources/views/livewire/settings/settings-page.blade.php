<div class="px-4 sm:px-6 space-y-4">
    <div>
        <h1 class="page-title">Settings</h1>
        <p class="page-subtitle">Configure system and integrations</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
        @foreach(['general' => 'General', 'whatsapp' => 'WhatsApp', 'ai' => 'AI Settings'] as $tab => $label)
        <button wire:click="$set('activeTab', '{{ $tab }}')"
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors -mb-px {{ $activeTab === $tab ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
            {{ $label }}
        </button>
        @endforeach
    </div>

    @if($activeTab === 'general')
    <div class="card max-w-lg">
        <div class="card-header"><h3 class="font-semibold text-gray-900">General Settings</h3></div>
        <form wire:submit="saveGeneral" class="card-body space-y-4">
            <div class="form-group">
                <label class="label">Application Name</label>
                <input wire:model="app_name" type="text" class="input">
            </div>
            <div class="form-group">
                <label class="label">Timezone</label>
                <select wire:model="app_timezone" class="input">
                    @foreach(timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}" {{ $app_timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    @elseif($activeTab === 'whatsapp')
    <div class="space-y-4">
        {{-- Global WhatsApp Settings --}}
        <div class="card p-4">
            <form wire:submit="saveWhatsAppSettings" class="flex items-end gap-4">
                <div class="form-group flex-1 max-w-xs">
                    <label class="label">Greeting Cooldown (minutes)</label>
                    <input wire:model="wa_greeting_cooldown" type="number" min="1" max="1440" class="input">
                    <p class="text-xs text-gray-400 mt-1">Resend greeting after this much inactivity. Default: 30 min.</p>
                </div>
                <button type="submit" class="btn-primary h-10">Save</button>
            </form>
        </div>

        <div class="flex justify-between items-center">
            <h3 class="font-semibold text-gray-900">WhatsApp Business Accounts</h3>
            <button wire:click="openWhatsAppForm()" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Account
            </button>
        </div>

        @forelse($this->whatsappAccounts as $account)
        <div class="card" wire:key="wa-account-{{ $account->id }}">
            <div class="p-4 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $account->name }}</p>
                        <p class="text-xs text-gray-500">WABA ID: {{ $account->waba_id }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">Verify Token: <code class="bg-gray-100 px-1 rounded">{{ $account->verify_token }}</code></p>
                        <p class="text-xs text-gray-400">Webhook URL: <code class="bg-gray-100 px-1 rounded">{{ url('/api/webhook/whatsapp') }}</code></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge {{ $account->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <button wire:click="openWhatsAppForm({{ $account->id }})" class="btn-icon" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button wire:click="toggleWhatsAppAccount({{ $account->id }})" class="btn-icon" title="{{ $account->is_active ? 'Disable' : 'Enable' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $account->is_active ? 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z' : 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Phone Numbers --}}
            <div class="border-t border-gray-100 px-4 py-3">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Phone Numbers</p>
                    <button wire:click="openPhoneForm({{ $account->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">+ Add Number</button>
                </div>

                @forelse($account->phoneNumbers as $phone)
                <div class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-gray-50' : '' }}" wire:key="phone-{{ $phone->id }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg {{ $phone->is_active ? 'bg-indigo-100' : 'bg-gray-100' }} flex items-center justify-center">
                            <span class="text-xs font-bold {{ $phone->is_active ? 'text-indigo-700' : 'text-gray-400' }}">{{ strtoupper(substr($phone->name, 0, 2)) }}</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $phone->name }}</p>
                            <p class="text-[11px] text-gray-400">ID: {{ $phone->phone_number_id }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($phone->ai_enabled)
                            <span class="badge bg-emerald-100 text-emerald-700 text-[10px]">AI</span>
                        @endif
                        <span class="badge {{ $phone->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} text-[10px]">
                            {{ $phone->is_active ? 'Active' : 'Off' }}
                        </span>
                        <button wire:click="openPhoneForm({{ $account->id }}, {{ $phone->id }})" class="btn-icon" title="Edit">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                        <button wire:click="togglePhoneNumber({{ $phone->id }})" class="btn-icon" title="Toggle">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $phone->is_active ? 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z' : 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }}"/>
                            </svg>
                        </button>
                        <button wire:click="deletePhoneNumber({{ $phone->id }})" wire:confirm="Delete this phone number? Conversations linked to it will lose their association." class="btn-icon text-red-400 hover:text-red-600" title="Delete">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @empty
                <p class="text-xs text-gray-400 italic py-2">No phone numbers added yet</p>
                @endforelse
            </div>
        </div>
        @empty
        <div class="card p-10 text-center">
            <p class="text-gray-400">No WhatsApp accounts configured yet.</p>
        </div>
        @endforelse

        <!-- Templates Section -->
        @foreach($this->whatsappAccounts as $account)
        <div class="card mt-4">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">Templates — {{ $account->name }}</h3>
                <button wire:click="syncTemplates({{ $account->id }})" class="btn-sm bg-indigo-50 text-indigo-700 hover:bg-indigo-100 flex items-center gap-1.5">
                    <svg class="w-4 h-4 animate-none" wire:loading.class="animate-spin" wire:target="syncTemplates({{ $account->id }})" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync from Meta
                </button>
            </div>
            <div class="card-body p-0">
                @forelse($account->templates()->orderBy('name')->get() as $template)
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 last:border-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-gray-900">{{ $template->name }}</span>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded {{ $template->status === 'approved' ? 'bg-green-100 text-green-700' : ($template->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                {{ ucfirst($template->status) }}
                            </span>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-gray-100 text-gray-600">{{ $template->language }}</span>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-blue-50 text-blue-600">{{ ucfirst($template->category) }}</span>
                        </div>
                        @php
                            $bodyComponent = collect($template->components)->firstWhere('type', 'BODY');
                            $bodyText = $bodyComponent['text'] ?? '';
                        @endphp
                        @if($bodyText)
                        <p class="text-xs text-gray-500 mt-1 truncate">{{ $bodyText }}</p>
                        @endif
                        @php
                            $allVars = [];
                            foreach ($template->components as $comp) {
                                $compType = strtolower($comp['type'] ?? '');
                                if (!in_array($compType, ['header', 'body'])) continue;
                                preg_match_all('/\{\{(\d+)\}\}/', $comp['text'] ?? '', $m);
                                foreach ($m[1] as $n) {
                                    $allVars[] = ['type' => $compType, 'num' => (int)$n];
                                }
                            }
                        @endphp
                        @if(count($allVars))
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            @foreach($allVars as $v)
                                <span class="text-[9px] font-semibold px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-200">
                                    {{ ucfirst($v['type']) }} &#123;&#123;{{ $v['num'] }}&#125;&#125;
                                </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    <button wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Delete template '{{ $template->name }}'? This will also delete it from Meta." class="text-red-400 hover:text-red-600 p-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
                @empty
                <p class="text-xs text-gray-400 italic py-4 px-4">No templates synced. Click "Sync from Meta" to fetch templates.</p>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>

    @elseif($activeTab === 'ai')
    <div class="card max-w-lg">
        <div class="card-header"><h3 class="font-semibold text-gray-900">AI Configuration</h3></div>
        <form wire:submit="saveAi" class="card-body space-y-4">
            <div class="form-group">
                <label class="label">Model</label>
                <select wire:model="ai_model" class="input">
                    <option value="gpt-4o-mini">GPT-4o Mini (Recommended)</option>
                    <option value="gpt-4o">GPT-4o</option>
                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                </select>
            </div>
            <div class="form-group">
                <label class="label">Max Tokens</label>
                <input wire:model="ai_max_tokens" type="number" min="100" max="4000" class="input">
                <p class="text-xs text-gray-400 mt-1">Maximum length of AI responses (100-4000)</p>
            </div>
            <div class="form-group">
                <label class="label">Temperature</label>
                <input wire:model="ai_temperature" type="range" min="0" max="1" step="0.1" class="w-full">
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>Precise (0)</span>
                    <span class="font-medium text-gray-600">{{ $ai_temperature }}</span>
                    <span>Creative (1)</span>
                </div>
            </div>
            <div class="form-group">
                <label class="label">OpenAI API Key</label>
                <input wire:model="ai_api_key" type="password" class="input" placeholder="{{ $this->hasAiApiKey ? '••••••••••••••••••••••••' : 'sk-...' }}">
                <p class="text-xs text-gray-400 mt-1">
                    @if($this->hasAiApiKey)
                        Key is set. Leave blank to keep current key.
                    @else
                        Enter your OpenAI API key. Stored encrypted.
                    @endif
                </p>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">Save AI Settings</button>
            </div>
        </form>
    </div>
    @endif

    {{-- WhatsApp Account Modal --}}
    @if($showWhatsAppForm)
    <div class="modal-backdrop" wire:key="wa-form-{{ $editingAccountId ?? 'new' }}-{{ now()->timestamp }}">
        <div class="modal modal-lg mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">{{ $editingAccountId ? 'Edit' : 'Add' }} WhatsApp Account</h3>
                <button wire:click="$set('showWhatsAppForm', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="saveWhatsApp" class="p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-group sm:col-span-2">
                        <label class="label">Account Name <span class="text-red-500">*</span></label>
                        <input wire:model="wa_name" type="text" class="input @error('wa_name') input-error @enderror" placeholder="e.g. My Business WABA">
                        @error('wa_name') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">WABA ID <span class="text-red-500">*</span></label>
                        <input wire:model="wa_waba_id" type="text" class="input @error('wa_waba_id') input-error @enderror" placeholder="WhatsApp Business Account ID">
                        @error('wa_waba_id') <p class="error-msg">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group">
                        <label class="label">Verify Token <span class="text-red-500">*</span></label>
                        <input wire:model="wa_verify_token" type="text" class="input font-mono text-sm" readonly>
                        <p class="text-xs text-gray-400 mt-1">Use this when setting up the webhook in Meta Developer Console.</p>
                    </div>
                    <div class="form-group sm:col-span-2">
                        <label class="label">Access Token {{ $editingAccountId ? '(leave blank to keep current)' : '' }} <span class="text-red-500">{{ $editingAccountId ? '' : '*' }}</span></label>
                        <input wire:model="wa_access_token" type="password" class="input @error('wa_access_token') input-error @enderror" placeholder="EAAxxxxxx...">
                        @error('wa_access_token') <p class="error-msg">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Shared access token for all phone numbers under this WABA.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('showWhatsAppForm', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Account</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Phone Number Modal --}}
    @if($showPhoneForm)
    <div class="modal-backdrop" wire:key="phone-form-{{ $editingPhoneId ?? 'new' }}-{{ now()->timestamp }}">
        <div class="modal modal-sm mx-4">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">{{ $editingPhoneId ? 'Edit' : 'Add' }} Phone Number</h3>
                <button wire:click="$set('showPhoneForm', false)" class="btn-icon">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit="savePhone" class="p-6 space-y-4">
                <div class="form-group">
                    <label class="label">Name <span class="text-red-500">*</span></label>
                    <input wire:model="pn_name" type="text" class="input @error('pn_name') input-error @enderror" placeholder="e.g. PG, XA">
                    @error('pn_name') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Phone Number ID <span class="text-red-500">*</span></label>
                    <input wire:model="pn_phone_number_id" type="text" class="input @error('pn_phone_number_id') input-error @enderror" placeholder="From Meta Developer Console">
                    @error('pn_phone_number_id') <p class="error-msg">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="label">Greeting Message</label>
                    <textarea wire:model="pn_greeting_message" rows="3" class="input" placeholder="Hello! Welcome to [Company]. How can we help you today?"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Sent to first-time customers and after inactivity. Leave blank to disable.</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl space-y-3">
                    <div class="flex items-center gap-2">
                        <input wire:model="pn_ai_enabled" type="checkbox" id="pn_ai_enabled" class="rounded border-gray-300 text-indigo-600">
                        <label for="pn_ai_enabled" class="text-sm font-medium text-gray-700">Enable AI Auto-Reply</label>
                    </div>
                    @if($pn_ai_enabled)
                    <div class="form-group">
                        <label class="label">AI System Prompt</label>
                        <textarea wire:model="pn_ai_prompt" rows="4" class="input" placeholder="You are a helpful sales assistant for [Company Name]..."></textarea>
                    </div>
                    @endif
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('showPhoneForm', false)" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Number</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
