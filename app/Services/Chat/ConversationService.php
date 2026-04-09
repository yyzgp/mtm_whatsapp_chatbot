<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\WhatsAppPhoneNumber;

class ConversationService
{
    public function findOrCreate(
        WhatsAppPhoneNumber $phoneNumber,
        string $contactPhone,
        ?string $contactName = null
    ): ChatConversation {
        // First look for an active conversation
        $conversation = ChatConversation::where('whatsapp_phone_number_id', $phoneNumber->id)
            ->where('contact_phone', $contactPhone)
            ->whereIn('status', ['open', 'pending'])
            ->latest()
            ->first();

        if ($conversation) {
            if ($contactName && !$conversation->contact_name) {
                $conversation->update(['contact_name' => $contactName]);
            }
            return $conversation;
        }

        // Reopen the most recent resolved conversation if one exists
        $resolved = ChatConversation::where('whatsapp_phone_number_id', $phoneNumber->id)
            ->where('contact_phone', $contactPhone)
            ->where('status', 'resolved')
            ->latest()
            ->first();

        if ($resolved) {
            $resolved->update(['status' => 'open']);
            return $resolved;
        }

        // No conversation at all — create one
        $customer = $this->findCustomerByPhone($contactPhone);

        $conversation = ChatConversation::create([
            'whatsapp_phone_number_id' => $phoneNumber->id,
            'customer_id' => $customer?->id,
            'assigned_to' => $customer?->assigned_to,
            'contact_phone' => $contactPhone,
            'contact_name' => $contactName ?? ($customer?->full_name),
            'status' => 'open',
            'ai_active' => $phoneNumber->ai_enabled,
        ]);

        if (!$customer) {
            $customer = $this->createCustomerFromWhatsApp($contactPhone, $contactName);
            $conversation->update(['customer_id' => $customer->id]);
        }

        return $conversation;
    }

    private function findCustomerByPhone(string $phone): ?Customer
    {
        $normalized = preg_replace('/[\s\-\(\)\+]/', '', $phone);
        return Customer::where('phone', 'like', "%{$normalized}%")
            ->orWhere('phone_whatsapp', 'like', "%{$normalized}%")
            ->first();
    }

    private function createCustomerFromWhatsApp(string $phone, ?string $name): Customer
    {
        $nameParts = $name ? explode(' ', trim($name), 2) : ['WhatsApp', 'User'];
        $firstName = $nameParts[0] ?? 'WhatsApp';
        $lastName = $nameParts[1] ?? 'User';

        return Customer::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'phone_whatsapp' => $phone,
            'status' => 'inquiry',
            'priority' => 'medium',
        ]);
    }
}
