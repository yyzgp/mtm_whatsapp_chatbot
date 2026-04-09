<?php

namespace App\Livewire\Chat;

use App\Enums\ConversationStatus;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsAppPhoneNumber;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class ChatInterface extends Component
{
    public ?int $selectedConversationId = null;
    public string $messageText = '';
    public string $search = '';
    public string $statusFilter = 'open';
    public string $agentFilter = '';
    public string $phoneFilter = '';

    // New conversation modal
    public bool $showNewConversation = false;
    public string $customerSearch = '';
    public ?int $selectedCustomerId = null;

    // Template sending
    public bool $showTemplateModal = false;
    public ?int $selectedTemplateId = null;
    public array $templateParams = [];

    public function mount(): void
    {
        $customerId = request()->query('customer');
        if ($customerId) {
            $conversation = ChatConversation::where('customer_id', $customerId)
                ->whereIn('status', ['open', 'pending'])
                ->latest('last_message_at')
                ->first();

            if ($conversation) {
                $this->selectedConversationId = $conversation->id;
                $this->statusFilter = $conversation->status instanceof ConversationStatus ? $conversation->status->value : $conversation->status;
            } else {
                $this->selectedCustomerId = (int) $customerId;
                $this->showNewConversation = true;
            }
        }
    }

    #[Computed]
    public function accessiblePhoneNumberIds(): array
    {
        return auth()->user()->getAccessiblePhoneNumberIds();
    }

    #[Computed]
    public function filterablePhoneNumbers()
    {
        $ids = $this->accessiblePhoneNumberIds;

        if (empty($ids)) {
            return WhatsAppPhoneNumber::where('is_active', true)->orderBy('name')->get();
        }

        return WhatsAppPhoneNumber::whereIn('id', $ids)->orderBy('name')->get();
    }

    #[Computed]
    public function visibleAgentIds(): array
    {
        $user = auth()->user();

        if ($user->can('chat.view_all')) {
            return [];
        }

        $ids = [$user->id];

        $teamMemberIds = $user->getTeamMemberIds();
        if (!empty($teamMemberIds)) {
            $ids = array_unique(array_merge($ids, $teamMemberIds));
        }

        return $ids;
    }

    #[Computed]
    public function filterableAgents()
    {
        $ids = $this->visibleAgentIds;

        if (empty($ids)) {
            return User::whereIn('role', [1, 2])->orderBy('name')->get();
        }

        return User::whereIn('id', $ids)->orderBy('name')->get();
    }

    #[Computed]
    public function conversations()
    {
        $query = ChatConversation::with(['customer.assignedAgent', 'phoneNumber'])
            ->latest('last_message_at');

        // 1. Phone number access filter
        $phoneIds = $this->accessiblePhoneNumberIds;

        if ($this->phoneFilter !== '') {
            $query->where('whatsapp_phone_number_id', (int) $this->phoneFilter);
        } elseif (!empty($phoneIds)) {
            $query->whereIn('whatsapp_phone_number_id', $phoneIds);
        }

        // 2. Agent visibility filter
        $visibleIds = $this->visibleAgentIds;

        if ($this->agentFilter !== '') {
            $agentId = (int) $this->agentFilter;
            if ($agentId === 0) {
                $query->where(function ($q) {
                    $q->whereHas('customer', fn($cq) => $cq->whereNull('assigned_to'))
                      ->orWhereNull('customer_id');
                });
            } else {
                $query->whereHas('customer', fn($cq) => $cq->where('assigned_to', $agentId));
            }
        } elseif (!empty($visibleIds)) {
            $query->where(function ($q) use ($visibleIds) {
                $q->whereHas('customer', fn($cq) => $cq->whereIn('assigned_to', $visibleIds));
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('contact_name', 'like', "%{$this->search}%")
                  ->orWhere('contact_phone', 'like', "%{$this->search}%");
            });
        }

        return $query->get();
    }

    #[Computed]
    public function selectedConversation(): ?ChatConversation
    {
        if (!$this->selectedConversationId) return null;
        return ChatConversation::with(['customer.assignedAgent', 'phoneNumber.account'])->find($this->selectedConversationId);
    }

    #[Computed]
    public function messages()
    {
        if (!$this->selectedConversationId) return collect();
        return ChatMessage::where('conversation_id', $this->selectedConversationId)
            ->with('sender')
            ->oldest()
            ->get();
    }

    #[Computed]
    public function isWindowOpen(): bool
    {
        $conv = $this->selectedConversation;
        if (!$conv) return false;
        return $conv->isWithinMessageWindow();
    }

    #[Computed]
    public function customerResults()
    {
        if (strlen($this->customerSearch) < 2) return collect();

        return Customer::where(function ($q) {
            $q->where('first_name', 'like', "%{$this->customerSearch}%")
              ->orWhere('last_name', 'like', "%{$this->customerSearch}%")
              ->orWhere('phone', 'like', "%{$this->customerSearch}%")
              ->orWhere('company_name', 'like', "%{$this->customerSearch}%");
        })
        ->whereNotNull('phone')
        ->limit(10)
        ->get();
    }

    #[Computed]
    public function selectedCustomer(): ?Customer
    {
        if (!$this->selectedCustomerId) return null;
        return Customer::find($this->selectedCustomerId);
    }

    public function pickCustomer(int $id): void
    {
        $this->selectedCustomerId = $id;
        $this->customerSearch = '';
    }

    public function startNewConversation(): void
    {
        $customer = Customer::find($this->selectedCustomerId);
        if (!$customer || !$customer->phone) {
            $this->dispatch('notify', ['message' => 'Customer has no phone number.', 'type' => 'error']);
            return;
        }

        // Check for existing open/pending conversation
        $conversation = ChatConversation::where('customer_id', $customer->id)
            ->whereIn('status', ['open', 'pending'])
            ->latest('last_message_at')
            ->first();

        if (!$conversation) {
            // Reopen resolved conversation if exists
            $conversation = ChatConversation::where('customer_id', $customer->id)
                ->where('status', 'resolved')
                ->latest('last_message_at')
                ->first();

            if ($conversation) {
                $conversation->update(['status' => 'open']);
            } else {
                $phoneNumber = WhatsAppPhoneNumber::where('is_active', true)->first();

                $conversation = ChatConversation::create([
                    'whatsapp_phone_number_id' => $phoneNumber?->id,
                    'customer_id' => $customer->id,
                    'assigned_to' => $customer->assigned_to,
                    'contact_phone' => $customer->phone,
                    'contact_name' => $customer->full_name,
                    'status' => 'open',
                    'ai_active' => $phoneNumber?->ai_enabled ?? false,
                ]);
            }
        }

        $this->showNewConversation = false;
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
        $this->selectedConversationId = $conversation->id;
        $this->statusFilter = $conversation->status instanceof ConversationStatus ? $conversation->status->value : $conversation->status;
        unset($this->conversations, $this->selectedConversation, $this->messages);
        $this->dispatch('notify', ['message' => 'Conversation opened.', 'type' => 'success']);
    }

    public function cancelNewConversation(): void
    {
        $this->showNewConversation = false;
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
    }

    #[Computed]
    public function templates()
    {
        $conv = $this->selectedConversation;
        if (!$conv || !$conv->whatsapp_phone_number_id) return collect();

        $phoneNumber = $conv->phoneNumber;
        if (!$phoneNumber || !$phoneNumber->whatsapp_account_id) return collect();

        return \App\Models\WhatsAppTemplate::where('whatsapp_account_id', $phoneNumber->whatsapp_account_id)
            ->approved()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedTemplateVars(): array
    {
        if (!$this->selectedTemplateId) return [];

        $template = \App\Models\WhatsAppTemplate::find($this->selectedTemplateId);
        if (!$template) return [];

        $vars = [];
        foreach ($template->components ?? [] as $component) {
            $type = strtolower($component['type'] ?? '');
            if (!in_array($type, ['header', 'body'])) continue;

            $text = $component['text'] ?? '';
            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            if (empty($matches[1])) continue;

            $vars[] = [
                'type'  => $type,
                'label' => ucfirst($type),
                'text'  => $text,
                'vars'  => array_map('intval', $matches[1]),
            ];
        }

        return $vars;
    }

    public function updatedSelectedTemplateId(): void
    {
        $this->templateParams = [];
        unset($this->selectedTemplateVars);

        if (!$this->selectedTemplateId) return;

        $template = \App\Models\WhatsAppTemplate::find($this->selectedTemplateId);
        if (!$template) return;

        foreach ($template->components ?? [] as $component) {
            $type = strtolower($component['type'] ?? '');
            if (!in_array($type, ['header', 'body'])) continue;

            $text = $component['text'] ?? '';
            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
            if (empty($matches[1])) continue;

            foreach ($matches[1] as $varNum) {
                $this->templateParams[$type][(int) $varNum - 1] = '';
            }
        }
    }

    public function openTemplateModal(): void
    {
        $this->showTemplateModal = true;
        $this->selectedTemplateId = null;
        $this->templateParams = [];
        unset($this->selectedTemplateVars);
    }

    public function sendTemplate(): void
    {
        if (!$this->selectedTemplateId || !$this->selectedConversationId) return;

        $conversation = $this->selectedConversation;
        $template = \App\Models\WhatsAppTemplate::find($this->selectedTemplateId);
        if (!$conversation || !$template) return;

        if (!$conversation->whatsapp_phone_number_id) {
            $this->dispatch('notify', ['message' => 'No WhatsApp number linked. Configure one in Settings.', 'type' => 'error']);
            return;
        }

        try {
            app(MessageService::class)->sendTemplateMessage(
                $conversation,
                $template,
                auth()->user(),
                $this->templateParams
            );
            $this->showTemplateModal = false;
            $this->selectedTemplateId = null;
            $this->templateParams = [];
            unset($this->messages, $this->conversations, $this->selectedTemplateVars);
            $this->dispatch('notify', ['message' => 'Template sent.', 'type' => 'success']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Failed to send template: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    public function reactToMessage(int $messageId, string $emoji): void
    {
        $message = \App\Models\ChatMessage::find($messageId);
        if (!$message) return;

        // Toggle: clicking the same emoji removes the reaction
        $newEmoji = ($message->reaction === $emoji) ? null : $emoji;

        $message->update([
            'reaction'    => $newEmoji,
            'reaction_by' => $newEmoji ? 'agent' : null,
        ]);

        unset($this->messages);
    }

    public function selectConversation(int $id): void
    {
        $this->selectedConversationId = $id;
        $this->messageText = '';
        ChatConversation::where('id', $id)->update(['unread_count' => 0]);
        unset($this->messages, $this->selectedConversation, $this->isWindowOpen);
        $this->dispatch('scroll-chat');
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->messageText))) return;

        $conversation = $this->selectedConversation;
        if (!$conversation) return;

        if (!$conversation->isWithinMessageWindow()) {
            $this->dispatch('notify', ['message' => '24hr window expired. Send a template message to re-engage.', 'type' => 'error']);
            return;
        }

        app(MessageService::class)->sendAgentMessage(
            $conversation,
            trim($this->messageText),
            auth()->user()
        );

        $this->messageText = '';
        unset($this->messages, $this->conversations);
        $this->dispatch('scroll-chat');
    }

    public function toggleAi(): void
    {
        $conversation = $this->selectedConversation;
        if (!$conversation) return;

        if ($conversation->ai_active) {
            $conversation->update(['ai_active' => false]);
        } else {
            $conversation->update(['ai_active' => true, 'ai_paused_until' => null]);
        }
        unset($this->selectedConversation);
    }

    public function resolveConversation(): void
    {
        $conversation = $this->selectedConversation;
        if (!$conversation) return;
        $conversation->update(['status' => ConversationStatus::Resolved]);
        $this->selectedConversationId = null;
        unset($this->conversations, $this->selectedConversation);
        $this->dispatch('notify', ['message' => 'Conversation resolved.', 'type' => 'success']);
    }

    public function getListeners(): array
    {
        $listeners = [
            "echo-private:chat,.ChatMessageSent" => 'onNewMessage',
        ];

        if ($this->selectedConversationId) {
            $listeners["echo-private:chat.{$this->selectedConversationId},.ChatMessageSent"] = 'onConversationMessage';
        }

        return $listeners;
    }

    public function onNewMessage(): void
    {
        unset($this->conversations);
    }

    public function onConversationMessage(): void
    {
        unset($this->messages, $this->conversations, $this->isWindowOpen);
        $this->dispatch('scroll-chat');
    }

    public function render()
    {
        return view('livewire.chat.chat-interface')
            ->layout('layouts.chat');
    }
}
