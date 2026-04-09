<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppPhoneNumber;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppApiService
{
    private string $baseUrl;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('whatsapp.base_url', 'https://graph.facebook.com');
        $this->apiVersion = config('whatsapp.api_version', 'v19.0');
    }

    public function sendTextMessage(string $to, string $text, WhatsAppPhoneNumber $phoneNumber): array
    {
        return $this->sendMessage($phoneNumber, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => ['body' => $text, 'preview_url' => false],
        ]);
    }

    public function sendTemplateMessage(
        string $to,
        string $templateName,
        string $language,
        array $components,
        WhatsAppPhoneNumber $phoneNumber
    ): array {
        return $this->sendMessage($phoneNumber, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);
    }

    public function uploadMedia(string $filePath, string $mimeType, WhatsAppPhoneNumber $phoneNumber): ?string
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$phoneNumber->phone_number_id}/media";
        $token = $phoneNumber->account->access_token;

        $response = Http::withToken($token)
            ->attach('file', file_get_contents($filePath), basename($filePath), ['Content-Type' => $mimeType])
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'type' => $mimeType,
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        Log::error('WhatsApp media upload failed', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);
        return null;
    }

    public function sendMediaMessage(string $to, string $mediaType, string $mediaId, ?string $caption, WhatsAppPhoneNumber $phoneNumber): array
    {
        $mediaPayload = ['id' => $mediaId];
        if ($caption && in_array($mediaType, ['image', 'video', 'document'])) {
            $mediaPayload['caption'] = $caption;
        }
        if ($mediaType === 'document') {
            $mediaPayload['filename'] = $caption ?: 'file';
        }

        return $this->sendMessage($phoneNumber, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => $mediaType,
            $mediaType => $mediaPayload,
        ]);
    }

    public function getTemplates(\App\Models\WhatsAppAccount $account): array
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$account->waba_id}/message_templates";
        $token = $account->access_token;

        $templates = [];
        $nextUrl = $url . '?limit=100';

        while ($nextUrl) {
            $response = Http::withToken($token)->get($nextUrl);

            if (!$response->successful()) {
                Log::error('WhatsApp getTemplates failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                break;
            }

            $data = $response->json();
            $templates = array_merge($templates, $data['data'] ?? []);
            $nextUrl = $data['paging']['next'] ?? null;
        }

        return $templates;
    }

    public function createTemplate(\App\Models\WhatsAppAccount $account, array $payload): array
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$account->waba_id}/message_templates";
        $token = $account->access_token;

        $response = Http::withToken($token)->post($url, $payload);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error('WhatsApp createTemplate failed', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);
        return ['error' => $response->json()];
    }

    public function deleteTemplate(\App\Models\WhatsAppAccount $account, string $templateName): bool
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$account->waba_id}/message_templates";
        $token = $account->access_token;

        $response = Http::withToken($token)->delete($url, ['name' => $templateName]);

        if (!$response->successful()) {
            Log::error('WhatsApp deleteTemplate failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }

        return $response->successful();
    }

    public function sendReaction(string $to, string $messageId, string $emoji, WhatsAppPhoneNumber $phoneNumber): array
    {
        return $this->sendMessage($phoneNumber, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'reaction',
            'reaction' => [
                'message_id' => $messageId,
                'emoji' => $emoji,
            ],
        ]);
    }

    public function markMessageAsRead(string $wamid, WhatsAppPhoneNumber $phoneNumber): void
    {
        $this->post($phoneNumber, [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $wamid,
        ]);
    }

    public function getMediaUrl(string $mediaId, WhatsAppPhoneNumber $phoneNumber): ?string
    {
        $token = $phoneNumber->account->access_token;
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/{$this->apiVersion}/{$mediaId}");

        if ($response->successful()) {
            return $response->json('url');
        }

        Log::error('WhatsApp getMediaUrl failed', [
            'media_id' => $mediaId,
            'response' => $response->json(),
        ]);
        return null;
    }

    public function downloadMedia(string $mediaId, WhatsAppPhoneNumber $phoneNumber): ?string
    {
        $token = $phoneNumber->account->access_token;

        // Step 1: Get the download URL
        $metaResponse = Http::withToken($token)
            ->get("{$this->baseUrl}/{$this->apiVersion}/{$mediaId}");

        if (!$metaResponse->successful()) {
            Log::error('WhatsApp getMediaUrl failed', [
                'media_id' => $mediaId,
                'status' => $metaResponse->status(),
                'response' => $metaResponse->json(),
            ]);
            return null;
        }

        $downloadUrl = $metaResponse->json('url');
        $mimeType = $metaResponse->json('mime_type', 'application/octet-stream');
        if (!$downloadUrl) return null;

        // Step 2: Download the file
        $fileResponse = Http::withToken($token)->get($downloadUrl);

        if (!$fileResponse->successful()) {
            Log::error('WhatsApp media download failed', ['url' => $downloadUrl]);
            return null;
        }

        // Step 3: Store locally
        $ext = match (true) {
            str_contains($mimeType, 'jpeg'), str_contains($mimeType, 'jpg') => 'jpg',
            str_contains($mimeType, 'png') => 'png',
            str_contains($mimeType, 'webp') => 'webp',
            str_contains($mimeType, 'gif') => 'gif',
            str_contains($mimeType, 'mp4') => 'mp4',
            str_contains($mimeType, 'ogg') => 'ogg',
            str_contains($mimeType, 'opus') => 'opus',
            str_contains($mimeType, 'pdf') => 'pdf',
            str_contains($mimeType, 'audio') => 'mp3',
            default => 'bin',
        };

        $filename = 'whatsapp/' . date('Y/m') . '/' . $mediaId . '.' . $ext;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $fileResponse->body());

        return 'storage/' . $filename;
    }

    public function sendRawPayload(WhatsAppPhoneNumber $phoneNumber, array $payload): array
    {
        return $this->sendMessage($phoneNumber, $payload);
    }

    private function sendMessage(WhatsAppPhoneNumber $phoneNumber, array $payload): array
    {
        $response = $this->post($phoneNumber, $payload);

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::error('WhatsApp sendMessage failed', [
            'payload' => $payload,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return ['error' => $response->json()];
    }

    private function post(WhatsAppPhoneNumber $phoneNumber, array $payload): Response
    {
        $url = "{$this->baseUrl}/{$this->apiVersion}/{$phoneNumber->phone_number_id}/messages";
        $token = $phoneNumber->account->access_token;

        return Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!str_starts_with($phone, '+')) {
            $phone = '+' . ltrim($phone, '0');
        }
        return $phone;
    }
}
