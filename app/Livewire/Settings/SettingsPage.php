<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Models\WhatsAppAccount;
use App\Models\WhatsAppPhoneNumber;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppApiService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SettingsPage extends Component
{
    public string $activeTab = 'general';

    // General Settings
    public string $app_name = '';
    public string $app_timezone = '';

    // WhatsApp Account form
    public bool $showWhatsAppForm = false;
    public ?int $editingAccountId = null;
    public string $wa_name = '';
    public string $wa_waba_id = '';
    public string $wa_access_token = '';
    public string $wa_verify_token = '';

    // Phone Number form
    public bool $showPhoneForm = false;
    public ?int $editingPhoneId = null;
    public ?int $phoneAccountId = null;
    public string $pn_name = '';
    public string $pn_phone_number_id = '';
    public bool $pn_ai_enabled = true;
    public string $pn_ai_prompt = '';
    public string $pn_greeting_message = '';

    // WhatsApp global settings
    public string $wa_greeting_cooldown = '30';

    // AI Settings
    public string $ai_api_key = '';
    public string $ai_model = 'gpt-4o-mini';
    public string $ai_max_tokens = '1000';
    public string $ai_temperature = '0.7';

    public function mount(): void
    {
        $this->app_name = Setting::get('general', 'app_name', config('app.name'));
        $this->app_timezone = Setting::get('general', 'timezone', 'UTC');
        $this->wa_greeting_cooldown = Setting::get('whatsapp', 'greeting_cooldown_minutes', '30');
        $this->ai_api_key = '';
        $this->ai_model = Setting::get('ai', 'model', 'gpt-4o-mini');
        $this->ai_max_tokens = Setting::get('ai', 'max_tokens', '1000');
        $this->ai_temperature = Setting::get('ai', 'temperature', '0.7');
    }

    #[Computed]
    public function whatsappAccounts()
    {
        return WhatsAppAccount::with('phoneNumbers')->orderBy('name')->get();
    }

    public function saveGeneral(): void
    {
        $this->validate([
            'app_name' => 'required|string|max:100',
            'app_timezone' => 'required|string',
        ]);

        Setting::set('general', 'app_name', $this->app_name);
        Setting::set('general', 'timezone', $this->app_timezone);
        $this->dispatch('notify', ['message' => 'General settings saved.', 'type' => 'success']);
    }

    public function saveAi(): void
    {
        $this->validate([
            'ai_api_key' => 'nullable|string',
            'ai_model' => 'required|string',
            'ai_max_tokens' => 'required|integer|min:100|max:4000',
            'ai_temperature' => 'required|numeric|min:0|max:1',
        ]);

        if ($this->ai_api_key) {
            Setting::set('ai', 'api_key', $this->ai_api_key, encrypted: true);
            $this->ai_api_key = '';
        }
        Setting::set('ai', 'model', $this->ai_model);
        Setting::set('ai', 'max_tokens', $this->ai_max_tokens);
        Setting::set('ai', 'temperature', $this->ai_temperature);
        $this->dispatch('notify', ['message' => 'AI settings saved.', 'type' => 'success']);
    }

    #[Computed]
    public function hasAiApiKey(): bool
    {
        return !empty(Setting::get('ai', 'api_key'));
    }

    public function saveWhatsAppSettings(): void
    {
        $this->validate([
            'wa_greeting_cooldown' => 'required|integer|min:1|max:1440',
        ]);

        Setting::set('whatsapp', 'greeting_cooldown_minutes', $this->wa_greeting_cooldown);
        $this->dispatch('notify', ['message' => 'WhatsApp settings saved.', 'type' => 'success']);
    }

    // === WhatsApp Account CRUD ===

    public function openWhatsAppForm(?int $id = null): void
    {
        if ($id) {
            $account = WhatsAppAccount::findOrFail($id);
            $this->editingAccountId = $id;
            $this->wa_name = $account->name;
            $this->wa_waba_id = $account->waba_id;
            $this->wa_access_token = '';
            $this->wa_verify_token = $account->verify_token;
        } else {
            $this->editingAccountId = null;
            $this->wa_name = $this->wa_waba_id = $this->wa_access_token = '';
            $this->wa_verify_token = \Illuminate\Support\Str::random(32);
        }
        $this->showWhatsAppForm = true;
    }

    public function saveWhatsApp(): void
    {
        $this->validate([
            'wa_name' => 'required|string|max:255',
            'wa_waba_id' => 'required|string',
            'wa_access_token' => $this->editingAccountId ? 'nullable|string' : 'required|string',
            'wa_verify_token' => 'required|string',
        ]);

        $data = [
            'name' => $this->wa_name,
            'waba_id' => $this->wa_waba_id,
            'verify_token' => $this->wa_verify_token,
        ];

        if ($this->wa_access_token) {
            $data['access_token'] = $this->wa_access_token;
        }

        if ($this->editingAccountId) {
            WhatsAppAccount::findOrFail($this->editingAccountId)->update($data);
        } else {
            if (!$this->wa_access_token) {
                $this->addError('wa_access_token', 'Access token is required for new accounts.');
                return;
            }
            WhatsAppAccount::create($data);
        }

        $this->showWhatsAppForm = false;
        unset($this->whatsappAccounts);
        $this->dispatch('notify', ['message' => 'WhatsApp account saved.', 'type' => 'success']);
    }

    public function toggleWhatsAppAccount(int $id): void
    {
        $account = WhatsAppAccount::findOrFail($id);
        $account->update(['is_active' => !$account->is_active]);
        unset($this->whatsappAccounts);
    }

    // === Phone Number CRUD ===

    public function openPhoneForm(int $accountId, ?int $phoneId = null): void
    {
        $this->phoneAccountId = $accountId;

        if ($phoneId) {
            $phone = WhatsAppPhoneNumber::findOrFail($phoneId);
            $this->editingPhoneId = $phoneId;
            $this->pn_name = $phone->name;
            $this->pn_phone_number_id = $phone->phone_number_id;
            $this->pn_ai_enabled = $phone->ai_enabled;
            $this->pn_ai_prompt = $phone->ai_prompt ?? '';
            $this->pn_greeting_message = $phone->greeting_message ?? '';
        } else {
            $this->editingPhoneId = null;
            $this->pn_name = $this->pn_phone_number_id = $this->pn_ai_prompt = $this->pn_greeting_message = '';
            $this->pn_ai_enabled = true;
        }
        $this->showPhoneForm = true;
    }

    public function savePhone(): void
    {
        $this->validate([
            'pn_name' => 'required|string|max:255',
            'pn_phone_number_id' => 'required|string',
            'pn_ai_prompt' => 'nullable|string',
            'pn_greeting_message' => 'nullable|string',
        ]);

        $data = [
            'whatsapp_account_id' => $this->phoneAccountId,
            'name' => $this->pn_name,
            'phone_number_id' => $this->pn_phone_number_id,
            'ai_enabled' => $this->pn_ai_enabled,
            'ai_prompt' => $this->pn_ai_prompt ?: null,
            'greeting_message' => $this->pn_greeting_message ?: null,
        ];

        if ($this->editingPhoneId) {
            WhatsAppPhoneNumber::findOrFail($this->editingPhoneId)->update($data);
        } else {
            WhatsAppPhoneNumber::create($data);
        }

        $this->showPhoneForm = false;
        unset($this->whatsappAccounts);
        $this->dispatch('notify', ['message' => 'Phone number saved.', 'type' => 'success']);
    }

    public function togglePhoneNumber(int $id): void
    {
        $phone = WhatsAppPhoneNumber::findOrFail($id);
        $phone->update(['is_active' => !$phone->is_active]);
        unset($this->whatsappAccounts);
    }

    public function deletePhoneNumber(int $id): void
    {
        WhatsAppPhoneNumber::findOrFail($id)->delete();
        unset($this->whatsappAccounts);
        $this->dispatch('notify', ['message' => 'Phone number deleted.', 'type' => 'success']);
    }

    // === Template Sync ===

    public function syncTemplates(int $accountId): void
    {
        $account = WhatsAppAccount::findOrFail($accountId);
        $api = app(WhatsAppApiService::class);
        $metaTemplates = $api->getTemplates($account);

        if (empty($metaTemplates)) {
            $this->dispatch('notify', ['message' => 'No templates found or sync failed.', 'type' => 'warning']);
            return;
        }

        $synced = 0;
        $existingNames = WhatsAppTemplate::where('whatsapp_account_id', $account->id)->pluck('name', 'id')->toArray();
        $metaNames = [];

        foreach ($metaTemplates as $tpl) {
            $metaNames[] = $tpl['name'];

            // Extract body text from components
            $components = $tpl['components'] ?? [];

            WhatsAppTemplate::updateOrCreate(
                ['whatsapp_account_id' => $account->id, 'name' => $tpl['name'], 'language' => $tpl['language']],
                [
                    'category' => strtolower($tpl['category'] ?? 'utility'),
                    'status' => strtolower($tpl['status'] ?? 'pending'),
                    'components' => $components,
                ]
            );
            $synced++;
        }

        // Remove templates that no longer exist on Meta
        WhatsAppTemplate::where('whatsapp_account_id', $account->id)
            ->whereNotIn('name', array_unique($metaNames))
            ->delete();

        unset($this->whatsappAccounts);
        $this->dispatch('notify', ['message' => "Synced {$synced} templates.", 'type' => 'success']);
    }

    public function deleteTemplate(int $templateId): void
    {
        $template = WhatsAppTemplate::findOrFail($templateId);
        $account = WhatsAppAccount::findOrFail($template->whatsapp_account_id);

        $api = app(WhatsAppApiService::class);
        $deleted = $api->deleteTemplate($account, $template->name);

        if ($deleted) {
            // Delete all language variants
            WhatsAppTemplate::where('whatsapp_account_id', $account->id)
                ->where('name', $template->name)
                ->delete();
            $this->dispatch('notify', ['message' => 'Template deleted.', 'type' => 'success']);
        } else {
            $this->dispatch('notify', ['message' => 'Failed to delete template from Meta.', 'type' => 'error']);
        }

        unset($this->whatsappAccounts);
    }

    public function render()
    {
        return view('livewire.settings.settings-page')
            ->layout('layouts.app');
    }
}
